/*
 * Edit Finder Class
 */
var editfinder_preview = false;
var g_eftype = '';
var g_bEdited = false;

function EditFinder() {
	this.m_title = '';
	this.m_searchterms = '';
}

EditFinder.prototype.init = function () {
	g_eftype = editFinder.getEditType();

	editFinder.getArticle();
	
	//bind skip link
	jQuery('#editfinder_skip a').click(function(e) {
		e.preventDefault();
		if (!jQuery(this).hasClass('clickfail'))
			editFinder.getArticle();	
	});
	
	//update name/type
	jQuery('#editfinder_stats h3').html('Your '+WH.lang[g_eftype]+' Stats');
	
	/*category choosing*/
	jQuery('#editfinder_choose_cats').click(function(e){
		e.preventDefault();
		jQuery('#dialog-box').html('');
		jQuery('#dialog-box').load('/Special:SuggestCategories', function(){
			if (g_eftype !== '') {
				jQuery('#suggest_cats').attr('action',"/Special:SuggestCategories?type="+g_eftype);
			}
			jQuery('#dialog-box').dialog( "option", "position", 'center' );
			jQuery('#dialog-box td').each(function(){
				var myInput = $(this).find('input');
				var position = $(this).position();
				$(myInput).css('top', position.top + 10 + "px");
				$(myInput).css('left', position.left + 10 + "px");
				$(this).click(function(){
					choose_cat($(this).attr('id'));
				})
			})
		});
		jQuery('#dialog-box').dialog({
			width: 826,
			modal: true,
			title: 'Categories'
		});
	});
	
}

function choose_cat(key) {
	safekey = key.replace("&", "and");
 	var e = $("#" + safekey);
 	if (e.hasClass('not_chosen')) {
 		e.removeClass('not_chosen');
 		e.addClass('chosen');
 		document.suggest_cats.cats.value += ", " + key;
		jQuery('#check_' + safekey).attr('checked', true);
 	} else {
 		e.removeClass('chosen');
 		e.addClass('not_chosen');
 		var reg = new RegExp (key, "g");
 		document.suggest_cats.cats.value = document.suggest_cats.cats.value.replace(reg, '');
		jQuery('#check_' + safekey).attr('checked', false);
 	}
 }

EditFinder.prototype.updateStandingsTable = function() {
	var url = '/Special:EditFinder?getstandings=1';

	jQuery.get(url, function (data) {
		jQuery('#editfinder_standings_table').html(data);
	});
}

EditFinder.prototype.getArticle = function(id) {
	var url = '/Special:EditFinder?fetchArticle=1';
	var e = jQuery('#article_title');
	if (e.html()) {
		url += '&skip=' + encodeURIComponent(e.html());
	}	
	var title = '';
	
	//add the edit type
	if (g_eftype !== '') 
		url += '&edittype=' + g_eftype;
		
	//add the article id if we need a specific one
	if (id) 
		url += '&id=' + id;
	
	jQuery('#editfinder_article_inner').fadeOut('fast');
	jQuery('#editfinder_preview').fadeOut('fast',function() {
		jQuery('#editfinder_spinner').fadeIn();
		
		jQuery.get(url, function (data) {
			var json = jQuery.parseJSON(data);
			
			title = json['title'];
			aURL = json['url'];
			aid = json['aid'];

			window.setTimeout(editFinder.getStats, 1000);
			editFinder.display(title,aURL,aid,'editfinder_preview','intro');
		});
	});
	
	

}

// 
//
EditFinder.prototype.display = function (title, url, id, DIV, origin, currentStep) {

	this.m_title = title;
	this.m_product = 'editfinder';
	this.m_textAreaID = 'summary';
	this.m_currentStep = 0;

	// set up post- dialog load callback
	var showBox = this.m_currentStep !== 0;
	var that = this;

		
	var urlget = '/Special:EditFinder?show-article=1&aid='+id;
	jQuery.get(urlget, function(data) {
		jQuery('#' + DIV).html(data);

		//stop spinning and show stuff
		jQuery('#editfinder_spinner').fadeOut('fast',function() {
		
			
			//fill in the blanks
			if (title == undefined) {
				titlelink = '[No articles found]';
			}
			else {
				titlelink = '<a href="'+url+'">'+title+'</a>';
				jQuery('#editfinder_yes').click(function(e) {
					e.preventDefault();
					if (!jQuery(this).hasClass('clickfail')) {
						editFinder.edit(id);
					}
				});
			}
			jQuery('#editfinder_article_inner').html(titlelink);
			
			jQuery('#editfinder_article_inner').fadeIn();
			jQuery('#' + DIV).fadeIn();
		});
	});

}


EditFinder.prototype.edit = function (id,title) {
	var url = '/Special:EditFinder?edit-article=1&aid='+id;

	jQuery.ajax({
		url: url,
		success: function(data) {
			document.getElementById('editfinder_preview').innerHTML = data;
			jQuery('#editfinder_preview').css('height','950px');
			restoreToolbarButtons();
			//Preview button
			jQuery('#wpPreview').click(function() {
				editfinder_preview = true;
			});
			//Publish button
			jQuery('#wpSave').click(function() {
				editfinder_preview = false;
			});
			//form submit
			jQuery('#editform').submit(function(e) {
				e.preventDefault();
				//just a preview?
				if (editfinder_preview) {
					editFinder.showPreview(id);
					jQuery('html, body').animate({scrollTop:0});
					}
				else {
					//pop conf modal
					editFinder.displayConfirmation(id);
				}
			});
			
			//make Cancel do the right thing
			jQuery('.editButtons a:last-child').click(function(e) {
				//do we need to make the preview disappear?
				if (editfinder_preview) {
					jQuery('#editfinder_preview_updated').fadeOut('fast');
				}
				editFinder.cancelConfirmationModal(id);
				e.preventDefault();
			});
			
			//disable edit/skip choices
			editFinder.disableTopButtons();		
			
	
			//throw cursor in the textarea
			jQuery('#wpTextbox1').focus();
			jQuery('#wpTextbox1').change(function() {
				g_bEdited = true;
			});
	
			//add the id to the action url
			action = jQuery('#editform').attr('action');
			jQuery('#editform').attr('action',action+"&aid="+id+"&type="+g_eftype);
		}
	});
}

EditFinder.prototype.showPreview = function (id) {
	var editform = jQuery('#wpTextbox1').val();	
	var url = '/index.php?action=submit&wpPreview=true&live=true';
	
	jQuery.ajax({
		url: url,
		type: 'POST',
		data: 'wpTextbox1='+editform,
		success: function(data) {
			
			var XMLObject = data;
			var previewElement = jQuery(data).find('preview').first();

			/* Inject preview */
			var previewContainer = jQuery('#editfinder_preview_updated');
			if ( previewContainer && previewElement ) {
				previewContainer.html(previewElement.first().text());
				previewContainer.slideDown('slow');
			}		
		}
	});
}

EditFinder.prototype.getStats = function () {
	var url = '/Special:EditFinder?fetchStats=true';

	jQuery.get(url, function (data) {
		var json = jQuery.parseJSON(data);
		jQuery('#editfinder_stats_today').html( json['today'] );
		jQuery('#editfinder_stats_week').html( json['week'] );
		jQuery('#editfinder_stats_all').html( json['all'] );
		if (json['standing'] == 0) {
			jQuery('#editfinder_stats_standing').html('NA');
		} else {
			jQuery('#editfinder_stats_standing').html( json['standing'] );
		}

		jQuery('#editfinder_msg').html( json['defaultmsg'] );
	});
}

//returns string or empty string
EditFinder.prototype.getEditType = function () {
	var regexS = "[\\?&]type=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( window.location.href );
	if (results.length > 0) {
		return results[1];
	}
}

EditFinder.prototype.displayConfirmation = function( id ) {
	var url = '/Special:EditFinder?confirmation=1&type='+g_eftype+'&aid='+ id;

	jQuery('#img-box').load(url, function() {
		jQuery('#img-box').dialog({
		   width: 450,
		   modal: true,
		   title: 'Edit Finder Confirmation',
		   show: 'slide',
			closeOnEscape: true,
			position: 'center'
		});
	});
}

EditFinder.prototype.closeConfirmation = function( bRemoveTemplate ) {	
	//removing the template?
	if (bRemoveTemplate) {
		var text = jQuery('#wpTextbox1').val();
		var reg = new RegExp('{{'+g_eftype+'[^\r\n]*}}','i');
		jQuery('#wpTextbox1').val(text.replace(reg,''));
	}
	
	//close modal window
	jQuery('#img-box').dialog('close');
	
	jQuery('#editfinder_article_inner').fadeOut('fast');
	jQuery('#editfinder_preview').fadeOut('fast');
	jQuery('#editfinder_preview_updated').fadeOut('fast', function() {
		jQuery('#editfinder_spinner').fadeIn();
		jQuery('html, body').animate({scrollTop:0});
	});
	
	//submit
	jQuery.ajax({
		type: 'POST',
		url: jQuery('#editform').attr('action'),
		data: jQuery('#editform').serialize(),
		success: function() {
			//cool.  load another edit finder article
			window.location.reload();
		}
	});	
	return false;
}

EditFinder.prototype.cancelConfirmationModal = function( id ) {
	var url = '/Special:EditFinder?cancel-confirmation=1&aid='+ id;

	if (g_bEdited) {
		jQuery('#img-box').load(url, function(data) {	
			//changes; get the box
			jQuery('#img-box').dialog({
			   width: 450,
			   modal: true,
			   title: 'Edit Finder Confirmation',
			   show: 'slide',
				closeOnEscape: true,
				position: 'center'
			});
			
			//initialize buttons
			jQuery('#efcc_yes').click(function() {
				jQuery('#img-box').dialog('close');
				jQuery('html, body').animate({scrollTop:0});
				editFinder.getArticle(id);
				
			});
			jQuery('#efcc_no').click(function() {
				jQuery('#img-box').dialog('close');
			});
		});
	}
	else {
		//no change; go back
		jQuery('html, body').animate({scrollTop:0});
		editFinder.resetTopButtons();
		editFinder.getArticle(id);
		return;
	}
}

EditFinder.prototype.disableTopButtons = function() {
	//disable edit/skip choices
	jQuery('#editfinder_yes').addClass('clickfail');	
	jQuery('#editfinder_skip a').addClass('clickfail');
	jQuery('#editfinder_skip_arrow').css('background-position','-165px -13px');
}

EditFinder.prototype.resetTopButtons = function() {
	//disable edit/skip choices
	jQuery('#editfinder_yes').removeClass('clickfail');
	jQuery('#editfinder_skip a').removeClass('clickfail');
	jQuery('#editfinder_skip_arrow').css('background-position','-165px 0');
}


var editFinder = new EditFinder();
setInterval('editFinder.updateStandingsTable()', 600000);

window.setTimeout(updateWidgetTimer, 60*1000);

function updateWidgetTimer() {
	updateTimer('stup');
	window.setTimeout(updateWidgetTimer, 60*1000);
}

function updateTimer(id) {
    var e = jQuery("#" + id);
    var i = parseInt(e.html());
    if (i > 1) {
       e.fadeOut(400, function() {
           i--;
           e.html(i);
           e.fadeIn();
        });
    }
}
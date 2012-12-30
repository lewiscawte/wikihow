/*
 * Edit Finder Class
 */
var editfinder_preview = false;

function EditFinder() {
	this.m_title = '';
	this.m_searchterms = '';
}

EditFinder.prototype.init = function () {
	editFinder.getArticle();
	
	/*category choosing*/
	jQuery('#editfinder_choose_cats').click(function(){
		jQuery('#dialog-box').html('');
		jQuery('#dialog-box').load('/Special:SuggestCategories', function(){
			var type = editFinder.getEditType();
			if (type !== '') {
				jQuery('#suggest_cats').attr('action',"/Special:SuggestCategories?type="+type);
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
		return false;
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

/*
IntroImageAdder.prototype.getStats = function () {
	var url = '/Special:IntroImageAdder?fetchStats=true';

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

		if (pastmessages.indexOf( json['message'] ) == -1) {
			jQuery('#editfinder_msg').html( json['message'] );
			pastmessages.push( json['message'] );
		} else {
			jQuery('#editfinder_msg').html( json['defaultmsg'] );
		}
	});
}

IntroImageAdder.prototype.getMessage = function () {
	var url = '/Special:IntroImageAdder?fetchMessage=true';

	jQuery.get(url, function (data) {
		var json = jQuery.parseJSON(data);
		jQuery('#editfinder_msg').html(json);
	});
}

IntroImageAdder.prototype.updateStandingsTable = function() {
	var url = '/Special:IntroImageAdder?standingsTable=true';

	jQuery.get(url, function (data) {
		jQuery('#editfinder_standings_table').html(data);
	});
}
*/
EditFinder.prototype.getArticle = function() {
	var url = '/Special:EditFinder?fetchArticle=1';
	var e = jQuery('#article_title');
	if (e.html()) {
		url += '&skip=' + encodeURIComponent(e.html());
	}	
	var title = '';
	
	//add the edit type
	var type = editFinder.getEditType();
	if (type !== '') 
		url += '&edittype=' + type;
	
	jQuery('#editfinder_article_inner').fadeOut('fast');
	jQuery('#editfinder_preview').fadeOut('fast',function() {
		jQuery('#editfinder_spinner').fadeIn();
		
		jQuery.get(url, function (data) {
			var json = jQuery.parseJSON(data);
			
			title = json['title'];
			aURL = json['url'];
			aid = json['aid'];

			//easyImageUpload.doEIU_IIA(title, searchterms, 'editfinder_main', 'intro');
			//window.setTimeout(introImageAdder.getStats, 1000);
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
			titlelink = '<a href="'+url+'">'+title+'</a>';
			jQuery('#editfinder_article_inner').html(titlelink);
			jQuery('#editfinder_yes').click(function() {
				editFinder.edit(id);
			});
			
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
			jQuery('#editform').submit(function() {
				//just a preview?
				if (editfinder_preview) {
					editFinder.showPreview(id);
					return false;	
				}
			});
			//throw cursor in the textarea
			jQuery('#wpTextbox1').focus();
			
			//add the id to the action url
			action = jQuery('#editform').attr('action');
			//action = "/Special:EditFinder?action=submit&aid="+id+"&type=stub";
			jQuery('#editform').attr('action',action+"&aid="+id);
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
				previewContainer.slideDown();
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

		if (pastmessages.indexOf( json['message'] ) == -1) {
			jQuery('#editfinder_msg').html( json['message'] );
			pastmessages.push( json['message'] );
		} else {
			jQuery('#editfinder_msg').html( json['defaultmsg'] );
		}
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

/*
IntroImageAdder.prototype.displayConfirmation = function( ret ) {
	if (getCookie('editfinder_modal') == null) {
		var url = '/Special:IntroImageAdder?confirmation=1&iiatitle='+ret['title']+'&imgtag='+ret['img'];
		popModal(url);
	}
}

IntroImageAdder.prototype.closeConfirmation = function() {
	if (jQuery('#confirmModalFlag').attr('checked')) {
		setCookie('editfinder_modal', 1, 365);
	}
	closeModal();
}
*/
var editFinder = new EditFinder();
//setInterval('introImageAdder.updateStandingsTable()', 600000);

/*
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
*/

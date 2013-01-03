/*
 * Starter Tool Class
 */

function StarterTool() {
}

StarterTool.prototype.init = function () {
	starter.getSentence();
	$('#starter_edit_button').click( function(e) {
		e.preventDefault();
		starter.edit();
	});
	$('#starter_skip a').click( function(e) {
		e.preventDefault();
		alert("boo...");
	});
}

StarterTool.prototype.getSentence = function () {
	var url = '/Special:StarterTool?getsome=1';
	
	$.get(url, function(data) {
		document.getElementById('starter_contents').innerHTML = data;
	});
}

StarterTool.prototype.doit = function () {
	//submit
	jQuery.ajax({
		type: 'POST',
		url: jQuery('#editform').attr('action'),
		data: jQuery('#editform').serialize()
	});	
}


StarterTool.prototype.edit = function () {
	var title = jQuery('#test_title').val();
    var url = '/Special:StarterTool?edit=1&test_title='+title;
	
	$.get(url, function (data) {
		document.getElementById('starter_contents').innerHTML = data;
		jQuery('#weave_button').css('display','none');
		jQuery('#easyimageupload_button').css('display','none');
		jQuery('#editfinder_preview').css('height','950px');

		//Preview button
		jQuery('#wpPreview').click(function() {
			starter_preview = true;
		});
		//Publish button
		jQuery('#wpSave').click(function() {
			starter_preview = false;
		});
		//form submit
		jQuery('#editform').submit(function(e) {
			e.preventDefault();
			//just a preview?
			if (starter_preview) {
				starter.showPreview();
				jQuery('html, body').animate({scrollTop:0});
				}
			else {				
				//submit
				starter.publish();				
			}
		});

		//pre-fill summary
		jQuery('#wpSummary').val("Edit from the Starter Tool");

		//make Cancel do the right thing
		jQuery('.editButtons a:last-child').unbind('click');
		jQuery('.editButtons a:last-child').click(function(e) {
			e.preventDefault();
			//do we need to make the preview disappear?
			if (starter_preview) {
				jQuery('#starter_preview').fadeOut('fast');
			}
			
			starter.resetTopButtons();
			starter.getSentence();
			jQuery('html, body').animate({scrollTop:0});
		});

		starter.disableTopButtons();
		
		jQuery('#editform').attr('action',jQuery('#editform').attr('action')+'&starter-title='+title);
	});
}

StarterTool.prototype.showPreview = function () {
	var title = jQuery('#test_title').val();
	var editform = jQuery('#wpTextbox1').val();	
	
	var url = '/index.php?action=submit&wpPreview=true&live=true&test_title='+title;
	
	jQuery.ajax({
		url: url,
		type: 'POST',
		data: 'wpTextbox1='+editform,
		success: function(data) {
			
			var XMLObject = data;
			var previewElement = jQuery(data).find('preview').first();

			/* Inject preview */
			var previewContainer = jQuery('#starter_preview');
			if ( previewContainer && previewElement ) {
				previewContainer.html(previewElement.first().text());
				previewContainer.slideDown('slow');
			}		
		}
	});
}

StarterTool.prototype.publish = function() {

	//submit it
	jQuery.ajax({
		type: 'POST',
		url: jQuery('#editform').attr('action'),
		data: jQuery('#editform').serialize()
	});
	
	jQuery('#starter_contents').html(starter.getGrats_options());
	jQuery('#starter_head').html(starter.getGrats());
	jQuery('#starter_snaggle').fadeOut('fast');
	jQuery('#starter_preview').fadeOut('fast');
	jQuery('html, body').animate({scrollTop:0});
}

StarterTool.prototype.getGrats = function() {
	var grats = '';

	grats = "<div id='starter_grats'><h3>Thank you!</h3>" +
			"<p>You've made an edit and improved wikiHow!</p></div>";
	
	return grats;
}

StarterTool.prototype.getGrats_options = function() {
	var grats = '';

	grats = "<div id='starter_grats_options'><p>What next's for you?  Try one of these:</p>" +
			"<p>or<a href='/Special:ListRequestedTopics' class='button button136' style='float:right'>Answer Requests</a>" +
			"<a href='/Special:IntroImageAdder' class='button button136' style='float:left'>Add an image</a></p>" +
			"<p>Or you can visit our <a href='/Special:CommunityDashboard'>Community Dashboard</a>.</p></div>";
	
	return grats;
}


StarterTool.prototype.disableTopButtons = function() {
	//disable edit/skip choices
	jQuery('#starter_edit_button').addClass('clickfail');	
	jQuery('#starter_skip a').addClass('clickfail');
	//jQuery('#editfinder_skip_arrow').css('background-position','-165px -13px');
	return;
}

StarterTool.prototype.resetTopButtons = function() {
	//disable edit/skip choices
	jQuery('#starter_edit_button').removeClass('clickfail');
	jQuery('#starter_skip a').removeClass('clickfail');
	return;
}

var starter = new StarterTool();

//kick it like rock death
starter.init();
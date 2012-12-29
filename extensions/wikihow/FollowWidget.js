/*
 * All the javascript required to display and use the Follow Widget
 */


/*
 *
 * Follow Widget class
 *
 */

// class to do "save emails" 

var overrideWinpopFuncs = {
	'replaceLinks': null,
	'resizeModal': null,
	'closeModal': null
};

function FollowWidget() {
	
}

// put up the modal dialog
FollowWidget.prototype.doFollowModal = function () {

	// scroll window to top
	window.scroll(75, 0);

	if (!overrideWinpopFuncs.closeModal) {
		overrideWinpopFuncs.closeModal = closeModal;
	}

	// set up post- dialog load callback
	var showBox = this.m_currentStep !== 0;
	var that = this;
	var onloadFunc = function () {
		// override default winpop.js behaviour -- don't change the size of
		// the dialog based on the viewable browser window
		if (!overrideWinpopFuncs.resizeModal) {
			overrideWinpopFuncs.resizeModal = resizeModal;
		}
		resizeModal = function() {};

		jQuery('#winpop_inner').css('height', '200px');
		jQuery('#winpop_inner').css('padding-right', '5px');
		jQuery('#winpop_outer').css('width', '450px');
		jQuery('#winpop_outer').css('left', '30%');
		jQuery('#winpop_content').css('background-image', 'url(/skins/WikiHow/images/email_middle.png)');
		var top = jQuery('.top');
		top.attr('src', '/skins/WikiHow/images/email_top.png');
		top.attr('width', 450);
		top.attr('height', 13);
		var bottom = jQuery('.bottom');
		bottom.attr('src', '/skins/WikiHow/images/email_bottom.png');
		bottom.attr('width', 450);
		bottom.attr('height', 13);

	};

	// display dialog
	popModal('/Special:FollowWidget?article-title=' + encodeURIComponent(wgTitle), '350', '180', true, onloadFunc);
	jQuery('#winpop_outer').css('position', 'absolute');

	// override this replaceLinks function before calling our popup 
	// function, because it breaks the EIU dialog to rewrite links
	if (!overrideWinpopFuncs.replaceLinks) {
		overrideWinpopFuncs.replaceLinks = replaceLinks;
	}
	replaceLinks = function() {};

	// override closeModal to undo other overrides once EIU has closed
	closeModal = function() {
		if (overrideWinpopFuncs.replaceLinks) {
			replaceLinks = overrideWinpopFuncs.replaceLinks;
		}
		if (overrideWinpopFuncs.resizeModal) {
			resizeModal = overrideWinpopFuncs.resizeModal;
		}
		if (overrideWinpopFuncs.closeModal) {
			var func = overrideWinpopFuncs.closeModal;
			closeModal = overrideWinpopFuncs.closeModal;
			func();
		}
	};
};


FollowWidget.prototype.displayError = function (msgID) {
	jQuery('#eiu-error-message').css('display', 'block');
	var msg = wfMsg(msgID);
	msg = (msg != '' ? msg : msgID);
	jQuery('#eiu-error-message').html(msg);
};

FollowWidget.prototype.resetError = function () {
	var errDiv = jQuery('#eiu-error-message');
	if (errDiv.length) {
		errDiv.css('display', 'none');
	}
};

FollowWidget.prototype.submitEmail = function (email) {
	var params = 'email=' + email;
	var that = this;
	jQuery.getJSON(
		'/Special:SubmitEmail',
		{newEmail: email},
		function(data){
			if (data.success) {
				alert(data.message);
				closeModal();
			}
			else{
				alert(data.message);
				
			}
		}
	);
};


FollowWidget.prototype.htmlBusyWheel = function () {
	var html = '<img src="/extensions/wikihow/rotate.gif" alt="" />';
	return html;
};

// singleton instance of this class
var followWidget = new FollowWidget();



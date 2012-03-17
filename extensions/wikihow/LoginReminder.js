/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
var focusElement = "";

jQuery(document).ready(function(){

	//show the instructions if they exist
	jQuery(".input_med").focus(function(){
		id = $(this).attr("id");
		focusElement = id;
		if($("#" + id + "_mark").is(":visible")){
			$("#" + id + "_error").show();
		}
		else{
			$("#" + id + "_info").show();
		}
	});

	//hide the instructions if user isn't hovering
	//over them/
	jQuery(".input_med").blur(function(){
		id = $(this).attr("id");
		if(focusElement == id)
			focusElement = "";
		$("#" + id + "_error").hide();
		
		if($("#" + id + "_showhide").val() != 1)
			$("#" + id + "_info").hide();
	});

	jQuery(".mw-info").hover(function(){
		idInfo = $(this).attr("id");
		id = idInfo.substring(0, idInfo.length - 5);
		$("#" + id + "_showhide").val(1);
	}, function(){
		idInfo = $(this).attr("id");
		id = idInfo.substring(0, idInfo.length - 5);
		$("#" + id + "_showhide").val(0);
		if(id != focusElement)
			$(this).hide();
	});

	jQuery("#wpUseRealNameAsDisplay").change(function(){
		if (jQuery("#wpUseRealNameAsDisplay").is(':checked')){
			$("#real_name_row").removeClass('hiderow');
		} else {
			$("#real_name_row").addClass('hiderow');
			$("#wpRealName").val("");
		}
	});

	if (jQuery("#wpUseRealNameAsDisplay").is(':checked')){
		$("#real_name_row").removeClass('hiderow');
	}

	jQuery("#userCreate #wpName").blur(function(){
		checkName();
	})

	jQuery("#userCreate #wpPassword2").blur(function(){
		pass1 = jQuery(this).val();
		if(pass1.length < 4 && pass1.length > 0){
			jQuery("#wpPassword2_error div").html(unescape(passwordtooshort));
			jQuery("#wpPassword2_mark").show();
		}
		else{
			jQuery("#wpPassword2_mark").hide();
		}
	});

	jQuery("#userCreate #wpRetype").blur(function(){
		pass1 = jQuery("#userCreate #wpPassword2").val();
		pass2 = jQuery("#userCreate #wpRetype").val();

		if(pass1 != pass2){
			jQuery("#wpRetype_error div").html(unescape(badretype));
			jQuery("#wpRetype_mark").show();
		}
		else{
			jQuery("#wpRetype_mark").hide();
		}
	});

	jQuery(".wpMark").click(function(){
		idInfo = $(this).attr("id");
		id = idInfo.substring(0, idInfo.length - 5);
		jQuery("#" + id + "_error").show();
		jQuery("#" + id).focus();
	});

});

function checkName(){
	username = jQuery("#userCreate #wpName").val();
	var params = 'username=' + encodeURIComponent(username);
	var that = this;
	var url = '/Special:LoginCheck?' + params;
	jQuery.get(url, function(json) {
		if (json) {
			data = jQuery.parseJSON( json );
			if(data.error){
				jQuery("#wpName_error div").html(data.error);
				jQuery("#wpName_mark").show();
			}
			else{
				jQuery("#wpName_mark").hide();
			}
		} else {
			jQuery("#wpName_mark").hide();
		}
	});
}

var whWasPasswordReset = false;
function getPassword(url){
	jQuery('#dialog-box').html('');
	url += "?name=" + $("#wpName1").val();
	jQuery('#dialog-box').load(url, function(){
		whWasPasswordReset = false;
		jQuery('#dialog-box').dialog({
			width: 620,
			modal: true,
			title: 'Password Reset',
			close: function () {
				if (whWasPasswordReset) {
					jQuery('#password-reset-dialog').dialog({
						width: 250,
						modal: true
					});
					jQuery('#password-reset-ok').click(function() {
						jQuery('#password-reset-dialog').dialog('close');
						return false;
					});
				}
			}
		});
	});
}

function checkSubmit(name, captchaWord, captchaId) {
	var params = 'submit=true&name=' + encodeURIComponent(jQuery("#" + name).val()) + '&wpCaptchaId=' + jQuery("#" + captchaId).val() + '&wpCaptchaWord=' + jQuery("#" + captchaWord).val();
	var that = this;
	var url = '/Special:LoginReminder?' + params;
	jQuery.get(url, function(json) {
		if (json) {
			data = jQuery.parseJSON( json )
			jQuery(".mw-error").hide();
			if(data.success){
				whWasPasswordReset = true;
				jQuery('#form_message').html(data.success);
				jQuery('#dialog-box').dialog('close');
			}
			else{
				if(data.error_username){
					jQuery('#wpName2_error div').html(data.error_username);
					jQuery('#wpName2_error').show();
				}
				if(data.error_captcha){
					jQuery('#wpCaptchaWord_error div').html(data.error_captcha);
					jQuery('#wpCaptchaWord_error').show();
					jQuery('.captcha').html(decodeURI(data.newCaptcha));
				}
				if(data.error_general){
					jQuery('#wpName2_error div').html(data.error_general);
					jQuery('#wpName2_error').show();
				}
			}
		} else {
			
		}
	});
	return false;
};

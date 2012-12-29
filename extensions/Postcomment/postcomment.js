
var postcomment_request; 
var postcomment_target;
var postcomment_form;

function postcommentHandler() {
	if ( postcomment_request.readyState == 4) {
		if ( postcomment_request.status == 200 || postcomment_request.status == 500) {
			var targetBox = document.getElementById(postcomment_target);
			if (!targetBox) return;
			if (postcomment_target.indexOf("preview") > 0) {
				targetBox.innerHTML = gPreviewMsg + '<br/>' +  postcomment_request.responseText;
			} else {
				if (gNewpage) {
					var article = document.getElementById('noarticletext');
					if (article) article.innerHTML = '';
				}
				targetBox.innerHTML += postcomment_request.responseText; 
				var txtbox = document.getElementById("comment_text_" + postcomment_target.replace(/postcomment_newmsg_/, ''));
				if (postcomment_request.status == 200) 
					txtbox.value = "";
				txtbox.disabled = false;
				txtbox.focus();
				var previewBox = document.getElementById(postcomment_target.replace(/newmsg/, "preview"));
				if (previewBox) previewBox.innerHTML = "";
   				var p = document.getElementById("postcomment_progress_" + postcomment_target.replace(/postcomment_newmsg_/, ''));
    			if (p) p.setAttribute('style', 'display: none;');
				var button = document.getElementById("postcommentbutton_" + postcomment_target.replace(/postcomment_newmsg_/, ''));
				if (button) button.disabled = false;
			}
		}
	}
}

function postcommentPreview (target) {
	var strResult;
	var previewBox = document.getElementById("postcomment_preview_" +target);
	if (confirm) {
		previewBox.innerHTML = gPreviewText;
		try {
			postcomment_request = new XMLHttpRequest();
		} catch (error) {
			try {
				postcomment_request = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (error) {
				return false;
			}
		}
		//set globals
		postcomment_target = "postcomment_preview_" + target;

		var parameters = "comment=" + encodeURIComponent( document.getElementsByName("comment_text_" + target)[0].value );
		postcomment_request.open('POST', gPreviewURL,true);
		postcomment_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		postcomment_request.send(parameters); 
		
		postcomment_request.onreadystatechange = postcommentHandler;
	}
}

function publishHandler() {
    if ( postcomment_request.readyState == 4) {
        if ( postcomment_request.status == 200) {
            var previewBox = document.getElementById('postcomment_newmsg');
            previewBox.innerHTML = gPreviewMsg + '<br/>' +  postcomment_request.responseText;
        }
    }
}
function postcommentPublish(target, form) {
    var parameters = "";
    for (var i=0; i < form.elements.length; i++) {
        var element = form.elements[i];
        if (parameters != "") {
            parameters += "&";
        }
        if (element.name != 'wpPreview' && element.name != 'wpDiff')
            parameters += element.name + "=" + encodeURIComponent(element.value);
    }
    var strResult;
	//set globals
	postcomment_target = target;
	postcomment_form = form;

    try {
    	postcomment_request = new XMLHttpRequest();
    } catch (error) {
    	try {
        	postcomment_request = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (error) {
        	return false;
		}
	}
	//var button = document.getElementByID('postcommentbutton_' + 
	var button = document.getElementById("postcommentbutton_" + target.replace(/postcomment_newmsg_/, ''));
	if (button) {
		button.disabled = true;
	}
	var txtbox = document.getElementById("comment_text_" + target.replace(/postcomment_newmsg_/, ''));
	if (txtbox)  txtbox.disabled = true;
	var p = document.getElementById("postcomment_progress_" + target.replace(/postcomment_newmsg_/, ''));
	if (p) p.setAttribute('style', 'display: inline;');

	if (document.getElementById('wpCaptchaId')) {
		parameters += "&wpCaptchaId" + document.getElementById('wpCaptchaId').value;
		parameters += "&wpCaptchaWord" + document.getElementById('wpCaptchaWord').value;
	}
    postcomment_request.open('POST', gPostURL + "?fromajax=true",true);
    postcomment_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    postcomment_request.send(parameters);
    postcomment_request.onreadystatechange = postcommentHandler;

	return false;
}

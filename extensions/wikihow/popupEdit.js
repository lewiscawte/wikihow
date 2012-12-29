
function initPopupEdit(editURL) {
	document.getElementById('editModalPage').style.display = 'block';
	pue_editClick(editURL);
	return false;
}

function popupEditClose() {
	document.getElementById('editModalPage').style.display = 'none';
	return false;
}


var pue_request; 
var pue_cc_request; 
var pue_editUrl;
var pue_preview = false;
var pue_close = false;
var needToConfirm = true;

function cleanForPopup(windowText) {
	windowText = windowText.replace(/<a href.*?>Cancel<\/a>/g, '');
	windowText = windowText.replace(/<input id="wpDiff".*?\/>/g, '<input class="button white_button_100 submit_button" onmouseout="button_unswap(this);" onmouseover="button_swap(this);" type="button" value="Cancel"  onclick="popupEditClose();return false;">');
	windowText = windowText.replace(/<span class='editHelp'>.*opens in new window\)<\/span>/g, '');
	windowText = windowText.replace(/<a href.*?>Guided Editing<\/a>/g, '');
	windowText = windowText.replace(/<input id='weave_button'.*?>/g, '');
	windowText = windowText.replace(/<input id='fixcaps_button'.*?>/g, '');
	windowText = windowText.replace(/<input name="wpMinoredit".*?>This is a minor edit<\/label>/g, '');
	windowText = windowText.replace(/<input name="wpWatchthis".*?>Watch<\/label>/g, '');

	windowText = "<div id='article' >" + windowText + "</div>";
	//windowText = "<link rel=\"stylesheet\" type=\"text/css\" href=\"/skins/WikiHow/articledialog.css\" />" + windowText;

	return(windowText);
}

function pue_Handler() {
	if ( pue_request.readyState == 4) {
		if ( pue_request.status == 200) {
			var ac = document.getElementById('article_contents');

			var windowText = pue_request.responseText;
			ac.innerHTML = cleanForPopup(windowText);

			var textbxid = document.getElementById('wpTextbox1');
			textbxid.rows = 20;
			textbxid.cols = 70;

			var summary = document.getElementById('wpSummary');
			summary.value = gAutoSummaryText;

			document.editform.target = "_blank";
			var previewButton = document.getElementById('wpPreview');
			previewButton.setAttribute('onclick', 'pue_preview=true;');
			var saveButton = document.getElementById('wpSave');
			saveButton.setAttribute('onclick', 'pue_preview=false;');
			document.editform.setAttribute('onsubmit', 'return pue_SubmitForm();');
			document.editform.wpTextbox1.focus();

			//window.onbeforeunload = confirmExit;
		}
	}
}

function pue_editClick(url) {

	var strResult;
	pue_editUrl = url;
	var ac = document.getElementById('article_contents');
	ac.innerHTML = '<b>Loading...</b>';	
	ac.setAttribute('onDblClick', '');

	try {
		pue_request = new XMLHttpRequest();
	} catch (error) {
		try {
			pue_request = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (error) {
			return false;
		}
	}
	pue_request.open('GET', url,true);
	pue_request.send(''); 
	pue_request.onreadystatechange = pue_Handler;
}

function pue_clearEditForm() {
	var ac = document.getElementById('article_contents');
	ac.innerHTML = "Article saved.";
}

function pue_processEditHandler() {
    if ( pue_request.readyState == 4) {
        if ( pue_request.status == 200) {
            var ac = document.getElementById('article_contents');
				ac.innerHTML = cleanForPopup(pue_request.responseText);

//				ac.setAttribute('style', '');
//				ac.setAttribute('onDblClick', 'pue_editClick("' + pue_editUrl + '");');

//            document.editform.target = "_blank";
//            var save = document.getElementById('wpSave');
//            document.editform.setAttribute('onsubmit', 'return pue_SubmitForm();');
//            document.editform.wpTextbox1.focus();
			if (pue_preview) {
				var headID = document.getElementsByTagName("head")[0];         
				var cssNode = document.createElement('link');
				cssNode.type = 'text/css';
				cssNode.rel = 'stylesheet';
				cssNode.href = '/skins/WikiHow/articledialog.css';
				cssNode.media = 'screen';
				headID.appendChild(cssNode);

				var textbxid = document.getElementById('wpTextbox1');
				textbxid.rows = 20;
				textbxid.cols = 67;

				ac.scrollTop = 0;

				var previewButton = document.getElementById('wpPreview');
				previewButton.setAttribute('onclick', 'pue_preview=true;');
				var saveButton = document.getElementById('wpSave');
				saveButton.setAttribute('onclick', 'pue_preview=false;');
				document.editform.setAttribute('onsubmit', 'return pue_SubmitForm();');
				document.editform.wpTextbox1.focus();
			}
            if ( pue_close ) {
                ac.innerHTML = 'Saving...';
                pue_close = false;

                var newdiffid = document.getElementById('mw-diff-ntitle3');
                var confirmEdit = '<br\/><div style="background: yellow;"><b>'+gQuickEditComplete+'<\/b><\/div>';
                newdiffid.innerHTML = newdiffid.innerHTML+confirmEdit;

                popupEditClose();
            }
        }
    } 
}

function pue_SubmitForm() {
	var parameters = "";
	for (var i=0; i < document.editform.elements.length; i++) {
   		var element = document.editform.elements[i];
		if (parameters != "") {
			parameters += "&";
		}
		if (element.name == 'wpSave' && !pue_preview) {
            if (typeof handleQESubmit == 'function') {
                handleQESubmit();
            }
			pue_close = true;
		}
	
		if ( (element.name == 'wpPreview' && pue_preview) || (element.name == 'wpSave' && !pue_preview)) {
			parameters += element.name + "=" + encodeURIComponent(element.value);
		} else if (element.name != 'wpDiff' && element.name != 'wpPreview' && element.name != 'wpSave' && element.name.substring(0,7) != 'wpDraft')  {
			if (element.type == 'checkbox') {
				if (element.checked) {
					parameters += element.name + "=1";
				}
			} else {
				parameters += element.name + "=" + encodeURIComponent(element.value);
			}
		}
	}
    pue_request.open('POST', pue_editUrl + "&action=submit",true);
	pue_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    pue_request.send(parameters);
    pue_request.onreadystatechange = pue_processEditHandler;
	window.onbeforeunload = null;
		
	//window.setTimeout(pue_clearEditForm, 1000);
	return false; // block sending the forum
}

function pue_Merge(title) {
	document.pue_form.template3_merge.checked = 1;
	document.pue_form.param3_param1.value=title;
	document.pue_form.param3_param1.focus();
}

function confirmExit() {
 	if (needToConfirm) {
		//return gChangesLost;
	}
	return '';
}

function pue_cCheck_Handler() {
    if ( pue_cc_request.readyState == 4) {
    	var ac = document.getElementById('pue_copyrightresults');
        ac.innerHTML = pue_cc_request.responseText
    }
}

function pue_cCheck() {
    
	var ac = document.getElementById('pue_copyrightresults');
    ac.innerHTML = "<center><img src='/extensions/wikihow/rotate.gif'></center>"; 
    
    try {
        pue_cc_request = new XMLHttpRequest();
    } catch (error) {
        try {
            pue_cc_request = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (error) {
            return false;
        }
    }
    pue_cc_request.open('GET', pue_cc_url,true);
    pue_cc_request.send(''); 
    pue_cc_request.onreadystatechange = pue_cCheck_Handler;
}




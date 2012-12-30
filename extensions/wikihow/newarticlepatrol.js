
var nap_request; 
var nap_cc_request; 
var nap_editUrl;
var nap_preview = false;
var needToConfirm = true;

function nap_Handler() {
	if ( nap_request.readyState == 4) {
		if ( nap_request.status == 200) {
			var ac = document.getElementById('article_contents');
			ac.innerHTML = nap_request.responseText
			document.editform.target = "_blank";
			restoreToolbarButtons();
			var previewButton = document.getElementById('wpPreview');
			previewButton.setAttribute('onclick', 'nap_preview=true;');
			var saveButton = document.getElementById('wpSave');
			saveButton.setAttribute('onclick', 'nap_preview=false;');
			document.editform.setAttribute('onsubmit', 'return nap_SubmitForm();');
			document.editform.wpTextbox1.focus();
			var summary = document.getElementById('wpSummary');
			summary.value = gAutoSummaryText;
			window.onbeforeunload = confirmExit;
		}
	}
}

function nap_editClick(url) {
	var strResult;
	nap_editUrl = url;
	var ac = document.getElementById('article_contents');
	ac.innerHTML = '<b>Loading...</b>';	
	//ac.setAttribute('style', 'height: 450px;');
	ac.setAttribute('onDblClick', '');
	var bt = document.getElementById('editButton');
	bt.setAttribute('style', 'display:none');

	try {
		nap_request = new XMLHttpRequest();
	} catch (error) {
		try {
			nap_request = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (error) {
			return false;
		}
	}
	nap_request.open('GET', url,true);
	nap_request.send(''); 
	nap_request.onreadystatechange = nap_Handler;
}

function nap_clearEditForm() {
	var ac = document.getElementById('article_contents');
	ac.innerHTML = "Article saved.";
}

function nap_processEditHandler() {
    if ( nap_request.readyState == 4) {
        if ( nap_request.status == 200) {
            var ac = document.getElementById('article_contents');
            ac.innerHTML = nap_request.responseText
			ac.setAttribute('style', '');
			ac.setAttribute('onDblClick', 'nap_editClick("' + nap_editUrl + '");');
            //document.editform.target = "_blank";
            //var save = document.getElementById('wpSave');
            //document.editform.setAttribute('onsubmit', 'return nap_SubmitForm();');
           // document.editform.wpTextbox1.focus();
			var bt = document.getElementById('editButton');
			bt.setAttribute('style', '');
			if (nap_preview) {
				var previewButton = document.getElementById('wpPreview');
				previewButton.setAttribute('onclick', 'nap_preview=true;');
				var saveButton = document.getElementById('wpSave');
				saveButton.setAttribute('onclick', 'nap_preview=false;');
				document.editform.setAttribute('onsubmit', 'return nap_SubmitForm();');
				document.editform.wpTextbox1.focus();
			} else {
				nap_getDiffLink();
			}
        }
    }
}

function nap_SubmitForm() {
	var parameters = "";
	for (var i=0; i < document.editform.elements.length; i++) {
   		var element = document.editform.elements[i];
		if (parameters != "") {
			parameters += "&";
		}
	
		if ( (element.name == 'wpPreview' && nap_preview) || (element.name == 'wpSave' && !nap_preview)) {
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
    nap_request.open('POST', nap_editUrl + "&action=submit",true);
	nap_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    nap_request.send(parameters);
    nap_request.onreadystatechange = nap_processEditHandler;
	window.onbeforeunload = null;
		
	//window.setTimeout(nap_clearEditForm, 1000);
	return false; // block sending the forum
}

function nap_Merge(title) {
	document.nap_form.template3_merge.checked = 1;
	document.nap_form.param3_param1.value=title.replace(/&#39;/, "'");
	document.nap_form.param3_param1.focus();
}
function nap_Dupe(title) {
	document.nap_form.template4_nfddup.checked = 1;
	document.nap_form.param4_param1.value = title.replace(/&#39;/, "'");
	document.nap_form.param4_param1.focus();
}

function nap_onlyDup1() {
	if (document.nap_form.template4_nfddup.checked == 1)
		document.nap_form.template1_nfd.checked = 0;
}
function nap_onlyDup2() {
	if (document.nap_form.template1_nfd.checked == 1)
		document.nap_form.template4_nfddup.checked = 0;
}

function checkNap() {
	// check existence of dup article
	if (document.nap_form.template4_nfddup.checked) {
	   	nap_cc_request = getRequestObject();
		api_url = "http://" + window.location.hostname + "/api.php"
		params = "action=query&format=xml&titles=" + encodeURIComponent(document.nap_form.param4_param1.value);
	    nap_cc_request.open('POST', api_url, false);
		nap_cc_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	    nap_cc_request.send(params); 
		if (nap_cc_request.responseText.indexOf("pageid=") < 0) {
			alert("Oops!  The title, \"How to " + document.nap_form.param4_param1.value + "\", doesn't match any articles.  Capitalization and spelling must match perfectly.  \n\nCan you fix this and resubmit it?");
			return false;
		}
		return true;
	}
	return true;
}

function confirmExit() {
 	if (needToConfirm) {
		return gChangesLost;
	}
	return '';
}

function nap_cCheck_Handler() {
    if ( nap_cc_request.readyState == 4) {
    	var ac = document.getElementById('nap_copyrightresults');
        ac.innerHTML = nap_cc_request.responseText
    }
}

function nap_cCheck() {
    
	var ac = document.getElementById('nap_copyrightresults');
    ac.innerHTML = "<center><img src='/extensions/wikihow/rotate.gif'></center>"; 
    
   	nap_cc_request = getRequestObject();
    nap_cc_request.open('GET', nap_cc_url,true);
    nap_cc_request.send(''); 
    nap_cc_request.onreadystatechange = nap_cCheck_Handler;
}


function nap_MarkRelated(id, p1, p2) {
	url = "http://" + window.location.hostname + "/Special:Markrelated?p1=" + p1 + "&p2=" + p2;
    nap_cc_request = getRequestObject();
    nap_cc_request.open('GET', url, false);
    nap_cc_request.send(null);
	jQuery("#mr_" + id).fadeOut(400, function() {
			var e = jQuery("#mr_" + id);
			e.html("<b>Done!</b>");
			e.fadeIn();
		}
	);
}


function nap_copyVio(url) {
	document.nap_form.template5_copyvio.checked =true;
	document.nap_form.param5_param1.value = url;
	document.nap_form.param5_param1.focus();
	return false;
}

function nap_getDiffLink() {
	var r = getRequestObject();
	var target = document.nap_form.target.value;
	var url = "http://" + window.location.hostname + "/api.php?action=query&prop=revisions&titles=" + encodeURIComponent(target) + "&rvlimit=20&rvprop=timestamp|user|comment|ids&format=json";
	var pageid = document.nap_form.page.value;
	r.open('GET', url,false);
    r.send('');
	//alert(r.responseText);
	var obj = eval("(" + r.responseText + ")");
	//alert(obj.toSource());
	var first = obj.query.pages[pageid].revisions[0].revid;
	var last = null;
	for (i = 1; i < obj.query.pages[pageid].revisions.length; i++) {
		var rev = obj.query.pages[pageid].revisions[i];
		if (rev.user != wgUserName) {
			last = rev.revid;
			break;
		}
	}
	var e = $("#article_contents");
	e.html(e.html() + "<center><b><a href='/index.php?title=" +  encodeURIComponent(target) + "&diff=" + first + "&oldid=" + last +'>Link to Diff</a></center></b>');
}

$(function() {
	$("#tabs").tabs();
});

function checkNewbieFlush() {
	if (confirm("Are you sure you want to clear the newbie queue?")) {
		document.location = "/Special:Newarticleboost?newbie=1&flushnewbie=1&flushlimit=" + $("#newbieflush_limit").val();
	}
}



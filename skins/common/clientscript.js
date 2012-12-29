function Trim(s) {
	while ((s.substring(0,1) == ' ') || (s.substring(0,1) == '\n') || (s.substring(0,1) == '\r')){
		s = s.substring(1,s.length);
	}
	while ((s.substring(s.length-1,s.length) == ' ') || (s.substring(s.length-1,s.length) == '\n') || (s.substring(s.length-1,s.length) == '\r')) {
		s = s.substring(0,s.length-1);
	}
	return s;
}

function createOption(name) {
	var o = new Option(name, name, false, false);
	return o;
}

function addC3(name) {
	document.editform.cat3.options[document.editform.cat3.length] = createOption(name);
}

function addC2(name) {
	document.editform.cat2.options[document.editform.cat2.length] = createOption(name);
}

function changeOne() {
	var index = document.editform.cat1.options[document.editform.cat1.selectedIndex].value;
	fillInCat2(index);
}

function changeTwo() {
	var index = document.editform.cat2.options[document.editform.cat2.selectedIndex].value;
	fillInCat3(index);
}

function insertTagsWH(txtarea, tagOpen, tagClose, sampleText) {

	// IE
	if(document.selection  && !is_gecko) {
		var theSelection = document.selection.createRange().text;
		if(!theSelection) { theSelection=sampleText;}
		txtarea.focus();
		if(theSelection.charAt(theSelection.length - 1) == " "){// exclude ending space char, if any
			theSelection = theSelection.substring(0, theSelection.length - 1);
			document.selection.createRange().text = tagOpen + theSelection + tagClose + " ";
		} else {
			document.selection.createRange().text = tagOpen + theSelection + tagClose;
		}

	// Mozilla
	} else if(txtarea.selectionStart || txtarea.selectionStart == '0') {
		var startPos = txtarea.selectionStart;
		var endPos = txtarea.selectionEnd;
		var scrollTop=txtarea.scrollTop;
		var myText = (txtarea.value).substring(startPos, endPos);
		if(!myText) { myText=sampleText;}
		if(myText.charAt(myText.length - 1) == " "){ // exclude ending space char, if any
			subst = tagOpen + myText.substring(0, (myText.length - 1)) + tagClose + " ";
		} else {
			subst = tagOpen + myText + tagClose;
		}
		txtarea.value = txtarea.value.substring(0, startPos) + subst +
			txtarea.value.substring(endPos, txtarea.value.length);
		txtarea.focus();

		var cPos=startPos+(tagOpen.length+myText.length+tagClose.length);
		txtarea.selectionStart=cPos;
		txtarea.selectionEnd=cPos;
		txtarea.scrollTop=scrollTop;

	// All others
	} else {
		var copy_alertText=alertText;
		var re1=new RegExp("\\$1","g");
		var re2=new RegExp("\\$2","g");
		copy_alertText=copy_alertText.replace(re1,sampleText);
		copy_alertText=copy_alertText.replace(re2,tagOpen+sampleText+tagClose);
		var text;
		if (sampleText) {
			text=prompt(copy_alertText);
		} else {
			text="";
		}
		if(!text) { text=sampleText;}
		text=tagOpen+text+tagClose;
		document.infoform.infobox.value=text;
		// in Safari this causes scrolling
		if(!is_safari) {
			txtarea.focus();
		}
		noOverwrite=true;
	}
	// reposition cursor if possible
	if (txtarea.createTextRange) txtarea.caretPos = document.selection.createRange().duplicate();
}

function addNumToSteps(e, element) {
	var key;
	if(window.event) {
		// for IE, e.keyCode or window.event.keyCode can be used
		key = e.keyCode;
	}
	else if(e.which) {
		// netscape
		key = e.which;
	}
	else {
		// no event, so pass through
		return true;
	}

	if (key == '13' && !e.shiftKey) {
		if (element)
			insertTagsWH(element, "#  ", "", "");
		else
			insertTagsWH(document.editform.steps, "#  ", "", "");
		return;
	}
}

function addNumToTips(e) {
	var key;
	if(window.event) {
		// for IE, e.keyCode or window.event.keyCode can be used
		key = e.keyCode;
	}
	else if(e.which) {
		// netscape
		key = e.which;
	}
	else {
		// no event, so pass through
		return true;
	}

	if (key == '13' && !e.shiftKey) {
		insertTagsWH(document.editform.tips, "*  ", "", "");
		return;
	}
}

function addStars(e, element) {
	var key;
	if(window.event) {
		// for IE, e.keyCode or window.event.keyCode can be used
		key = e.keyCode;
	}
	else if(e.which) {
		// netscape
		key = e.which;
	}
	else {
		// no event, so pass through
		return true;
	}

	if (key == '13' && !e.shiftKey) {
		insertTagsWH(element, "*  ", "", "");
	}
}

function addCategories() {
	options = "toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=0,resizable=0,width=500,height=350";
	popupWindow = window.open("cat.php", "", options);
	if (!popupWindow.opener) popupWindow.opener = self;
}

function removeCategories() {
	url = "cat.php?action=remove&cats=" + escape(document.editform.categories.value);
	options = "toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=0,resizable=0,width=500,height=300";
	popupWindow = window.open(url, "", options);
	if (!popupWindow.opener) popupWindow.opener = self;
}

function checkSummary() {
	var e = jQuery("#wpSummary");
	var f = jQuery("#wpSummary1");
	if (!f.val()) return;
	var a = (e ? e.val() : "") + "," + (f? f.val() : "");
	var parts = a.split(",")
	var summ = "";
	for (i = 0; i < parts.length; i++) {
		var t = jQuery.trim(parts[i]);
		if (t == "," || t == "") continue;
		if (summ.indexOf(t) < 0) {
			summ += t + ", ";
		}
	}
	if (summ.length > 2)
		summ = summ.substring(0, summ.length -2);
	e.val(summ);
	f.val(summ);
}

function checkForm() {

	checkSummary();
	if (document.editform.title && document.editform.title.value == "") {
		alert ('Please enter a title for your wikiHow.');
		document.editform.title.focus();
		return false;
	}
	if (document.editform.title &&  document.editform.title.value.indexOf(".") >= 0) {
		alert ('The follow characters are not allowed in the title: .');
		document.editform.title.focus();
		return false;
	}
	if (document.editform.title &&  document.editform.title.value.indexOf("?") >= 0) {
		alert ('The follow characters are not allowed in the title: ?');
		document.editform.title.focus();
		return false;
	}
	if (document.editform.title &&  document.editform.title.value.indexOf(":") >= 0) {
		alert ('The follow characters are not allowed in the title: :');
		document.editform.title.focus();
		return false;
	}
	if (document.editform.summary.value == "") {
		alert ('Please enter a summary for your wikiHow.');
		document.editform.summary.focus();
		return false;
	}

	if (document.editform.title) {
		output = document.editform.title.value.replace(" ", "-");
		document.editform.action += "&title=" + output;
	}

	document.editform.related_list.value = "";
	if (document.editform.related) {
		for(var f=0; f<document.editform.related.length; ++f)
			document.editform.related_list.value += document.editform.related.options[f].value + "|";
	}

	var text = "";
	var fields = new Array("summary", "steps", "tips", "warnings");
	for (i = 0; i < fields.length; i++) {
		var name = fields[i];
		var e = document.editform[name];
		if (e) text += "\n" + e.value;
	}
	if (!checkMinLength)
		return true;
	var words = text.split(" ");
	var count = words.length - 6;
	if (wgArticleId == 0 && wgContentLanguage == 'en') {
		if (count <= 100) {
			jQuery('#dialog-box').html('');
			jQuery('#dialog-box').load('/Special:CreatepageWarn?warn=words&words=' + count);
			jQuery('#dialog-box').dialog({
				width: 600,
				modal: true,
				title: 'Warning'
			});
			//popModal('/Special:CreatepageWarn?warn=words&words=' + count, '600', '170');
			return false;
		} else {
			var up = 0;
			var lo = 0;
			var sen = 0;
			for (i = 0; i < text.length; i++) {
				var ch = text.substring(i, i+1);
				if (ch.match(/[A-Z]/))
					up++;
				else if (ch.match(/[a-z]/))
					lo++;
				else if (ch.match(/[\W]/) && !ch.match(/[\s]/) && !ch.match(/[#\*]/)) {
					sen++;
				}
			}
			var ratio = up / ( up + lo);
			if (ratio >= 0.10) {
				jQuery('#dialog-box').html('');
				jQuery('#dialog-box').load('/Special:CreatepageWarn?warn=caps&ratio=' + ratio);
				jQuery('#dialog-box').dialog({
					width: 600,
					modal: true,
					title: 'Warning'
				});
				//popModal('/Special:CreatepageWarn?warn=caps&ratio=' + ratio, '600', '170');
				return false;
			} else if (sen - 2 < 10) {
				jQuery('#dialog-box').html('');
				jQuery('#dialog-box').load('/Special:CreatepageWarn?warn=sentences&sen=' + (sen));
				jQuery('#dialog-box').dialog({
					width: 600,
					modal: true,
					title: 'Warning'
				});
				//popModal('/Special:CreatepageWarn?warn=sentences&sen=' + (sen), '600', '170');
				return false;
			}
			var headID = document.getElementsByTagName("head")[0];
			var cssNode = document.createElement('link');
			cssNode.type = 'text/css';
			cssNode.rel = 'stylesheet';
			cssNode.href = '/skins/WikiHow/articledialog.css';
			cssNode.media = 'screen';
			headID.appendChild(cssNode);

			popModal('/Special:CreatepageReview', '800', '400', true);
			window.setTimeout("launch_preview();", 1000);
			return false;
		}
	}
	return true;
}

function imagePopup(url, e) {
	popupWindow=open(url, null, 'scrollbars=no,status=no,width=790,height=220');
	if (popupWindow.opener != null) popupWindow.opener = self;
	else alert ("Oops! A pop-up blocker may have blocked this window. Please add ehow.com to your list of allowed sites for your pop-up blocker.");
}

function add_related() {
	if (document.editform.q.value != '') {
		document.editform.related.options[document.editform.related.length] = new Option(document.editform.q.value,document.editform.q.value);
		document.editform.q.value = "";
		document.editform.q.focus();
	}

	return false;
}

function keyxxx(e) {
	var key;
	if(window.event) {
		// for IE, e.keyCode or window.event.keyCode can be used
		key = e.keyCode;
	}
	else if(e.which) {
		// netscape
		key = e.which;
	}
	else {
		// no event, so pass through
		return true;
	}

	if (key == 13) {
		add_related();

		return false;
	}

}

function viewRelated() {
	for (var f=0; f < document.editform.related.length; ++f) {
		if (document.editform.related.options[f].selected) {
			window.open('http://www.wikihow.com/' + document.editform.related.options[f].value);
			break;
		}
	}
}

function removeRelated() {
	for (var f=0; f < document.editform.related.length; ++f) {
		if (document.editform.related.options[f].selected) {
			document.editform.related.options[f] = null;
			break;
		}
	}
	document.editform.q.focus();
	return false;
}

function moveRelated(bDir) {
	var el = document.editform.related;
	var idx = el.selectedIndex;
	if (idx == -1) {
		return;
	} else {
		var nxidx = idx + (bDir ? -1 : 1);
		if (nxidx < 0) nxidx = el.length - 1;
		if (nxidx >= el.length) nxidx = 0;
		var oldVal = el[idx].value;
		var oldText = el[idx].text;
		el[idx].value = el[nxidx].value;
		el[idx].text = el[nxidx].text;
		el[nxidx].value = oldVal;
		el[nxidx].text = oldText;
		el.selectedIndex = nxidx;
	}
}

function showhiderow(row, box) {
	var rowobject = document.getElementById(row);
	var boxobject = document.getElementById(box);
	if (rowobject != null) {
		if (boxobject.checked)
			rowobject.className = 'display';
		else
			rowobject.className = 'hide';
	}
}

function fixcaps(e) {
	var text = e.value.toLowerCase().replace(/(^\s*\w|[\.\!\?]\s*\w)/g,function(c){return c.toUpperCase()});
	text = text.replace(/(^(#|\*)[ ]*[^ ])/gim,function(c){return c.toUpperCase()});
	text = text.replace(/(^==[ ]*[^ ])/gim,function(c){return c.toUpperCase()});
	e.value = text;
}

var pre_request;
var pre_id;

function pre_Handler() {
	if (pre_request.readyState == 4) {
		if (pre_request.status == 200) {
			var e = document.getElementById('preview_landing');
			e.innerHTML = pre_request.responseText;
		}
	}
}

function launch_preview() {
	try {
		pre_request = new XMLHttpRequest();
	} catch (error) {
		try {
			pre_request = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (error) {
			return false;
		}
	}
	var params = "";
	for (i = 0; document.editform && i < document.editform.elements.length; i++) {
		if (document.editform.elements[i].name != "") {
			params += document.editform.elements[i].name + "=" + encodeURIComponent(document.editform.elements[i].value) + "&";
		}
	}
	var url = "http://" + window.location.hostname + "/Special:BuildWikiHow?parse=1";
	pre_request.open('POST', url);
	pre_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	pre_request.send(params);
	pre_request.onreadystatechange = pre_Handler;
}

function resetVideoLinks() {
	s = "";
	var iv = document.getElementById('importvideo');
	var links = iv.getElementsByTagName('A');
	for (i=0; i < links.length; i++) {
		s += links[i].nodeName + ", " + links[i].href + "\n";
	}
	alert(s);
}

function changeVideo (target) {
	/* jQuery('#dialog-box').load('/Special:Importvideo?popup=true&target=' + target);
	jQuery('#dialog-box').dialog({
		width: 600,
		modal: true
	}); */
	popModal('/Special:Importvideo?popup=true&target=' + target, '750', '600');
	//window.setTimeout(resetVideoLinks, 1000);
}


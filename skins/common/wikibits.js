// MediaWiki JavaScript support functions

var clientPC = navigator.userAgent.toLowerCase(); // Get client info
var is_gecko = ((clientPC.indexOf('gecko')!=-1) && (clientPC.indexOf('spoofer')==-1)
	&& (clientPC.indexOf('khtml') == -1) && (clientPC.indexOf('netscape/7.0')==-1));
var is_safari = ((clientPC.indexOf('applewebkit')!=-1) && (clientPC.indexOf('spoofer')==-1));
var is_khtml = (navigator.vendor == 'KDE' || ( document.childNodes && !document.all && !navigator.taintEnabled ));
// For accesskeys
var is_ff2_win = (clientPC.indexOf('firefox/2')!=-1 || clientPC.indexOf('minefield/3')!=-1) && clientPC.indexOf('windows')!=-1;
var is_ff2_x11 = (clientPC.indexOf('firefox/2')!=-1 || clientPC.indexOf('minefield/3')!=-1) && clientPC.indexOf('x11')!=-1;
if (clientPC.indexOf('opera') != -1) {
	var is_opera = true;
	var is_opera_preseven = (window.opera && !document.childNodes);
	var is_opera_seven = (window.opera && document.childNodes);
	var is_opera_95 = (clientPC.search(/opera\/(9.[5-9]|[1-9][0-9])/)!=-1);
}

// Global external objects used by this script.
/*extern ta, stylepath, skin */

// set defaults for these so that JS errors don't occur
if (typeof stylepath === 'undefined') stylepath = ''; // '/skins';
if (typeof wgContentLanguage === 'undefined') wgContentLanguage = ''; // 'en';

// add any onload functions in this hook (please don't hard-code any events in the xhtml source)
var doneOnloadHook;

if (!window.onloadFuncts) {
	var onloadFuncts = [];
}

function addOnloadHook(hookFunct) {
	// Allows add-on scripts to add onload functions
	onloadFuncts[onloadFuncts.length] = hookFunct;
}

function hookEvent(hookName, hookFunct) {
	if (window.addEventListener) {
		window.addEventListener(hookName, hookFunct, false);
	} else if (window.attachEvent) {
		window.attachEvent("on" + hookName, hookFunct);
	}
}

// document.write special stylesheet links
/*
if (typeof stylepath != 'undefined' && typeof skin != 'undefined') {
	if (is_opera_preseven) {
		document.write('<link rel="stylesheet" type="text/css" href="'+stylepath+'/'+skin+'/Opera6Fixes.css">');
	} else if (is_opera_seven && !is_opera_95) {
		document.write('<link rel="stylesheet" type="text/css" href="'+stylepath+'/'+skin+'/Opera7Fixes.css">');
	} else if (is_khtml) {
		document.write('<link rel="stylesheet" type="text/css" href="'+stylepath+'/'+skin+'/KHTMLFixes.css">');
	}
}
*/

if (typeof wgBreakFrames !== 'undefined' && wgBreakFrames) {
	// Un-trap us from framesets
	if (window.top != window) {
		window.top.location = window.location;
	}
}

// for enhanced RecentChanges
function toggleVisibility(_levelId, _otherId, _linkId) {
	var thisLevel = document.getElementById(_levelId);
	var otherLevel = document.getElementById(_otherId);
	var linkLevel = document.getElementById(_linkId);
	if (thisLevel.style.display == 'none') {
		thisLevel.style.display = 'block';
		otherLevel.style.display = 'none';
		linkLevel.style.display = 'inline';
	} else {
		thisLevel.style.display = 'none';
		otherLevel.style.display = 'inline';
		linkLevel.style.display = 'none';
	}
}

function historyRadios(parent) {
	var inputs = parent.getElementsByTagName('input');
	var radios = [];
	for (var i = 0; i < inputs.length; i++) {
		if (inputs[i].name == "diff" || inputs[i].name == "oldid") {
			radios[radios.length] = inputs[i];
		}
	}
	return radios;
}

// check selection and tweak visibility/class onclick
function diffcheck() {
	var dli = false; // the li where the diff radio is checked
	var oli = false; // the li where the oldid radio is checked
	var hf = document.getElementById('pagehistory');
	if (!hf) {
		return true;
	}
	var lis = hf.getElementsByTagName('tr');
	for (var i=0;i<lis.length;i++) {
		var inputs = historyRadios(lis[i]);
		if (inputs[1] && inputs[0]) {
			if (inputs[1].checked || inputs[0].checked) { // this row has a checked radio button
				if (inputs[1].checked && inputs[0].checked && inputs[0].value == inputs[1].value) {
					return false;
				}
				if (oli) { // it's the second checked radio
					if (inputs[1].checked) {
						oli.className = "selected";
						return false;
					}
				} else if (inputs[0].checked) {
					return false;
				}
				if (inputs[0].checked) {
					dli = lis[i];
				}
				if (!oli) {
					inputs[0].style.visibility = 'hidden';
				}
				if (dli) {
					inputs[1].style.visibility = 'hidden';
				}
				//lis[i].className = "selected";
				oli = lis[i];
			}  else { // no radio is checked in this row
				if (!oli) {
					inputs[0].style.visibility = 'hidden';
				} else {
					inputs[0].style.visibility = 'visible';
				}
				if (dli) {
					inputs[1].style.visibility = 'hidden';
				} else {
					inputs[1].style.visibility = 'visible';
				}
				//lis[i].className = "";
			}
		}
	}
	return true;
}

// page history stuff
// attach event handlers to the input elements on history page
function histrowinit() {
	var hf = document.getElementById('pagehistory');
	if (!hf) {
		return;
	}
	var lis = hf.getElementsByTagName('tr');
	for (var i = 0; i < lis.length; i++) {
		var inputs = historyRadios(lis[i]);
		if (inputs[0] && inputs[1]) {
			inputs[0].onclick = diffcheck;
			inputs[1].onclick = diffcheck;
		}
	}
	diffcheck();
}

// generate toc from prefs form, fold sections
// XXX: needs testing on IE/Mac and safari
// more comments to follow
function tabbedprefs() {
	var prefform = document.getElementById('preferences');
	if (!prefform || !document.createElement) {
		return;
	}
	if (prefform.nodeName.toLowerCase() == 'a') {
		return; // Occasional IE problem
	}
	prefform.className = prefform.className + 'jsprefs';
	var sections = [];
	var children = prefform.childNodes;
	var seci = 0;
	for (var i = 0; i < children.length; i++) {
		if (children[i].nodeName.toLowerCase() == 'fieldset') {
			children[i].id = 'prefsection-' + seci;
			children[i].className = 'prefsection';
			if (is_opera || is_khtml) {
				children[i].className = 'prefsection operaprefsection';
			}
			var legends = children[i].getElementsByTagName('legend');
			sections[seci] = {};
			legends[0].className = 'mainLegend';
			if (legends[0] && legends[0].firstChild.nodeValue) {
				sections[seci].text = legends[0].firstChild.nodeValue;
			} else {
				sections[seci].text = '# ' + seci;
			}
			sections[seci].secid = children[i].id;
			seci++;
			if (sections.length != 1) {
				children[i].style.display = 'none';
			} else {
				var selectedid = children[i].id;
			}
		}
	}
	var toc = document.createElement('div');
	toc.id = 'preferences_tabs';
	toc.selectedid = selectedid;
	for (i = 0; i < sections.length; i++) {
		var a = document.createElement('a');
		if (i === 0) {
			a.className = 'on';
		}
		a.href = '#' + sections[i].secid;
		a.onmousedown = a.onclick = uncoversection;
		a.appendChild(document.createTextNode(sections[i].text));
		a.title = sections[i].text;
		a.id = "tab_"+sections[i].secid;
		toc.appendChild(a);
	}

/* VU CLEAN THIS CRAP UP!!!! */

/*
	var toc = document.createElement('ul');
	toc.id = 'preftoc';
	toc.selectedid = selectedid;
	for (i = 0; i < sections.length; i++) {
		var li = document.createElement('li');
		if (i === 0) {
			li.className = 'selected';
		}
		var a = document.createElement('a');
		a.href = '#' + sections[i].secid;
		a.onmousedown = a.onclick = uncoversection;
		a.appendChild(document.createTextNode(sections[i].text));
		a.secid = sections[i].secid;
		li.appendChild(a);
		toc.appendChild(li);
	}
*/
	var arttabsline = document.getElementById('article_tabs_line');
	prefform.parentNode.insertBefore(toc, prefform.parentNode.childNodes[0]);
	arttabsline.parentNode.insertBefore(toc, arttabsline);
	document.getElementById('prefsubmit').id = 'prefcontrol';
}

function uncoversection() {
	//var oldsecid = this.parentNode.parentNode.selectedid;
	var newsec = document.getElementById(this.id);
	if (newsec.className != 'on') {
		var a = document.getElementById('preferences_tabs');
		var as = a.getElementsByTagName('a');
		for (var i = 0; i< as.length; i++) {
			as[i].className = '';
			var section = document.getElementById( as[i].id.replace("tab_","") );
			section.style.display = 'none';
		}

		//document.getElementById(oldsecid).style.display = 'none';
		newsec.className = 'on';
		//newsec.style.display = 'block';
		var section = document.getElementById( newsec.id.replace("tab_","") );
		section.style.display = 'block';
		//ul.selectedid = this.secid;
		//oldsecid.className = '';

	}
	return false;
}

/* function uncoversection_old_DELETE() {
	var oldsecid = this.parentNode.parentNode.selectedid;
	var newsec = document.getElementById(this.secid);
	if (oldsecid != this.secid) {
		var ul = document.getElementById('preftoc');
		document.getElementById(oldsecid).style.display = 'none';
		newsec.style.display = 'block';
		ul.selectedid = this.secid;
		var lis = ul.getElementsByTagName('li');
		for (var i = 0; i< lis.length; i++) {
			lis[i].className = '';
		}
		this.parentNode.className = 'selected';
	}
	return false;
} */

// Timezone stuff
// tz in format [+-]HHMM
function checkTimezone(tz, msg) {
	var localclock = new Date();
	// returns negative offset from GMT in minutes
	var tzRaw = localclock.getTimezoneOffset();
	var tzHour = Math.floor( Math.abs(tzRaw) / 60);
	var tzMin = Math.abs(tzRaw) % 60;
	var tzString = ((tzRaw >= 0) ? "-" : "+") + ((tzHour < 10) ? "0" : "") + tzHour + ((tzMin < 10) ? "0" : "") + tzMin;
	if (tz != tzString) {
		var junk = msg.split('$1');
		document.write(junk[0] + "UTC" + tzString + junk[1]);
	}
}

function unhidetzbutton() {
	var tzb = document.getElementById('guesstimezonebutton');
	if (tzb) {
		tzb.style.display = 'inline';
	}
}

// in [-]HH:MM format...
// won't yet work with non-even tzs
function fetchTimezone() {
	// FIXME: work around Safari bug
	var localclock = new Date();
	// returns negative offset from GMT in minutes
	var tzRaw = localclock.getTimezoneOffset();
	var tzHour = Math.floor( Math.abs(tzRaw) / 60);
	var tzMin = Math.abs(tzRaw) % 60;
	var tzString = ((tzRaw >= 0) ? "-" : "") + ((tzHour < 10) ? "0" : "") + tzHour +
		":" + ((tzMin < 10) ? "0" : "") + tzMin;
	return tzString;
}

function guessTimezone(box) {
	document.getElementsByName("wpHourDiff")[0].value = fetchTimezone();
}

function showTocToggle() {
	if (document.createTextNode) {
		// Uses DOM calls to avoid document.write + XHTML issues

		var linkHolder = document.getElementById('toctitle');
		if (!linkHolder) {
			return;
		}

		var outerSpan = document.createElement('span');
		outerSpan.className = 'toctoggle';

		var toggleLink = document.createElement('a');
		toggleLink.id = 'togglelink';
		toggleLink.className = 'internal';
		toggleLink.href = 'javascript:toggleToc()';
		toggleLink.appendChild(document.createTextNode(tocHideText));

		outerSpan.appendChild(document.createTextNode('['));
		outerSpan.appendChild(toggleLink);
		outerSpan.appendChild(document.createTextNode(']'));

		linkHolder.appendChild(document.createTextNode(' '));
		linkHolder.appendChild(outerSpan);

		var cookiePos = document.cookie.indexOf("hidetoc=");
		if (cookiePos > -1 && document.cookie.charAt(cookiePos + 8) == 1) {
			toggleToc();
		}
	}
}

function changeText(el, newText) {
	// Safari work around
	if (el.innerText) {
		el.innerText = newText;
	} else if (el.firstChild && el.firstChild.nodeValue) {
		el.firstChild.nodeValue = newText;
	}
}

function toggleToc() {
	var toc = document.getElementById('toc').getElementsByTagName('ul')[0];
	var toggleLink = document.getElementById('togglelink');

	if (toc && toggleLink && toc.style.display == 'none') {
		changeText(toggleLink, tocHideText);
		toc.style.display = 'block';
		document.cookie = "hidetoc=0";
	} else {
		changeText(toggleLink, tocShowText);
		toc.style.display = 'none';
		document.cookie = "hidetoc=1";
	}
}

var mwEditButtons = [];
var mwCustomEditButtons = []; // eg to add in MediaWiki:Common.js

// this function generates the actual toolbar buttons with localized text
// we use it to avoid creating the toolbar where javascript is not enabled
function addButton(imageFile, speedTip, tagOpen, tagClose, sampleText, imageId) {
	// Don't generate buttons for browsers which don't fully
	// support it.
	mwEditButtons[mwEditButtons.length] =
		{"imageId": imageId,
		 "imageFile": imageFile,
		 "speedTip": speedTip,
		 "tagOpen": tagOpen,
		 "tagClose": tagClose,
		 "sampleText": sampleText};
}

// this function generates the actual toolbar buttons with localized text
// we use it to avoid creating the toolbar where javascript is not enabled
function mwInsertEditButton(parent, item) {
	var image = document.createElement("img");
	image.width = 23;
	image.height = 22;
	image.className = "mw-toolbar-editbutton";
	if (item.imageId) image.id = item.imageId;
	image.src = item.imageFile;
	image.border = 0;
	image.alt = item.speedTip;
	image.title = item.speedTip;
	image.style.cursor = "pointer";
	image.onclick = function() {
		insertTags(item.tagOpen, item.tagClose, item.sampleText);
		return false;
	};

	parent.appendChild(image);
	return true;
}

function mwSetupToolbar() {
	var toolbar = document.getElementById('toolbar');
	if (!toolbar) { return false; }

	var textbox = document.getElementById('wpTextbox1');
	if (!textbox) { return false; }

	// Don't generate buttons for browsers which don't fully
	// support it.
	if (!(document.selection && document.selection.createRange)
		&& textbox.selectionStart === null) {
		return false;
	}

	for (var i = 0; i < mwEditButtons.length; i++) {
		mwInsertEditButton(toolbar, mwEditButtons[i]);
	}
	for (var i = 0; i < mwCustomEditButtons.length; i++) {
		mwInsertEditButton(toolbar, mwCustomEditButtons[i]);
	}
	return true;
}

function escapeQuotes(text) {
	var re = new RegExp("'","g");
	text = text.replace(re,"\\'");
	re = new RegExp("\\n","g");
	text = text.replace(re,"\\n");
	return escapeQuotesHTML(text);
}

function escapeQuotesHTML(text) {
	var re = new RegExp('&',"g");
	text = text.replace(re,"&amp;");
	re = new RegExp('"',"g");
	text = text.replace(re,"&quot;");
	re = new RegExp('<',"g");
	text = text.replace(re,"&lt;");
	re = new RegExp('>',"g");
	text = text.replace(re,"&gt;");
	return text;
}

// apply tagOpen/tagClose to selection in textarea,
// use sampleText instead of selection if there is none
function insertTags(tagOpen, tagClose, sampleText) {
	var txtarea;
	if (document.editform) {
		txtarea = document.editform.wpTextbox1;
	} else {
		// some alternate form? take the first one we can find
		var areas = document.getElementsByTagName('textarea');
		txtarea = areas[0];
	}
	var selText, isSample = false;

	if (document.selection  && document.selection.createRange) { // IE/Opera

		//save window scroll position
		if (document.documentElement && document.documentElement.scrollTop)
			var winScroll = document.documentElement.scrollTop
		else if (document.body)
			var winScroll = document.body.scrollTop;
		//get current selection
		txtarea.focus();
		var range = document.selection.createRange();
		selText = range.text;
		//insert tags
		checkSelectedText();
		range.text = tagOpen + selText + tagClose;
		//mark sample text as selected
		if (isSample && range.moveStart) {
			if (window.opera)
				tagClose = tagClose.replace(/\n/g,'');
			range.moveStart('character', - tagClose.length - selText.length);
			range.moveEnd('character', - tagClose.length);
		}
		range.select();
		//restore window scroll position
		if (document.documentElement && document.documentElement.scrollTop)
			document.documentElement.scrollTop = winScroll
		else if (document.body)
			document.body.scrollTop = winScroll;

	} else if (txtarea.selectionStart || txtarea.selectionStart == '0') { // Mozilla

		//save textarea scroll position
		var textScroll = txtarea.scrollTop;
		//get current selection
		txtarea.focus();
		var startPos = txtarea.selectionStart;
		var endPos = txtarea.selectionEnd;
		selText = txtarea.value.substring(startPos, endPos);
		//insert tags
		checkSelectedText();
		txtarea.value = txtarea.value.substring(0, startPos)
			+ tagOpen + selText + tagClose
			+ txtarea.value.substring(endPos, txtarea.value.length);
		//set new selection
		if (isSample) {
			txtarea.selectionStart = startPos + tagOpen.length;
			txtarea.selectionEnd = startPos + tagOpen.length + selText.length;
		} else {
			txtarea.selectionStart = startPos + tagOpen.length + selText.length + tagClose.length;
			txtarea.selectionEnd = txtarea.selectionStart;
		}
		//restore textarea scroll position
		txtarea.scrollTop = textScroll;
	}

	function checkSelectedText(){
		if (!selText) {
			selText = sampleText;
			isSample = true;
		} else if (selText.charAt(selText.length - 1) == ' ') { //exclude ending space char
			selText = selText.substring(0, selText.length - 1);
			tagClose += ' '
		}
	}

}


/**
 * Set the accesskey prefix based on browser detection.
 */
var tooltipAccessKeyPrefix = 'alt-';
if (is_opera) {
	tooltipAccessKeyPrefix = 'shift-esc-';
} else if (is_safari
	   || navigator.userAgent.toLowerCase().indexOf('mac') != -1
	   || navigator.userAgent.toLowerCase().indexOf('konqueror') != -1 ) {
	tooltipAccessKeyPrefix = 'ctrl-';
} else if (is_ff2_x11 || is_ff2_win) {
	tooltipAccessKeyPrefix = 'alt-shift-';
}
var tooltipAccessKeyRegexp = /\[(ctrl-)?(alt-)?(shift-)?(esc-)?.\]$/;

/**
 * Add the appropriate prefix to the accesskey shown in the tooltip.
 * If the nodeList parameter is given, only those nodes are updated;
 * otherwise, all the nodes that will probably have accesskeys by
 * default are updated.
 *
 * @param Array nodeList -- list of elements to update
 */
function updateTooltipAccessKeys( nodeList ) {
	if ( !nodeList ) {
		// skins without a "column-one" element don't seem to have links with accesskeys either
		var columnOne = document.getElementById("column-one");
		if ( columnOne )
			updateTooltipAccessKeys( columnOne.getElementsByTagName("a") );
		// these are rare enough that no such optimization is needed
		updateTooltipAccessKeys( document.getElementsByTagName("input") );
		updateTooltipAccessKeys( document.getElementsByTagName("label") );
		return;
	}

	for ( var i = 0; i < nodeList.length; i++ ) {
		var element = nodeList[i];
		var tip = element.getAttribute("title");
		var key = element.getAttribute("accesskey");
		if ( key && tooltipAccessKeyRegexp.exec(tip) ) {
			tip = tip.replace(tooltipAccessKeyRegexp,
				  "["+tooltipAccessKeyPrefix+key+"]");
			element.setAttribute("title", tip );
		}
	}
}

/**
 * Add a link to one of the portlet menus on the page, including:
 *
 * p-cactions: Content actions (shown as tabs above the main content in Monobook)
 * p-personal: Personal tools (shown at the top right of the page in Monobook)
 * p-navigation: Navigation
 * p-tb: Toolbox
 *
 * This function exists for the convenience of custom JS authors.  All
 * but the first three parameters are optional, though providing at
 * least an id and a tooltip is recommended.
 *
 * By default the new link will be added to the end of the list.  To
 * add the link before a given existing item, pass the DOM node of
 * that item (easily obtained with document.getElementById()) as the
 * nextnode parameter; to add the link _after_ an existing item, pass
 * the node's nextSibling instead.
 *
 * @param String portlet -- id of the target portlet ("p-cactions", "p-personal", "p-navigation" or "p-tb")
 * @param String href -- link URL
 * @param String text -- link text (will be automatically lowercased by CSS for p-cactions in Monobook)
 * @param String id -- id of the new item, should be unique and preferably have the appropriate prefix ("ca-", "pt-", "n-" or "t-")
 * @param String tooltip -- text to show when hovering over the link, without accesskey suffix
 * @param String accesskey -- accesskey to activate this link (one character, try to avoid conflicts)
 * @param Node nextnode -- the DOM node before which the new item should be added, should be another item in the same list
 *
 * @return Node -- the DOM node of the new item (an LI element) or null
 */
function addPortletLink(portlet, href, text, id, tooltip, accesskey, nextnode) {
	var node = document.getElementById(portlet);
	if ( !node ) return null;
	node = node.getElementsByTagName( "ul" )[0];
	if ( !node ) return null;

	var link = document.createElement( "a" );
	link.appendChild( document.createTextNode( text ) );
	link.href = href;

	var item = document.createElement( "li" );
	item.appendChild( link );
	if ( id ) item.id = id;

	if ( accesskey ) {
		link.setAttribute( "accesskey", accesskey );
		tooltip += " ["+accesskey+"]";
	}
	if ( tooltip ) {
		link.setAttribute( "title", tooltip );
	}
	if ( accesskey && tooltip ) {
		updateTooltipAccessKeys( new Array( link ) );
	}

	if ( nextnode && nextnode.parentNode == node )
		node.insertBefore( item, nextnode );
	else
		node.appendChild( item );  // IE compatibility (?)

	return item;
}


/**
 * Set up accesskeys/tooltips from the deprecated ta array.  If doId
 * is specified, only set up for that id.  Note that this function is
 * deprecated and will not be supported indefinitely -- use
 * updateTooltipAccessKey() instead.
 *
 * @param mixed doId string or null
 */
function akeytt( doId ) {
	// A lot of user scripts (and some of the code below) break if
	// ta isn't defined, so we make sure it is.  Explictly using
	// window.ta avoids a "ta is not defined" error.
	if (!window.ta) window.ta = new Array;

	// Make a local, possibly restricted, copy to avoid clobbering
	// the original.
	var ta;
	if ( doId ) {
		ta = [doId];
	} else {
		ta = window.ta;
	}

	// Now deal with evil deprecated ta
	var watchCheckboxExists = document.getElementById( 'wpWatchthis' ) ? true : false;
	for (var id in ta) {
		var n = document.getElementById(id);
		if (n) {
			var a = null;
			var ak = '';
			// Are we putting accesskey in it
			if (ta[id][0].length > 0) {
				// Is this object a object? If not assume it's the next child.

				if (n.nodeName.toLowerCase() == "a") {
					a = n;
				} else {
					a = n.childNodes[0];
				}
				// Don't add an accesskey for the watch tab if the watch
				// checkbox is also available.
				if (a && ((id != 'ca-watch' && id != 'ca-unwatch') || !watchCheckboxExists)) {
					a.accessKey = ta[id][0];
					ak = ' ['+tooltipAccessKeyPrefix+ta[id][0]+']';
				}
			} else {
				// We don't care what type the object is when assigning tooltip
				a = n;
				ak = '';
			}

			if (a) {
				a.title = ta[id][1]+ak;
			}
		}
	}
}

function setupRightClickEdit() {
	if (document.getElementsByTagName) {
		var spans = document.getElementsByTagName('span');
		for (var i = 0; i < spans.length; i++) {
			var el = spans[i];
			if(el.className == 'editsection') {
				addRightClickEditHandler(el);
			}
		}
	}
}

function addRightClickEditHandler(el) {
	for (var i = 0; i < el.childNodes.length; i++) {
		var link = el.childNodes[i];
		if (link.nodeType == 1 && link.nodeName.toLowerCase() == 'a') {
			var editHref = link.getAttribute('href');
			// find the enclosing (parent) header
			var prev = el.parentNode;
			if (prev && prev.nodeType == 1 &&
			prev.nodeName.match(/^[Hh][1-6]$/)) {
				prev.oncontextmenu = function(e) {
					if (!e) { e = window.event; }
					// e is now the event in all browsers
					var targ;
					if (e.target) { targ = e.target; }
					else if (e.srcElement) { targ = e.srcElement; }
					if (targ.nodeType == 3) { // defeat Safari bug
						targ = targ.parentNode;
					}
					// targ is now the target element

					// We don't want to deprive the noble reader of a context menu
					// for the section edit link, do we?  (Might want to extend this
					// to all <a>'s?)
					if (targ.nodeName.toLowerCase() != 'a'
					|| targ.parentNode.className != 'editsection') {
						document.location = editHref;
						return false;
					}
					return true;
				};
			}
		}
	}
}

var checkboxes;
var lastCheckbox;

function setupCheckboxShiftClick() {
	checkboxes = [];
	lastCheckbox = null;
	var inputs = document.getElementsByTagName('input');
	addCheckboxClickHandlers(inputs);
}

function addCheckboxClickHandlers(inputs, start) {
	if ( !start) start = 0;

	var finish = start + 250;
	if ( finish > inputs.length )
		finish = inputs.length;

	for ( var i = start; i < finish; i++ ) {
		var cb = inputs[i];
		if ( !cb.type || cb.type.toLowerCase() != 'checkbox' )
			continue;
		var end = checkboxes.length;
		checkboxes[end] = cb;
		cb.index = end;
		//cb.onclick = checkboxClickHandler;
		/**************************************
		Bebeth: Not sure what this function does overall, but it overwrites any onclick handlers that may
		have been assigned already. In order to not overwrite them, this checks to see if one exists. If so,
		it appends this handler to the end. Its done this way (rather than manipulating strings so that
		it will work in IE.
		***************************************/

		if(cb.onclick == undefined || cb.onclick == null)
			cb.onclick = checkboxClickHandler;
		else{
			cb.oldonclick = cb.onclick;
			cb.onclick = function (e){
				this.oldonclick();
				checkboxClickHandler(e);
			}
		}
	}

	if ( finish < inputs.length ) {
		setTimeout( function () {
			addCheckboxClickHandlers(inputs, finish);
		}, 200 );
	}
}

function checkboxClickHandler(e) {
	if (typeof e == 'undefined') {
		e = window.event;
	}
	if ( !e.shiftKey || lastCheckbox === null ) {
		lastCheckbox = this.index;
		return true;
	}
	var endState = this.checked;
	var start, finish;
	if ( this.index < lastCheckbox ) {
		start = this.index + 1;
		finish = lastCheckbox;
	} else {
		start = lastCheckbox;
		finish = this.index - 1;
	}
	for (var i = start; i <= finish; ++i ) {
		checkboxes[i].checked = endState;
	}
	lastCheckbox = this.index;
	return true;
}

function toggle_element_activation(ida,idb) {
	if (!document.getElementById) {
		return;
	}
	document.getElementById(ida).disabled=true;
	document.getElementById(idb).disabled=false;
}

function toggle_element_check(ida,idb) {
	if (!document.getElementById) {
		return;
	}
	document.getElementById(ida).checked=true;
	document.getElementById(idb).checked=false;
}

/**
 * Restore the edit box scroll state following a preview operation,
 * and set up a form submission handler to remember this state
 */
function scrollEditBox() {
	var editBox = document.getElementById( 'wpTextbox1' );
	var scrollTop = document.getElementById( 'wpScrolltop' );
	var editForm = document.getElementById( 'editform' );
	if( editBox && scrollTop ) {
		if( scrollTop.value )
			editBox.scrollTop = scrollTop.value;
		addHandler( editForm, 'submit', function() {
			document.getElementById( 'wpScrolltop' ).value = document.getElementById( 'wpTextbox1' ).scrollTop;
		} );
	}
}
hookEvent( 'load', scrollEditBox );

var allmessages_nodelist = false;
var allmessages_modified = false;
var allmessages_timeout = false;
var allmessages_running = false;

function allmessagesmodified() {
	allmessages_modified = !allmessages_modified;
	allmessagesfilter();
}

function allmessagesfilter() {
	if ( allmessages_timeout )
		window.clearTimeout( allmessages_timeout );

	if ( !allmessages_running )
		allmessages_timeout = window.setTimeout( 'allmessagesfilter_do();', 500 );
}

function allmessagesfilter_do() {
	if ( !allmessages_nodelist )
		return;

	var text = document.getElementById('allmessagesinput').value;
	var nodef = allmessages_modified;

	allmessages_running = true;

	for ( var name in allmessages_nodelist ) {
		var nodes = allmessages_nodelist[name];
		var display = ( name.indexOf( text ) == -1 ? 'none' : '' );

		for ( var i = 0; i < nodes.length; i++)
			nodes[i].style.display =
				( nodes[i].className == "def" && nodef
				  ? 'none' : display );
	}

	if ( text != document.getElementById('allmessagesinput').value ||
	     nodef != allmessages_modified )
		allmessagesfilter_do();  // repeat

	allmessages_running = false;
}

function allmessagesfilter_init() {
	if ( allmessages_nodelist )
		return;

	var nodelist = new Array();
	var templist = new Array();

	var table = document.getElementById('allmessagestable');
	if ( !table ) return;

	var rows = document.getElementsByTagName('tr');
	for ( var i = 0; i < rows.length; i++ ) {
		var id = rows[i].getAttribute('id')
		if ( id && id.substring(0,16) != 'sp-allmessages-r' ) continue;
		templist[ id ] = rows[i];
	}

	var spans = table.getElementsByTagName('span');
	for ( var i = 0; i < spans.length; i++ ) {
		var id = spans[i].getAttribute('id')
		if ( id && id.substring(0,17) != 'sp-allmessages-i-' ) continue;
		if ( !spans[i].firstChild || spans[i].firstChild.nodeType != 3 ) continue;

		var nodes = new Array();
		var row1 = templist[ id.replace('i', 'r1') ];
		var row2 = templist[ id.replace('i', 'r2') ];

		if ( row1 ) nodes[nodes.length] = row1;
		if ( row2 ) nodes[nodes.length] = row2;
		nodelist[ spans[i].firstChild.nodeValue ] = nodes;
	}

	var k = document.getElementById('allmessagesfilter');
	if (k) { k.style.display = ''; }

	allmessages_nodelist = nodelist;
}

hookEvent( "load", allmessagesfilter_init );

/*
	Written by Jonathan Snook, http://www.snook.ca/jonathan
	Add-ons by Robert Nyman, http://www.robertnyman.com
	Author says "The credit comment is all it takes, no license. Go crazy with it!:-)"
	From http://www.robertnyman.com/2005/11/07/the-ultimate-getelementsbyclassname/
*/
function getElementsByClassName(oElm, strTagName, oClassNames){
	var arrElements = (strTagName == "*" && oElm.all)? oElm.all : oElm.getElementsByTagName(strTagName);
	var arrReturnElements = new Array();
	var arrRegExpClassNames = new Array();
	if(typeof oClassNames == "object"){
		for(var i=0; i<oClassNames.length; i++){
			arrRegExpClassNames[arrRegExpClassNames.length] =
				new RegExp("(^|\\s)" + oClassNames[i].replace(/\-/g, "\\-") + "(\\s|$)");
		}
	}
	else{
		arrRegExpClassNames[arrRegExpClassNames.length] =
			new RegExp("(^|\\s)" + oClassNames.replace(/\-/g, "\\-") + "(\\s|$)");
	}
	var oElement;
	var bMatchesAll;
	for(var j=0; j<arrElements.length; j++){
		oElement = arrElements[j];
		bMatchesAll = true;
		for(var k=0; k<arrRegExpClassNames.length; k++){
			if(!arrRegExpClassNames[k].test(oElement.className)){
				bMatchesAll = false;
				break;
			}
		}
		if(bMatchesAll){
			arrReturnElements[arrReturnElements.length] = oElement;
		}
	}
	return (arrReturnElements)
}

function redirectToFragment(fragment) {
	var match = navigator.userAgent.match(/AppleWebKit\/(\d+)/);
	if (match) {
		var webKitVersion = parseInt(match[1]);
		if (webKitVersion < 420) {
			// Released Safari w/ WebKit 418.9.1 messes up horribly
			// Nightlies of 420+ are ok
			return;
		}
	}
	if (is_gecko) {
		// Mozilla needs to wait until after load, otherwise the window doesn't scroll
		addOnloadHook(function () {
			if (window.location.hash == "")
				window.location.hash = fragment;
		});
	} else {
		if (window.location.hash == "")
			window.location.hash = fragment;
	}
}

/*
 * Table sorting script  by Joost de Valk, check it out at http://www.joostdevalk.nl/code/sortable-table/.
 * Based on a script from http://www.kryogenix.org/code/browser/sorttable/.
 * Distributed under the MIT license: http://www.kryogenix.org/code/browser/licence.html .
 *
 * Copyright (c) 1997-2006 Stuart Langridge, Joost de Valk.
 *
 * @todo don't break on colspans/rowspans (bug 8028)
 * @todo language-specific digit grouping/decimals (bug 8063)
 * @todo support all accepted date formats (bug 8226)
 */

var ts_image_path = stylepath+"/common/images/";
var ts_image_up = "sort_up.gif";
var ts_image_down = "sort_down.gif";
var ts_image_none = "sort_none.gif";
var ts_europeandate = wgContentLanguage != "en"; // The non-American-inclined can change to "true"
var ts_alternate_row_colors = true;
var SORT_COLUMN_INDEX;

function sortables_init() {
	var idnum = 0;
	// Find all tables with class sortable and make them sortable
	var tables = getElementsByClassName(document, "table", "sortable");
	for (var ti = 0; ti < tables.length ; ti++) {
		if (!tables[ti].id) {
			tables[ti].setAttribute('id','sortable_table_id_'+idnum);
			++idnum;
		}
		ts_makeSortable(tables[ti]);
	}
}

function ts_makeSortable(table) {
	var firstRow;
	if (table.rows && table.rows.length > 0) {
		if (table.tHead && table.tHead.rows.length > 0) {
			firstRow = table.tHead.rows[table.tHead.rows.length-1];
		} else {
			firstRow = table.rows[0];
		}
	}
	if (!firstRow) return;

	// We have a first row: assume it's the header, and make its contents clickable links
	for (var i = 0; i < firstRow.cells.length; i++) {
		var cell = firstRow.cells[i];
		if ((" "+cell.className+" ").indexOf(" unsortable ") == -1) {
			cell.innerHTML += '&nbsp;&nbsp;<a href="#" class="sortheader" onclick="ts_resortTable(this);return false;"><span class="sortarrow"><img src="'+ ts_image_path + ts_image_none + '" alt="&darr;"/></span></a>';
		}
	}
	if (ts_alternate_row_colors) {
		ts_alternate(table);
	}
}

function ts_getInnerText(el) {
	if (typeof el == "string") return el;
	if (typeof el == "undefined") { return el };
	if (el.textContent) return el.textContent; // not needed but it is faster
	if (el.innerText) return el.innerText;     // IE doesn't have textContent
	var str = "";

	var cs = el.childNodes;
	var l = cs.length;
	for (var i = 0; i < l; i++) {
		switch (cs[i].nodeType) {
			case 1: //ELEMENT_NODE
				str += ts_getInnerText(cs[i]);
				break;
			case 3: //TEXT_NODE
				str += cs[i].nodeValue;
				break;
		}
	}
	return str;
}

function ts_resortTable(lnk) {
	// get the span
	var span = lnk.getElementsByTagName('span')[0];

	var td = lnk.parentNode;
	var tr = td.parentNode;
	var column = td.cellIndex;

	var table = tr.parentNode;
	while (table && !(table.tagName && table.tagName.toLowerCase() == 'table'))
		table = table.parentNode;
	if (!table) return;

	// Work out a type for the column
	if (table.rows.length <= 1) return;

	// Skip the first row if that's where the headings are
	var rowStart = (table.tHead && table.tHead.rows.length > 0 ? 0 : 1);

	var itm = "";
	for (var i = rowStart; i < table.rows.length; i++) {
		if (table.rows[i].cells.length > column) {
			itm = ts_getInnerText(table.rows[i].cells[column]);
			itm = itm.replace(/^[\s\xa0]+/, "").replace(/[\s\xa0]+$/, "");
			if (itm != "") break;
		}
	}

	sortfn = ts_sort_caseinsensitive;
	if (itm.match(/^\d\d[\/. -][a-zA-Z]{3}[\/. -]\d\d\d\d$/))
		sortfn = ts_sort_date;
	if (itm.match(/^\d\d[\/.-]\d\d[\/.-]\d\d\d\d$/))
		sortfn = ts_sort_date;
	if (itm.match(/^\d\d[\/.-]\d\d[\/.-]\d\d$/))
		sortfn = ts_sort_date;
	if (itm.match(/^[\u00a3$\u20ac]/)) // pound dollar euro
		sortfn = ts_sort_currency;
	if (itm.match(/^[\d.,]+\%?$/))
		sortfn = ts_sort_numeric;

	var reverse = (span.getAttribute("sortdir") == 'down');

	var newRows = new Array();
	for (var j = rowStart; j < table.rows.length; j++) {
		var row = table.rows[j];
		var keyText = ts_getInnerText(row.cells[column]);
		var oldIndex = (reverse ? -j : j);

		newRows[newRows.length] = new Array(row, keyText, oldIndex);
	}

	newRows.sort(sortfn);

	var arrowHTML;
	if (reverse) {
		arrowHTML = '<img src="'+ ts_image_path + ts_image_down + '" alt="&darr;"/>';
		newRows.reverse();
		span.setAttribute('sortdir','up');
	} else {
		arrowHTML = '<img src="'+ ts_image_path + ts_image_up + '" alt="&uarr;"/>';
		span.setAttribute('sortdir','down');
	}

	// We appendChild rows that already exist to the tbody, so it moves them rather than creating new ones
	// don't do sortbottom rows
	for (var i = 0; i < newRows.length; i++) {
		if ((" "+newRows[i][0].className+" ").indexOf(" sortbottom ") == -1)
			table.tBodies[0].appendChild(newRows[i][0]);
	}
	// do sortbottom rows only
	for (var i = 0; i < newRows.length; i++) {
		if ((" "+newRows[i][0].className+" ").indexOf(" sortbottom ") != -1)
			table.tBodies[0].appendChild(newRows[i][0]);
	}

	// Delete any other arrows there may be showing
	var spans = getElementsByClassName(tr, "span", "sortarrow");
	for (var i = 0; i < spans.length; i++) {
		spans[i].innerHTML = '<img src="'+ ts_image_path + ts_image_none + '" alt="&darr;"/>';
	}
	span.innerHTML = arrowHTML;

	ts_alternate(table);
}

function ts_dateToSortKey(date) {
	// y2k notes: two digit years less than 50 are treated as 20XX, greater than 50 are treated as 19XX
	if (date.length == 11) {
		switch (date.substr(3,3).toLowerCase()) {
			case "jan": var month = "01"; break;
			case "feb": var month = "02"; break;
			case "mar": var month = "03"; break;
			case "apr": var month = "04"; break;
			case "may": var month = "05"; break;
			case "jun": var month = "06"; break;
			case "jul": var month = "07"; break;
			case "aug": var month = "08"; break;
			case "sep": var month = "09"; break;
			case "oct": var month = "10"; break;
			case "nov": var month = "11"; break;
			case "dec": var month = "12"; break;
			// default: var month = "00";
		}
		return date.substr(7,4)+month+date.substr(0,2);
	} else if (date.length == 10) {
		if (ts_europeandate == false) {
			return date.substr(6,4)+date.substr(0,2)+date.substr(3,2);
		} else {
			return date.substr(6,4)+date.substr(3,2)+date.substr(0,2);
		}
	} else if (date.length == 8) {
		yr = date.substr(6,2);
		if (parseInt(yr) < 50) {
			yr = '20'+yr;
		} else {
			yr = '19'+yr;
		}
		if (ts_europeandate == true) {
			return yr+date.substr(3,2)+date.substr(0,2);
		} else {
			return yr+date.substr(0,2)+date.substr(3,2);
		}
	}
	return "00000000";
}

function ts_parseFloat(num) {
	if (!num) return 0;
	num = parseFloat(num.replace(/,/g, ""));
	return (isNaN(num) ? 0 : num);
}

function ts_sort_date(a,b) {
	var aa = ts_dateToSortKey(a[1]);
	var bb = ts_dateToSortKey(b[1]);
	return (aa < bb ? -1 : aa > bb ? 1 : a[2] - b[2]);
}

function ts_sort_currency(a,b) {
	var aa = ts_parseFloat(a[1].replace(/[^0-9.]/g,''));
	var bb = ts_parseFloat(b[1].replace(/[^0-9.]/g,''));
	return (aa != bb ? aa - bb : a[2] - b[2]);
}

function ts_sort_numeric(a,b) {
	var aa = ts_parseFloat(a[1]);
	var bb = ts_parseFloat(b[1]);
	return (aa != bb ? aa - bb : a[2] - b[2]);
}

function ts_sort_caseinsensitive(a,b) {
	var aa = a[1].toLowerCase();
	var bb = b[1].toLowerCase();
	return (aa < bb ? -1 : aa > bb ? 1 : a[2] - b[2]);
}

function ts_sort_default(a,b) {
	return (a[1] < b[1] ? -1 : a[1] > b[1] ? 1 : a[2] - b[2]);
}

function ts_alternate(table) {
	// Take object table and get all it's tbodies.
	var tableBodies = table.getElementsByTagName("tbody");
	// Loop through these tbodies
	for (var i = 0; i < tableBodies.length; i++) {
		// Take the tbody, and get all it's rows
		var tableRows = tableBodies[i].getElementsByTagName("tr");
		// Loop through these rows
		// Start at 1 because we want to leave the heading row untouched
		for (var j = 0; j < tableRows.length; j++) {
			// Check if j is even, and apply classes for both possible results
			var oldClasses = tableRows[j].className.split(" ");
			var newClassName = "";
			for (var k = 0; k < oldClasses.length; k++) {
				if (oldClasses[k] != "" && oldClasses[k] != "even" && oldClasses[k] != "odd")
					newClassName += oldClasses[k] + " ";
			}
			tableRows[j].className = newClassName + (j % 2 == 0 ? "even" : "odd");
		}
	}
}

/*
 * End of table sorting code
 */


/**
 * Add a cute little box at the top of the screen to inform the user of
 * something, replacing any preexisting message.
 *
 * @param String message HTML to be put inside the right div
 * @param String className   Used in adding a class; should be different for each
 *   call to allow CSS/JS to hide different boxes.  null = no class used.
 * @return Boolean       True on success, false on failure
 */
function jsMsg( message, className ) {
	if ( !document.getElementById ) {
		return false;
	}
	// We special-case skin structures provided by the software.  Skins that
	// choose to abandon or significantly modify our formatting can just define
	// an mw-js-message div to start with.
	var messageDiv = document.getElementById( 'mw-js-message' );
	if ( !messageDiv ) {
		messageDiv = document.createElement( 'div' );
		if ( document.getElementById( 'column-content' )
		&& document.getElementById( 'content' ) ) {
			// MonoBook, presumably
			document.getElementById( 'content' ).insertBefore(
				messageDiv,
				document.getElementById( 'content' ).firstChild
			);
		} else if ( document.getElementById('content')
		&& document.getElementById( 'article' ) ) {
			// Non-Monobook but still recognizable (old-style)
			document.getElementById( 'article').insertBefore(
				messageDiv,
				document.getElementById( 'article' ).firstChild
			);
		} else {
			return false;
		}
	}

	messageDiv.setAttribute( 'id', 'mw-js-message' );
	if( className ) {
		messageDiv.setAttribute( 'class', 'mw-js-message-'+className );
	}
	messageDiv.innerHTML = message;
	return true;
}

/**
 * Inject a cute little progress spinner after the specified element
 *
 * @param element Element to inject after
 * @param id Identifier string (for use with removeSpinner(), below)
 */
function injectSpinner( element, id ) {
	var spinner = document.createElement( "img" );
	spinner.id = "mw-spinner-" + id;
	spinner.src = stylepath + "/common/images/spinner.gif";
	spinner.alt = spinner.title = "...";
	if( element.nextSibling ) {
		element.parentNode.insertBefore( spinner, element.nextSibling );
	} else {
		element.parentNode.appendChild( spinner );
	}
}

/**
 * Remove a progress spinner added with injectSpinner()
 *
 * @param id Identifier string
 */
function removeSpinner( id ) {
	var spinner = document.getElementById( "mw-spinner-" + id );
	if( spinner ) {
		spinner.parentNode.removeChild( spinner );
	}
}

function runOnloadHook() {
	// don't run anything below this for non-dom browsers
	if (doneOnloadHook || !(document.getElementById && document.getElementsByTagName)) {
		return;
	}

	// set this before running any hooks, since any errors below
	// might cause the function to terminate prematurely
	doneOnloadHook = true;

	histrowinit();
	unhidetzbutton();
	tabbedprefs();
	updateTooltipAccessKeys( null );
	akeytt( null );
	scrollEditBox();
	setupCheckboxShiftClick();
	sortables_init();

	// Run any added-on functions
	for (var i = 0; i < onloadFuncts.length; i++) {
		onloadFuncts[i]();
	}
}

/**
 * Add an event handler to an element
 *
 * @param Element element Element to add handler to
 * @param String attach Event to attach to
 * @param callable handler Event handler callback
 */
function addHandler( element, attach, handler ) {
	if (window.addEventListener) {
		element.addEventListener( attach, handler, false );
	} else if (window.attachEvent) {
		element.attachEvent( 'on' + attach, handler );
	}
}

/**
 * Add a click event handler to an element
 *
 * @param Element element Element to add handler to
 * @param callable handler Event handler callback
 */
function addClickHandler( element, handler ) {
	addHandler( element, 'click', handler );
}
//note: all skins should call runOnloadHook() at the end of html output,
//      so the below should be redundant. It's there just in case.
hookEvent("load", runOnloadHook);
hookEvent("load", mwSetupToolbar);

// courtesy of http://www.gamedev.net/community/forums/topic.asp?topic_id=281951
function copyFF(e) {
	window.netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');
	var textHtml = e.value;
	var htmlstring = Components.classes["@mozilla.org/supports-string;1"].
	createInstance(Components.interfaces.nsISupportsString);
	if (!htmlstring) return false; // couldn't get string obj
	htmlstring.data = textHtml;

	var trans = Components.classes["@mozilla.org/widget/transferable;1"].
	createInstance(Components.interfaces.nsITransferable);
	if (!trans) return false; //no transferable widget found

	trans.addDataFlavor("text/html");
	trans.setTransferData("text/html", htmlstring, textHtml.length * 2); // *2 because it's unicode

	var clipboard = Components.classes["@mozilla.org/widget/clipboard;1"].
	getService(Components.interfaces.nsIClipboard);
	if (!clipboard) return false; // couldn't get the clipboard

	clipboard.setData(trans, null, Components.interfaces.nsIClipboard.kGlobalClipboard);
	return true;
}

// courtesy of
// http://www.jeffothy.com/weblog/clipboard-copy/
function copy(inElement) {
	if (window.netscape) {
		copyFF(inElement);
	} else if (inElement.createTextRange) {
		var range = inElement.createTextRange();
		if (range && BodyLoaded==1)
			range.execCommand('Copy');
	} else {
		var flashcopier = 'flashcopier';
		if(!document.getElementById(flashcopier)) {
			var divholder = document.createElement('div');
			divholder.id = flashcopier;
			document.body.appendChild(divholder);
		}
		document.getElementById(flashcopier).innerHTML = '';
		var divinfo = '<embed src="/skins/common/_clipboard.swf" FlashVars="clipboard='+encodeURIComponent(inElement.value)+'" width="0" height="0" type="application/x-shockwave-flash"></embed>';
		document.getElementById(flashcopier).innerHTML = divinfo;
	}
	alert('copied.');
}

var cp_request;
var cp_request2;

function cp_finish() {
	gatTrack("Author_engagement","Click_done","Publishing_popup");

	if (document.getElementById('email_friend_cb') && document.getElementById('email_friend_cb').checked == true) {
		gatTrack("Author_engagement","Author_mail_friends","Publishing_popup");

		try {
			cp_request = new XMLHttpRequest();
		} catch (error) {
			try {
				cp_request = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (error) {
				return false;
			}
		}
		var params =  "friends=" + encodeURIComponent(document.getElementById('email_friends').value) + "&target=" + window.location.pathname.substring(1);
		var url = "http://" + window.location.hostname + "/Special:CreatepageEmailFriend";
		cp_request.open('POST', url);
		cp_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		cp_request.send(params);
	}

	if (document.getElementById('email_notification') && document.getElementById('email_notification').checked == true) {

		gatTrack("Author_engagement","Email_updates","Publishing_popup");
		try {
			cp_request2 = new XMLHttpRequest();
		} catch (error) {
			try {
				cp_request2 = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (error) {
				return false;
			}
		}

		var params = "";
		if (document.getElementById('email_address_flag').value == '1') {
			params =  "action=addNotification&target=" + window.location.pathname.substring(1);
		} else {
			params =  "action=addNotification&email=" + encodeURIComponent(document.getElementById('email_me').value) + "&target=" + window.location.pathname.substring(1);
		}


		var url = "http://" + window.location.hostname + "/Special:AuthorEmailNotification";
		cp_request2.open('POST', url);
		cp_request2.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		cp_request2.send(params);
	}

	if (document.getElementById('dont_show_again') && document.getElementById('dont_show_again').checked == true) {

		gatTrack("Author_engagement","Reject_pub_pop","Reject_pub_pop");
		try {
			cp_request2 = new XMLHttpRequest();
		} catch (error) {
			try {
				cp_request2 = new ActiveXObject('Microsoft.XMLHTTP');
			} catch (error) {
				return false;
			}
        }

		var params =  "action=updatePreferences&dontshow=1";

		var url = "http://" + window.location.hostname + "/Special:AuthorEmailNotification";
		cp_request2.open('POST', url);
		cp_request2.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		cp_request2.send(params);
	}

	jQuery("#dialog-box").dialog("close");
	//closeModal();
}

function ShareTab(obj,bTab) {
	var ShOptions = document.getElementById("ShareOptions");

	if (ShOptions.style.display !== "block") {
		//set position if on the tab
		if (bTab) {
			var coords = findPos(obj);
			ShOptions.style.left = (coords[0] - 19) + "px";
			ShOptions.style.top = (coords[1]) + "px";
		}
		//show it
		ShOptions.style.display = "block";
	}
	else {
		//hide it
		ShOptions.style.display = "none";
	}
}

var share_requester;
function handle_shareResponse() {

}

function clickshare(selection) {
	share_requester = null;
	try {
		share_requester = new XMLHttpRequest();
	} catch (error) {
		try {
			share_requester = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (error) {
			 return false;
		}
	}
	share_requester.onreadystatechange =  handle_shareResponse;
	url = 'http://' + window.location.hostname + '/Special:CheckJS?selection=' + selection;
	share_requester.open('GET', url);
	share_requester.send(' ');
}

function shareTwitter(source) {
	//var title = encodeURIComponent(wgTitle);
	var title = wgTitle;
	var url = encodeURIComponent(location.href);

	if (title.search(/How to/) != 0) {
		title = 'How to '+title;
	}

	if (source == 'aen') {
		status = "I just wrote an article on @wikiHow - "+title+".";
	} else if (source == 'africa') {
		status = "wikiHow.com is sending a book to Africa when you write a new how-to article. Help out here: http://bit.ly/9qWKe";
		title = "";
		url = "";
	} else {
		status = "Reading @wikiHow on "+title+".";
	}

	window.open('https://twitter.com/home?status='+ status +' '+url );

	return false;
}

function button_click(obj) {
	if ((navigator.appName == "Microsoft Internet Explorer") && (navigator.appVersion < 7)) {
		return false;
	}
	jobj = jQuery(obj);

	background = jobj.css('background-position');
	if(background == undefined || background == null)
		background_x_position = jobj.css('background-position-x');
	else
		background_x_position = background.split(" ")[0];

	//article tabs
	if (obj.id.indexOf("tab_") >= 0) {
		obj.style.color = "#514239";
		obj.style.backgroundPosition = background_x_position + " -111px";
	}

	if (obj.id == "play_pause_button") {
		if (jobj.attr('class').indexOf("play") >= 0) {
			obj.style.backgroundPosition = "0 -130px";
		}
		else {
			obj.style.backgroundPosition = "0 -52px";
		}
	}

	if (jobj.attr('class').indexOf("search_button") >= 0) {
		obj.style.backgroundPosition = "0 -29px";
	}
}

function button_swap(obj) {
	if ((navigator.appName == "Microsoft Internet Explorer") && (navigator.appVersion < 7)) {
		return false;
	}

	if (obj.id == "AdminOptions") {obj = document.getElementById("tab_admin");}
	jobj = jQuery(obj);


	background = jobj.css('background-position');
	if (background == undefined || background == null)
		background_x_position = jobj.css('background-position-x');
	else
		background_x_position = background.split(" ")[0];

	//upper navigation tabs
	if (obj.id.indexOf("nav_") >= 0) {
		obj.style.color = "#FFFFFF";
		(obj.id == "nav_home") ? obj.style.backgroundPosition = "-102px -73px" : obj.style.backgroundPosition = "0 -73px";
	} else if (jobj.attr('class').indexOf("disabled") >= 0) {
		return false;
	} else if(jobj.attr('class').indexOf("search_button_loggedout") >= 0){
		obj.style.backgroundPosition = background_x_position + " -23px";
	} else if (jobj.attr('class').indexOf("button136") >= 0) {
		obj.style.backgroundPosition = background_x_position + " -38px";
	} else if (jobj.attr('class').indexOf("button190") >= 0) {
		obj.style.backgroundPosition = background_x_position + " -42px";
	} else if (obj.id == "tab_admin") { //article admin tab
		obj.style.backgroundPosition = background_x_position + " -111px";
	} else if (jobj.attr('class').indexOf("search_button") >= 0) {
		obj.style.backgroundPosition = background_x_position + " -29px";
	} else if (jobj.attr('class').indexOf("expand_button") >= 0) {
		obj.style.backgroundPosition = "-20px 0";
	} else if (jobj.attr('class').indexOf("contract_button") >= 0) {
		obj.style.backgroundPosition = "-20px -21px";
	} else if (obj.id == "play_pause_button" && jobj.attr('class').indexOf('play') >= 0) {
		obj.style.backgroundPosition = background_x_position + " -104px";
	} else {
		obj.style.backgroundPosition = background_x_position + " -26px";
	}

}

function button_unswap(obj) {
	if ((navigator.appName == "Microsoft Internet Explorer") && (navigator.appVersion < 7)) {
		return false;
	}

	if (obj.id == "AdminOptions") { obj = document.getElementById("tab_admin"); }
	jobj = jQuery(obj);

	background = jobj.css('background-position');
	if(background == undefined || background == null)
		background_x_position = jobj.css('background-position-x');
	else
		background_x_position = background.split(" ")[0];

	if (obj.id == "arrow_right") {
		obj.style.backgroundPosition = "-26px 0";
	} else if(jobj.attr('class').indexOf("disabled") >= 0){
		return false;
	} else if (jobj.attr('class').indexOf("contract_button") >= 0) {
		obj.style.backgroundPosition = background_x_position + " -21px";
	} else if (obj.id == "play_pause_button" && jobj.attr('class').indexOf('play') >= 0) {
		obj.style.backgroundPosition = background_x_position + " -78px";
	} else if (obj.id == "tab_admin") { //article admin tab
		obj.style.backgroundPosition = background_x_position + " -80px";
	} else {
		obj.style.backgroundPosition = background_x_position + " 0";
	}

	//upper navigation tabs
	if (obj.id.indexOf("nav_") >= 0) {
		obj.style.color = "#514239";
	}

	if (jobj.attr('class').indexOf("white_button") >= 0) {
		obj.style.color = "#018EAB";
	}

}

//expand/collapse the nav menu
function sidenav_toggle(list_id) {
	//alert("!" . list_id . "!");
	var list = jQuery("#" + list_id );
	//var list = jQuery("#visit_list");
//alert(list.html());
	if(list.css('display') == "none"){
		list.css('display', "block");
		jQuery('#href_' + list_id).html("- collapse");
		setCookie("expand_" + list_id, 1, 365);
	}
	else{
		list.css('display', "none");
		jQuery('#href_' + list_id).html("+ expand");
		setCookie("expand_" + list_id, 0, 365);
	}
	return false;

	/*if (list.style.display == "none") {
		list.style.display = "block";
		document.getElementById("href_" + list_id).innerHTML = "- collapse";
		setCookie("expand_" + list_id, 1, 365);
	}
	else {
		list.style.display = "none";
		document.getElementById("href_" + list_id).innerHTML = "+ expand";
		setCookie("expand_" + list_id, 0, 365);
	}
	return false;*/
}

//do a scrolling reveal
function findPos(obj) {
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		curleft = obj.offsetLeft
		curtop = obj.offsetTop
		while (obj = obj.offsetParent) {
			curleft += obj.offsetLeft
			curtop += obj.offsetTop
		}
	}
	return [curleft,curtop];
}

function AdminTab(obj,bTab) {
	var AdmOptions = document.getElementById("AdminOptions");

	if (AdmOptions.style.display !== "block") {
		//set position if on the tab
		if (bTab) {
			//var coords = findPos(obj); //took out this call b/c of css changes
			AdmOptions.style.left = (obj.offsetLeft) + "px";
			AdmOptions.style.top = (obj.offsetTop) + "px";
		}
		//show it
		AdmOptions.style.display = "block";
	}
	else {
		//hide it
		AdmOptions.style.display = "none";
	}
}

function AdminCheck(obj,bOn) {
	if (bOn) {
		obj.style.background = "url(/skins/WikiHow/images/admin_check.gif) #F2ECDE no-repeat 65px 9px";
	}
	else {
		obj.style.background = "#F2ECDE";
	}
}

function setCookie(name, value) {
	var exdate=new Date();
	var expiredays = 7;
	exdate.setDate(exdate.getDate()+expiredays);
	document.cookie=name+ "=" +escape(value)+ ((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
}

function getCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function loadToggles() {
	lists =Array("navigation_list", "editing_list", "my_pages_list", "visit_list", "my_links_list");
	for (var i = 0; i < lists.length; i++) {
		var l = lists[i];
		switch(l) {
			// this one is shown by default, hide it if desired
			case 'navigation_list':
				if (getCookie("expand_" + l) == "0")  {
					sidenav_toggle(l);
				}
				break;
			default:
				if (getCookie("expand_" + l) == 1) {
					sidenav_toggle(l);
				}
				break;
		}
	}
}

window.onload = loadToggles;

//do a scrolling reveal
function scroll_open(id,height,max_height) {
	document.getElementById(id).style.top = height + "px";
	document.getElementById(id).style.display = "block";
	document.getElementById(id).style.position = "relative";
	height += 1;
	if (height < max_height) {
		window.setTimeout("scroll_open('" + id + "'," + height + "," + max_height + ")",15);
	}
}

function share_article(who) {

	switch (who) {

		case 'email':
			clickshare(1);
			window.location='http://' + window.location.hostname + '/Special:EmailLink/' + window.location.pathname;
			break;
		case 'facebook':
			clickshare(4);
			var d=document,f='http://www.facebook.com/share',
				l=d.location,e=encodeURIComponent,p='.php?src=bm&v=4&i=1178291210&u='+e(l.href)+'&t='+e(d.title);1; try{ if(!/^(.*\.)?facebook\.[^.]*$/.test(l.host))throw(0);share_internal_bookmarklet(p)}catch(z){a=function(){if(!window.open(f+'r'+p,'sharer','toolbar=0,status=0,resizable=0,width=626,height=436'))l.href=f+p};if(/Firefox/.test(navigator.userAgent))setTimeout(a,0);else{a()}}void(0);
			break;
		case 'twitter':
			clickshare(8);
			shareTwitter();
			break;
		case 'delicious':
			clickshare(2);
			window.open('http://del.icio.us/post?v=4&partner=whw&noui&jump=close&url='+encodeURIComponent(location.href)+'&title='+encodeURIComponent(document.title),'delicious','toolbar=no,width=700,height=400');
			void(0);
			break;
		case 'stumbleupon':
			clickshare(9);
			window.open('http://www.stumbleupon.com/submit?url='+encodeURIComponent(location.href)); void(0);
			break;
		case 'digg':
			javascript:clickshare(3);
			window.open(' http://digg.com/submit?phase=2&url=' + encodeURIComponent(location.href) + '&title=' + encodeURIComponent(document.title) + '&bodytext=&topic=');
			break;
		case 'blogger':
			javascript:clickshare(7);
			window.open('http://www.blogger.com/blog-this.g?&u=' +encodeURIComponent(location.href)+ '&n=' +encodeURIComponent(document.title), 'blogger', 'toolbar=no,width=700,height=400');
			void(0);
			break;
		case 'google':
			javascript:clickshare(5);
			(function(){var a=window,b=document,c=encodeURIComponent,d=a.open("http://www.google.com/bookmarks/mark?op=edit&output=popup&bkmk="+c(b.location)+"&title="+c(b.title),"bkmk_popup","left="+((a.screenX||a.screenLeft)+10)+",top="+((a.screenY||a.screenTop)+10)+",height=420px,width=550px,resizable=1,alwaysRaised=1");a.setTimeout(function(){d.focus()},300)})();
			break;
	}
}

// remote scripting library
// (c) copyright 2005 modernmethod, inc
var sajax_debug_mode = false;
var sajax_request_type = "GET";

/**
 * if sajax_debug_mode is true, this function outputs given the message into
 * the element with id = sajax_debug; if no such element exists in the document,
 * it is injected.
 */
function sajax_debug(text) {
	if (!sajax_debug_mode) return false;

	var e= document.getElementById('sajax_debug');

	if (!e) {
		e= document.createElement("p");
		e.className= 'sajax_debug';
		e.id= 'sajax_debug';

		var b= document.getElementsByTagName("body")[0];

		if (b.firstChild) b.insertBefore(e, b.firstChild);
		else b.appendChild(e);
	}

	var m= document.createElement("div");
	m.appendChild( document.createTextNode( text ) );

	e.appendChild( m );

	return true;
}

/**
* compatibility wrapper for creating a new XMLHttpRequest object.
*/
function sajax_init_object() {
	sajax_debug("sajax_init_object() called..")
	var A;
	try {
		// Try the new style before ActiveX so we don't
		// unnecessarily trigger warnings in IE 7 when
		// set to prompt about ActiveX usage
		A = new XMLHttpRequest();
	} catch (e) {
		try {
			A=new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				A=new ActiveXObject("Microsoft.XMLHTTP");
			} catch (oc) {
				A=null;
			}
		}
	}
	if (!A)
		sajax_debug("Could not create connection object.");

	return A;
}

/**
* Perform an ajax call to mediawiki. Calls are handeled by AjaxDispatcher.php
*   func_name - the name of the function to call. Must be registered in $wgAjaxExportList
*   args - an array of arguments to that function
*   target - the target that will handle the result of the call. If this is a function,
*            if will be called with the XMLHttpRequest as a parameter; if it's an input
*            element, its value will be set to the resultText; if it's another type of
*            element, its innerHTML will be set to the resultText.
*
* Example:
*    sajax_do_call('doFoo', [1, 2, 3], document.getElementById("showFoo"));
*
* This will call the doFoo function via MediaWiki's AjaxDispatcher, with
* (1, 2, 3) as the parameter list, and will show the result in the element
* with id = showFoo
*/
function sajax_do_call(func_name, args, target) {
	var i, x, n;
	var uri;
	var post_data;
	uri = wgServer +
		((wgScript == null) ? (wgScriptPath + "/index.php") : wgScript) +
		"?action=ajax";
	if (sajax_request_type == "GET") {
		if (uri.indexOf("?") == -1)
			uri = uri + "?rs=" + encodeURIComponent(func_name);
		else
			uri = uri + "&rs=" + encodeURIComponent(func_name);
		for (i = 0; i < args.length; i++)
			uri = uri + "&rsargs[]=" + encodeURIComponent(args[i]);
		//uri = uri + "&rsrnd=" + new Date().getTime();
		post_data = null;
	} else {
		post_data = "rs=" + encodeURIComponent(func_name);
		for (i = 0; i < args.length; i++)
			post_data = post_data + "&rsargs[]=" + encodeURIComponent(args[i]);
	}
	x = sajax_init_object();
	if (!x) {
		alert("AJAX not supported");
		return false;
	}

	try {
		x.open(sajax_request_type, uri, true);
	} catch (e) {
		if (window.location.hostname == "localhost") {
			alert("Your browser blocks XMLHttpRequest to 'localhost', try using a real hostname for development/testing.");
		}
		throw e;
	}
	if (sajax_request_type == "POST") {
		x.setRequestHeader("Method", "POST " + uri + " HTTP/1.1");
		x.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	}
	x.setRequestHeader("Pragma", "cache=yes");
	x.setRequestHeader("Cache-Control", "no-transform");
	x.onreadystatechange = function() {
		if (x.readyState != 4)
			return;

		sajax_debug("received (" + x.status + " " + x.statusText + ") " + x.responseText);

		//if (x.status != 200)
		//	alert("Error: " + x.status + " " + x.statusText + ": " + x.responseText);
		//else

		if ( typeof( target ) == 'function' ) {
			target( x );
		}
		else if ( typeof( target ) == 'object' ) {
			if ( target.tagName == 'INPUT' ) {
				if (x.status == 200) target.value= x.responseText;
				//else alert("Error: " + x.status + " " + x.statusText + " (" + x.responseText + ")");
			}
			else {
				if (x.status == 200) target.innerHTML = x.responseText;
				else target.innerHTML= "<div class='error'>Error: " + x.status + " " + x.statusText + " (" + x.responseText + ")</div>";
			}
		}
		else {
			alert("bad target for sajax_do_call: not a function or object: " + target);
		}

		return;
	}

	sajax_debug(func_name + " uri = " + uri + " / post = " + post_data);
	x.send(post_data);
	sajax_debug(func_name + " waiting..");
	delete x;

	return true;
}

var mainPageFAToggleFlag = false;
function mainPageFAToggle() {
	var firstChild = jQuery('#toggle');
	if (mainPageFAToggleFlag == false) {
		jQuery('#hiddenFA').slideDown('slow');
		firstChild.html('See Fewer Featured Articles');
		jQuery('#moreOrLess').attr('src', '/skins/WikiHow/images/arrowLess.png');
		mainPageFAToggleFlag = true;
	} else {
		jQuery('#hiddenFA').slideUp('slow');
		firstChild.html('See More Featured Articles');
		jQuery('#moreOrLess').attr('src', '/skins/WikiHow/images/arrowMore.png');
		mainPageFAToggleFlag = false;
	}
}

function setStyle(obj, style) {
	if (obj) {
		if (navigator.userAgent.indexOf('MSIE') > 0) {
			obj.style.setAttribute('csstext', style, 0);
		} else {
			obj.setAttribute('style', style);
		}
	}
}

/**
 * Translates a MW message (ie, 'new-link') into the correct language text.  Eg:
 * wfMsg('new-link', 'http://mylink.com/');
 *
 * - loads all messages from WH.lang
 * - added by Reuben
 */
function wfMsg(key) {
	if (typeof WH.lang[key] === 'undefined') {
		return '[' + key + ']';
	} else {
		var msg = WH.lang[key];
		if (arguments.length > 1) {
			// matches symbols like $1, $2, etc
			var syntax = /(^|.|\r|\n)(\$([1-9]))/g;
			var replArgs = arguments;
			msg = msg.replace(syntax, function(match, p1, p2, p3) {
				return p1 + replArgs[p3];
			});
			// This was the old prototype.js Template syntax
			//var template = new Template(msg, syntax);
			//var args = $A(arguments); // this has { 1: '$1', ... }
			//msg = template.evaluate(args);
		}
		return msg;
	}
}

/**
 * Templates html etc.  Use as follows:
 *
 * var html = wfTemplate('<a href="$1">$2</a>', mylink, mytext);
 */
function wfTemplate(tmpl) {
	var syntax = /(^|.|\r|\n)(\$([1-9]))/g; // matches symbols like $1, $2, etc
	var replArgs = arguments;
	var out = tmpl.replace(syntax, function(match, p1, p2, p3) {
		return p1 + replArgs[p3];
	});
	return out;
}

function checkIphone() {

	if ( navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/iPod/i) ) {

		var iphonediv = document.getElementById('iphone_notice');
		var iphone_content = "<a id=\"gatIphoneNoticeHide\" onclick=\"javascript:document.cookie='iphoneNoticeHide=hide';getElementById('iphone_notice').style.display = 'none';\">[Hide]</a><br> <div id=\"ip_message\"> <img src=\"/extensions/wikihow/app_store_badge.png\" height=\"40px\" > <h3>Using an iPhone?<br> <a id=\"gatIphoneNotice\" href=\"http://itunes.apple.com/WebObjects/MZStore.woa/wa/viewSoftware?id=309209200&mt=8&uo=6\">Download the wikiHow App</a> from the iTunes Store!</h3> </div>\n";
		var cookiePos = document.cookie.indexOf("iphoneNoticeHide=");
		if (cookiePos == -1)  {
			iphonediv.innerHTML = iphone_content;
		}
	}
}

function getRequestObject() {
	var request;
	try {
		request = new XMLHttpRequest();
	} catch (error) {
		try {
			request = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (error) {
			return false;
		}
	}
	return request;
}


//var ad_units = Array('lower_ads', 'side_ad1', 'sponsoredlinks', 'embed_ads', 'chikita_ads', 'embed_ads_mid', 'embed_ads_video', 'rad_links_video', 'rad_links');
//var sh_links = Array('showads1', 'showads2', 'showads3', 'showads4', 'showads5', 'showads6');

var ad_units = Array('adunit0', 'adunit1', 'adunit2', 'adunit3', 'adunit4');
var sh_links = Array("showads");

function sethideadscookie(val) {
	var date = new Date();
	if (val == 1)
		date.setTime(date.getTime()+(1*24*60*60*1000));
	else
		date.setTime(date.getTime()-(30*24*60*60*1000));
	var expires = "; expires="+date.toGMTString();
	document.cookie = "wiki_hideads="+val+expires+"; path=/";
}

function showorhideads(hide) {
	var style = 'display: inline;';
	if (hide) {
		style = 'display: none;';
	}
	$(".wh_ad_inner").hide();
	for (var i = 0; i < sh_links.length; i++) {
		var e = document.getElementById(sh_links[i]);
		if (!e) continue;
		if (hide) {
			style = 'display: inline;';
		} else {
			style = 'display: none;';
		}
		setStyle(e, style);
	}
	$(".show_ads").show();
}

function hideads() {
	sethideadscookie(1);
	showorhideads(true);
	clickshare(20);
	if (typeof Meebo != 'undefined') {
		Meebo('hide');
	}
}

function showads() {
	sethideadscookie(0);
	showorhideads(false);
	window.location.reload();
}

var ca = document.cookie.split(';');
var gHideAds = false;
var gchans = "";
for(var i=0;i < ca.length;i++) {
	var c = ca[i];
	var pair = c.split('=');
	var key = pair[0];
	var value = pair[1];
	key = key.replace(/ /, '');
	if (key == 'wiki_hideads') {
		if (value == '1') {
			// gHideAds = true will take care of showing 0 units
			gHideAds = true;
			//document.write('<style type="text/css" media="all">/*<![CDATA[*/ @import "/skins/WikiHow/noads.css"; /*]]>*/</style>');
		}
	}
}
var google_analytics_domain_name = ".wikihow.com"

var gRated = false;
function rateArticle(r) {
	if (!gRated) {
		var req = getRequestObject();
		requester.open('GET', 'http://www.wikihow.com/Special:RateArticle?page_id=' + wgArticleId+ '&rating=' + r);
		requester.send(null);
		if (r == 0) {
			y.innerHTML = 'Thanks. Please <a href="/Discussion:" + wgPageName + "#post">click here</a> to provide specific details on how we can improve this article.';        } else {
			y.innerHTML = 'Thanks. Your vote has been counted.';
		}
		gRated = true;
	}
}

var google_page_url = null;
if (wgServer == "http://wiki112.wikidiy.com") {
	google_page_url = "wikihow.com";
}

function loadimg(url, credits, imgh, imgw) {
	var maxH = jQuery(window).height() - 100;
	var maxW = jQuery(window).width() - 100;
	var bottomM = 120;
	var sideM = 60;
	var mH = 0; 
	var mW = 0;
	if (imgh > maxH -bottomM) {
		imgw = Math.round((imgw / imgh) * (maxH - bottomM));
		imgh = maxH - bottomM;
		mH = maxH;
		mW = Math.round(imgw /imgh * mH);	
	} else {
		mH = imgh + bottomM;
		mW = Math.round((imgw / imgh) * mH );
	}
	if (imgw > maxW - sideM) {
		imgh = Math.round(imgh / imgw * (maxW - sideM));
		imgw = maxW - sideM;
		mW = maxW;
		mH = Math.round(imgh / imgw * mW) + bottomM;	
	}	
	//alert("maxH: " + maxH + ', maxW: ' + maxW + ", mH: " + mH + ',imgH:' + imgh + ',mW: ' + mW + ', imgw:' + imgw);	
	jQuery('#img-box').html('<center><img src="' + url + '" style="width:' + imgw + 'px; height:' + imgh + 'px;"/><br/><a href="' + credits + '">Credits</a>');
    //jQuery('#img-box').load(url);
    jQuery('#img-box').dialog({
        width: mW,
		height: mH,
        modal: true,
        title: 'View Image',
		show: 'slide',
		closeOnEscape: true,
		position: 'center'
    });
	return false;
}

var rad = false;
var showC = false;
var showFirst = false;

//this code is left over from test on showing google ads on the side
//in related ads OR separate module. 
var radPos1 = false;
r = Math.random();
var radChan1 = ""; 
/*if (r < 0.5) {
  radChan1 += "+8354837063";
} else {
  radPos1 = true;
  radChan1 += "+3168052762";
}*/

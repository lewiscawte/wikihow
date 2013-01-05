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
if (typeof stylepath == 'undefined') stylepath = ''; // '/skins';
if (typeof wgContentLanguage == 'undefined') wgContentLanguage = ''; // 'en';
if (typeof WH_CDN_BASE == 'undefined') WH_CDN_BASE = 'http://pad1.whstatic.com';

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

if (typeof wgBreakFrames !== 'undefined' && wgBreakFrames) {
	// Un-trap us from framesets
	if (window.top != window) {
		window.top.location = window.location;
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

// used for upload page
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

/**
 * Inject a cute little progress spinner after the specified element
 *
 * @param element Element to inject after
 * @param id Identifier string (for use with removeSpinner(), below)
 */
function injectSpinner( element, id ) {
	var spinner = document.createElement( "img" );
	spinner.id = "mw-spinner-" + id;
	spinner.src = WH_CDN_BASE + "/skins/common/images/spinner.gif";
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
	updateTooltipAccessKeys( null );

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
//	  so the below should be redundant. It's there just in case.
hookEvent("load", runOnloadHook);

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

	window.open('https://twitter.com/intent/tweet?text='+ status +' '+url );

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
		jQuery('#href_' + list_id).html(wfMsg('navlist_collapse'));
		setCookie("expand_" + list_id, 1, 365);
	}
	else{
		list.css('display', "none");
		jQuery('#href_' + list_id).html(wfMsg('navlist_expand'));
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
		obj.style.background = "url(" + WH_CDN_BASE + "/skins/WikiHow/images/admin_check.gif) #F2ECDE no-repeat 65px 9px";
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
*			if will be called with the XMLHttpRequest as a parameter; if it's an input
*			element, its value will be set to the resultText; if it's another type of
*			element, its innerHTML will be set to the resultText.
*
* Example:
*	sajax_do_call('doFoo', [1, 2, 3], document.getElementById("showFoo"));
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
		jQuery('#hiddenFA').slideDown('slow').show(function(){
			firstChild.html(wfMsg('mainpage_fewer_featured_articles'));
			jQuery('#moreOrLess').attr('src', WH_CDN_BASE + '/skins/WikiHow/images/arrowLess.png');
			jQuery("#featuredNav").hide(); //need to do this for IE7
			jQuery("#featuredNav").show();
		});
		
		mainPageFAToggleFlag = true;
	} else {
		jQuery('#hiddenFA').slideUp('slow').hide(function(){
			firstChild.html(wfMsg('mainpage_more_featured_articles'));
			jQuery('#moreOrLess').attr('src', WH_CDN_BASE + '/skins/WikiHow/images/arrowMore.png');
			jQuery("#featuredNav").hide(); //need to do this for IE7
			jQuery("#featuredNav").show();
		});
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

/**
 * A simple pad function.  Note that it won't match up with the output of
 * the php.
 */
function wfGetPad(url) {
	if (url.search(/^http:\/\//) >= 0) {
		return url;
	} else {
		return 'http:\/\/pad1.whstatic.com' + url;
	}
}

function checkIphone() {

	if ( navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/iPod/i) ) {

		var iphonediv = document.getElementById('iphone_notice');
		var iphone_content = "<a id=\"gatIphoneNoticeHide\" onclick=\"javascript:document.cookie='iphoneNoticeHide=hide';getElementById('iphone_notice').style.display = 'none';\">[Hide]</a><br> <div id=\"ip_message\"> <img src=\"" + WH_CDN_BASE + "/extensions/wikihow/app_store_badge.png\" height=\"40px\" > <h3>Using an iPhone?<br> <a id=\"gatIphoneNotice\" href=\"http://itunes.apple.com/WebObjects/MZStore.woa/wa/viewSoftware?id=309209200&mt=8&uo=6\">Download the wikiHow App</a> from the iTunes Store!</h3> </div>\n";
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
		$.ajax( 
			{ url: '/Special:RateArticle?page_id=' + wgArticleId+ '&rating=' + r
			}
		);
	}
	var msg = wfMsg('ratearticle_rated');
	if (r == 0) {
		msg = wfMsg('ratearticle_notrated', wgPageName, wfMsg('ratearticle_talkpage'));
	}
	$('#page_rating').html(msg);
	gRated = true;
}

function updateWidget(id, x) {
	var url = '/Special:Standings/' + x;
	$.get(url, function (data) {
		$(id).fadeOut();
		$(id).html(data['html']);
		$(id).fadeIn();
	},
	'json'
	);
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



var google_page_url = null;
if (wgServer == "http://wiki112.wikidiy.com") {
	google_page_url = "wikihow.com";
}

var rad = false;
var showC = false;
var showFirst = false;
var adPadding = '';
var adColor = '';
var adUrl = '';
var adTitle = '';
var adText = '';
var showImageAd = false;
var adNum = 0;

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

// Returns true if referrer is a search engine 
function isFromSearch() {
	var ref = document.referrer;
	
	// Google check
	if (ref.indexOf('google.com') != -1 && ref.indexOf('mail.google.com') == -1 && ref.indexOf('url?q=') == -1 && ref.indexOf('q=') != -1) {
		//console.log('google search');
		return true;
	}
	
	// Bing check
	if (ref.indexOf('bing.com') != -1 && ref.indexOf('q=') != -1) {
		//console.log('bing search');
		return true;
	}

	// Yahoo check
	if (ref.indexOf('yahoo.com') != -1 && ref.indexOf('p=') != -1) {
		//console.log('yahoo search');
		return true;
	}
	
	// Ask.com check
	if (ref.indexOf('ask.com') != -1 && ref.indexOf('q=') != -1) {
		//console.log('ask search');
		return true;
	}
	
	// Aol search check
	if (ref.indexOf('aol.com') != -1 && ref.indexOf('q=') != -1) {
		//console.log('aol search');
		return true;
	}
	
	// Baidu check
	if (ref.indexOf('baidu.com') != -1 && ref.indexOf('wd=') != -1) {
		//console.log('baidu search');
		return true;
	}

	// Yandex check
	if (ref.indexOf('yandex.com') != -1 && ref.indexOf('text=') != -1) {
		//console.log('yandex search');
		return true;
	} 
	
	// No search engine referral detected. Return false
	return false;
}

// Used for a website optimizer test to remove the intro section of an article
function removeIntro(state) {
	// remove intro 
	if (state == 1) {
		// Remove Intro
		$('#bodycontents > .article_inner:first').remove();
		// Remove first H2 (which should be steps section)
		$('#bodycontents > h2:first').remove();
	}
	
	// Don't do anything. Control
	if (state == 2) {}

	// Remove intro image
	if (state == 3) {
		$('.mwimg:first').remove();
	}
	
}

function setCookie(name, value) {
	document.cookie=name + "=" + value;
}

function getCookie(c_name) {
	var i,x,y,ARRcookies=document.cookie.split(";");
	for (i=0;i<ARRcookies.length;i++) {
	  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
	  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
	  x=x.replace(/^\s+|\s+$/g,"");
	  if (x==c_name) {
		return unescape(y);
	  }
	}
}

function parseIntWH(num) {
	if (!num) {
		return 0;
	}
	return parseInt(num.replace(/,/, ''));
}

function addCommas(nStr) {
	nStr += '';
	x = nStr.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}

function setupEmailLinkForm() {
	$("#emaillink").submit(function() {
		var params = { fromajax: true };
		$("#emaillink input").each(function() {
			params[$(this).attr('name')] = $(this).val();
		});
		$.post('/Special:EmailLink', params, function(data) {
			$("#img-box").html(data);
			setupEmailLinkForm();
		});
		return false;
	});

}


function emailLink() {
	$("#img-box").load('/Special:EmailLink?target=' + wgPageName + '&fromajax=true', function() {
			$("#img-box").dialog( {
				modal: true,
				title: "E-mail This Page to a Friend",
				height: 620,
				width: 650,
				position: 'center'
			}) ;
			setupEmailLinkForm();
		});
	return false;
}
var fromsearch = isFromSearch();

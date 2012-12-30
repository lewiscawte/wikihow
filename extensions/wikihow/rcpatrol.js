var mpreq = null;
var nextrev = null;
var marklink = null;
var skiplink = null;
var loaded = false;
var backsize = 20;
var backurls = new Array(backsize);
var backindex = 0;
var rev = false;
var ns = -1;
var rc_user_filter = "";

var search = window.location.search.replace(/^\?/, "");
var parts = search.split("&");
for (i = 0; i < parts.length; i++) {
	var term = parts[i];
	var keyterm = term.split("=");
	if (keyterm.length == 2 && keyterm[0] == 'rc_user_filter') {
		rc_user_filter = keyterm[1];
	}
}

if (rc_user_filter != "") {
	$(document).ready(function() {
		openSubMenu('user');
		$("#rc_user_filter").val(rc_user_filter);
	});
}
function confirmLeave() {
	alert("leaving the page, did you mean to leave the page? perhaps it's a bug");
    var e = document.getElementById('bodycontents2');
    var links = e.getElementsByTagName("a");
	//alert(links[1].href + "," + links[1].innerHTML + ", " + window.location.hostname + "," + links[1].target);
	return true;
}
//window.onbeforeunload = confirmLeave;

function setRCLinks() {
    var e = document.getElementById('bodycontents2');
    var links = e.getElementsByTagName("a");
    for (i = 0; i < links.length; i++) {
        if (links[i].href != wgServer + "/" + wgPageName)
			links[i].setAttribute('target','new');
		if (links[i].getAttribute('accesskey')){
			if (links[i].getAttribute('accesskey') == 'p'
				&& links[i].id != 'markpatrolurl') {
        		links[i].setAttribute('accesskey',null);
			} else if (links[i].getAttribute('accesskey') == 's'
                && links[i].id != 'skippatrolurl') {
                links[i].setAttribute('accesskey',null);
            }
		}
    }
	
	var e = document.getElementById('numrcusers');
	if (e.innerHTML != "1") {
		e = $("#mw-diff-ntitle1a");
		if (e.html().indexOf("and others") < 0) {
			e.html(e.html() + " <b>and others</b>.");
		}
	}

	$('.white_button_100').each( function() {
			if ($(this).html() == "quick edit") {
				$(this).click(function () {
					hookSaveButton();
				});
				return;
			}
		}
	);
	addPressedState();
}

function incQuickEditCount() {
    // increment the active widget
    var p = $('#quickedits');
    p.fadeOut(400, function() {
            var cur = parseInt(p.html());
            cur++;
            p.html(cur);
            p.fadeIn();
        }
    );
}
function hookSaveButton() {
	if ($("#wpSave").html() == null ) {
		window.setTimeout(hookSaveButton,200);
		return;
	}
	$("#wpSave").click(function() {
			incQuickEditCount();			
		}
	);
}

function setContentInner(html, fade) {
	$("#bodycontents2").html(html);
	if (fade) {
		$("#bodycontents2").fadeIn(300);
	} else {
		$("#bodycontents2").show();
	}
	
	var e =  document.getElementById('articletitle');
	if (!e) return;
	var title = e.innerHTML;
	var h1 =document.getElementsByTagName("h1");
	for(i = 0; i < h1.length; i++) {
		h1[i].innerHTML = title;
		break;
	}
	document.title = title;
    var matches = html.match(/<div id='newrollbackurl'[^<]*<\/div>/);
    newlink = matches[0];
    gRollbackurl = newlink.replace(/<(?:.|\s)*?>/g, ""); 
	setRCLinks();
	addBackLink();
	if (rev) {
		$("#reverse").attr('checked', true);
	} else {
	}
	$("#namespace").val(ns);
	$("#rc_user_filter").val(rc_user_filter);
	if (rc_user_filter != "")
		openSubMenu('user');
}

function setContent(html) {
	var e = document.getElementById('bodycontents2');
	if (navigator.appVersion.indexOf("MSIE") >= 0) {
		$("#bodycontents2").hide(300, function() { 
			setContentInner(html,false);
		});
	} else {
		$("#bodycontents2").fadeOut(300, function() { 
			setContentInner(html, true);
		});
	}
	return;
}


function resetRCLinks() {
	var matches = nextrev.match(/<div id='skiptitle'[^<]*<\/div>/);
	if (matches == null || matches.length ==0) {
		return;
	}
	var newlink = matches[0];
	var skiptitle = "&skiptitle=" + newlink.replace(/<(?:.|\s)*?>/g, ""); 

	/// set the mark link to the current contents
	if (navigator.userAgent.indexOf('MSIE') > 0) {
		marklink = document.getElementById('newlinkpatrol').innerText + skiptitle;
		skiplink = document.getElementById('newlinkskip').innerText + skiptitle;
 	} else {
		marklink = document.getElementById('newlinkpatrol').textContent+ skiptitle;
		skiplink = document.getElementById('newlinkskip').textContent + skiptitle;	
	}
}

function setupTabs(){
    jQuery('#rctab_advanced a').click(function(){
	openSubMenu('advanced');
	return false;
    });
    jQuery('#rctab_ordering a').click(function(){
	openSubMenu('ordering');
	return false;
    });
    jQuery('#rctab_user a').click(function(){
	openSubMenu('user');
	return false;
    });
    jQuery('#rctab_help a').click(function(){
	openSubMenu('help');
	return false;
    });
}

function addPressedState(){
   jQuery('#skippatrolurl').mousedown(function(){
	jQuery(this).css('background-position', '0 -52px');
    });

    jQuery('#markpatrolurl').mousedown(function(){
	jQuery(this).css('background-position', '0 -52px');
    });
}


function mp_handler() {
    if ( mpreq.readyState == 4){
		if ( mpreq.status == 200) {
			nextrev = mpreq.responseText;
			resetRCLinks();
			loaded = true;
		} 
	}
}

function skip() {
	if (!loaded) {
		window.setTimeout(skip, 500);
		return;
	}
	sendMP(skiplink);
	resetQuickNoteLinks();
	return false;
}

function formatnum(x1){
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1;
}

function mp() {
	if (!loaded) {
		window.setTimeout(mp, 500);
		return;
	}

	// increment the active widget
	var n = $('#numedits');
	var p = $('#patrolledcount');
	var a = $('#alltime');
	p.fadeOut(400, function() {
			var cur = parseInt(p.html());
			cur += parseInt(n.html());
			p.html(cur);
			p.fadeIn();
		}
	);
	var alltime = parseInt(a.html().replace(/,/, ''));
	alltime += parseInt(n.html());
	alltime = formatnum(alltime.toString());
	a.fadeOut(400, function() {
		a.html(alltime);
		a.fadeIn() 
		});
	sendMP(marklink);
	// increment count

	//change quick note links
	resetQuickNoteLinks();
	return false;
}

function resetQuickNoteLinks(){
	jQuery('#qnote_buttons').load("/Special:QuickNoteEdit/quicknotebuttons");
}

function loadPreviousHandler() {
	if (mpreq.readyState == 4) {
		if (mpreq.status == 200) {
			//alert('setting content:' + mpreq.responseText);
			setContent(mpreq.responseText);
			$('#goback').hide();
			loaded = true;
		} else {
			alert(mpreq.status + "\n\n" + mpreq.responseText);
		}
	}

}

var ignore_rcid = 0;

function changeReverse() {
	var tmp =  $("input[name='reverse']:checked").val();
	if (tmp == 1) {
		ignore_rcid = 2;
		rev = true;
	} else {
		rev = false;
	}
}

function changeUserFilter() {
	rc_user_filter = $("#rc_user_filter").val();
}

function modUrl(url) {
	var nselem = $("#namespace");
	url = url.replace(/reverse=[0-9]?/, "&");	
	if (rev) 
		url += "&reverse=1&namespace=" + nselem.val();
	if (ignore_rcid > 0) {
		url += "&ignore_rcid=1";
		ignore_rcid--;
	}
	ns = $("#namespace").val();
	url += "&rc_user_filter=" + encodeURIComponent(rc_user_filter);
	return url;
}
function loadPrevious(url) {
	var e = document.getElementById('bodycontents2');
	e.innerHTML = "<center><img src='/extensions/wikihow/rotate.gif'/></center>";	
    mpreq = getRequestObject();
	url = modUrl(url);
    mpreq.open('GET', url);
    mpreq.onreadystatechange = loadPreviousHandler;
    mpreq.send(null);
    loaded = false;
    return false;
}

function sendMP(url) {
	mpreq = getRequestObject();
	url = modUrl(url);
	mpreq.open('GET', url);
    mpreq.onreadystatechange = mp_handler;
	mpreq.send(null);
	loaded = false;
	addBackLink();
	setContent(nextrev);	
	return false;
}

function grabnext(url) {
	mpreq = getRequestObject();
	url = modUrl(url);
	mpreq.open('GET', url);
    mpreq.onreadystatechange = mp_handler;
	mpreq.send(null);

	return false;
}

function addBackLink() {
	var link = document.getElementById('permalink').textContent;
	backurls[backindex % backsize] = link;	
	backindex++;
}

function goback() {
	if (backindex > 0) {
		backindex--;
		var index = backindex-1;
		var backlink = backurls[index % backsize];
		//alert('going to previous link: ' + backlink);
		loadPrevious(backlink);
	} else {
		alert('No diff to go back to, sorry!');
	}
	return false;
}

function handleQESubmit() {
	incQuickEditCount();
}


function updateActivewidget() {
    var rq = getRequestObject();
    rq.open('GET', "http://" + window.location.hostname + "/Special:RCActiveWidget", false);
    rq.send(null);
	var widget = $("#rcactivewidget .rcleaders");
	widget.fadeOut(400, function() {
			widget.html(rq.responseText);
			widget.fadeIn();
		}
	);
	window.setTimeout(updateActivewidget, 1000 * 60 * 10);
    return false;

}

function updateLeaderboard() {
    var rq = getRequestObject();
    rq.open('GET', "http://" + window.location.hostname + "/Special:RCActiveWidget?leaderboard=1", false);
    rq.send(null);
    var widget = $("#rcleaderboard");
    widget.fadeOut(400, function() {
            widget.html(rq.responseText);
            widget.fadeIn();
        }
    );
    window.setTimeout(updateLeaderboard, 1000 * 60 * 5);
    return false;

}

function updateTimer(id) {
  	var e = $(id);
  	var i = parseInt(e.html());
    if (i > 1) {
       e.fadeOut(400, function() {
           i--;
           e.html(i);
           e.fadeIn();
        }); 
    }
}

function updateTimers() {
	var ids = new Array("#activecdown", "#leaderboardcdown");
	for (x = 0; x < ids.length; x++) {
		updateTimer(ids[x]);
	}
	window.setTimeout(updateTimers, 1000 * 60);
}
$(document).ready(function() {
	window.setTimeout(updateActivewidget, 1000 * 60 * 5);
	window.setTimeout(updateLeaderboard, 1000 * 60 * 10);
	window.setTimeout(updateTimers, 1000 * 60);
});

function openSubMenu(menuName){
    var menu = jQuery("#rc_" + menuName);
    if(menu.is(":visible")){
	menu.hide();
	jQuery("#rctab_" + menuName).removeClass("on");
    }
    else{
	jQuery(".rc_submenu").hide();
	menu.show();
	jQuery(".tableft").removeClass("on");
	jQuery("#rctab_" + menuName).addClass("on");
    }
}

function changeUser(user) {
	if (user)
		window.location.href = "/Special:RCPatrol?rc_user_filter=" + encodeURIComponent($("#rc_user_filter").val());
	else
		window.location.href = "/Special:RCPatrol";
}



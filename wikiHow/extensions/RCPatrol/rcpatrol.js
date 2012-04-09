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


// refresh the leaderboard every seconds
var RC_WIDGET_LEADERBOARD_REFRESH = 10 * 60; 
var RC_WIDGET_USERSTATS_REFESH = 5 * 60; 

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
	$("#iia_stats_group, #iia_stats_today_rc_quick_edits, #iia_stats_week_rc_quick_edits").each(function(index, elem) {
			$(this).fadeOut();
			var cur = parseIntWH($(this).html());
			$(this).html(addCommas(cur + 1));
			$(this).fadeIn();
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
		$("#reverse").prop('checked', true);
	} else {
	}
	$("#namespace").val(ns);
	$("#rc_user_filter").val(rc_user_filter);
	if (rc_user_filter != "") {
		openSubMenu('user');
	}
	if (rev || ns >= 0) {
		openSubMenu('ordering');
	}
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

//initialize mixpanel
//try {  var mpmetrics = new MixpanelLib('2024f9ec02f75a42654c1a7631d45502'); } catch(err) { null_fn = function () {}; var mpmetrics = {  track: null_fn,  track_funnel: null_fn,  register: null_fn,  register_once: null_fn, register_funnel: null_fn }; } 

function mp() {
	if (!loaded) {
		window.setTimeout(mp, 500);
		return;
	}
	
	//track it
	//mpmetrics.track('WH ACTION',{'trigger':'markaspatrolled'});
	
	var numedits = parseIntWH($('#numedits').html());
	$("#iia_stats_today_rc_edits, #iia_stats_week_rc_edits, #iia_stats_all_rc_edits").each(function(index, elem) {
			$(this).fadeOut();
			var cur = parseIntWH($(this).html());
			$(this).html(addCommas(cur + numedits));
			$(this).fadeIn();
		}
	);
	sendMP(marklink);

	//change quick note links
	resetQuickNoteLinks();
	return false;
}

function resetQuickNoteLinks(){
	jQuery('#qnote_buttons').load("/Special:QuickNoteEdit/quicknotebuttons");
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
	url = url.replace(/reverse=[0-9]?/, "&");	

	// If it's a test, let the special page know
	var RCTestObj = RCTestObj ||  null;
	if (RCTestObj) {
		url += "&rctest=1";
	}
	// If we're debugging, let the special page know
	var mode = extractParamFromUri(document.location.search, 'rct_mode');
	if (mode) {
		url += "&rct_mode=" + mode;
	}

	if (rev) {
		url += "&reverse=1";
	}
	if (ns >= 0) {
		url += "&namespace=" + ns;
	}
	if (ignore_rcid > 0) {
		url += "&ignore_rcid=1";
		ignore_rcid--;
	}
	url += "&rc_user_filter=" + encodeURIComponent(rc_user_filter);
	return url;
}

function loadPrevious(url) {
	url = modUrl(url);
	loaded = false;
	$.get(url,
		function(data) {
			setContent(data['html']);
		},
		'json'
	);
    return false;
}

function setUnpatrolled(count) {
	$("#rcpatrolcount").fadeOut(400, function() {
		$("#rcpatrolcount").html(count)
				.fadeIn();
			}
		);
}


function setData(data) {
	nextrev = data['html'];
	resetRCLinks();
	loaded = true;
	setUnpatrolled(data['unpatrolled']);
}

function sendMP(url) {
	url = modUrl(url);
	loaded = false;
	$.get(url,
		function(data) {
			setData(data);
		},
		'json'
	);
	addBackLink();
	setContent(nextrev);	
	return false;
}

function grabnext(url) {
	url = modUrl(url);
	$.get(url,
		function(data) {
			setData(data);
		},
		'json'
	);
	return false;
}

function addBackLink() {
	// If it's a test, don't add this revision to the back links
	if (WH.RCTest) {
		return;
	}
	var link = $('#permalink').val();
	backurls[backindex % backsize] = link;	
	backindex++;
}

function goback() {
	if (backindex > 0) {
		backindex--;
		var index = backindex-1;
		var backlink = backurls[index % backsize];
		loadPrevious(backlink);
	} else {
		alert('No diff to go back to, sorry!');
	}
	return false;
}

function handleQESubmit() {
	incQuickEditCount();
}

function updateLeaderboard() {
	updateWidget("#iia_standings_table", "QuickEditStandingsGroup");
	var min = RC_WIDGET_LEADERBOARD_REFRESH / 60;
	$("#stup").html(min);
    window.setTimeout(updateLeaderboard, 1000 * RC_WIDGET_LEADERBOARD_REFRESH);
    return false;
}

function updateTimers() {
	updateTimer("stup");
	window.setTimeout(updateTimers, 1000 * 60);
}
$(document).ready(function() {
	window.setTimeout(updateLeaderboard, 1000 * RC_WIDGET_LEADERBOARD_REFRESH);
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

$(document).ready(function() {
		$("#article").prepend("<div id='rcpatrolcount'></div>");
		$("#namespace").change(function() {
			ns=$("#namespace").val();
		});
	}
	);


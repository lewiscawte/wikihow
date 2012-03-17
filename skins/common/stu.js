if (typeof WH === 'undefined') WH = {};

// BOUNCE TIMER MODULE
WH.ExitTimer = (function ($) {

//var odds = 2;
//var rand = Math.floor(Math.random()*odds);
//var LOGGER_ENABLE = (rand==1 && wgNamespaceNumber==0 && wgAction=="view");
var LOGGER_ENABLE = (wgNamespaceNumber == 0 && wgAction == "view");

var startTime = false;
var loadTime = false;
//var id = Math.floor(Math.random() * 100000);
var duration = 0;
//var whC = (console && console.log);
var etDebug = false;

function getTime() {
	var date = new Date();
	return date.getTime();
}

function pingSend(priority, domain, message, doAsync){
	var loggerUrl = '/Special:BounceTimeLogger?v=6&';
	loggerUrl += '_priority=' + priority;
	loggerUrl += '&_domain=' + domain;
	loggerUrl += '&_message=' + encodeURI(message);
	$.ajax({url: loggerUrl, async: doAsync});
}

function getDomain() {
	if (skin=="mobile") return "mb";
	return "bt";
}

function sendExitTime(e) {
	if (!LOGGER_ENABLE) return;  //do nothing if startTime wasn't set

	var viewTime = -1;
	if (startTime) {
		//startTime may not be set if window was blurred, then close
		//without being brought to the foreground
		viewTime = (getTime() - startTime);
		duration = duration + viewTime;
	}
	startTime = false;

	var message = wgPageName + " btraw " + (duration / 1000);
	var domain = getDomain();
	pingSend(0, domain, message, false);
}

function onUnload() { sendExitTime('unload'); }

function onBlur() {
	if (!LOGGER_ENABLE) return;
	var viewTime = getTime() - startTime;
	duration += viewTime; 
	startTime = false;
}

function fromGoogle() {
	var ref = typeof document.referrer === 'string' ? document.referrer : '';
	var googsrc = !!(ref.match(/^[a-z]*:\/\/[^\/]*google/i));
	return googsrc;
}

function start(dbg) {
	etDebug = dbg;
	if (LOGGER_ENABLE && (etDebug || fromGoogle())){
		startTime = getTime(); 
		loadTime = getTime();
		$(window).unload(onUnload);
		$(window).focus( function() {
			if (LOGGER_ENABLE) startTime = getTime();
		});
		$(window).blur(onBlur);
	} 
}

return {
	'start': start
};

})(jQuery);


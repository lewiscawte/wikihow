if (typeof WH === 'undefined') WH = {};

// BOUNCE TIMER MODULE
WH.ExitTimer = (function ($) {

var LOGGER_ENABLE = (wgNamespaceNumber == 0 && wgAction == "view");

var startTime = false;
var duration = 0;
var etDebug = false;
var DEFAULT_PRIORITY = 0;
var fromGoogle = false;

function getTime() {
	var date = new Date();
	return date.getTime();
}

function pingSend(priority, domain, message, doAsync) {
	var loggerUrl = '/Special:BounceTimeLogger?v=6';
	if (priority != DEFAULT_PRIORITY) {
		loggerUrl += '&_priority=' + priority;
	}
	loggerUrl += '&_domain=' + domain;
	loggerUrl += '&_message=' + encodeURI(message);
	$.ajax({url: loggerUrl, async: doAsync});
}

function getDomain() {
	if (fromGoogle) {
		if (skin == 'mobile') {
			return 'vm'; // virtual domain mapping to mb and pv domains
		} else {
			return 'vw'; // virtual domain mapping to bt and pv domains
		}
	} else {
		return 'pv';
	}
}

function sendExitTime(e) {
	if (startTime) {
		//startTime may not be set if window was blurred, then close
		//without being brought to the foreground
		var viewTime = (getTime() - startTime);
		duration = duration + viewTime;
	}
	startTime = false;

	var message = wgPageName + " btraw " + (duration / 1000);
	var domain = getDomain();
	pingSend(DEFAULT_PRIORITY, domain, message, false);
}

function onUnload() {
	sendExitTime('unload');
}

function onBlur() {
	var viewTime = getTime() - startTime;
	duration += viewTime;
	startTime = false;
}

function onFocus() {
	startTime = getTime();
}

function checkFromGoogle() {
	var ref = typeof document.referrer === 'string' ? document.referrer : '';
	var googsrc = !!(ref.match(/^[a-z]*:\/\/[^\/]*google/i));
	return googsrc;
}

function start(dbg) {
	etDebug = dbg;
	if (LOGGER_ENABLE) {
		fromGoogle = checkFromGoogle();
		startTime = getTime(); 
		$(window).unload(onUnload);
		$(window).focus(onFocus);
		$(window).blur(onBlur);
	} 
}

return {
	'start': start
};

})(jQuery);


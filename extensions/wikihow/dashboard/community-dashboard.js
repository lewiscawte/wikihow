if (typeof console == 'undefined') console = {};
if (typeof console.log == 'undefined') console.log = {};

if (typeof WH == 'undefined') WH = {};

WH.dashboard = (function ($) {

	// module (closure) variables
	var thresholds = {},
		refreshGlobalData = 5,
		refreshUserData = 60,
		isPaused = false,
		globalTimer = null,
		userTimer = null,
		apps = {};

	// constants
	var CDN_BASE = 'http://pad1.whstatic.com';

	// called on DOM ready event
	function init() {
		if (wgServer.indexOf('www.wikihow.com') == -1) {
			CDN_BASE = '';
		}

		addUIListeners();

		startTimers();

		initApps();

		$(".comdash-more").click(function(){
			id = $(this).attr("id");
			widgetId = id.substring(13); //13 = comdash-more-

			$.ajax({
				url: '/Special:CommunityDashboard/leaderboard?widget=' + widgetId,
				dataType: 'json',
				success: function(data) {
					json = $.parseJSON(data);
					
					leader = $(".comdash-widget-" + widgetId + " .comdash-widget-leaders");
					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders .comdash-widget-body table").html(json.leaderboard);

					//temp hack b/c you can't get the height of an hidden element
					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders .comdash-widget-leaders-content").css("visibility", "hidden");
					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders .comdash-widget-leaders-content").show();
					height = $(leader).height();
					position = $(leader).position();
					newTop = position.top - height;

					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders .comdash-widget-leaders-content").hide();
					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders .comdash-widget-leaders-content").css("visibility", "visible");
					
					$(leader).animate({"top": newTop+"px"}, "slow");
					
					$(".comdash-widget-" + widgetId + " .comdash-widget-leaders-content").slideToggle("slow");
				}
			});

			return false;
		});

		$(".comdash-widget-leaders .comdash-close").click(function(){
			id = $(this).attr("id");
			widgetId = id.substring(14); //14 = comdash-close-

			$(".comdash-widget-" + widgetId + " .comdash-widget-leaders").animate({"top": "190px"}, "slow");
			$(".comdash-widget-" + widgetId + " .comdash-widget-leaders-content").slideToggle("slow");

			return false;
		});

		$("#comdash-header-customize").click(function(){
			alert("not implemented yet");
		});

	}

	// To be called by all subclasses of WH.DashboardWidget so that they
	// can hook into the data feed from the site.
	//
	// Note: it's not necessary to call init() before this method (by design).
	function registerDataListener(app, instance) {
		apps[app] = instance;
	}

	// Start the timers which ping the server for data and reload the page
	// after a day
	function startTimers() {
		globalTimer = setInterval(onGlobalTimer, refreshData('global') * 1000);
		userTimer = setInterval(onUserTimer, refreshData('user') * 1000);
		setTimeout(function () {
			window.location.href = window.location.href;
		}, 24 * 60 * 60 * 1000);
	}

	// Listen for actions on DOM nodes relating to UI stuff
	function addUIListeners() {
		$('.comdash-pause').click(function () {
			if (!paused()) {
				$(this).html(wfMsg('cd-resume-updates'));
				paused(true);
			} else {
				$(this).html(wfMsg('cd-pause-updates'));
				paused(false);
			}
			return false;
		});

		$('.comdash-settings').click(function () {
			var wasPaused = isPaused;
			paused(true);
			alert('do dialog');
			return false;
		});
	}

	// Get around the same origin problem by creating a <script> elements to
	// grab the data off a different domain and setting a callback (JSONP).
	function loadData(type, callbackFunc) {
		var REFRESH_URL = '/Special:CommunityDashboard?action=$1&function=$2';
		if (!paused()) {
			var action = type == 'global' ? 'refresh' : 'userrefresh';
			var url = CDN_BASE + wfTemplate(REFRESH_URL, action, callbackFunc);
			var node = $('<script src="' + url + '"></script>');
			$('body').append(node);
		}
	}

	// The setInterval callback to fetch new global data once in a while
	function onGlobalTimer() {
		loadData('global', 'WH.dashboard.globalDataCallback');
	}

	// The setInterval callback to fetch new user data once in a while
	function onUserTimer() {
		loadData('user', 'WH.dashboard.userDataCallback');
	}

	// Gets/sets to control whether updating the widgets is paused
	function paused(_isPaused) {
		if (typeof _isPaused != 'undefined') {
			isPaused = _isPaused;
		}
		return isPaused;
	}

	// Gets/sets the thresholds for all apps
	function allThresholds(_thresholds) {
		if (typeof thresholds != 'undefined') {
			thresholds = _thresholds;
		}
		return thresholds;
	}

	// Gets/sets the thresholds for a particular app
	function thresholds(app, _thresholds) {
		if (thresholds[app]) {
			if (typeof _thresholds != 'undefined') {
				thresholds[app] = _thresholds;
			}
			return thresholds[app];
		} else {
			return {};
		}
	}

	// Gets/sets the update (via server call) interval
	//
	// Note: these settings must be made before init() is called
	function refreshData(type, secs) {
		if (type == 'user') {
			if (typeof secs != 'undefined') {
				refreshUserData = secs;
			}
			return refreshUserData;
		} else if (type == 'global') {
			if (typeof secs != 'undefined') {
				refreshGlobalData = secs;
			}
			return refreshGlobalData;
		} else {
			console.log('refreshData: unknown type');
		}
	}

	// ping each of the registered app with their data
	function sendDataToApps(type, data) {
		$.each(apps, function(name, app) {
			if (typeof app.listenData == 'function') {
				var appData = null;
				if (type == 'global') {
					if (typeof data['widgets'] != 'undefined' &&
						typeof data['widgets'][name] != 'undefined')
					{
						appData = data['widgets'][name];
					}
				} else {
					if (typeof data[name] != 'undefined') {
						appData = data[name];
					}
				}
				if (appData !== null) {
					app.listenData(type, appData);
				}
			}
		});
	}

	// call init on each app, if it exists
	function initApps() {
		$.each(apps, function(name, app) {
			if (typeof app.init == 'function') {
				app.init();
			}
		});
	}

	// This callback is made after the data is loaded by the <script> tag.
	// It only needs to be public because of the way JSONP happens.
	function userDataCallback(data) {
		sendDataToApps('user', data);
	}

	// This callback is made after the data is loaded by the <script> tag.
	// It only needs to be public because of the way JSONP happens.
	function globalDataCallback(data) {
		sendDataToApps('global', data);
	}

	function fadeImages(){
		
	}

	// To be called by widgets to make updates more interactive
	function animateUpdate(div, newValue) {
		var oldValue = div.html();

		if (oldValue == newValue) return;
	   
		var offset = div.offset(),
			offsetTop = Math.round(offset['top']),
			offsetLeft = Math.round(offset['left']),
			offsetRight = offsetLeft + div.width(),
			offsetBottom = offsetTop + div.height();

		var containerNode = $('<div style="overflow: hidden; z-index: 10; width: 100%; float: none;"></div>'),
			contentNode = $('<div style="position: relative; z-index: 1; width: 100%; float: none;"></div>'),
			beforeNode = $('<div style="height: auto; float:none; padding: 0px;">' + oldValue + '</div>'),
			afterNode = $('<div style="float:none; padding: 0px;">' + newValue + '</div>');
	   
		div.html(containerNode);
		containerNode.append(contentNode);
		contentNode.append(beforeNode);
		contentNode.append(afterNode);

		var height = beforeNode.height();
		containerNode.css({
			'clip': 'rect(' + offsetTop + 'px ' + offsetRight + 'px ' + offsetBottom + 'px ' + offsetLeft + 'px)',
			'height': height + 'px',
			'top': '0px'
		});
	   
		var initial = 0,
			duration = 500,
			timeIncrement = 50,
			change = height,
			startTime = new Date().getTime();

		var quadEaseOut = function (interval, start, delta, duration) {
			var percent = interval / duration;
			return start + delta * percent * (percent - 2);
		};
	 
		var timerID = setInterval(function () {
			var delta = new Date().getTime() - startTime,
				ypos = quadEaseOut(delta, initial, change, duration);
			if (delta >= duration) {
				div.html(newValue);
				clearInterval(timerID);
			} else {
				contentNode.css('top', Math.round(ypos) + 'px');
			}
		}, timeIncrement);

	}

	// the public interface -- only these methods can be called from outside
	// the module
	return {
		init: init,
		thresholds: thresholds,
		allThresholds: allThresholds,
		refreshData: refreshData,
		paused: paused,
		userDataCallback: userDataCallback,
		globalDataCallback: globalDataCallback,
		registerDataListener: registerDataListener,
		animateUpdate: animateUpdate
	};
})(jQuery);


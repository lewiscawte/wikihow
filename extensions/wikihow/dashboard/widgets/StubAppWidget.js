// Add this widget to the WH.dashboard module
WH.dashboard.StubAppWidget = (function($) {

	// Make aliases for things we use a lot
	var animateUpdate = WH.dashboard.animateUpdate,
		animateUpdateImage = WH.dashboard.animateUpdateImage,
		unpatrolledNode = null,
		completedNode = null,
		lastImage = null,
		lastName = null,
		lastTime = null;

	// Our new widget class
	function StubAppWidget() {

		// Called by WH.dashboard.init after it's done running
		this.init = function() {
			unpatrolledNode = $('.comdash-widget-StubAppWidget .comdash-count span');
			lastImage = $('.comdash-widget-StubAppWidget .comdash-lastcontributor .avatar');
			lastName = $('.comdash-widget-StubAppWidget .comdash-lastcontributor .name');
			lastTime = $('.comdash-widget-StubAppWidget .comdash-lastcontributor .time');
			completedNode = $('.cd-rcw-completed');
		};

		this.getWidgetName = function(){
			return "StubAppWidget";
		}

		// Called by WH.dashboard after new data has been downloaded from
		// the server.
		//
		// @param type either 'global' or 'user'
		this.listenData = function(type, data) {
			if (type == 'global') {
				var unpatrolled = data['ct'];
				var img = this.getAvatarLink(data['lt']['im']);
				var userLink = this.getUserLink(data['lt']['na']);

				//get weather
				var weatherIcon = this.getWeatherIcon(unpatrolled);
				this.animateUpdateWeather(weatherIcon);

				animateUpdate(unpatrolledNode, unpatrolled);
				animateUpdateImage(lastImage, img);
				animateUpdate(lastName, userLink);
				animateUpdate(lastTime, data['lt']['da']);
			} else if (type == 'user') {
				var completion = data;
				animateUpdate(completedNode, completion);
			}
		};

	}

	// Make our widget inherit from the base widget
	StubAppWidget.prototype = new WH.dashboard.DashboardWidget();

	// Instantiate this widget
	widget = new StubAppWidget();

	// Listen for data updates from the server
	WH.dashboard.registerDataListener('StubAppWidget', widget);

	return widget;
})(jQuery);


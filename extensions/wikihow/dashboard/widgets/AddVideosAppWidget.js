// Add this widget to the WH.dashboard module
WH.dashboard.AddVideosAppWidget = (function($) {

	// Make aliases for things we use a lot
	var animateUpdate = WH.dashboard.animateUpdate,
		animateUpdateImage = WH.dashboard.animateUpdateImage,
		unpatrolledNode = null,
		completedNode = null,
		lastImage = null,
		lastName = null,
		lastTime = null;

	// Our new widget class
	function AddVideosAppWidget() {

		this.getWidgetName = function(){
			return "AddVideosAppWidget";
		}

		// Called by WH.dashboard.init after it's done running
		this.init = function() {
			unpatrolledNode = $('.comdash-widget-AddVideosAppWidget .comdash-count span');
			lastImage = $('.comdash-widget-AddVideosAppWidget .comdash-lastcontributor .avatar');
			lastName = $('.comdash-widget-AddVideosAppWidget .comdash-lastcontributor .name');
			lastTime = $('.comdash-widget-AddVideosAppWidget .comdash-lastcontributor .time');
			completedNode = $('.cd-rcw-completed');
		};

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
	AddVideosAppWidget.prototype = new WH.dashboard.DashboardWidget();

	// Instantiate this widget
	widget = new AddVideosAppWidget();

	// Listen for data updates from the server
	WH.dashboard.registerDataListener('AddVideosAppWidget', widget);

	return widget;
})(jQuery);


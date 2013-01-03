// Add this widget to the WH.dashboard module
WH.dashboard.WriteAppWidget = (function($) {

	// Make aliases for things we use a lot
	var animateUpdate = WH.dashboard.animateUpdate,
		animateUpdateImage = WH.dashboard.animateUpdateImage,
		unpatrolledNode = null,
		completedNode = null,
		lastImage = null,
		lastName = null,
		lastTime = null;

	// Our new widget class
	function WriteAppWidget() {

		this.getWidgetName = function(){
			return "WriteAppWidget";
		}

		// Called by WH.dashboard.init after it's done running
		this.init = function() {
			unpatrolledNode = $('.comdash-widget-WriteAppWidget .comdash-count span');
			lastImage = $('.comdash-widget-WriteAppWidget .comdash-lastcontributor .avatar');
			lastName = $('.comdash-widget-WriteAppWidget .comdash-lastcontributor .name');
			lastTime = $('.comdash-widget-WriteAppWidget .comdash-lastcontributor .time');
			completedNode = $('.comdash-widget-WriteAppWidget .comdash-today');

			/*$('.comdash-widget-WriteAppWidget .comdash-start').click(function(){
				gatTrack('comm_engagement', 'request_start', 'dashboard');
			});
			$('.comdash-widget-WriteAppWidget .comdash-login').click(function(){
				gatTrack('comm_engagement', 'request_login', 'dashboard');
			});*/
		};

		// Called by WH.dashboard after new data has been downloaded from
		// the server.
		//
		// @param type either 'global' or 'user'
		this.listenData = function(type, data) {
			if (type == 'global') {
				var unpatrolled = data['ct'];
				var img = this.getAvatarLink(data['lt']['im'], data['lt']['hp']);
				var userLink = this.getUserLink(data['lt']['na']);

				//get weather
				var weatherIcon = this.getWeatherIcon(unpatrolled);
				this.animateUpdateWeather(weatherIcon);

				animateUpdate(unpatrolledNode, unpatrolled, this.getWidgetName());
				animateUpdateImage(lastImage, img);
				animateUpdate(lastName, userLink, this.getWidgetName());
				animateUpdate(lastTime, data['lt']['da'], this.getWidgetName());
			} else if (type == 'user') {
				var completion = data;
				$(completedNode).show();
			}
		};

	}

	// Make our widget inherit from the base widget
	WriteAppWidget.prototype = new WH.dashboard.DashboardWidget();

	// Instantiate this widget
	widget = new WriteAppWidget();

	// Listen for data updates from the server
	WH.dashboard.registerDataListener('WriteAppWidget', widget);

	return widget;
})(jQuery);


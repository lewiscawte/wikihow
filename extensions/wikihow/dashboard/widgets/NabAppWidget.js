// Add this widget to the WH.dashboard module
WH.dashboard.NabAppWidget = (function($) {

	// Make aliases for things we use a lot
	var animateUpdate = WH.dashboard.animateUpdate,
		animateUpdateImage = WH.dashboard.animateUpdateImage,
		unpatrolledNode = null,
		completedNode = null,
		lastImage = null,
		lastName = null,
		lastTime = null;

	// Our new widget class
	function NabAppWidget() {

		this.getWidgetName = function(){
			return "NabAppWidget";
		}

		// Called by WH.dashboard.init after it's done running
		this.init = function() {
			unpatrolledNode = $('.comdash-widget-NabAppWidget .comdash-count span');
			lastImage = $('.comdash-widget-NabAppWidget .comdash-lastcontributor .avatar');
			lastName = $('.comdash-widget-NabAppWidget .comdash-lastcontributor .name');
			lastTime = $('.comdash-widget-NabAppWidget .comdash-lastcontributor .time');
			completedNode = $('.comdash-widget-NabAppWidget .comdash-today');

			$('.comdash-widget-NabAppWidget .comdash-start').click(function(){
				gatTrack('comm_engagement', 'addimages_start', 'dashboard');
			});
			$('.comdash-widget-NabAppWidget .comdash-login').click(function(){
				gatTrack('comm_engagement', 'addimages_login', 'dashboard');
			});
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
				$(completedNode).show();
			}
		};

	}

	// Make our widget inherit from the base widget
	NabAppWidget.prototype = new WH.dashboard.DashboardWidget();

	// Instantiate this widget
	widget = new NabAppWidget();

	// Listen for data updates from the server
	WH.dashboard.registerDataListener('NabAppWidget', widget);

	return widget;
})(jQuery);


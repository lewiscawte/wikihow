// Add this widget to the WH.dashboard module
WH.dashboard.NabAppWidget = (function($) {

	// Make aliases for things we use a lot
	var animateUpdate = WH.dashboard.animateUpdate,
		unpatrolledNode = null,
		completedNode = null,
		lastImage = null,
		lastName = null,
		lastTime = null;

	// Our new widget class
	function NabAppWidget() {

		// Called by WH.dashboard.init after it's done running
		this.init = function() {
			unpatrolledNode = $('.comdash-widget-NabAppWidget .comdash-count span');
			lastImage = $('.comdash-widget-NabAppWidget .comdash-lastcontributor .avatar');
			lastName = $('.comdash-widget-NabAppWidget .comdash-lastcontributor .name');
			lastTime = $('.comdash-widget-NabAppWidget .comdash-lastcontributor .time');
			completedNode = $('.cd-rcw-completed');
		};

		// Called by WH.dashboard after new data has been downloaded from
		// the server.
		//
		// @param type either 'global' or 'user'
		this.listenData = function(type, data) {
			if (type == 'global') {
				var unpatrolled = data['ct'];
				animateUpdate(unpatrolledNode, unpatrolled);
				animateUpdate(lastImage, data['lt']['im']);
				animateUpdate(lastName, data['lt']['na']);
				animateUpdate(lastTime, data['lt']['da']);
			} else if (type == 'user') {
				var completion = data;
				animateUpdate(completedNode, completion);
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


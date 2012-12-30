if (WH.dashboard && !WH.dashboard.DashboardWidget) {
	WH.dashboard.DashboardWidget = function () {
		this.listenData = function(type, data) {
			// this method needs to be overridden
		};
	};
}


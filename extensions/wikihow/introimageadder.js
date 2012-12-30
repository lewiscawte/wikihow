/*
 * Intro Image Adder Class
 */

function IntroImageAdder() {
	this.m_title = '';
	this.m_searchterms = '';
}

IntroImageAdder.prototype.init = function () {
	//introImageAdder.getMessage();
	//introImageAdder.getStats();
	introImageAdder.getArticle();
}

IntroImageAdder.prototype.getStats = function () {
	var url = '/Special:IntroImageAdder?fetchStats=true';

	jQuery.get(url, function (data) {
		var json = jQuery.parseJSON(data);
		jQuery('#iia_stats_today').html( json['today'] );
		jQuery('#iia_stats_week').html( json['week'] );
		jQuery('#iia_stats_all').html( json['all'] );
		if (json['standing'] == 0) {
			jQuery('#iia_stats_standing').html('NA');
		} else {
			jQuery('#iia_stats_standing').html( json['standing'] );
		}

		if (pastmessages.indexOf( json['message'] ) == -1) {
			jQuery('#iia_msg').html( json['message'] );
			pastmessages.push( json['message'] );
		} else {
			jQuery('#iia_msg').html( json['defaultmsg'] );
		}
	});
}

IntroImageAdder.prototype.getMessage = function () {
	var url = '/Special:IntroImageAdder?fetchMessage=true';

	jQuery.get(url, function (data) {
		var json = jQuery.parseJSON(data);
		jQuery('#iia_msg').html(json);
	});
}

IntroImageAdder.prototype.updateStandingsTable = function() {
	var url = '/Special:IntroImageAdder?standingsTable=true';

	jQuery.get(url, function (data) {
		jQuery('#iia_standings_table').html(data);
	});
}

IntroImageAdder.prototype.getArticle = function() {
	var url = '/Special:IntroImageAdder?fetchArticle=true';
	var e = jQuery('#article_title');
	if (e.html()) {
		url += '&skip=' + encodeURIComponent(e.html());;
	}	
	var title = '';
	var searchterms = '';

	jQuery.get(url, function (data) {
		var json = jQuery.parseJSON(data);
		title = json['title'];
		aURL = json['url'];
		searchterms = json['terms'];
		aid = json['aid'];

		//EasyImageUpload.prototype.doEIU_IIA( title, searchterms, 'iia_main', 'intro');
		easyImageUpload.doEIU_IIA(title, searchterms, 'iia_main', 'intro');
		window.setTimeout(introImageAdder.getStats, 1000);
	});

}

IntroImageAdder.prototype.displayConfirmation = function( ret ) {
	if (getCookie('iia_modal') == null) {
		var url = '/Special:IntroImageAdder?confirmation=1&iiatitle='+ret['title']+'&imgtag='+ret['img'];

		jQuery('#img-box').load(url, function() {
			jQuery('#img-box').dialog({
			   width: 450,
			   modal: true,
			   title: 'Intro Image Adder Confirmation',
			   show: 'slide',
				closeOnEscape: true,
				position: 'center'
			});
		});
		
	}
}

IntroImageAdder.prototype.closeConfirmation = function() {
	if (jQuery('#confirmModalFlag').attr('checked')) {
		setCookie('iia_modal', 1, 365);
	}
	jQuery('#img-box').dialog('close');
}

var introImageAdder = new IntroImageAdder();
setInterval('introImageAdder.updateStandingsTable()', 600000);


window.setTimeout(updateWidgetTimer, 60*1000);

function updateWidgetTimer() {
	updateTimer('stup');
	window.setTimeout(updateWidgetTimer, 60*1000);
}

function updateTimer(id) {
    var e = jQuery("#" + id);
    var i = parseInt(e.html());
    if (i > 1) {
       e.fadeOut(400, function() {
           i--;
           e.html(i);
           e.fadeIn();
        });
    }
}


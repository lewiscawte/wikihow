if ( typeof console == 'undefined' ) {
	console = {};
}
if ( typeof console.log == 'undefined' ) {
	console.log = {};
}

if ( typeof WH == 'undefined' ) {
	WH = {};
}

WH.wikihowAds = ( function( $ ) {

	var adsSet = false,
		adArray = {},
		adIndex = 0,
		adUnitArray = {},
		adCount = {
			'intro': 1,
			'0': 1,
			'1': 3,
			'2': 3,
			'2a': 3,
			'5': 1,
			'7': 2
		};

	function loadAds( channels ) {
		google_max_num_ads = '15';
		google_ad_channel = gchans + channels;
		google_ad_client = 'pub-9543332082073187';
		google_ad_output = 'js';
		google_ad_type = 'text';
		google_feedback = 'on';
		google_ad_region = 'test';
		google_ad_format = '250x250_as';
		document.write( '<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></' + 'script>' );
	}

	/**
	 * Get the "Ads by Google" string in the wiki's content language
	 * This should probably be rewritten to use ResourceLoader one day...
	 */
	function localizeAdsByLine() {
		var googleAds = 'Ads by Google';
		if( wgContentLanguage == 'es' ) {
			googleAds = 'Anucios Google';
		} else if ( wgContentLanguage == 'fi' ) {
			googleAds = 'Google-mainokset';
		} else if( wgContentLanguage == 'pt' ) {
			googleAds = 'Anúncios Google';
		}
		return googleAds;
	}

	function setAds( google_ads ) {
		adsSet = true;
		adArray = google_ads;

		for( adUnit in adUnitArray ) {
			if( !adUnitArray[adUnit].done ) {
				setupAd( adUnit );
				adUnitArray[adUnit].done = true;
			}
		}
	}

	function setupAd( adId ) {
		var ad;
		if( adIndex < adArray.length ) {
			if( adId == '2' || adId == '2a' ) {
				var adHtml = s = '<div class="adunit adunitp2"><div id="adunit1" ' +
					adPadding + '><p style="margin:0 0 5px 0; padding:0; font-size:1em;"><a href="'
					+ google_info.feedback_url + '" ' + adColor + '>' + localizeAdsByLine() + '</a></p>';
			} else if( adId == '5' ) {
				discussionAd( adId );
				return;
			} else if( adId == '7' ) {
				var adHtml = s = '<div class="horiztonalAdContainer"><div class="adunit adunitp0"><div id="adunit1" ' +
					adPadding + '><p style="margin:0 0 5px 0; padding:0; font-size:1em;"><a href="'
					+ google_info.feedback_url + '" ' + adColor + '>' + localizeAdsByLine() + '</a></p>';
			} else {
				var adHtml = s = '<div class="adunit adunitp0"><div id="adunit1" ' +
					adPadding + '><p style="margin:0 0 5px 0; padding:0; font-size:1em;"><a href="'
					+ google_info.feedback_url + '" ' + adColor + '>' + localizeAdsByLine() + '</a></p>';
			}

			for( var i = 0; i < adCount[adId]; i++ ) {
				ad = adArray[adIndex];
				adIndex++;

				if( ad != undefined ) {
					s += '<div class="ad1"' + adText + '>'+ '<h4><a href="' +
						ad.url + '"' + adTitle + ' target="_blank">'
						+ ad.line1 + '</a></h4> '
						+ ad.line2 + ' ' + ad.line3 + '<br />' +
						'<a href="' + ad.url + '" ' + adUrl + ' target="_blank">' +
						ad.visible_url + '</a></div>';
				}
			}

			if( adId == '7' ) {
				s += '<div style="clear: both;"></div></div></div></div>';
			} else {
				s += '</div></div>';
			}

			$( '#wikihowad_' + adId ).html( s );
		}
	}

	function discussionAd( adId ) {
		s = '<div class="de"><div style="background-color: #eee; height: 24px; width: 623px;"><p class="de_user"><a class="new" href="'
			+ google_info.feedback_url + '" style="">' + localizeAdsByLine() + '</a></p></div><div style="padding: 15px;">';
		for( var i = 0; i < adCount[adId]; i++ ) {
			ad = adArray[adIndex];
			adIndex++;

			s += '<div class="ad1">'+ '<h4><a href="' + ad.url + '" target="_blank">'
						+ ad.line1 + '</a></h4> '
			+ ad.line2 + ' ' + ad.line3 + '<br />' +
			'<a href="' + ad.url + '" target="_blank">' + ad.visible_url + '</a></div>';

		}
		s += '</div><div class="de_reply"> </div></div>';

		$( '#wikihowad_' + adId ).html( s );
	}

	function addUnit( adId ) {
		var unit = {};
		unit.id = adId;
		unit.done = false;
		adUnitArray[adId] = unit;

		if( adsSet ) {
			setupAd( adId );
			adUnitArray[adId].done = true;
		}

	}

	function getAdsSet() {
		return adsSet;
	}

	return {
		setAds: setAds,
		addUnit: addUnit,
		setupAd: setupAd,
		getAdsSet: getAdsSet
	};

})( jQuery );
var mobileWikihow = (function () {
	var contentTabs = ['ingredients', 'steps', 'thingsyoullneed', 'tips', 'warnings', 'video'];
	var contentDropdowns = ['ingredients', 'steps', 'thingsyoullneed', 'tips', 'warnings', 'video', 'relatedwikihows', 'sources'];

	function initQGCTA() {
		var isMQG = document.location.pathname.indexOf('Special:MQG') != -1;
		var now = new Date();
		var mqgTypes = ['pic', 'rating', 'video', 'yesno', 'recommend'];
		// Show the CTA about 10% of the time
		if (!isMQG && now.getMilliseconds() % 20 == 0) {
		//if (true) {
			var ctaLink = $('#qg_intro_link');
			now = new Date();
			var pos = now.getMilliseconds() % 5;
			var href = ctaLink.attr('href') + '?qc_type=' + mqgTypes[pos];
			ctaLink.attr('href', href);
			$('#qg_cta').delay(200).slideDown('fast');
		}
	}

	function initAppLink() {
		var uagent = navigator.userAgent || '';
		if (uagent.match(/android/i)) {
			$('#mobile_app_android').show();
		} else if (uagent.match(/iphone/i)) {
			$('#mobile_app_iphone').show();
		}
	}

	// If we are on iPhone, scroll down and hide URL bar
	function iphoneHideUrlBar() {
		if (/iphone|ipod/i.test(navigator.userAgent) > 0) {
			setTimeout( function () {
				window.scrollTo(0, 1);
			}, 0);
		}
	}

	function hideDropHeadings(clickedTab) {
		$('div[id^=drop-heading-]').show();
		$('#drop-heading-' + clickedTab).hide();

		// Tips and warnings show on the same tab. Hide warnings section if 
		// tips is clicked
		if(clickedTab == 'tips') {
			$('#drop-heading-warnings').hide();
		}
	}

	// singleton class
	return {
		startup: function() {
			$(document).ready( function() {
				initQGCTA();
				initAppLink();
				if (typeof WH != "undefined"  && typeof WH.CheckMarks != "undefined") {
					WH.CheckMarks.init();
				}

				// hide URL bar on iphone
				iphoneHideUrlBar();

				// Initial case when step tab loads.  No click so manually send in
				// steps as the clicked tab.
				hideDropHeadings('steps');

				// add click handlers -- tabs
				$.each(contentTabs, function(i, clickedTab) {
					if (clickedTab == 'warnings') return;
					$('#tab-' + clickedTab).click( function(e) {
						hideDropHeadings(clickedTab);
						$.each(contentTabs, function(j, tab) {
							var thisTab = (tab == clickedTab || clickedTab == 'tips' && tab == 'warnings');
							var node = $('#tab-content-' + tab);
							if (thisTab) {
								if (clickedTab == 'steps') {
									$('#drop-heading-steps').hide();
								} else {
									$('#drop-heading-steps').show();
								}
								node.addClass('content-show');
							} else {
								node.removeClass('content-show');
							}
							$(this).toggleClass('active');

							if (tab == 'warnings') return;
							if (thisTab) {
								$('#tab-' + tab).addClass('active');
							} else {
								$('#tab-' + tab).removeClass('active');
							}
						});
						e.preventDefault();
					});
				});

				// add click handlers -- dropdowns
				$.each(contentDropdowns, function(i, clickedDrop) {
					var drop = $('#drop-heading-' + clickedDrop);
					if (drop !== null) {
						drop.click( function(e) {
							$('#drop-heading-' + clickedDrop + ' h2').toggleClass('expanded');
							var content = $('#drop-content-' + clickedDrop);
							content.toggleClass('content-show');
							e.preventDefault();
						});
					}
				});

				// add image preview click handlers
				$('.image-zoom').click( function(e) {
					var id = e.currentTarget.id;
					var detailsID = id.replace(/^image-zoom/, 'image-details');
					var jsonDetails = $('#' + detailsID).html();
					var details = $.parseJSON(jsonDetails);
					//alert('preview: url=' + details.url + ' width=' + details.width + ' height=' + details.height);
					e.preventDefault();
					var image_obj = $('#image-src');
					image_obj.attr("src", "");
					image_obj.attr("src", details.url); 
					image_obj.css("height", details.height);
					image_obj.css("width", details.width);
					$('#image-preview .rounders').css("width", details.width);
					$('#image-preview .rounders').css("height", details.height);
					var offsetCurrent = $(e.currentTarget).offset();
					var offsetArticle = $('#article').offset();
					var preview_obj = $('#image-preview')
					preview_obj.css("top", offsetCurrent.top - offsetArticle.top - 55);
					if (navigator.userAgent.indexOf('MSIE') > 0) {
						winWidth = document.documentElement.clientWidth;
					} else {
						winWidth = window.innerWidth;
					}
					previewWidth = details.width + $('#image-preview').css('padding-left').replace("px", "")*2 + 4;//adding 4 for the border. Can't figure out how to get it via jquery
					preview_obj.css("margin-left", (winWidth - previewWidth)/2);
					preview_obj.show();
				});
			});

			$(window).load(function() {            
				/*
				if ($('.twitter-share-button').length) {
					// Load twitter script
					$.getScript("http://platform.twitter.com/widgets.js", function() {
						twttr.events.bind('tweet', function(event) {
							if (event) {                            
								var targetUrl;
								if (event.target && event.target.nodeName == 'IFRAME') {                              
									targetUrl = extractParamFromUri(event.target.src, 'url');
								}                            
								if (pageTracker) {
									pageTracker._trackSocial('twitter', 'tweet', targetUrl);                        
								}
							}
						});

					});
				}

				if($('.g-plusone').length){
					var node2 = document.createElement('script');
					node2.type = 'text/javascript';
					node2.async = true;
					node2.src = 'http://apis.google.com/js/plusone.js';
					$('body').append(node2);
				}

				// Init Facebook components
				if($('.fb-like').length){
					(function(d, s, id) {
					  var js, fjs = d.getElementsByTagName(s)[0];
					  if (d.getElementById(id)) return;
					  js = d.createElement(s); js.id = id;
					  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
					  js.async = true;
					  fjs.parentNode.insertBefore(js, fjs);
					}(document, 'script', 'facebook-jssdk'));
				}
				*/
			});
		}
	};
})();

function createCallback(that, func) {
	var params = $.makeArray(arguments).slice(2, arguments.length);
	return function() {
		func.apply(that, params);
	};
}

function closeImagePreview(){
	$('#image-preview').hide();	
}


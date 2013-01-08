var mobileWikihow = (function () {
	var contentTabs = ['ingredients', 'steps', 'thingsyoullneed', 'tips', 'warnings', 'video'];
	var contentDropdowns = ['ingredients', 'steps', 'thingsyoullneed', 'tips', 'warnings', 'video', 'relatedwikihows', 'sources'];

	// If we are on iPhone, scroll down and hide URL bar
	function iphoneHideUrlBar() {
		if (/iphone|ipod/i.test(navigator.userAgent) > 0) {
			setTimeout( function () {
				window.scrollTo(0, 1);
			}, 0);
		}
	}

	// singleton class
	return {
		startup: function() {
			$(document).ready( function() {
				// hide URL bar on iphone
				iphoneHideUrlBar();

				// add click handlers -- tabs
				$.each(contentTabs, function(i, clickedTab) {
					if (clickedTab == 'warnings') return;
					$('#tab-' + clickedTab).click( function(e) {
						$.each(contentTabs, function(j, tab) {
							var thisTab = (tab == clickedTab || clickedTab == 'tips' && tab == 'warnings');
							var node = $('#tab-content-' + tab);
							if (thisTab) {
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


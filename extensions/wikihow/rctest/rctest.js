RCTestObj = null;
RCTestObj = {};

jQuery.extend(RCTestObj, (function ($) {
	function RCTest() {
		// Button Constants
		var RESP_QUICKNOTE = 1;
		var RESP_QUICKEDIT = 2;
		var RESP_ROLLBACK = 3;
		var RESP_SKIP = 4;
		var RESP_PATROLLED = 5;
		var RESP_THUMBSUP = 6;

		this.init = function() {
			addEventHandlers();
			fakeRevisionTime();
			// If we're in a debugging mode, add some debug info
			if (extractParamFromUri(document.location.search, 'rct_mode')) {
				addDebugInfo();
			}
		}

		function addEventHandlers() {
			$('#qn_button').attr('onclick', '').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_QUICKNOTE);
			});

			$('#qe_button').attr('onclick', '').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_QUICKEDIT);
			});

			$('#rb_button').attr('onclick', '').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_ROLLBACK);
			});

			$('#skippatrolurl').attr('onclick', '').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_SKIP);
			});

			$('#markpatrolurl').attr('onclick', '').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_PATROLLED);
			});

			$('#gb_button').attr('onclick', '').click(function(e) {
				e.preventDefault();
				goback();
			});

			$('.thumbbutton').unbind('click').click(function(e) {
				e.preventDefault();
				handleQuiz(RESP_THUMBSUP);
			});
		}

		function addDebugInfo() {
			$("<div id='rct_debug' />").html('DEBUG: Test id ' + $("#rct_data").html()).insertAfter("#rct_data");	
		}

		// Fake the time of the revision since we're using an old revision
		function fakeRevisionTime() {
			var d = new Date();
			// Use seconds as the unit.  A little more random than minutes. And keep the unit less than 10
			// to better emulate actual diff times
			var unit  = d.getSeconds() % 10;
			// Make sure unit is at least two so we can make the string plural
			if (unit  < 2) 
				unit = 2;
			$('#mw-diff-ntitle2').html(unit + ' minutes ago');
			$('#mw-diff-ntitle1 a').first().html('Current revision');
		}

		function handleQuiz(response) {
			var testId = $('#rct_data').html();
			var url = '/Special:RCTestGrader?id=' + testId + '&response=' + response;
			//alert(url);
			$.get(url, function(data) {
				$('#rct_results').html(data).slideDown('fast');
				// Add an event listener to the button that is added as a result of the /Special:RCTestGrader call
				$('#rct_next_button').unbind('click').click(function(e) {
					e.preventDefault();
					skip();
					$('#rct_results').slideUp('fast');
				});
			});
		}
	}

	// Initialize the RCTest object
	var rcTest =  new RCTest();
	rcTest.init();
	return rcTest;

})(jQuery) );

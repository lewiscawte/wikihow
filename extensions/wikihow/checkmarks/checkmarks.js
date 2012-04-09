WH = WH || {};

jQuery.extend(WH, (function($) {

	function CheckMarks() {
		var messages = [];
		var numSteps = null;

		function parseJSON() {
			if (typeof(JSON) == 'object') {            
				messages = JSON.parse($('#chk_praise_data').text());     
			}
		}

		function isLastStep(li) {
			var lastLi = $('#steps ol:last').children('li:last');
			return lastLi[0] == li[0];
		}

		// Returns whether the step number if it's the 1st, 2nd or 3rd
		// Returns -1 otherwise
		function stepNum(li) {
			var stepNum = -1;
			var ul = li.parent('ul');
			if (ul.index() == 0) {
				var liStepNum = li.index() + 1;
				if (liStepNum <= 3) {
					stepNum = liStepNum;
				}
			}
			return stepNum;
		}

		function generateMessage(li) {
			var html = '';

			if (isLastStep(li) && messages.last.length) {
				// Get the last step message
				html = getMessageHtml(messages.last.pop());
			} else if (messages.msgs.length) {
				// Get a regular step message
				html = getMessageHtml(messages.msgs.pop());
			}
			return html;
		}
		
		function getMessageHtml(msg) {
			var div = $('#chk_praise_content').clone();
			$(div).find('.chk_msg').html(msg);
			return $(div).html();
		}

		function initEventListeners() {
			// CheckMark message close handler 
			$('#steps').on('click', '.chk_close', function(e) {
				e.preventDefault();
				$(this).parents('.chk_praise').slideUp('slow');
			});

			// CheckMark click handlers
			$('.step_checkbox').on('click', function() {
				var li = $(this).parent();
				if ($(this).hasClass('step_checked')) {
					$(this).removeClass('step_checked');
					li.children('.chk_praise').slideUp('slow');
					li.children('.step_content').removeClass('txt_strike');
				}
				else {
					li.children('.step_content').addClass('txt_strike');
					if (!li.children('.chk_praise').length) {
						li.append(generateMessage(li));
						li.children('.chk_praise').slideDown('slow');
					}
					$(this).addClass('step_checked');
					trackCheck(li);
				}
				return false;
			});
		}

		function trackCheck(li) {
			
			var step = stepNum(li);
			var action = '';
			if (isLastStep(li)) {
				action = 'step-last';
			} else if (step == 1 || step == 2 || step == 3) {
				action = 'step-' + step;
			} 
			try{
				if (action.length && pageTracker) {
					pageTracker._trackEvent('m-checks', action, wgTitle);
				}
			} catch(err) {}
		}

		this.init = function() {
			numSteps = $('#steps').find('li').size();
			parseJSON();
			initEventListeners();
		}
	}

	return {
		CheckMarks: new CheckMarks()
	};

	//
})(jQuery));

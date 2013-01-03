
function Slider() {
	this.m_link = '/Special:ListRequestedTopics';
	this.test = 'test';
	this.test_on = false;
}

Slider.prototype.init = function () {

	if (!slider.test_on)
		return;
		
		
	$('#sliderbox').delay(8000).animate({
		width: ['show', 'swing'],
		height: ['show', 'swing'],
		opacity: ['show', 'linear']
	}, 1000,function() {
	
		//initialize buttons/links
		slider.buttonize();

		//set a sesh cookie
		document.cookie = 'sliderbox = 1';

		//MIXPANEL track it
		if (slider.test_on)
			mpmetrics.track("NEAT appear " + slider.test);
		
		//log it!
		//slider.log('appear');
	});

}

Slider.prototype.buttonize = function() {
	$('#sliderbox h2 a').click(function(e) {
		e.preventDefault();
		slider.closeSlider();
		//MIXPANEL track it
		if (slider.test_on)
			mpmetrics.track("NEAT x-button " + slider.test);
		//slider.log('x-button');
	});
	$('#slider_no a').click(function(e) {
		slider.closeSlider();
		//MIXPANEL track it
		if (slider.test_on)
			mpmetrics.track("NEAT no-link " + slider.test);
		//slider.log('no-link');
	});
	$('#slider_start_link').click(function(e) {
		e.preventDefault();
		window.location = slider.m_link;
		//MIXPANEL track it
		if (slider.test_on)
			mpmetrics.track("NEAT start-link " + slider.test);
		//slider.log('start-link');
	});
	$('#slider_start_button').click(function(e) {
		e.preventDefault();
		window.location = slider.m_link;
		//MIXPANEL track it
		if (slider.test_on)
			mpmetrics.track("NEAT start-button " + slider.test);
		//slider.log('start-button');
	});
}

Slider.prototype.closeSlider = function() {
	$('#sliderbox').animate({
		width: ['hide', 'swing'],
		height: ['hide', 'swing'],
		opacity: ['hide', 'linear']
	}, 1000);
	
	//set a 30-day cookie
	var exdate = new Date();
	var expiredays = 30;
	exdate.setDate(exdate.getDate()+expiredays);
	document.cookie = "sliderbox=2;expires="+exdate.toGMTString();
}

//let's log the choice in the database
Slider.prototype.log = function(action) {
	var url = '/Special:Slider?action='+action;
	$.get(url);
}
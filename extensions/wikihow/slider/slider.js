
function Slider() {
	this.m_link = '/Special:ListRequestedTopics';
}

Slider.prototype.init = function () {
//	$('#sliderbox').delay(8000).animate({
	$('#sliderbox').delay(1000).animate({
		width: ['show', 'swing'],
		height: ['show', 'swing'],
		opacity: ['show', 'linear']
	}, 1000,function() {
	
		//initialize buttons/links
		slider.buttonize();

		//set a sesh cookie
		document.cookie = 'sliderbox = 1';

		//MIXPANEL track it
		mpmetrics.track("NEAT appear");
		
		//log it!
		//slider.log('appear');
	});

}

Slider.prototype.buttonize = function() {
	$('#sliderbox h2 a').click(function(e) {
		e.preventDefault();
		slider.closeSlider();
		//MIXPANEL track it
		mpmetrics.track("NEAT x-button");
		//slider.log('x-button');
	});
	$('#slider_no a').click(function(e) {
		slider.closeSlider();
		//MIXPANEL track it
		mpmetrics.track("NEAT no-link");
		//slider.log('no-link');
	});
	$('#slider_start_link').click(function(e) {
		e.preventDefault();
		window.location = this.m_link;
		//MIXPANEL track it
		mpmetrics.track("NEAT start-link");
		//slider.log('start-link');
	});
	$('#sliders_start_button').click(function(e) {
		e.preventDefault();
		window.location = this.m_link;
		//MIXPANEL track it
		mpmetrics.track("NEAT start-button");
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

//var slider = new Slider();

//kick it
//if (!getCookie('sliderbox')) {
	//slider.init();
//}

var flag_request;

function flagVideo() {
	try {
		flag_request = new XMLHttpRequest();
	} catch (error) {
		try {
			flag_request = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (error) {
			return false;
		}
	}
	flag_request.open('GET', flagUrl,true);
	flag_request.send(''); 

	var e = document.getElementById('flagbutton');
	e.innerHTML = "<img src='/extensions/wikihow/dialog-warning.png' height='10px'> Thank you.";
}


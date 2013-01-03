var messages_requester;
var logged_in = false;

document.write("Starting to initialize..<br/>");

function handleCheckMessageResponse() {
document.write("Check messages received a response...<br/>");
	if ( messages_requester.readyState == 4) {
        if ( messages_requester.status == 200) {
document.write("we received a 200 message<br/>");
		}
	}
}
function checkMessages() {
    messages_requester = null;
    try {
        messages_requester = new XMLHttpRequest();
		document.write("using XMLHttpRequest<br/>");
    } catch (error) {
        try {
            messages_requester = new ActiveXObject('Microsoft.XMLHTTP');
			document.write("using Microsoft.XMLHTTP<br/>");
        } catch (error) {
			document.write("<font color=red>Warning: could not initialize messsage_requester.</font> " + error.description + "<br/>");
            return false;
        }
    }
    messages_requester.onreadystatechange = handleCheckMessageResponse;
    var url = 'http://' + window.location.hostname + '/Special:Checkmessages?source=' + window.location;
currentTime = new Date();
	document.writeln("Ok, checkMessages is requesting the proper url (" + url + ") <br>");
document.writeln("starting at " + currentTime + "<br/>");
    messages_requester.open('GET', url, false); 
    messages_requester.send('');
currentTime = new Date();
document.writeln("finishing at " + currentTime + "<br/>");
document.writeln("last status was: " + messages_requester.status + "<br/>");
displayNotifications();
displayUserLinks();
}

function displayUserLinks () {
	if (logged_in && user_links != "") 
		document.writeln("Would display user links: " + user_links + "<br/>");
}
function displayNotifications() {
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		var pair = c.split('=');
		var key = pair[0];
		var value = pair[1];
		key=key.replace(/ /, '');
		if (key == 'wiki_sharedAnnouncement') {
			value = unescape(value);
			value = value.replace(/\+/g, " ");
		document.write("displayNotifications would write this: " + value + "<br/>");
		}
	}
	if (logged_in) {
		document.write("displayNotifications: would show the login information: " +  '<a href="/Special:Mypage">' + username + '</a><br/>');	
	}
}

	var username = "";
	var is_admin = false;
	var has_session = false;
	var user_links = "";
	var ca = document.cookie.split(';');
	document.write("Cookies:<br/><table align='center' border='1' width='80%'>");
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		var pair = c.split('=');
		var key = pair[0];
		var value = pair[1];
		key=key.replace(/ /, '');
		document.write("<tr><td>" + key + "</td><td>" + value + "</td></tr>");
		if (key == 'wiki_sharedLoggedOut') {
			// safe to break		
			logged_in = false;
			break;	
		} 
		if (key == 'wiki_shared_session') {
			has_session = true;
			// continue in case we find wiki_sharedLoggedOut
		}
		if (key == 'wiki_sharedisSysop') {
			is_admin = true;
		}
		if (key == 'wiki_sharedUserName') {
			logged_in = true;
                        value = value.replace(/\+/g, " ");
			username = unescape(value);	
		}
		if (key == 'wiki_sharedUserLinks') {
                        value = unescape(value);
                        value = value.replace(/\+/g, " ");
			user_links = value;
		}
	}
	document.write("</table>");
	if (logged_in) {
		document.write("User appears to be logged in<br/>");
	} else {
		document.write("User appears to be not logged in, whups<br/>");
	}
	if (is_admin && logged_in) {
		document.write("User appears to be logged in and an admin<br/>");
	}
	if (has_session) {
		document.write("User appears to have a session<br/>");
		checkMessages();
	} else {
		document.write("Not checking messages because we don't have a session.");
	}

document.write("All done.... please copy and paste the contents of this page and send it to <a href='mailto:travis@wikihow.com'>travis</a>");


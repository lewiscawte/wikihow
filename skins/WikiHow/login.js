var messages_requester;
var logged_in = false;


function handleCheckMessageResponse() {

}
function checkMessages() {
    messages_requester = null;
    try {
        messages_requester = new XMLHttpRequest();
    } catch (error) {
        try {
            messages_requester = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (error) {
            return false;
        }
    }
    messages_requester.onreadystatechange = handleCheckMessageResponse;
    var url = 'http://' + window.location.hostname + '/Special:Checkmessages?source=' + window.location;
    messages_requester.open('GET', url); 
    messages_requester.send(' ');
}

function displayUserLinks () {
	if (logged_in && user_links != "") 
		document.writeln(user_links);
}
function displayNotifications() {
	if (logged_in) {
		var link = document.getElementById('nav_loggedin');
		if (link != null)
			link.innerHTML = '<a href="/Special:Mypage">' + username + '</a>';
	}
	var location = window.location.href;
	if (location && location.indexOf("/User_talk:" + username) > 0 ) {
		return;
	}
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
			var a = document.getElementById('announcements');
			//document.write(value);
			if (a != null) a.innerHTML = value;
		}
	}
}

	var username = "";
	var is_admin = false;
	var has_session = false;
	var user_links = "";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		var pair = c.split('=');
		var key = pair[0];
		var value = pair[1];
		key=key.replace(/ /, '');
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
	if (logged_in) {
		document.write('<style type="text/css" media="all">/*<![CDATA[*/ @import "/skins/WikiHow/loggedin.css"; /*]]>*/</style>');
	}
	if (is_admin && logged_in) {
		document.write('<style type="text/css" media="all">/*<![CDATA[*/ @import "/skins/WikiHow/admin.css"; /*]]>*/</style>');
	}
	if (has_session) {
		checkMessages();
	}



var article = "";
var user = "";
var maxChar = 10000;

var users;
var regdates;
var contribs;

function switchUser() {
	var i = $('#userdropdown').val() ;
	$('#qnTarget').val("User_talk:"+users[i]);
	html = "";
    if (contribs[i] != 0) {
        html += users[i] +" has <b>"+contribs[i]+"</b> edits";
        if (regdates[i]!= "") {
            html +=  " and joined us on <b>"+regdates[i]+"</b>";
        }
        html += ". ";
    }
	$('#contribsreg').html(html);
}

function initQuickNote( qnArticle, qnUser, contrib, regdate ) {
	article = urldecode(qnArticle);

	var mesid = document.getElementById('comment_text');
	var message = qnMsgBody.replace(/\<nowiki\>|\<\/nowiki\>/ig, '');
	message = message.replace(/\[\[ARTICLE\]\]/, '[['+article+']]');
	mesid.value = message;
	maxChar2 = maxChar + message.length;

	users 		= qnUser.split("|");
	regdates 	= regdate.split("|");
	contribs 	= contrib.split("|");

	html = "Leave a quick note for ";

	if (users.length > 1) {
		html += "<select id='userdropdown' onchange='switchUser();'>";
		for (i = 0; i < users.length; i++) {
			html += "<OPTION value='" + i + "'>" + users[i] + "</OPTION>";
		}
		html += "</select>";
	} else { 
		html += "<input type='hidden' name='userdropdown' id='userdropdown' value'" + users[0] +"'/><b>" + users[0] + "</b>."
	}
	html += "<br/><span id='contribsreg'>";	

	$('#qnTarget').val("User_talk:"+users[0]);

	if (contrib[0] != 0) {
		html += users[0] +" has <b>"+contribs[0]+"</b> edits";
		if (regdates[0] != "") {
			html +=  " and joined us on <b>"+regdates[0]+"</b>";
		}
		html += ". </span><br />\n";
	}
	var editorid = $('#qnEditorInfo');
	editorid.html(html);

	document.getElementById('modalPage').style.display = 'block';
	return false;
}

function qnClose() {
	document.getElementById('modalPage').style.display = 'none';
}

function qnButtons(pc_newmsg, obj, tmpl) {
	var mesid = document.getElementById('comment_text');
	var message = tmpl.replace(/\[\[ARTICLE\]\]/, '[['+article+']]');
	mesid.value = message;
	
	postcommentPublish(pc_newmsg, obj);
	document.getElementById('modalPage').style.display = 'none';
	return false;
}

function qnSend(pc_newmsg, obj) {
	var commentid = document.getElementById('comment_text');

	if (commentid.value.length > maxChar2) {
		alert("Your message is too long.  Please delete "+(commentid.value.length - maxChar2)+" characters.");
	} else {
		postcommentPublish(pc_newmsg, obj);

		var button = document.getElementById("postcommentbutton_" + pc_newmsg.replace(/postcomment_newmsg_/, ''));
		if (button) { button.disabled = false; }

		document.getElementById('modalPage').style.display = 'none';
	}
	return false;
}

function qnCountchars(obj) {
	//var countid = document.getElementById('qnCharcount');

	//while(obj.value.length>maxChar2){
	//	obj.value=obj.value.replace(/.$/,'');//removes the last character
	//}

	//countid.innerHTML = (maxChar2 - obj.value.length) + " Characters Left";

	return false;
}


//###########################

function urldecode( str ) {
    // Decodes URL-encoded string
    // 
    // +    discuss at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_urldecode/
    // +       version: 901.1411
    // +   original by: Philip Peterson
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: AJ
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir
    // %          note: info on what encoding functions to use from: http://xkr.us/articles/javascript/encode-compare/
    
    var histogram = {};
    var ret = str.toString();
    
    var replacer = function(search, replace, str) {
        var tmp_arr = [];
        tmp_arr = str.split(search);
        return tmp_arr.join(replace);
    };
    
    // The histogram is identical to the one in urlencode.
    histogram["'"]   = '%27';
    histogram['(']   = '%28';
    histogram[')']   = '%29';
    histogram['*']   = '%2A';
    histogram['~']   = '%7E';
    histogram['!']   = '%21';
    histogram['%20'] = '+';

    for (replace in histogram) {
        search = histogram[replace]; // Switch order when decoding
        ret = replacer(search, replace, ret) // Custom replace. No regexing   
    }
    
    // End with decodeURIComponent, which most resembles PHP's encoding functions
    ret = decodeURIComponent(ret);

    return ret;
}

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

function initQuickNote( qnArticle, qnUser, contrib, regdate, qnArticleLink ) {
	article = urldecode(qnArticle);
	
	if (!qnArticleLink) { qnArticleLink = ""; }	
	articlelink = urldecode(qnArticleLink);
	
	var mesid = document.getElementById('comment_text');
	var message = qnMsgBody.replace(/\<nowiki\>|\<\/nowiki\>/ig, '');
	
	//If this is an image, then we want to link to it, so need an extra :
	if(article.indexOf("Image:") == 0)
		message = message.replace(/\[\[ARTICLE\]\]/, '[[:'+article+']]');
	else
		message = message.replace(/\[\[ARTICLE\]\]/, '[['+article+']]');

	//for QG
	//message = message.replace(/\[\[ARTICLELINK\]\]/, '['+articlelink.replace(/ /g,'-')+' '+article+']');
	message = message.replace(/\[\[ARTICLELINK\]\]/, articlelink.replace(/ /g,'-'));
	
	mesid.value = message;
	maxChar2 = maxChar + message.length;

	users 		= qnUser.split("|");
	regdates 	= regdate.split("|");
	contribs 	= contrib.split("|");

	//html = "Leave a quick note for ";
	html = wfMsg('qn_note_for') + " ";

	if (users.length > 1) {
		html += "<select id='userdropdown' onchange='switchUser();'><OPTION></OPTION>";
		for (i = 0; i < users.length; i++) {
			html += "<OPTION value='" + i + "'>" + users[i] + "</OPTION>";
		}
		html += "</select>";
	} else { 
		html += "<input type='hidden' name='userdropdown' id='userdropdown' value'" + users[0] +"'/><b>" + users[0] + "</b>."
		$('#qnTarget').val("User_talk:"+users[0]);
	}
	html += "<br/><span id='contribsreg'>";	

	var editorid = $('#qnEditorInfo');
	editorid.html(html);

	if ($('#thumbUp').length && !isThumbedUp) {
		$('#qn_thumbsup').show();
		$('input[name="qn_thumbs_check"]').attr('checked', false);
	}
	else {
		$('#qn_thumbsup').hide();
	}

	document.getElementById('modalPage').style.display = 'block';
	return false;
}

function qnClose() {
	document.getElementById('modalPage').style.display = 'none';
}

function qnButtons(pc_newmsg, obj, tmpl) {
	var mesid = document.getElementById('comment_text');
	var message = tmpl.replace(/\{\{\{1\}\}\}/, '[['+article+']]');
	mesid.value = message;

	//no longer want to automatically publish
	//postcommentPublish(pc_newmsg, obj);
	//document.getElementById('modalPage').style.display = 'none';
	return false;
}

function checkThumbsUp() {
	if ($('#qn_thumbsup').is(":visible")) {
		$('input[name="qn_thumbs_check"]').attr('checked', true);
	}
}

function qnSend(pc_newmsg, obj) {
	var commentid = document.getElementById('comment_text');

	if($('#qnTarget').val()  == '') {
		alert('Please select a user to send the quick note.'); 
		return false;
	}

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

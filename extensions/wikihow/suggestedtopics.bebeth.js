var howtoDefaultText = "How to...";

jQuery(document).ready(function(){
	jQuery('#entry_howto').focus(howtoFocus);
	jQuery('#entry_howto').blur(howtoBlur);
});

function howtoFocus(){
	if(jQuery(this).val() == howtoDefaultText)
		jQuery(this).val("");
}

function howtoBlur(){
	if(jQuery(this).val() == "")
		jQuery(this).val(howtoDefaultText);
}

function changeCat() {
	location.href='/Special:ListRequestedTopics?category=' + escape(document.getElementById('suggest_cat').value);
}

var gId = null;

function saveSuggestion() {
	var n = document.getElementById('newsuggestion').value;
	document.suggested_topics_manage["st_newname_" + gId].value = n;
	document.getElementById("st_display_id_" + gId).innerHTML= n;
    for (i=0;i<document.suggested_topics_manage.elements.length;i++) {
        if (document.suggested_topics_manage.elements[i].type ==    'radio'
            && document.suggested_topics_manage.elements[i].name == 'ar_' + gId
            && document.suggested_topics_manage.elements[i].value == 'accept') {
            document.suggested_topics_manage.elements[i].checked = true;
        }
    }
	closeModal();
}

var gName = null;
function setName() {
	if (document.getElementById('newsuggestion'))
		document.getElementById('newsuggestion').value = gName;
	else
		window.setTimeout("setName()", 100);
}
function editSuggestion(id) {
	popModal('/Special:RenameSuggestion', '500', '90');
	var e = document.getElementById('st_display_id_' + id);
	gName = e.innerHTML;
	gId = id;
	setName();
	return false;
}

function checkSTForm() {
	if (document.suggest_topic_form.suggest_topic.value =='') {
		alert(gEnterTitle);
		return false;
	}
	if (document.suggest_topic_form.suggest_category.value =='') {
		alert(gSelectCat);
		return false;
	}
	if (document.suggest_topic_form.suggest_email_me_check.checked && document.suggest_topic_form.suggest_email.value =='') {
		alert(gEnterEmail);
		return false;
	}
	return true;
}

 function choose_cat(key) {
 	var e = $("#" + key);
 	if (e.hasClass('not_chosen')) {
 		e.removeClass('not_chosen');
 		e.addClass('chosen');
 		document.suggest_cats.cats.value += ", " + key;
 	} else {
 		e.removeClass('chosen');
 		e.addClass('not_chosen');
 		var reg = new RegExp (key, "g");
 		document.suggest_cats.cats.value = document.suggest_cats.cats.value.replace(reg, '');
 	}
 }

function reloadTopRow(){
	$("#top_suggestions_top").fadeOut(400, function() {
		if (Math.random() < 0)  {
			// bat boy easter egg!
		    jQuery('#img-box').html('<center><img src="http://www.freakingnews.com/images/contest_images/bat-boy.jpg" style="height: 400px;"/><br/>');
    		//jQuery('#img-box').load(url);
    		jQuery('#img-box').dialog({
        		modal: true,
				width: 400,
        		title: 'Surprise!',
        		show: 'slide',
        		closeOnEscape: true,
        		position: 'center'
    		});
		}
		$("#top_suggestions_top").load('/Special:RecommendedArticles/TopRow',
            	function () {
				 	$("#top_suggestions_top").fadeIn();
            	}
        	);
	}
	);
}


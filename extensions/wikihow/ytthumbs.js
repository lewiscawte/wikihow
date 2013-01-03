var currentStep = 0;
var ytParams = null;
var ytLoaded = false; 

function ytRemove(step) {
alert('removing ' + step);
	$("#ytadd_" + step).html("<input type='image' id='add_" + step + "' src='/extensions/wikihow/h5e/i/add_image_cam.png' onclick='ytHandler(" + step + ");'/><div class='clearall'></div>");
	ytParams['step' + currentStep] = null;
	return false;
}

function ytImage(url) {
	$("#add_" + currentStep).hide(); 
	$("#ytadd_" + currentStep).html("<img style='width: 200px;' src='" + url + "'/><br/><a href='#' onclick='return ytRemove(" + currentStep + ");'>Remove</a>"); 
	$("#img-box").dialog("close");
	ytParams['step' + currentStep] = url;
	return false;
}

function ytHandler(i) {
	currentStep = i; 
	var url = "http://" + window.location.hostname + "/Special:YTThumb/" + wgPageName;

	var modalParams = {
		width: 650,
		height: 500,
		title: "Add Photos",
		modal: true,
		draggable: true,
		position: 'center'
	};
	if (!ytLoaded) {
		$('#img-box').load(url, function() {
				$("#img-box").dialog(modalParams);
				ytLoaded = true;
			}
		);
	} else {
		$("#img-box").dialog(modalParams);
	}
	return true; 
}

function submitYT() {
	ytParams['target'] = wgPageName; 
	$("#img-box").html("<center><img src='/extensions/wikihow/rotate.gif'></center>");
	$("#img-box").dialog(
		{
			width: 250,
			height: 120,
			title: "Uploading...",
			modal: true,
			position: 'center',
		}
	);

	$.post("/Special:YTThumb", ytParams, function (result) {
			$("#img-box").dialog("close");
			if (result['error']) {
				$("#img-box").html(result['error']);
				$("#img-box").dialog(
					{
						width: 250,
						height: 150,
						title: "Error uploading images",
						modal: true,
						position: 'center',
					}
				);
			} else {
				//alert(result); alert(result['success']);	
				window.location.href = result['success'];
			}
		}
		, 'json'
	);
}

var yt_run = false;

function ytThumb() {
	if (yt_run) {
		return false;
	}

	var url = "http://" + window.location.hostname + "/Special:YTThumb/" + wgPageName;

	$("#steps li").each(function(index) {
			if ($(this).html().indexOf("<img") < 0) {	
				$(this).html("<table style='width: 580px;' ><tr><td valign='top'>" + $(this).html() + "</td><td valign='top'align='right'><div class='ytplacement' id='ytadd_" + index + "'><input type='image' id='add_" + index + "' src='/extensions/wikihow/h5e/i/add_image_cam.png' onclick='ytHandler(" + index + ");'/><div class='clearall'></div></div></td></tr></table>");
				$(this).css("margin-left", "80px");
			}
		}
	);

	$('body').append('<div id="edit_page_footer" style="text-align:center; font-weight: bold;"><div style="float: left; padding-top: 15px; padding-left: 300px; padding-right: 10px;">When you are done, click:</div><div style="padding-top: 10px; "><input type="submit" style="float: left; margin-right: 0px;" onmouseover="button_swap(this);" onmouseout="button_unswap(this);" class="button button100 submit_button" value="Upload" onclick="submitYT();"> or <a href="#" onclick="return window.location.reload();">Cancel</a></div></div>');
	ytParams = { };
	yt_run = true;

	return false;
}

$("document").ready(function() {
	if (typeof(wgYTThumbs) != "undefined" && wgYTThumbs) {
		$("#editing_list").append("<li><a href='#' onclick='return ytThumb();'>Add step by step photos from Youtube</a></li>");

		$("h2").each(function() {
			if ($(this).next().attr("id") == "steps") {
				$(this).append("<img src='/extensions/wikihow/youtube-logo-14.gif' style='float:right; margin-top: 7px; margin-right: 7px; width:24px;' onclick='ytThumb();'/>");
			}
		});
	}
}
);


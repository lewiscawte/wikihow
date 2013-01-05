
var modalParams = {
	width: 450,
	height: 200,
	title: "Notifications",
	modal: true,
	position: 'center'
};

$(document).ready(function () {
	var html = "You have just embedded a Youtube video. Would you like us to notify you ";
	html += "with a talk page message when we have generated thumbnails for this video? <br/><br/><center> ";
	html += '<a onmouseout="button_unswap(this);" onmouseover="button_swap(this);" style="margin-left: 100px; float: left; background-position: 0% 0pt;" class="button button100" href="#" onclick="return ytSetupNotification();">Notify Me</a>'; 
	html += "<div style='float: left; padding-top: 5px; padding-left: 10px;'>or <a href='#' onclick='return ytCloseNotify();'>Cancel</a></div></center>";
	$("#img-box").html(html); 
	$("#img-box").dialog(modalParams);
	return true; 
});

function ytCloseNotify() {
	$("#img-box").dialog('close');
	return false;
}

function ytSetupNotification() {
	//$("#img-box").dialog('close');
	var url = "/Special:YTThumb?eaction=notify&id=" + wgVideoId;
	$.get(url, function() {
			var html = "Thanks! You will be notified through a talk page message when the thumbnails are ready.";
			html += "<br/><br/><center><a href='#' onclick='return ytCloseNotify();'>Close</a></center>";
			$("#img-box").html(html); 
		}
	);
	return false;
}



/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
var focusElement = "";

jQuery(document).ready(function(){

	//show the instructions if they exist
	jQuery(".input_med").focus(function(){
		id = $(this).attr("id");
		focusElement = id;
		if($("#" + id + "_error").is(":visible"))
			$("#" + id + "_showhide").val($(this).val());
		$("#" + id + "_info").show();
	});

	//hide the instructions if user isn't hovering
	//over them/
	jQuery(".input_med").blur(function(){
		id = $(this).attr("id");
		if(focusElement == id)
			focusElement = "";
		if($("#" + id + "_error").is(":visible")){
			if($("#" + id + "_showhide").val() != $(this).val())
				$("#" + id + "_error").hide();
		}
		if($("#" + id + "_showhide").val() != 1)
			$("#" + id + "_info").hide();
	});

	jQuery(".mw-info").hover(function(){
		idInfo = $(this).attr("id");
		id = idInfo.substring(0, idInfo.length - 5);
		$("#" + id + "_showhide").val(1);
	}, function(){
		idInfo = $(this).attr("id");
		id = idInfo.substring(0, idInfo.length - 5);
		$("#" + id + "_showhide").val(0);
		if(id != focusElement)
			$(this).hide();
	});
});

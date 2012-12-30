/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function(){
	var t=setTimeout("fadeImage();",5000);
})

function fadeImage(){
	$("#mock_image").fadeOut('slow', function(){
		$("#instructions").show();
	});
}
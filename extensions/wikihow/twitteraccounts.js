
function showPass(id, pass) {
	$('#pass_' + id).html(pass);	
	return false;
}

function showCats() {
	var url = '/Special:Categoryhelper?type=categorypopup';
	var url = '/x/info.php';
    var modalParams = {
        width: 650,
        height: 500,
        title: "Select a category",
        modal: true,
        position: 'center'
    };
	$('#img-box').load(url, function() {
			$("#img-box").dialog(modalParams);
		}
	);
	return false;
}
		
function twtDelete(cat, user) {
	if (confirm("Are you sure you no longer want to tweet to the twitter account " + user + " for the category " + cat + "?")) {
		window.location.href='/Special:TwitterAccounts?eaction=del&username=' + encodeURIComponent(user) + "&category=" + encodeURIComponent(cat);
	}
}

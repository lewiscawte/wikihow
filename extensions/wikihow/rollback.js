
var rb_req; 
var previousHTML = 'none';

function rollbackHandler() {
	if ( rb_req.readyState == 4) {
			span = document.getElementById('rollback-link');
			if (!span) {
				span = document.getElementById('rollback-status');
				span.innerHTML = rb_req.responseText;
				return false; 
			}
			if (rb_req.responseText.indexOf("<title>Rollback failed") > 0)
				span.innerHTML = '<br/><div style="background: red;"><b>' + msg_rollback_fail + '</b></div>';	
			else
				span.innerHTML = '<br/><div style="background: yellow;"><b>' + msg_rollback_complete + '</b></div>';	
	}
}

function cancelRollback() {
	span = document.getElementById('rollback-link');
	span.innerHTML = previousHTML;
}
function rollback () {
	var strResult;
	span = document.getElementById('rollback-link');
	if (!span) 
		span = document.getElementById('rollback-status');
	span.innerHTML = '<br/><b>' + msg_rollback_inprogress + '</b>';	
	rb_req = getRequestObject();
	rb_req.open('GET', gRollbackurl,true);
	rb_req.send(''); 
	rb_req.onreadystatechange = rollbackHandler;
	return false;
}
	

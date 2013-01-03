<?php

class FollowWidget extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage( 'FollowWidget' );
	}


	function showWidget() {

?>
	<h3><?= wfMsg('fw-header') ?></h3>
	<?= wfMsg('fw-table', wfGetPad()) ?>

<?php
	}
	
	public function getForm() {
?>
		<h3 style="margin-bottom:10px"><?= wfMsg('fw-title') ?></h3>
		<form action="#" >
			<input id="followEmail" type="text" value="" style="float:right; width:320px;" />
			<label for="followEmail">Your email:</label><br /><br />
			<p><?= wfMsg('fw-message') ?></p>
			<br />
			<a href="#" class="button button52" onmouseout="button_unswap(this);" onmouseover="button_swap(this);" style="float:right; margin-left:10px;" onclick="followWidget.submitEmail(jQuery('#followEmail').val()); return false;">OK</a> <a href="#" style="float:right; line-height:26px;" onclick="closeModal(); return false;" >Cancel</a> 
		</form>
		
		<?php
	}

	public function execute($par) {
		global $wgOut;

		wfLoadExtensionMessages('FollowWidget');


		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHTML($this->getForm());

	}

}

class SubmitEmail extends UnlistedSpecialPage {

	public function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('SubmitEmail');
	}

	public function execute($par) {
		global $wgRequest, $wgOut;
		wfLoadExtensionMessages('FollowWidget');
		
		$wgOut->disable(true);
		
		$email = $wgRequest->getVal('newEmail');
		
		if(!User::isValidEmailAddr($email)) {
			$arr = array ('success' => false, 'message' => wfMsg('invalidemailaddress') );
			echo json_encode($arr);
		
			return;
		}
		
		$dbw =& wfGetDB(DB_MASTER);
		$res = $dbw->select(
			array('emailfeed'),
			array('email'),
			array('email' => $email)
		);
		
		if($res->numRows() == 0) {
			$res = $dbw->insert(
				'emailfeed',
				array('email' => $email)
			);
			$arr = array ('success' => true, 'message' => wfMsg('fw-added') );
		} else {
			$arr = array ('success' => false,'message' => wfMsg('fw-exists') );
		}

		echo json_encode($arr);
	}
}



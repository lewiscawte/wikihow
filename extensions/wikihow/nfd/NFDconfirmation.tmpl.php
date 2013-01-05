<div class='nfd_modal'>
	<p><?= wfMsg('nfd_conf_question', $titleUrl, $title); ?> </p>
	<div style='clear:both'></div>
	<span style='float:right'>
		<input class='button blue_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='Yes' onclick='closeConfirmation(true);return false;' >
		<input class='button white_button_100 submit_button' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' type='button' value='No' onclick='closeConfirmation(false);return false;' >
	</span>
</div>
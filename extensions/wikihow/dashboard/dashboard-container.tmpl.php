<?= $cssTags ?>
<?= $jsTags ?>
<script>
WH.dashboard.allThresholds(<?= $thresholds ?>);
WH.dashboard.refreshData("global", <?= $GLOBAL_DATA_REFRESH_TIME_SECS ?>);
WH.dashboard.refreshData("user", <?= $USER_DATA_REFRESH_TIME_SECS ?>);
WH.dashboard.usernameMaxLength = <?= $USERNAME_MAX_LENGTH ?>;
WH.dashboard.priorityWidgets = <?= json_encode($priorityWidgets) ?>;
WH.dashboard.prefsOrdering = <?= json_encode($prefsOrdering) ?>;
WH.dashboard.widgetTitles = <?= json_encode($widgetTitles) ?>;
WH.dashboard.appShortCodes = <?= json_encode($appShortCodes) ?>;
</script>

<div class="comdash-container">
	<div class="comdash-top">
		<?php if($userImage != ""): ?>
		<div id="comdash-header-info"> <?= $userImage ?>
			<span class="header-line1">Thanks <?= $userName ?>!</span>
			<span class="header-line2"><?= wfMsg('cd-welcome') ?></span>
		</div>
		<? endif; ?>
		<h1 class="firstHeading" style="display:inline;">wikiHow Community</h1>
	</div>
	<div class="comdash-welcome">
		<p><?= wfMsg('cd-welcome-text'); ?></p>
		<div class="sandbox">
			<img src="<?= wfGetPad("/skins/WikiHow/images/cd_answerrequests.gif") ?>" />
			<p><?= wfMsgWikiHtml('cd-welcome-cta2'); ?></p>
		</div>
		<div class="sandbox">
			<img src="<?= wfGetPad("/skins/WikiHow/images/cd_addimages.gif") ?>" />
			<p><?= wfMsg('cd-welcome-cta1', $imageLink); ?></p>
		</div>
		<div class="clearall"></div>
	</div>
	<div class="comdash-header-priorities">
		Things to Try First
	</div>
	<div class="comdash-priorities">
		<?= call_user_func($displayWidgetsFunc, $priorityWidgets) ?>
		<div class="clearall"></div>
	</div><!--end comdash-priorities-->
	<div class="comdash-header-widgets">
		<?php if($userImage != ""): ?>
			<a id="comdash-header-customize" href="#">Customize <img src=' <?= wfGetPad('/skins/WikiHow/images/actionArrow.png') ?> ' alt=''></a>
		<? endif; ?>
		More Things to Do
	</div>
	<div class="comdash-widgets">
		<? // get the html for the user-defined list of widgets ?>
		<?= call_user_func($displayWidgetsFunc, $userWidgets) ?>
		<div class="clearall"></div>
	</div>
	<script>$(document).ready(WH.dashboard.init);</script>
	<a href="#" class="comdash-pause"><?= wfMsg('cd-pause-updates') ?></a> |
	<a href="#" class="comdash-settings"><?= wfMsg('cd-settings') ?></a>
	<div id="cd-user-box"></div>
</div><!--end comdash-container-->

<div class="cd-customize-dialog" title="<?= wfMsg('cd-customize-things-to-do') ?>">
	<div class="cust-head">
		<div class="cust-order"><?= wfMsg('cd-order') ?></div>
		<div class="cust-ttd"><?= wfMsg('cd-ttd') ?></div>
		<div class="cust-show"><?= wfMsg('cd-show') ?></div>
	</div>
	<div class="cd-customize-list">
		<ul class="cd-customize-sortable"><?php // list items go here ?></ul>
	</div>
	<div class="cd-bottom-buttons">
		<a class="cd-customize-cancel" href="#"><?= wfMsg('cd-cancel') ?></a>
		<input class="button button52 submit_button cd-customize-save" onmouseout="button_unswap(this);" onmouseover="button_swap(this);" value="<?= wfMsg('cd-save') ?>" />
	</div>
</div>

<div class="cd-network-loading">
	<?= wfMsg('cd-loading-stats') ?>
</div>

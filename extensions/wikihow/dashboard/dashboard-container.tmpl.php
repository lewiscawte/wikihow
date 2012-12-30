<link rel="stylesheet" type="text/css" href="<?= $cssUrl ?>" />
<script src="<?= $jsUrl ?>"></script>
<script>
WH.dashboard.allThresholds(<?= $thresholds ?>);
WH.dashboard.refreshData("global", <?= $GLOBAL_DATA_REFRESH_TIME_SECS ?>);
WH.dashboard.refreshData("user", <?= $USER_DATA_REFRESH_TIME_SECS ?>);
</script>
<div class="comdash-container">
	<div class="comdash-header-priorities">
		Top Goals This Week
	</div>
	<div class="comdash-priorities">
		<?= call_user_func($displayWidgetsFunc, $priorityWidgets) ?>
		<div class="clearall"></div>
	</div><!--end comdash-priorities-->
	<div class="comdash-header-widgets">
		More Things to Do
		<a id="comdash-header-customize" href="#">Customize <img src=' <?= wfGetPad('/skins/WikiHow/images/actionArrow.png') ?> ' alt=''></a>
	</div>
	<div class="comdash-widgets">
		<? // get the html for the user-defined list of widgets ?>
		<?= call_user_func($displayWidgetsFunc, $userWidgets) ?>
		<div class="clearall"></div>
	</div>
	<script>$(document).ready(WH.dashboard.init);</script>
	<a href="#" class="comdash-pause"><?= wfMsg('cd-pause-updates') ?></a> |
	<a href="#" class="comdash-settings"><?= wfMsg('cd-settings') ?></a>
</div><!--end comdash-container-->

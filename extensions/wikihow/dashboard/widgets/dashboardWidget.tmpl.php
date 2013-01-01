<?= $header ?>
<div class="comdash-widget-body <?= $status ?>">
	<div class="comdash-weather sunny <?= $weather=='sunny'?'active':'' ?>"><?= wfMsg('cd-sunny') ?></div>
	<div class="comdash-weather stormy <?= $weather=='stormy'?'active':'' ?>"><?= wfMsg('cd-stormy') ?></div>
	<div class="comdash-weather rainy <?= $weather=='rainy'?'active':'' ?>"><?= wfMsg('cd-rainy') ?></div>
	<div class="comdash-weather cloudy <?= $weather=='cloudy'?'active':'' ?>"><?= wfMsg('cd-cloudy') ?></div>
	<div class="comdash-count">
		<span><?= $data['ct'] ?></span><?= $countDescription ?>
	</div>
</div>
<div class="comdash-widget-footer">
	<div class='comdash-topcontributor'><div class="content"><?= $moreLink ?><span class='avatar'><?= call_user_func($getAvatarLink, $data['tp']['im']) ?></span><span>Leader</span><span class='name'><?= call_user_func($getUserLink, $data['tp']['na']) ?></span><span class='time'><?= $data['tp']['da'] ?></span></div><img src="<?=wfGetPad('/extensions/wikihow/rotate.gif')?>" class="waiting" /></div>
	<div class='comdash-lastcontributor'><span class='avatar'><?= call_user_func($getAvatarLink, $data['lt']['im']) ?></span><span>Last</span><span class='name'><?= call_user_func($getUserLink, $data['lt']['na']) ?></span><span class='time'><?= $data['lt']['da'] ?></span></div>
</div>
<div class="cd-info"><?= wfMsgWikiHtml('cd-disabled-info', $login)?></div>
<div class="comdash-widget-leaders">
	<div class="comdash-widget-leaders-content">
		<div class="comdash-widget-header">Leaders: <?= $title ?></div>
		<div class="comdash-widget-body">
			<table cellpadding="0" cellspacing="0">
			</table>
		</div>
		<div class="comdash-widget-footer">
			<div class='comdash-lastcontributor'><span class='avatar'><?= call_user_func($getAvatarLink, $data['lt']['im']) ?></span><span>Last</span><span class='name'><?= call_user_func($getUserLink, $data['lt']['na']) ?></span><span class='time'><?= $data['lt']['da'] ?></span></div>
			<a href="#" class="comdash-close" id="comdash-close-<?= $widgetName ?>">Done</a>
		</div>
	</div>
</div>

<?= $header ?>
<div class="comdash-widget-body">
	<div class="comdash-weather <?= $weather ?>">All Clear</div>
	<div class="comdash-count">
		<span>199</span>changes to go
	</div>
	<div style="display:none">
		unpatrolled:<br/>
		<span class="cd-rcw-unpatrolled"><?= $data['unpatrolled'] ?></span><br/>
		completed today:<br/>
		<span class="cd-rcw-completed"><?= intval($completedToday) ?></span><br/>
		thresholds: <?= print_r($thresholds,true) ?><br/>
		<br/>
	</div>
</div>
<?= $footer ?>

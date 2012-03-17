<? foreach ($scripts as $script): ?>
	<script type="text/javascript" src="<?= wfGetPad('/extensions/min/?g=' . $script) ?>&rev=<?= WH_SITEREV ?>"></script>
<? endforeach; ?>
<? if(!empty($scriptsCombine)): ?>
	<script type="text/javascript" src="<?= wfGetPad('/extensions/min/?g=' . join(',', $scriptsCombine)) ?>&rev=<?= WH_SITEREV ?>"></script>
<? endif; ?>

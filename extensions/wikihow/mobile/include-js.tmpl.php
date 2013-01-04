<? foreach ($scripts as $script): ?>
	<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f' . $script) ?>"></script>
<? endforeach; ?>
<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/' . join(',', $scriptsCombine)) ?>"></script>

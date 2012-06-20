<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width" /> 
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="google-site-verification" content="Jb3uMWyKPQ3B9lzp5hZvJjITDKG8xI8mnEpWifGXUb0" />
	<title><?= $title ?></title>
	<? if ($deviceOpts['show-css']): ?>
		<link href="<?= wfGetPad('/extensions/min/?g=' . join(',', $css)) ?>&rev=<?= WH_SITEREV ?>" rel="stylesheet" type="text/css" />
	<? endif; ?>
    <? if (isset($jsglobals)) echo $jsglobals; ?>
	<? if (isset($js) && is_array($js)): ?>
	<script type="text/javascript" src="<?= wfGetPad('/extensions/min/?g=' . join(',', $js)) ?>&rev=<?= WH_SITEREV ?>"></script>
	<? endif; ?>

	<?if (is_array($js) && false !== array_search('stu', $js)): ?>

	<script type="text/javascript">
	if (WH && WH.ExitTimer) {
			var debug = false;
			WH.ExitTimer.start(debug);
	}
	</script>
	<? endif; ?>
	<link rel="apple-touch-icon" href="<?= wfGetPad('/skins/WikiHow/safari-large-icon.png') ?>" />
	<link rel='canonical' href='<?= htmlentities($canonicalUrl) ?>'/>
	<? if (!$pageExists): ?>
		<meta name="robots" content="noindex,nofollow" />
	<? endif; ?>
</head>
<body>

	<? if ($deviceOpts['show-header-footer']): ?>
	<div id="header" class="header_static">
		<div id="header_logo">
			<? if (!$isMainPage): ?> <a href="/<?= wfMsg('mainpageurl') ?>" class="logo"> <? endif; ?>
			<img src="<?= wfGetPad('/skins/WikiHow/images/wikihow.png') ?>" alt="WikiHow" />
			<? if (!$isMainPage): ?> </a> <? endif; ?>
			<a href="<?= $randomUrl ?>" class="surprise"><?= wfMsg('surprise-me') ?></a>
		</div><!--end header_logo-->
	</div>
	<div class="search_static"></div>
	<?= EasyTemplate::html('search-box.tmpl.php') ?>
	<? if (@$showTagline): ?>
	<div id="tagline">
		<blockquote><p><?= wfMsg('mobile-tagline') ?></p></blockquote>
	</div><!--end tagline-->
	<? endif; ?>
	<? endif; ?>


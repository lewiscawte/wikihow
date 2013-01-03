<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width" /> 
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="google-site-verification" content="Jb3uMWyKPQ3B9lzp5hZvJjITDKG8xI8mnEpWifGXUb0" />
	<title><?= $title ?></title>
	<link href="<?= wfGetPad('/extensions/min/f/' . join(',', $cssFiles)) ?>" rel="stylesheet" type="text/css" />
	<link rel="apple-touch-icon" href="<?= wfGetPad('/skins/WikiHow/safari-large-icon.png') ?>" />
</head>
<body>

	<? if ($deviceOpts['show-header-footer']): ?>
	<div id="header">
		<div id="header_logo">
			<? if (!$isMainPage): ?> <a href="/<?= wfMsg('mainpageurl') ?>" class="logo"> <? endif; ?>
			<img src="<?= wfGetPad('/extensions/wikihow/mobile/images/logo.png') ?>" alt="WikiHow" />
			<? if (!$isMainPage): ?> </a> <? endif; ?>
			<a href="<?= $randomUrl ?>" class="surprise"><?= wfMsg('surprise-me') ?></a>
		</div><!--end header_logo-->
		<?= EasyTemplate::html('search-box') ?>
		<? if (@$showTagline): ?>
		<div id="tagline">
		  <blockquote><p><?= wfMsg('mobile-tagline') ?></p></blockquote>
		</div><!--end tagline-->
		<? endif; ?>
	</div>
	<? endif; ?>


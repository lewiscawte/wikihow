	<div id="article">
		<div id="image-preview">
<?
	// image: /extensions/wikihow/winpop_x.gif
	$img_data = 'R0lGODlhFQAVAMQAAOHh4cTExOnp6bKyssjIyPr6+rCwsPT09NPT0729veTk5MPDw9nZ2d7e3s7OzsXFxe/v77i4uK6urv///62trQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAAAAAALAAAAAAVABUAAAVw4AQ8VGmepURJASAaKmrGpwEEtKyrwar/wCAqJ/zREgKBo6QQMIoUxqSQQEwmCejgMIFcEUbZ4joRQE+CK+FcIpAPg/BpUJg06o1hCpWGuydrQlYTC0xTEWxAEouJilA8ckQlLTA+J4uSNiI4Zy0TIQA7';
?>
			<img src="data:image/gif;base64,<?= $img_data ?>" width="21" height="21" alt="close window" id="mobile_x" onclick="closeImagePreview();" />
			<div class="rounders">
				<img id="image-src" />
				<div class="corner top_left"></div>
				<div class="corner top_right"></div>
				<div class="corner bottom_right"></div>
				<div class="corner bottom_left"></div>
			</div><!--end rounders-->
		</div><!--end image-preview-->
		<div id="article_top">
			<? if ($thumb): ?>
				<?
					if ($deviceOpts['intro-image-format'] == 'conditional') {
						$className = ($width <= $deviceOpts['screen-width'] / 2 ? 'vertical' : 'horizontal');
					} else if ($deviceOpts['intro-image-format'] == 'right') {
						$className = 'floatright';
					}
				?>
				<div class="rounders grey <?= $className ?>" style="width:<?= $width ?>px; height:<?= $height ?>px;">
					<img src="<?= wfGetPad('/extensions/wikihow/mobile/images/' . wfMsg('mobile-howto-image-overlay')) ?>" alt="" class="home_label" />
					<?= $thumb->toHtml() ?>
					<div class="corner top_left"></div>
					<div class="corner top_right"></div>
					<div class="corner bottom_right"></div>
					<div class="corner bottom_left"></div>
				</div>
			<? endif; ?>
			<h1><?= ($thumb ? $title : wfMsg('howto', $title)) ?></h1>
			<p><?= $intro ?></p>
			<? if (@$showAds): ?>
			<div class="wh_ad">
				<?= wfMsg('adunitmobile1'); ?>
			</div>
			<? endif; ?>
			<div class="clear"></div>
			<?= MobileWikihow::showDeferredJS() ?>
			<div id="article_tabs">
				<? $tabs = 0; ?>
				<? if (isset($sections['ingredients'])): $tabs++; ?>
					<div id="tab-ingredients" class="tab active"><div class="tab_item"><a href="#"><?= wfMsg('ingredients') ?></a></div></div>
				<? endif; ?>
				<? if (isset($sections['steps'])): $tabs++; ?>
					<div id="tab-steps" class="tab<?= $tabs == 1 ? ' active' : '' ?>"><div class="tab_item"><a href="#"><?= $sections['steps']['name'] ?></a></div></div>
				<? endif; ?>
				<? if (!isset($sections['ingredients']) && isset($sections['thingsyoullneed'])): $tabs++; ?>
					<div id="tab-thingsyoullneed" class="tab"><div class="tab_item"><a href="#"><?= wfMsg('thingsyoullneedtab') ?></a></div></div>
				<? endif; ?>
				<? if (isset($sections['tips']) || isset($sections['warnings'])): $tabs++; ?>
					<div id="tab-tips" class="tab"><div class="tab_item"><a href="#"><?= wfMsg('tips-and-warnings') ?></a></div></div>
				<? endif; ?>
				<? if ($tabs < 3 && isset($sections['video'])): $tabs++; ?>
					<div id="tab-video" class="tab"><div class="tab_item"><a href="#"><?= $sections['video']['name'] ?></a></div></div>
				<? endif; ?>
			</div><!--end article_tabs-->
			<div id="article_tabs_line"></div>
		</div><!--end article_top-->

<? $gotFirst = false; foreach( array('ingredients', 'steps', 'thingsyoullneed', 'tips', 'warnings', 'video') as $section): ?>
	<? if (isset($sections[$section]) && ($deviceOpts['show-only-steps-tab'] || $section == 'steps')): ?>
		<div id="tab-content-<?= $section ?>" class="content <?= (!$gotFirst ? 'content-show' : '') ?>">
			<? if ($section == 'tips' || $section == 'warnings'): ?><h3><span><?= wfMsg($sections[$section]['name']) ?></span></h3><? endif; ?>
			<?= $sections[$section]['html'] ?>
		</div>
	<? $gotFirst = true; endif; ?>
<? endforeach; ?>

<? $first = true; foreach ($sections as $id => $section): ?>
	<? $expandSection = $id == 'relatedwikihows'; ?>
	<? if (!$first): ?>
		<div id="drop-heading-<?= $id ?>" class="drop-heading">
			<h2 class="<?= $expandSection ? 'expanded' : '' ?>"><a href="#"><?= $section['name'] ?></a></h2>
		</div>
		<div id="drop-content-<?= $id ?>" class="content <?= $expandSection ? 'content-show' : '' ?>">
			<?= $section['html'] ?>
		</div>
	<? else: $first = false; ?>
	<? endif; ?>
<? endforeach; ?>

<?= MobileWikihow::showBootStrapScript() ?>
		<? if (@$showAds): ?>
		<div class="wh_ad">
			<?= wfMsg('adunitmobile2'); ?>
		</div>
		<? endif; ?>
		<a href="#" id="back_to_top"><?= wfMsg('back-to-top') ?></a>
	</div><!--end article-->

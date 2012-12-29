	<div id="article">
		<div id="image-preview">
			<img src="<?= wfGetPad('/extensions/wikihow/winpop_x.gif') ?>" width="21" height="21" alt="close window" id="mobile_x" onclick="closeImagePreview();" />
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
				<div class="rounders grey <?= ($width <= $DEVICE_WIDTH / 2 ? 'vertical' : 'horizontal') ?>" style="width:<?= $width ?>px; height:<?= $height ?>px;">
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

<? $gotFirst = false; foreach( array('ingredients', 'steps', 'thingsyoullneed', 'tips', 'warnings', 'video') as $i => $section): ?>
	<? if (isset($sections[$section])): ?>
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

		<a href="#" id="back_to_top"><?= wfMsg('back-to-top') ?></a>
	</div><!--end article-->

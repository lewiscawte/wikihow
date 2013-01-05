	<div id="article_top">
		<div class="rounders grey" style="width:290px; height:194px;">
        	<img src="<?= wfGetPad('/extensions/wikihow/mobile/images/' . wfMsg('mobile-howto-image-overlay')) ?>" alt="" class="home_label" />
			<a href="/<?= $spotlight['url'] ?>"><img src="<?= $spotlight['img'] ?>" alt="" width="290" /></a>
			<div class="corner top_left"></div>
			<div class="corner top_right"></div>
			<div class="corner bottom_right"></div>
			<div class="corner bottom_left"></div>
		</div>
		<h1><a href="/<?= $spotlight['url'] ?>"><?= $spotlight['name'] ?></a></h1>
		<p><?= $spotlight['intro'] ?></p>
		<div class="clear"></div>
	</div><!--end article_top-->
	<div id="featured_articles">
		<h3><?= wfMsg('featured-articles') ?></h3>
		<table cellpadding="0" cellspacing="0">
			<tr>
			<? for ($i = 0; $i < 6; $i++): ?>
			<? if ($i == 3): ?></tr><tr><? endif; ?>
				<td>
					<? if ($i < count($featured)): $fa = $featured[$i]; ?>
						<div class="rounders grey" style="width:90px; height:54px;">
							<a href="/<?= $fa['url'] ?>"><img src="<?= $fa['img'] ?>" alt="" width="90" /></a>
							<div class="corner top_left"></div>
							<div class="corner top_right"></div>
							<div class="corner bottom_right"></div>
							<div class="corner bottom_left"></div>
						</div>
						<a href="/<?= $fa['url'] ?>"><?= $fa['name'] ?></a>
					<? endif; ?>
				</td>
			<? endfor; ?>
			</tr>
		</table>

		<a href="<?= $randomUrl ?>" class="button button_150 center" style="margin-bottom:25px;"><?= wfMsg('surprise-me') ?></a>

<!-- rs: disable other langs until they're deployed
		<a href="<?= $languagesUrl ?>" class="wikihow_world"><img src="<?= wfGetPad('/extensions/wikihow/mobile/images/globe.gif') ?>" alt="" /> <?= wfMsg('wikihow-other-languages') ?></a>
-->

	</div><!--end featured_articles-->


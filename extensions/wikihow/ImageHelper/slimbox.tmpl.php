<?=$js?>
<?=$css?>
<div class='is-images'>
	<table>
		<tr>
		<td style='padding-bottom:5px;'>
			<a id="lbg_0" href="#" class="lbg">
				<img border="0" src="<?= wfGetPad('/skins/WikiHow/images/is_left.png') ?>"/>
			</a>
		</td>
		<?for ($i = 0; $i < $numImages && $i < 5; $i++) { ?>
			<td style="padding-left:0px">
				<a id="lbgi_<?=$i?>" rel="lightbox-gallery" href="<?=wfGetPad($fileUrl[$i])?>"></a>
				<div class="mwimg">
					<div style="width:<?= $imageWidth[$i] ?>px;">
						<div style="width:<?= $imageWidth[$i] ?>px;height:<?= $imageHeight[$i] ?>px" class="rounders">
							<a id="lbg_<?=$i?>" href="#" class="image lbg">
								<img border="0" class="mwimage101" src="<?= wfGetPad($thumbUrl[$i]) ?>" alt="<?= $imageTitle[$i] ?>" />
							</a>
							<div class="corner top_left"></div>
							<div class="corner top_right"></div>
							<div class="corner bottom_left"></div>
							<div class="corner bottom_right"></div>
						</div>
					</div>
				</div>
			</td>
		<?}?>
		<td style='padding-left:0px;padding-bottom:5px;'>
			<a id="lbg_<?=$i - 1?>" href="#" class="lbg">
				<img border="0" src="<?= wfGetPad('/skins/WikiHow/images/is_right.png') ?>" alt="<?= $imageTitle[$lastPos] ?>" />
			</a>
		</td>
		<td style='padding-left:0px;padding-bottom:5px;'>
			<a id="lbg_0" href="#" class="lbg">
				<img id="is-view-img" border="0" src="<?= wfGetPad('/skins/WikiHow/images/is_view.png') ?>" />
				<div id="is-view">View<br> Slideshow</div>
			</a>
		</td>
		</tr>
	</table>
</div>

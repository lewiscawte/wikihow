<h2>More images from <?= $title ?></h2>
<div class='im-images'>
	<table>
		<td style="padding-left:0px">
			<div class="mwimg">
				<div style="width:<?= $imageWidth[0] ?>px;">
					<div style="width:<?= $imageWidth[0] ?>px;height:<?= $imageHeight[0] ?>px" class="rounders">
						<a href="<?= $imageUrl[0] ?>" title="<?= $imageTitle[0] ?>" class="image">
							<img border="0" class="mwimage101" src="<?= wfGetPad($thumbUrl[0]) ?>" alt="<?= $imageTitle[0] ?>">
						</a>
						<div class="corner top_left"></div>
						<div class="corner top_right"></div>
						<div class="corner bottom_left"></div>
						<div class="corner bottom_right"></div>
					</div>
				</div>
			</div>
	
		</td>
	<?php if($numImages > 1): ?>
		<td>
			<div class="mwimg">
				<div style="width:<?= $imageWidth[1] ?>px;">
					<div style="width:<?= $imageWidth[1] ?>px;height:<?= $imageHeight[1] ?>px" class="rounders">
						<a href="<?= $imageUrl[1] ?>" title="<?= $imageTitle[1] ?>" class="image">
							<img border="0" class="mwimage101" src="<?= wfGetPad($thumbUrl[1]) ?>" alt="<?= $imageTitle[1] ?>">
						</a>
						<div class="corner top_left"></div>
						<div class="corner top_right"></div>
						<div class="corner bottom_left"></div>
						<div class="corner bottom_right"></div>
					</div>
				</div>
			</div>

		</td>
	<?php endif; ?>
	<?php if($numImages > 2): ?>
		<td>
			<div class="mwimg">
				<div style="width:<?= $imageWidth[2] ?>px;">
					<div style="width:<?= $imageWidth[2] ?>px;height:<?= $imageHeight[2] ?>px" class="rounders">
						<a href="<?= $imageUrl[2] ?>" title="<?= $imageTitle[2] ?>" class="image">
							<img border="0" class="mwimage101" src="<?= wfGetPad($thumbUrl[2]) ?>" alt="<?= $imageTitle[2] ?>">
						</a>
						<div class="corner top_left"></div>
						<div class="corner top_right"></div>
						<div class="corner bottom_left"></div>
						<div class="corner bottom_right"></div>
					</div>
				</div>
			</div>

		</td>
	<?php endif; ?>
	<?php if($numImages > 3): ?>
		<td>
			<div class="mwimg">
				<div style="width:<?= $imageWidth[3] ?>px;">
					<div style="width:<?= $imageWidth[3] ?>px;height:<?= $imageHeight[3] ?>px" class="rounders">
						<a href="<?= $imageUrl[3] ?>" title="<?= $imageTitle[3] ?>" class="image">
							<img border="0" class="mwimage101" src="<?= wfGetPad($thumbUrl[3]) ?>" alt="<?= $imageTitle[3] ?>">
						</a>
						<div class="corner top_left"></div>
						<div class="corner top_right"></div>
						<div class="corner bottom_left"></div>
						<div class="corner bottom_right"></div>
					</div>
				</div>
			</div>

		</td>
	<?php endif; ?>
	<?php if($numImages > 4): ?>
		<td>
			<div class="mwimg">
				<div style="width:<?= $imageWidth[4] ?>px;">
					<div style="width:<?= $imageWidth[4] ?>px;height:<?= $imageHeight[4] ?>px" class="rounders">
						<a href="<?= $imageUrl[4] ?>" title="<?= $imageTitle[4] ?>" class="image">
							<img border="0" class="mwimage101" src="<?= wfGetPad($thumbUrl[4]) ?>" alt="<?= $imageTitle[4] ?>">
						</a>
						<div class="corner top_left"></div>
						<div class="corner top_right"></div>
						<div class="corner bottom_left"></div>
						<div class="corner bottom_right"></div>
					</div>
				</div>
			</div>
		</td>
	<?php endif; ?>
	</tr>
	</table>
</div>
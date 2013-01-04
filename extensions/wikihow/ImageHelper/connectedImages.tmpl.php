<h2>More images from <?= $title ?></h2>
<div class='im-images'>
	<a href='<?= $imageUrl[0] ?>'><img src='<?= wfGetPad($thumbUrl[0]) ?>' alt='<?= $imageTitle[0] ?>' /></a>
	<?php if($numImages > 1) ?>
		<a href='<?= $imageUrl[1] ?>'><img src='<?= wfGetPad($thumbUrl[1]) ?>' alt='<?= $imageTitle[1] ?>' /></a>
	<?php if($numImages > 2) ?>
		<a href='<?= $imageUrl[2] ?>'><img src='<?= wfGetPad($thumbUrl[2]) ?>' alt='<?= $imageTitle[2] ?>' /></a>
	<?php if($numImages > 3) ?>
		<a href='<?= $imageUrl[3] ?>'><img src='<?= wfGetPad($thumbUrl[3]) ?>' alt='<?= $imageTitle[3] ?>' /></a>
	<?php if($numImages > 4) ?>
		<a href='<?= $imageUrl[4] ?>'><img src='<?= wfGetPad($thumbUrl[4]) ?>' alt='<?= $imageTitle[4] ?>' /></a>
	<div class='clearall'></div>
</div>
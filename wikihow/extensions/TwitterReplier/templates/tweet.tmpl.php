<span>
	<?php if( $profileImage ): ?>
	<span class="avatar">
		<img src="<?php echo $profileImage ?>" alt="@<?php echo $fromUser ?> avatar" width="48" height="48"/>
	</span>
	<?php endif; ?>

	<span class="twitter_handle" style="font-weight:bold; color:#444">
		<?php echo $fromUser ?>
	</span>

	<br />

	<span class="tweet">
		<?php echo $tweet ?>
	</span>

	<br />
	<?php if( $createdOn ): ?>
	<span class="time reltime">
		<?php $timeStr = strtotime( $createdOn ); ?>
		<?php echo TwitterReplierTemplate::formatTime( $timeStr ) ?>
		<input type="hidden" name="ts" value="<?php echo $timeStr ?>" />
	</span>
	<?php endif; ?>
</span>
<span>
	<span class="avatar">
		<img src="<?php echo $profileImage ?>" alt="@<?php echo $fromUser ?> avatar" width="48" height="48"/>
	</span>

	<span class="twitter_handle" style="font-weight:bold;color:#444" >
		<?php echo $fromUser ?>
	</span>

	<br /> 

	<span class="tweet">
		<?php echo $tweet ?>
	</span> 

	<br />
	<span class="time">
		<?php echo TwitterReplierTemplate::formatTime( strtotime( $createdOn ) ) ?>
	</span>
</span>
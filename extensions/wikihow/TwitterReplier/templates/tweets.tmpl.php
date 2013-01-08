<?php if ( !empty( $tweets ) && is_array( $tweets ) ): ?>
		<?php foreach ( $tweets as $tweet ): ?>
			<?php $response = json_decode( base64_decode( $tweet['response_object'] ) ) ?>
			<li id="twitter_<?php echo $tweet['tweet_id'] ?>">
				<?php $vars = array(	'fromUser' => $response->from_user, 
										'profileImage' => $response->profile_image_url, 
										'tweet' => $tweet['tweet'], 
										'createdOn' => $tweet['twitter_created_on'] ); ?>
				<?php echo TwitterReplierTemplate::html('tweet', $vars ); ?>
			</li>
		<?php endforeach ?>
	<?php else: ?>
		<li>No tweets</li>
<?php endif ?>
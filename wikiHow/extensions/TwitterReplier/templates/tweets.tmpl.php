<?php if ( !empty( $tweets ) && is_array( $tweets ) ): ?>
		<?php global $wgRequest; ?>
		<?php $endClass = ( $wgRequest->getVal( 'returnType' ) == 'json' ? 'new_bottom' : 'last' ); ?>
		<?php $totalTweets = count( $tweets ); ?>
		<?php $i = 1; ?>
		<?php foreach ( $tweets as $tweet ): ?>

			<?php $response = json_decode( base64_decode( $tweet['response_object'] ) ) ?>
			<li id="twitter_<?php echo $tweet['tweet_id'] ?>" class="<?php if ( $i == $totalTweets ) { echo $endClass; } ?>">
				<?php
				$vars = array(
					'fromUser' => $response->from_user,
					'profileImage' => $response->profile_image_url,
					'tweet' => $tweet['tweet'],
					'createdOn' => $tweet['twitter_created_on']
				);
				?>

				<?php echo TwitterReplierTemplate::html( 'tweet', $vars ); ?>
				<?php $i++; ?>
			</li>
		<?php endforeach ?>
	<?php else: ?>
		<li><?php echo wfMessage( 'twitterreplier-no-tweets' )->text(); ?></li>
<?php endif ?>

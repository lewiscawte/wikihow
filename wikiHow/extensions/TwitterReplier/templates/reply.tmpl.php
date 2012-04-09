<?php
	global $wgExtensionAssetsPath;
	$closeMsg = wfMessage( 'twitterreplier-close' )->text();
?>
<div id="authorizationContainer" title="<?php echo wfMessage( 'twitterreplier-authorization-title' )->text() ?>">
	<p class="description"><?php echo wfMsg( 'twitterreplier-authentication-description' ) ?></p>
	<p class="images">
		<img src="<?php echo $wgExtensionAssetsPath . '/TwitterReplier/images/logo_wikihow.gif' ?>" alt="" />
		<img src="<?php echo $wgExtensionAssetsPath . '/TwitterReplier/images/right_arrow.png' ?>" alt="" />
		<img src="<?php echo $wgExtensionAssetsPath . '/TwitterReplier/images/logo_twitter.gif' ?>" alt="" />
	</p>
	<hr />
	<p class="button"><a href="#" id="authorizeMe"><img src="<?php echo $wgExtensionAssetsPath . '/TwitterReplier/images/btn_authorize_me.png' ?>" alt="<?php echo wfMessage( 'twitterreplier-authorize-me' )->text() ?>" /></a></p>
</div>

<div id="reply_container">
	<div id="instructions">
		<p><?php echo wfMsg( 'twitterreplier-right-rail-instructions' ) ?></p>
	</div>
	<div id="reply_content" style="display:none;">
		<p id="close"><a class="reply_close" href="#"><img src="<?php echo $wgExtensionAssetsPath . '/TwitterReplier/images/icon_close.gif' ?>" alt="<?php echo $closeMsg ?>" /></a> <a class="reply_close" href="#"><?php echo $closeMsg ?></a></p>

		<p class="bold"><?php echo wfMsg( 'twitterreplier-search-article-header' ) ?></p>
		<form method="post" action="" id="trSearchForm">
			<input class="default_search_input" type="text" name="trSearch" value="<?php echo wfMsg( 'twitterreplier-default-search-title' ) ?>"/>
		</form>

		<p class="bold suggest_article_header"><?php echo wfMsg( 'twitterreplier-suggest-article-header' ) ?></p>

		<table id="suggestedTitles">
			<thead>
				<tr>
					<th></th>
					<th><?php echo wfMessage( 'twitterreplier-page-title' )->text() ?></th>
				</tr>
			</thead>
			<tbody>

			</tbody>
		</table>

		<img src="<?php echo $wgExtensionAssetsPath . '/TwitterReplier/images/line_dotted.gif' ?>" alt=""/>
		<p class="customize_header grey bold"><?php echo wfMsg( 'twitterreplier-customize-tweet-header' ) ?></p>

		<p id="respond_to" class="grey bold"><?php echo wfMsg( 'twitterreplier-respond-to', '<span class="reply_to_user"></span>' ) ?></p>

		<form method="post">
			<textarea id="reply_tweet" class="light_grey">

			</textarea>
			<input type="image" src="<?php echo $wgExtensionAssetsPath . '/TwitterReplier/images/btn_TweetItForward.gif' ?>" name="tweet" value="<?php echo wfMessage( 'twitterreplier-tweet-suggestion' )->text() ?>" id="reply"/>
			<img id="reply_spinner" src="<?php echo $wgExtensionAssetsPath . '/TwitterReplier/images/spinner-circles.gif' ?>" />
			<div id="char_limit"></div>
			<div class="reply_as light_grey" id="reply_as"<?php if ( empty( $twitterHandle ) ) { echo ' style="display:none"'; } ?>>
				<?php echo wfMessage( 'twitterreplier-replying-as' )->text()->params(
					'<a id="twitter_handle" href="http://twitter.com/' . htmlspecialchars( $twitterHandle ) . '" rel="nofollow" target="_blank">@' .
						htmlspecialchars( $twitterHandle ) . '</a>'
				); ?> &mdash; <a class="unlink_action" href="#"><?php echo wfMessage( 'twitterreplier-unlink' )->parse() ?></a>
			</div>
		</form>
		<span id="reply_status_id" style="display:none"></span>
	</div>
</div>

<div class="clearall"></div>
</div>

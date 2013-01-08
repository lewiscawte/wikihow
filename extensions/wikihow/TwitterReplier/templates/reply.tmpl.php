<div id="authorizationContainer" title="Posting to Twitter Account">
	<p class="description"><?php echo wfMsg( 'authentication-description') ?></p>
	<p class="images"><img src="/extensions/wikihow/TwitterReplier/images/logo_wikihow.gif" alt="" /><img src="/extensions/wikihow/TwitterReplier/images/right_arrow.png" alt="" /><img src="/extensions/wikihow/TwitterReplier/images/logo_twitter.gif" alt="" /></p>
	<hr />
	<p class="button"><a href="#" id="authorizeMe"><img src="/extensions/wikihow/TwitterReplier/images/btn_authorize_me.png" alt="Authorize Me" /></a></p>
</div>

<div id="reply_container" >
	<div id="instructions">
		<p><?php echo wfMsg( 'right-rail-instructions' ) ?></p>
	</div>
	<div id="reply_content" style="display:none;">
		<p id="close"><a href="#"><img src="/extensions/wikihow/TwitterReplier/images/icon_close.gif" alt="close" /> close</a></p>

		<p class="bold"><?php echo wfMsg( 'search-article-header' ) ?></p>
		<form method="post" action="" id="trSearchForm">
			<input type="text" name="trSearch" value="Search for"/>
		</form>


		<p class="bold"><?php echo wfMsg( 'suggest-article-header' ) ?></p>

		<table id="suggestedTitles">
			<thead>
				<tr>
					<th></th>
					<th>Article Title</th>
				</tr>
			</thead>
			<tbody>

			</tbody>
		</table>

		<img src="/extensions/wikihow/TwitterReplier/images/line_dotted.gif" alt=""/>
		<p class="grey bold"><?php echo wfMsg( 'customize-tweet-header' ) ?></p>

		<p id="respond_to" class="grey bold"><?php echo wfMsg( 'respond-to' ) ?><span class="reply_to_user"></span></p>

		<form method="post">
			<textarea id="reply_tweet" class="light_grey">

			</textarea>
			<p id="over_limit"></p>
			<input type="image" src="/extensions/wikihow/TwitterReplier/images/btn_TweetItForward.gif"name="tweet" value="Tweet Suggestion" id="reply"/>
		</form>
		<span id="reply_status_id" style="display:none"></span>
	</div>
</div>

<div class="clearall"></div>
</div>

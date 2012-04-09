<?php
/**
 * LSearch search results UI template (for logged-in users)
 *
 * @file
 * @ingroup Templates
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

class LSearchSearchResultsTemplate extends QuickTemplate {
	function execute() {
		global $wgServer, $wgExtensionAssetsPath;

		// local aliases
		$q = $this->data['q'];
		$enc_q = $this->data['enc_q'];
		$me = $this->data['me'];
		$max_results = $this->data['max_results'];
		$start = $this->data['start'];
		$first = $this->data['first'];
		$last = $this->data['last'];
		$suggestionLink = $this->data['suggestionLink'];
		$results = $this->data['results'];
		$specialPageURL = $this->data['specialPageURL'];
		$total = $this->data['total'];
		$isBoss = $this->data['isBoss'];
?>
<form id="search_site" action="<?php echo $me->getFullURL() ?>" method="get">
	<?php if ( $isBoss ) { ?>
	<div style="padding-left: 5px"> Yahoo Boss Search Test</div>
	<?php } ?>
	<div id="search_head">
		<input type="hidden" name="fulltext" value="Search" />
		<input type="text" id="keywords" name="search" size="40" maxlength="75" value="<?php echo $enc_q ?>" />
	<?php if ( count( $results ) > 0 ): ?>
			<span class='result_count'><?php echo wfMsg( 'lsearch-num-results', number_format( $total ) ) ?></span>
	<?php endif; ?>
		<input type="submit" class="button button136 input_button" value="<?php echo wfMsg( 'search' ) ?>" />
	</div>
</form>

<?php
	// refactor: set vars if $q == empty
	if ( $q == null ):
		return;
	endif;
?>

<?php if ( count( $results ) > 0 ): ?>
	<div class="sr_for">
		<?php echo wfMsgForContent( 'lsearch-results-for', $enc_q ) ?>
	</div>
<?php endif; ?>

<?php if ( $suggestionLink ): ?>
	<div class="sr_suggest"><?php echo wfMsg( 'lsearch-suggestion', $suggestionLink ) ?></div>
<?php endif; ?>

<?php if ( count( $results ) == 0 ): ?>
	<div class="sr_noresults"><?php echo wfMsg( 'lsearch-noresults', $enc_q ) ?></div>
	<div id="searchresults_footer"><br /></div>
	<?php return; ?>
<?php endif; ?>

<div id="searchresults_list">
<?php foreach( $results as $i => $result ): ?>
	<div class="result">
		<?php if ( !$result['is_category'] ): ?>
			<?php if ( !empty( $result['img_thumb_100'] ) ): ?>
				<div class="result_thumb"><img src="<?php echo $result['img_thumb_100'] ?>" /></div>
			<?php endif; ?>
		<?php else: ?>
			<div class="result_thumb cat_thumb"><img src="<?php echo $result['img_thumb_100'] ? $result['img_thumb_100'] : $wgExtensionAssetsPath . '/LSearch/images/Book_75.png' ?>" /></div>
		<?php endif; ?>

<?php
	$url = $result['url'];
	if ( !preg_match( '@^http:@', $url ) ) {
		$url = $wgServer . '/' . $url;
	}
?>

		<?php if ( $result['has_supplement'] ): ?>
			<?php if ( !$result['is_category'] ): ?>
				<a href="<?php echo $url ?>" class="result_link"><?php echo $result['title_match'] ?></a>
			<?php else: ?>
				<a href="<?php echo $url ?>" class="result_link"><?php echo wfMsg( 'lsearch-article-category', $result['title_match'] ) ?></a>
			<?php endif; ?>

			<?php if ( !empty( $result['first_editor'] ) ): ?>
				<div>
					<?php
						$editorLink = Linker::link(
							Title::makeTitle( NS_USER, $result['first_editor'] ),
							$result['first_editor']
						);
					?>
					<?php if ( $result['num_editors'] <= 1 ): ?>
						<?php echo wfMsg( 'lsearch-edited-by', $editorLink ) ?>
					<?php elseif ( $result['num_editors'] == 2 ): ?>
						<?php echo wfMsg( 'lsearch_edited-by-other', $editorLink, $result['num_editors'] - 1 ) ?>
					<?php else: ?>
						<?php echo wfMsg( 'lsearch_edited-by-others', $editorLink, $result['num_editors'] - 1 ) ?>
					<?php endif; ?>
				</div>

				<?php if ( !empty( $result['last_editor'] ) && $result['num_editors'] > 1 ): ?>
					<div>
						<?php echo wfMsg(
							'lsearch_last_updated',
							wfTimeAgo( wfTimestamp( TS_UNIX, $result['timestamp'] ), true ),
							Linker::link(
								Title::makeTitle( NS_USER, $result['last_editor'] ),
								$result['last_editor']
							)
						) ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<ul class="search_results_stats">
				<?php if ( $result['is_featured'] ): ?>
					<li class="sr_featured"><?php echo wfMsg( 'lsearch-featured' ) ?></li>
				<?php endif; ?>
				<?php if ( $result['has_video'] ): ?>
					<li class="sr_video"><?php echo wfMsg( 'lsearch-has-video' ) ?></li>
				<?php endif; ?>
				<?php if ( $result['steps'] > 0 ): ?>
					<li class="sr_steps"><?php echo wfMsg( 'lsearch-steps', $result['steps'] ) ?></li>
				<?php endif; ?>

				<li class="sr_view">
				<?php if ( $result['popularity'] < 100 ): ?>
					<?php echo wfMsg( 'lsearch-views-tier0' ) ?>
				<?php elseif ( $result['popularity'] < 1000 ): ?>
					<?php echo wfMsg( 'lsearch-views-tier1' ) ?>
				<?php elseif ( $result['popularity'] < 10000 ): ?>
					<?php echo wfMsg( 'lsearch-views-tier2' ) ?>
				<?php elseif ( $result['popularity'] < 100000 ): ?>
					<?php echo wfMsg( 'lsearch-views-tier3' ) ?>
				<?php else: ?>
					<?php echo wfMsg( 'lsearch-views-tier4' ) ?>
				<?php endif; ?></li>
			</ul>
		<?php else: ?>
			<a href="<?php echo $url ?>" class="result_link"><?php echo $result['title_match'] ?></a>
		<?php endif; // has_supplement ?>

		<div class="clearall"></div>
	</div>
<?php endforeach; ?>
</div>

<?php
if ( ( $total > $start + $max_results
	  && $last == $start + $max_results )
	|| $start >= $max_results ): ?>

<div id="searchresults_footer">

<div class="sr_next">
<?php // "Next >" link
if ( $total > $start + $max_results && $last == $start + $max_results ): ?>
	<?php echo Linker::link(
		$me,
		wfMsg( 'lsearch-next' ),
		array(),
		array( 'search' => $q, 'start' => ( $start + $max_results ) )
	); ?>
<?php else: ?>
	<?php echo wfMsg( 'lsearch-next' ) ?>
<?php endif; ?>
</div>

<div class="sr_prev">
<?php // "< Prev" link
if ( $start - $max_results >= 0 ) {
	$linkParams = array( 'search' => $q );

	if ( $start - $max_results !== 0 ) {
		$linkParams['start'] = ( $start - $max_results );
	}

	echo Linker::link(
		$me,
		wfMsg( 'lsearch-previous' ),
		array(),
		$linkParams
	);
} else {
	echo wfMsg( 'lsearch-previous' );
}
?>
&nbsp;
</div>

<?php echo wfMsg( 'lsearch-results-range', $first, $last, $total ) ?>

<div class="sr_text"><?php echo wfMsg( 'lsearch-mediawiki', $specialPageURL . '?search=' . urlencode( $q ) ) ?></div>

</div>

<?php endif; ?>
<?php
	} // execute()
} // class
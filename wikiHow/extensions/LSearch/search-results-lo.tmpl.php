<?php
/**
 * LSearch search results UI template for anonymous users.
 *
 * @file
 * @ingroup Templates
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

class LSearchSearchResultsLoggedOutTemplate extends QuickTemplate {
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
?>
<div id="lo_search">
	<form id="search_site" action="<?php echo $me ?>" method="get">
		<div id="search_head" class="lo_search_head">
			<input type="text" id="keywords" class="lo_q" name="search" maxlength="75" value="<?php echo $enc_q ?>" />
			<input type="hidden" name="lo" value="1"/>
			<?php if ( count( $results ) > 0 ): ?>
				<span class='result_count lo_count'><?php echo wfMsg( 'lsearch-num-results', number_format( $total ) ) ?></span>
			<?php endif; ?>
			<input type="submit" class="button button100 input_button lo_search_button" value="<?php echo wfMsg( 'search' ) ?>" />
		</div>
	</form>

	<?php
		// refactor: set vars if $q == empty
		if ( $q == null ):
			return;
		endif;
	?>

<div id="lo_searchresults_list">
	<?php if ( count( $results ) > 0 ): ?>
		<div class="sr_for lo_for">
			<?php echo wfMsgForContent( 'lsearch-results-for', $enc_q ) ?>
		</div>
		<?php echo wfMsg( 'Adunit_search_right' ); ?>
		<?php echo wfMsg( 'Adunit_search_top' ); ?>
	<?php endif; ?>

	<?php if ( $suggestionLink ): ?>
		<div class="sr_suggest"><?php echo wfMsg( 'lsearch-suggestion', $suggestionLink ) ?></div>
	<?php endif; ?>

	<?php if ( count( $results ) == 0 ): ?>
		</div> <!--lo_searchresults_footer -->
		<div class="sr_noresults"><?php echo wfMsg( 'lsearch-noresults', $enc_q ) ?></div>
		<div id="searchresults_footer" class="lo_footer"><br /></div>
		<?php return; ?>
	<?php endif; ?>
	<div id="searchresults_list" class="lo_list">
	<?php foreach( $results as $i => $result ): ?>
		<div class="result lo_result">
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
				<a href="<?php echo $url ?>" class="result_link lo_result_link"><?php echo $result['title_match'] ?></a>
				<div class="lo_abstract"><?php echo $result['abstract'] ?></div>
				<div class="lo_url"><?php echo $result['dispurl'] ?></div>

			<div class="clearall"></div>
		</div>
	<?php endforeach; ?>
	</div>
	</div> <!-- lo_searchresults_footer -->
	<div style="clear:both"> </div>
	<?php
	if ( ( $total > $start + $max_results
		  && $last == $start + $max_results )
		|| $start >= $max_results ): ?>

	<div id="searchresults_footer" class="lo_footer">

		<div class="sr_next">
		<?php // "Next >" link
		if ( $total > $start + $max_results && $last == $start + $max_results ) {
			echo Linker::link(
				$me,
				wfMsg( 'lsearch-next' ),
				array(),
				array( 'search' => $q, 'start' => ( $start + $max_results ), 'lo' => '1' )
			);
		} else {
			echo wfMsg( 'lsearch-next' );
		}
		?>
		</div>

		<div class="sr_prev">
		<?php // "< Prev" link
		if ( $start - $max_results >= 0 ) {
			$linkParams = array( 'search' => $q, 'lo' => '1' );
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

	</div>

	<?php endif; ?>
</div>
<?php
	} // execute()
}
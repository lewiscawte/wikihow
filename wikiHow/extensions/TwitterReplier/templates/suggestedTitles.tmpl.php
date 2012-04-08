<?php if( is_array( $results ) && count( $results ) > 0 ): ?>
	<?php $titleObj = Title::newFromText( $result->mUrlform ); ?>
	<?php $counter = 0; ?>
	<?php foreach( $results as $result ): ?>
		<tr class="<?php echo ( $counter % 2 === 0 ) ? 'even' : 'odd' ?>">
			<td><input type="radio" class="suggested_article" name="article" value="<?php echo json_encode( array(
				'title' => htmlentities( $result->mTextform, ENT_QUOTES ),
				'url' => $titleObj->getFullURL() ) ?>" /></td>
			<td><?php echo Linker::link( $titleObj, $result->mTextform ) ?></td>
		</tr>
		<?php $counter++ ?>
	<?php endforeach ?>
<?php else: ?>
	<tr>
		<td colspan="2" class="no_wh_articles"><?php echo wfMessage( 'twitterreplier-no-pages-found', $tweet, SpecialPage::getTitleFor( 'RequestTopic' )->getFullURL() )->parse() ?></td>
	</tr>
<?php endif ?>
	<tr style="display:none"><td id="searchTerms"><?php echo $tweet ?></td></tr>

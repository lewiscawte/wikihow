<?php
/**
 * Main UI template for the EditFinder extension.
 *
 * @file
 * @ingroup Templates
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

class EditFinderMainTemplate extends QuickTemplate {
	function execute() {
		global $wgExtensionAssetsPath;

		$helpPage = $this->data['helparticle'];
		$title = Title::newFromText( $helpPage );
		$url = $title->getFullURL();
?>
<div id="editfinder_upper">
	<h1><?php echo $this->data['pagetitle'] ?></h1>
</div>
<div id="editfinder_cat_header"><b><?php echo wfMsg( 'editfinder-' . $this->data['topicMode'] ) ?>:</b> <span id="user_cats"></span> (<a href="" class="editfinder_choose_cats"><?php echo wfMsg( 'editfinder-change' ) ?></a>)</div>
<div id="editfinder_head">
	<div id="editfinder_options">
		<div id="editfinder_skip"><a href="#"><?php echo wfMsg( 'editfinder-no' ) ?></a><div id="editfinder_skip_arrow"></div></div>
		<a href="#" class="button editfinder_button_yes" id="editfinder_yes"><?php echo wfMsg( 'editfinder-yes' ) ?></a>
	</div>
	<h1><?php echo wfMsg( 'editfinder-question' ) ?></h1>
	<p id="editfinder_help"><?php echo wfMsg( 'editfinder-dont-know', $url, $title->getText() ); ?></p>
</div>
<div id="editfinder_title">
	<h1><?php echo wfMessage( 'editfinder-title', '<span id="editfinder_article_inner"></span>' )->text(); ?></h1>
</div>
<div id="editfinder_spinner"><img src="<?php echo $wgExtensionAssetsPath ?>/EditFinder/images/rotate.gif" alt="" /></div>
<div id="editfinder_preview_updated"></div>
<div id="editfinder_preview"></div>
<div id="article_contents"></div>
<div id="editfinder_cat_footer">
	<?php
	// for grep: editfinder-not-finding-categories, editfinder-not-finding-interests
	echo wfMsg( 'editfinder-not-finding-' . $this->data['topicMode'] ) ?>
</div>
<?php
	}
}
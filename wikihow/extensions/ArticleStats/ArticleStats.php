<?php
/**
 * Basic dashboard that gives some summarized information on a page.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'ArticleStats',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Basic dashboard that gives some summarized information on a page',
	'url' => 'http://www.mediawiki.org/wiki/Extension:ArticleStats',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['ArticleStats'] = $dir . 'ArticleStats.i18n.php';
$wgAutoloadClasses['ArticleStats'] = $dir . 'ArticleStats.body.php';
$wgSpecialPages['ArticleStats'] = 'ArticleStats';

// Hooked functions which add a link to Special:ArticleStats to the toolbox
$wgHooks['SkinTemplateBuildNavUrlsNav_urlsAfterPermalink'][] = 'wfArticleStatsAddLinkToToolbox';
$wgHooks['SkinTemplateToolboxEnd'][] = 'wfArticleStatsOnSkinTemplateToolboxEnd';

function wfArticleStatsAddLinkToToolbox( &$skinTemplate, &$nav_urls, &$oldid, &$revid ) {
	if ( $skinTemplate->getTitle()->getNamespace() == NS_MAIN ) {
		$nav_urls['articlestats'] = array(
			'text' => wfMessage( 'articlestats' )->plain(),
			'href' => SpecialPage::getTitleFor( 'ArticleStats', $skinTemplate->getTitle()->getText() )->getFullURL()
		);
	}
	return true;
}

function wfArticleStatsOnSkinTemplateToolboxEnd( $skinTemplate ) {
	if ( isset( $skinTemplate->data['nav_urls']['articlestats'] ) ) {
		if ( $skinTemplate->data['nav_urls']['articlestats']['href'] == '' ) {
			echo '<li id="t-isarticlestats">' .
				wfMessgage( 'articlestats' )->plain() . '</li>';
		} else {
			$url = $skinTemplate->data['nav_urls']['articlestats']['href'];
			echo '<li id="t-articlestats"><a href="' . htmlspecialchars( $url ) . '">';
			echo wfMessage( 'articlestats' )->plain();
			echo '</a></li>';
		}
	}
	return true;
}

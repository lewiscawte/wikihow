<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:RelatedArticle-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


/**#@+
 */
#$wgHooks['AfterArticleDisplayed'][] = array("wfRelatedArticleForm");

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RelatedArticle',
	'author' => 'Travis Derouin',
	'description' => 'Provides a way of searching, previewing and adding links to an existing article',
	'url' => 'http://www.wikihow.com/WikiHow:RelatedArticle-Extension',
);

$wgExtensionMessagesFiles['RelatedArticle'] = dirname(__FILE__) . '/RelatedArticle.i18n.php';

$wgSpecialPages['RelatedArticle'] = 'RelatedArticle';
$wgAutoloadClasses['RelatedArticle'] = dirname( __FILE__ ) . '/RelatedArticle.body.php';

$wgSpecialPages['PreviewPage'] = 'PreviewPage';
$wgAutoloadClasses['PreviewPage'] = dirname( __FILE__ ) . '/RelatedArticle.body.php';


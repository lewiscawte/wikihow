<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:BuildWikiHow-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


/**#@+
 */
#$wgHooks['AfterArticleDisplayed'][] = array("wfBuildWikiHowForm");


$wgSpecialPages['BuildWikiHow'] = 'BuildWikiHow';
$wgAutoloadClasses['BuildWikiHow'] = dirname( __FILE__ ) . '/BuildWikiHow.body.php';



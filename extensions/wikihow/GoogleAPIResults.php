<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
* Displays supplementary search results for logged in users searching on wikiHow
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Google-API-Results Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'GoogleAPIResults',
    'author' => 'Travis <travis@wikihow.com>',
	'description' => 'Displays supplementary search results for logged in users searching on wikiHow',
);

#$wgExtensionMessagesFiles['GoogleAPIResults'] = dirname(__FILE__) . '/GoogleAPIResults.i18n.php';

$wgSpecialPages['GoogleAPIResults'] = 'GoogleAPIResults';
$wgAutoloadClasses['GoogleAPIResults'] = dirname( __FILE__ ) . '/GoogleAPIResults.body.php';

$wgGoogleJSAPIKey = WH_GOOGLE_AJAX_SEARCH_API_KEY;

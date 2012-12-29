<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that displays basic stats about daily activity on the wiki.
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:DailyStats-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'DailyStats',
    'author' => 'Travis <travis@wikihow.com>',
	'description' => 'An extension that displays basic stats about daily activity on the wiki.',
);

$wgExtensionMessagesFiles['DailyStats'] = dirname(__FILE__) . '/DailyStats.i18n.php';

$wgSpecialPages['DailyStats'] = 'DailyStats'; 
$wgAutoloadClasses['DailyStats'] = dirname( __FILE__ ) . '/DailyStats.body.php';

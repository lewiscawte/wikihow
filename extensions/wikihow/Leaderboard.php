<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that displays number of new articles and number of rising stars
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 * @author Vu Nguyen <vu@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Leaderboard',
	'author' => 'Vu Nguyen',
	'description' => 'Shows leaderboard stats.',
);

$wgExtensionMessagesFiles['Leaderboard'] = dirname(__FILE__) . '/Leaderboard.i18n.php';

$wgSpecialPages['Leaderboard'] = 'Leaderboard';
$wgAutoloadClasses['Leaderboard'] = dirname( __FILE__ ) . '/Leaderboard.body.php';


<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Unpatrol-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Unpatrol',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Unpatrol bad patrols',
);

$wgSpecialPages['Unpatrol'] = 'Unpatrol';
$wgAutoloadClasses['Unpatrol'] = dirname( __FILE__ ) . '/Unpatrol.body.php';

$wgLogTypes[] = 'unpatrol';
$wgLogNames['unpatrol'] = 'unpatrol';
$wgLogHeaders['unpatrol'] = 'unpatrol';


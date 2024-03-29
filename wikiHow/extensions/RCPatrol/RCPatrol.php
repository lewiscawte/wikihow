<?php

if ( !defined( 'MEDIAWIKI' ) ) {
exit(1);
}

/**#@+
 * A simple extension that allows users to enter a title before creating a page. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'RCPatrol',
	'author' => 'Travis Derouin',
	'description' => 'An improved way of doing RC Patrol', 
);

$wgExtensionMessagesFiles['RCPatrol'] = dirname(__FILE__) . '/RCPatrol.i18n.php';
$wgSpecialPages['RCPatrol'] 		= 'RCPatrol';
$wgAutoloadClasses['RCPatrol'] 		= dirname( __FILE__ ) . '/RCPatrol.body.php';

$wgSpecialPages['RCPatrolGuts'] 	= 'RCPatrolGuts';
$wgAutoloadClasses['RCPatrolGuts'] 	= dirname( __FILE__ ) . '/RCPatrol.body.php';

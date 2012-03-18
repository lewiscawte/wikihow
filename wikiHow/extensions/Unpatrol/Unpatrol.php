<?php
/**
 * An extension that allows privileged users to unpatrol bad patrols.
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
	'name' => 'Unpatrol',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Allows privileged users to [[Special:Unpatrol|unpatrol]] bad patrols',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagsFiles['Unpatrol'] = $dir . 'Unpatrol.i18n.php';
$wgAutoloadClasses['Unpatrol'] = $dir . 'Unpatrol.body.php';
$wgSpecialPages['Unpatrol'] = 'Unpatrol';

// New user right, required to use Special:Unpatrol
$wgAvailableRights[] = 'unpatrol';
$wgGroupPermissions['bureaucrat']['unpatrol'] = true;

// Logging
$wgLogTypes[] = 'unpatrol';
$wgLogNames['unpatrol'] = 'unpatrol';
$wgLogHeaders['unpatrol'] = 'unpatrol';
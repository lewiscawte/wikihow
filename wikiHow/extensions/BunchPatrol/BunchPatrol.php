<?php
/**
 * An extension that allows privileged users to patrol a bunch of edits at once.
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
$wgExtensionCredits['special'][] = array(
	'name' => 'BunchPatrol',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Bunches a bunch of edits of 1 user together',
	'url' => 'http://www.mediawiki.org/wiki/Extension:BunchPatrol',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['BunchPatrol'] = $dir . 'BunchPatrol.i18n.php';
$wgAutoloadClasses['BunchPatrol'] = $dir . 'BunchPatrol.body.php';
$wgSpecialPages['BunchPatrol'] = 'BunchPatrol';

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.bunchPatrol'] = array(
	'scripts' => 'BunchPatrol.js',
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'BunchPatrol'
);

// New user right
$wgAvailableRights[] = 'bunchpatrol';
$wgGroupPermissions['sysop']['bunchpatrol'] = true;
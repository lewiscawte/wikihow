<?php 
/**
 * UserTalkTool extension -- allows privileged users to post to multiple user
 * talk pages at once
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Vu Nguyen <vu@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'UserTalkTool',
	'version' => '1.0',
	'author' => 'Vu Nguyen',
	'description' => 'Allows privileged users to post to multiple user talk pages at once',
);

// ResourceLoader support for MediaWiki 1.17+
$wgResourceModules['ext.userTalkTool'] = array(
	'scripts' => 'UserTalkTool.js',
	'messages' => array( 'usertalktool-sending', 'usertalktool-send-error' ),
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'UserTalkTool'
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['UserTalkTool'] = $dir . 'UserTalkTool.i18n.php';
$wgAutoloadClasses['UserTalkTool'] = $dir . 'UserTalkTool.body.php';
$wgSpecialPages['UserTalkTool'] = 'UserTalkTool';

// New user right, required to use the extension
$wgAvailableRights[] = 'usertalktool';
$wgGroupPermissions['sysop']['usertalktool'] = true;
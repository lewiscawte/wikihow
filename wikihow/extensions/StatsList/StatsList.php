<?php
/**
 * StatsList extension -- shows statistics about answered topic requests
 * See also the RequestTopic extension.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Bebeth Steudel
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'StatsList',
	'version' => '1.0',
	'author' => 'Bebeth Steudel',
	'description' => 'Shows [[Special:StatsList|statistics]] about answered topic requests',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgExtensionMessagesFiles['StatsList'] = $dir . 'StatsList.i18n.php';
$wgAutoloadClasses['StatsList'] = $dir . 'StatsList.body.php';
$wgSpecialPages['StatsList'] = 'StatsList';
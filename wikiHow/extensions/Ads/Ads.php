<?php
/**
 * Ads extension -- custom JavaScript code for displaying ads
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Ads',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Custom JavaScript code for displaying ads',
);

// Set up the new special page
$dir = dirname( __FILE__ ) . '/';
$wgAutoloadClasses['Ads'] = $dir . 'Ads.body.php';
$wgSpecialPages['Ads'] = 'Ads';
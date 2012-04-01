<?php
/**
 * Standings extension -- shows various different standings in the wikiHow skin.
 *
 * @file
 * @ingroup Extensions
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Standings',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'The standings widget',
);

// INTL: set $dir parth to the current file path.  Autoloader was having trouble finding otherwise
$oldDirIntl = $dir;
$dir = dirname( __FILE__ ) . '/';

// Set up the new special page and autoload all the classes
$wgSpecialPages['Standings'] = 'Standings';
$wgAutoloadClasses['Standings'] =  $dir . 'Standings.body.php';
$wgExtensionMessagesFiles['Standings'] = $dir . 'Standings.i18n.php';

$wgAutoloadClasses['StandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['StandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['QCStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['QCStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['NFDStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['NFDStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['IntroImageStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['IntroImageStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['VideoStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['VideoStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['QuickEditStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['QuickEditStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['RCPatrolStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['RCPatrolStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['EditFinderStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['EditFinderStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['CategorizationStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['CategorizationStandingsIndividual'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['NABStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['RequestsAnsweredStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['SpellcheckerStandingsGroup'] = $dir . 'Standings.class.php';
$wgAutoloadClasses['SpellcheckerStandingsIndividual'] = $dir . 'Standings.class.php';

// INTL: Change $dir path back to what it was before in case this is used elsewhere.
$dir = $oldDirIntl;
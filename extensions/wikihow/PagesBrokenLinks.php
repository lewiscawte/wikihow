<?php

if ( ! defined( 'MEDIAWIKI' ) )
    die();

/**#@+
 *  Lists pages that have links to non-existant pages
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
/**
 *
 * @package MediaWiki
 * @subpackage SpecialPage
 */

/**
 *
 */
$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Pageswithbrokenlinks',
    'author' => 'Travis Derouin',
    'description' => 'Lists pages that have links to non-existant pages',
);


$wgSpecialPages['PagesBrokenLinks'] = 'PagesBrokenLinks';
$wgAutoloadClasses['PagesBrokenLinks'] = dirname( __FILE__ ) . '/PagesBrokenLinks.body.php';


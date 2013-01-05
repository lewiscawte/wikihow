<?php

if ( ! defined( 'MEDIAWIKI' ) )
    die();

/**#@+
 * Generates a page of links to the top level categories and their subcatgories;
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
    'name' => 'Sitemap',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Generates a page of links to the top level categories and their subcatgories',
);

$wgExtensionMessagesFiles['Sitemap'] = dirname(__FILE__) . '/SpecialSitemap.i18n.php';

$wgSpecialPages['Sitemap'] = 'Sitemap';
$wgAutoloadClasses['Sitemap'] = dirname( __FILE__ ) . '/SpecialSitemap.body.php';
 

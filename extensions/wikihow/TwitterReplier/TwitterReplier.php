<?php
if (!defined('MEDIAWIKI')) die();
    
/**#@+
 * Tweets helpful articles
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/Special:Wheaty Documentation
 *
 * @author Mark Steudel <msteudel@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'TwitterReplier',
	'author' => 'Mark Steudel',
	'description' => 'Tweet helpful Wikihow articles',
	'url' => 'http://www.wikihow.com/Special:TwitterReplier',
);

$wgSpecialPages['TwitterReplier'] = 'TwitterReplier';
$wgAutoloadClasses['TwitterReplier'] = dirname( __FILE__ ) . '/TwitterReplier.body.php';
$wgExtensionMessagesFiles['TwitterReplier'] = dirname(__FILE__) . '/TwitterReplier.i18n.php';
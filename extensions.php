<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Mypages-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionFunctions[] = 'wfMypages';

/**#@+
 */
#$wgHooks['AfterArticleDisplayed'][] = array("wfMypagesForm");

$wgExtensionCredits['other'][] = array(
	'name' => 'Mypages',
	'author' => 'Travis Derouin',
	'description' => 'Provides redirecting static urls to dynamic user pages',
);

function wfMypages() {
	global $wgMessageCache;
	SpecialPage::AddPage(new UnlistedSpecialPage('Mypages'));
}


function wfSpecialMypages( $par )
{
	global $wgOut, $wgUser, $wgRequest; 
    $fname = "wfMypages";

	$url = '';
	switch ($par) {
		case 'Contributions':
			$url = Title::makeTitle(NS_SPECIAL, "Contributions")->getFullURL() . "/" . $wgUser->getName();
			break;
		case 'Fanmail':
			$url = Title::makeTitle(NS_USER_KUDOS, $wgUser->getName())->getFullURL();
			break;

	}
	if ($url != '')
		$wgOut->redirect($url);
}
?>
<?php 

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'LSearch',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Customed search backend for Google Mini and wikiHow',
);

$wgExtensionMessagesFiles['LSearch'] = dirname(__FILE__) . '/SpecialLSearch.i18n.php';

$wgSpecialPages['LSearch'] = 'LSearch';
$wgAutoloadClasses['LSearch'] = dirname( __FILE__ ) . '/SpecialLSearch.body.php';


<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Newcontributors',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'A list of users who have made their first contribution to the site',
);

$wgExtensionMessagesFiles['Newcontributors'] = dirname(__FILE__) . '/SpecialNewcontributors.i18n.php';

$wgSpecialPages['Newcontributors'] = 'Newcontributors';
$wgAutoloadClasses['Newcontributors'] = dirname( __FILE__ ) . '/SpecialNewcontributors.body.php';

<?

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'TitleSearch',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Used for the related wikihows tool drop down auto-suggest feature',
);

$wgSpecialPages['TitleSearch'] = 'TitleSearch';
$wgAutoloadClasses['TitleSearch'] = dirname( __FILE__ ) . '/SpecialTitleSearch.body.php';

<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'ThankAuthors',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'A way for users to leave fan mail on authors user_kudos page',
);

$wgSpecialPages['ThankAuthors'] = 'ThankAuthors';
$wgAutoloadClasses['ThankAuthors'] = dirname( __FILE__ ) . '/SpecialThankAuthors.body.php';

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
	'name' => 'CreatePage',
	'author' => 'Travis Derouin',
	'description' => 'Provides a basic way entering a title and searching for potential duplicate articles before creating a page',
	'url' => 'http://www.wikihow.com/WikiHow:CreatePage-Extension',
);
$wgExtensionMessagesFiles['CreatePage'] = dirname(__FILE__) . '/CreatePage.i18n.php';

$wgSpecialPages['CreatePage'] = 'CreatePage';
$wgAutoloadClasses['CreatePage'] = dirname( __FILE__ ) . '/CreatePage.body.php';

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
    'name' => 'RCBuddy',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Helper special page for the wikihow editors toobar',
);

$wgSpecialPages['RCBuddy'] = 'RCBuddy';
$wgExtensionMessagesFiles['RCBuddy'] = dirname(__FILE__) . '/RCBuddy.i18n.php';
$wgAutoloadClasses['RCBuddy'] = dirname( __FILE__ ) . '/RCBuddy.body.php';

<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Generatefeed',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Generates the RSS feed for the featured articles', 
);


$wgSpecialPages['Generatefeed'] = 'Generatefeed'; 
$wgAutoloadClasses['Generatefeed'] = dirname( __FILE__ ) . '/SpecialGeneratefeed.body.php';
<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * An extension that displays basic stats about daily activity on the wiki.
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:DailyStats-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'DailyStats',
    'author' => 'Travis <travis@wikihow.com>',
	'description' => 'An extension that displays basic stats about daily activity on the wiki.',
);

$wgExtensionMessagesFiles['DailyStats'] = dirname(__FILE__) . '/DailyStats.i18n.php';

$wgSpecialPages['DailyStats'] = 'DailyStats'; 
$wgAutoloadClasses['DailyStats'] = dirname( __FILE__ ) . '/DailyStats.body.php';
<?php

if ( ! defined( 'MEDIAWIKI' ) )
    die();

/**#@+
 * Server side helper for the Firefox toolbar
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
#require_once("SpecialRecentchanges.php");

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Toolbarhelper',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Server side helper for the toolbar, could be replaced by RCBuddy at some point',
);

$wgExtensionMessagesFiles['Toolbarhelper'] = dirname(__FILE__) . '/Toolbarhelper.i18n.php';

$wgSpecialPages['Toolbarhelper'] = 'Toolbarhelper';
$wgAutoloadClasses['Toolbarhelper'] = dirname( __FILE__ ) . '/Toolbarhelper.body.php';
 
<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
* Displays supplementary search results for logged in users searching on wikiHow
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Google-API-Results Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'GoogleAPIResults',
    'author' => 'Travis <travis@wikihow.com>',
	'description' => 'Displays supplementary search results for logged in users searching on wikiHow',
);

#$wgExtensionMessagesFiles['GoogleAPIResults'] = dirname(__FILE__) . '/GoogleAPIResults.i18n.php';

$wgSpecialPages['GoogleAPIResults'] = 'GoogleAPIResults';
$wgAutoloadClasses['GoogleAPIResults'] = dirname( __FILE__ ) . '/GoogleAPIResults.body.php';
<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    
/**#@+
 * Provides a basic way of preventing articles with certain titles from being saved or created
 * @addtogroup Extensions
 *
 * @link http://www.mediawiki.org/wiki/Extension:BlockTitles Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionFunctions[] = 'wfBlockTitles';
$wgExtensionCredits['other'][] = array(
	'name' => 'BlockTitles',
	'author' => 'Travis Derouin',
	'description' => 'Provides a basic way of preventing articles with certain titles from being saved or created',
	'descriptionmsg' => 'block_title_error-desc',
	'url' => 'http://www.mediawiki.org/wiki/Extension:BlockTitles',
);

$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['BlockTitles'] = $dir . 'BlockTitles.i18n.php';
// CONFIGURE - place any regular expressions you want here.  
$wgBlockTitlePatterns = array (
		"/^http/i",  // if you want to block titles of articles that are URLs
	);

$wgHooks['ArticleSave'][] = 'wfCheckBlockTitles';

function wfBlockTitles() {
	wfLoadExtensionMessages( 'BlockTitles' );
}

function wfCheckBlockTitles (&$article ) {
	global $wgBlockTitlePatterns;
	global $wgOut;
	$title = $article->getTitle();
	$t = $title->getFullText();
	foreach ($wgBlockTitlePatterns as $re) {
		if (preg_match($re, $t)) {
			// too bad you can't pass parameter to errorpage
			$wgOut->errorpage('block_title_error_page_title', 'block_title_error' );
			return false;
		}	
	}

	return true;
}
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


$wgCategoriesArticle = "WikiHow:Categories";
$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Sitemap',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Generates a page of links to the top level categories and their subcatgories',
);

$wgExtensionMessagesFiles['Sitemap'] = dirname(__FILE__) . '/SpecialSitemap.i18n.php';

$wgSpecialPages['Sitemap'] = 'Sitemap';
$wgAutoloadClasses['Sitemap'] = dirname( __FILE__ ) . '/SpecialSitemap.body.php';
 
<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();
    /**#@+
 * An extension that allows users to rate articles. 
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Docentsettings-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Docentsettings',
    'author' => 'Travis <travis@wikihow.com>',
	'description' => 'Provides a way of administering docent settings',
);

$wgExtensionMessagesFiles['Docentsettings'] = dirname(__FILE__) . '/Docentsettings.i18n.php';

$wgSpecialPages['Docentsettings'] = 'Docentsettings';
$wgAutoloadClasses['Docentsettings'] = dirname( __FILE__ ) . '/Docentsettings.body.php';
<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'EmailLink',
    'author' => 'Travis <travis@wikihow.com>',
    'description' => 'Customed search backend for Google Mini and wikiHow',
);
$wgSpecialPages['EmailLink'] = 'EmailLink';
$wgAutoloadClasses['EmailLink'] = dirname( __FILE__ ) . '/SpecialEmailLink.body.php';

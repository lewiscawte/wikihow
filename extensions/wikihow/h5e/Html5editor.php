<?

if (!defined('MEDIAWIKI')) die();
    
/**#@+
 * Uses new HTML 5 capabilities to allow article editing inline
 * 
 * @package MediaWiki
 * @subpackage Extensions
 *
 * @link http://www.wikihow.com/WikiHow:Html5editor-Extension Documentation
 *
 *
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

require_once("$IP/includes/EasyTemplate.php");
require_once("$IP/extensions/wikihow/WikiHow_i18n.class.php");

$wgExtensionCredits['special'][] = array(
	'name' => 'Html5editor',
	'author' => 'Travis Derouin',
	'description' => 'Allows for editing of content with HTML5 features',
	'url' => 'http://www.wikihow.com/WikiHow:Html5editor-Extension',
);

#$wgExtensionMessagesFiles['Html5editor'] = dirname(__FILE__) . '/Html5editor.i18n.php';

$wgSpecialPages['Html5editor'] = 'Html5editor';
$wgAutoloadClasses['Html5editor'] = dirname( __FILE__ ) . '/Html5editor.body.php';
$wgExtensionMessagesFiles['Html5editor'] = dirname(__FILE__) . '/Html5editor.i18n.php';

$wgHooks['BeforePageDisplay'][] = array('Html5Setup');
$wgHooks['ArticleBeforeOutputWikiText'][] = array('Html5WrapTemplates');


function isHtml5Editable() {
	global $wgTitle, $wgRequest;
	$action = $wgRequest->getVal('action', '');
	$editable =
		(empty($action) || $action == 'view')
		&& $wgTitle->getFullText() != wfMsg('mainpage')
		&& $wgTitle->getNamespace() == NS_MAIN
		&& hasHtml5Browser();
	return $editable;
}

function hasHtml5Browser() {
	$userAgent = @$_SERVER['HTTP_USER_AGENT'];
	$match = preg_match('@(firefox/[3-9]|webkit/[5-9])@i', $userAgent);
	return $match > 0;
}

function Html5Setup() {
	global $wgOut, $wgRequest, $IP;

	global $wgTitle;
	$editable = isHtml5Editable();
	if ($editable) {
		wfLoadExtensionMessages('Html5editor');

		$imageUpload = Easyimageupload::getUploadBoxJS();
		$wgOut->addScript($imageUpload);

		EasyTemplate::set_path( dirname(__FILE__).'/' );
		$script = EasyTemplate::html('skin.tmpl.php');
		$wgOut->addScript($script);
	}

	return true;
}

function Html5SetupAddWrapperDiv($parser, $text) {
	$text = "<div id='bodycontents'>$text</div>";
	return true;
}

function Html5WrapTemplates($article, $content) {
	$parts = preg_split("@({{[^}]*}})@", $content, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$newcontent = "";
	//TODO: when an article is saved from advanced editor and regenerated this isn't called
	while ($x = array_shift($parts)) {
		if (strpos($x, "{{") === 0 && trim($x) != "") {
			$newcontent .= "<div class='template'>{$x}</div>";
		} else {
			$newcontent .= "$x\n";
		}
	}
	#echo $newcontent; exit;
	$content = $newcontent;
	#echo $content; exit;
	return true;
}

/**
 * Returns script to be placed in the head of the html doc for when the
 * edit buttons are pushed (so that they can force waiting until
 * the rest of the page has loaded).
 */
function Html5EditButtonBootstrap() {
	global $wgOut;
	EasyTemplate::set_path( dirname(__FILE__).'/' );
	$script = EasyTemplate::html('edit-bootstrap.tmpl.php');
	return $script;
}

/**
 * Show the new default edit page instead of the default "page not found".
 */
function Html5DefaultContent() {
	global $wgRequest, $wgOut;
	EasyTemplate::set_path( dirname(__FILE__).'/' );
	return EasyTemplate::html('new-article.tmpl.php');
}


<?
if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'DocViewer',
	'author' => 'Scott Cushman',
	'description' => 'The page that displays embedded documents.',
);

$wgSpecialPages['DocViewer'] = 'DocViewer';
$wgAutoloadClasses['DocViewer'] = dirname( __FILE__ ) . '/DocViewer.body.php';
$wgExtensionMessagesFiles['DocViewer'] = dirname(__FILE__) . '/DocViewer.i18n.php';
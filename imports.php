<?
#
# wikiHow Extensions
#

# English-specific extensions
if ($wgLanguageCode == 'en') {
	require_once("$IP/extensions/wikihow/FeaturedContributor.php");
	require_once("$IP/extensions/wikihow/IntroImageAdder.php");
	require_once("$IP/extensions/wikihow/rctest/RCTest.class.php");
	require_once("$IP/extensions/wikihow/rctest/RCTestGrader.php");
	require_once("$IP/extensions/wikihow/rctest/RCTestAdmin.php");
	require_once("$IP/extensions/wikihow/thumbsup/ThumbsUp.php");
	require_once("$IP/extensions/wikihow/thumbsup/ThumbsNotifications.php");
	require_once("$IP/extensions/wikihow/thumbsup/ThumbsEmailNotifications.php");
	require_once("$IP/extensions/wikihow/IheartwikiHow.php");
	require_once("$IP/extensions/wikihow/fblogin/FBLink.php");
	require_once("$IP/extensions/wikihow/h5e/Html5editor.php");
	require_once("$IP/extensions/wikihow/HAWelcome/HAWelcome.php");
	require_once("$IP/extensions/wikihow/titus/TitusQueryTool.php");
}

# International-specific extensions
if ($wgLanguageCode != 'en') {
	require_once("$IP/extensions/wikihow/Imagecounter.php");
}

require_once("$IP/extensions/wikihow/Misc.php");
require_once("$IP/extensions/wikihow/Importvideo.php");
require_once("$IP/extensions/CheckUser/CheckUser.php");
require_once("$IP/extensions/Newuserlog/Newuserlog.php");
require_once("$IP/extensions/SpamBlacklist/SpamBlacklist.php");
$wgSpamBlacklistFiles = array(
	"DB: " . WH_DATABASE_NAME . " Spam-Blacklist",
	"$IP/extensions/SpamBlacklist/wikimedia_blacklist",
	"$IP/extensions/SpamBlacklist/wikihow_custom",
);
require_once("$IP/extensions/Cite/Cite.php");
require_once("$IP/extensions/AntiSpoof/AntiSpoof.php");
require_once("$IP/extensions/UniversalEditButton/UniversalEditButton.php");
require_once("$IP/extensions/Drafts/Drafts.php");
require_once("$IP/extensions/ImageMap/ImageMap.php");
require_once("$IP/includes/EasyTemplate.php");
require_once("$IP/extensions/wikihow/Articlestats.php");
require_once("$IP/extensions/wikihow/Patrolcount.php");
require_once("$IP/extensions/wikihow/PatrolHelper.php");
require_once("$IP/extensions/BlockTitles/BlockTitles.php");
require_once("$IP/extensions/wikihow/LSearch.php");
require_once("$IP/extensions/wikihow/GoogSearch.php");
require_once("$IP/extensions/wikihow/NVGadget.php");
require_once("$IP/extensions/wikihow/GoogGadget.php");
require_once("$IP/extensions/wikihow/Newcontributors.php");
require_once("$IP/extensions/wikihow/TitleSearch.php");
require_once("$IP/extensions/wikihow/ThankAuthors.php");
require_once("$IP/extensions/wikihow/CreatePage.php");
require_once("$IP/extensions/wikihow/TwitterFeed.php");
require_once("$IP/extensions/wikihow/Feed.php");
require_once("$IP/extensions/wikihow/Follow.php");
require_once("$IP/extensions/wikihow/Standings.php");
require_once("$IP/extensions/wikihow/QC.php");
require_once("$IP/extensions/wikihow/Unguard.php");
require_once("$IP/extensions/wikihow/CheckG.php");
require_once("$IP/extensions/wikihow/Vanilla.php");
require_once("$IP/extensions/ProxyConnect/ProxyConnect.php");
require_once("$IP/extensions/wikihow/AddRelatedLinks.php");
require_once("$IP/extensions/wikihow/ImportXML.php");
require_once("$IP/extensions/wikihow/TopCategoryHooks.php");
require_once("$IP/extensions/wikihow/Netseer.php");
require_once("$IP/extensions/wikihow/Managepagelist.php");
require_once("$IP/extensions/wikihow/Unpatrol.php");
require_once("$IP/extensions/wikihow/RCPatrol.php");
require_once("$IP/extensions/wikihow/fblogin/FBLogin.php");
require_once("$IP/extensions/wikihow/WikiHow.php");
require_once("$IP/extensions/wikihow/Wikitext.class.php");
require_once("$IP/extensions/wikihow/RobotPolicy.class.php");
require_once("$IP/extensions/wikihow/ConfigStorage.class.php");
require_once("$IP/extensions/wikihow/CachePrefetch.class.php");
require_once("$IP/extensions/wikihow/WikiPhoto.class.php");
require_once("$IP/extensions/wikihow/FBAppContact.php");
require_once("$IP/extensions/wikihow/Categorylisting.php");
require_once("$IP/extensions/wikihow/FixCaps.php");
require_once("$IP/extensions/wikihow/Randomizer.php");
require_once("$IP/extensions/wikihow/Radlinks.php");
require_once("$IP/extensions/wikihow/BuildWikiHow.php");
require_once("$IP/extensions/wikihow/Generatefeed.php");
require_once("$IP/extensions/wikihow/ToolbarHelper.php");
require_once("$IP/extensions/wikihow/Sitemap.php");
require_once("$IP/extensions/wikihow/EmailLink.php");
require_once("$IP/extensions/wikihow/Newarticleboost.php");
require_once("$IP/extensions/wikihow/Suggest.php");
require_once("$IP/extensions/wikihow/MWMessages.php");
require_once("$IP/extensions/wikihow/RateArticle.php");
require_once("$IP/extensions/wikihow/SpamDiffTool.php");
require_once("$IP/extensions/wikihow/Bunchpatrol.php");
require_once("$IP/extensions/wikihow/Republish.php");
require_once("$IP/extensions/wikihow/MultipleUpload.php");
require_once("$IP/extensions/wikihow/GenerateJSFeed.php");
require_once("$IP/extensions/FormatEmail/FormatEmail.php");
require_once("$IP/extensions/wikihow/MagicArticlesStarted.php");
require_once("$IP/extensions/Postcomment/SpecialPostcomment.php");
require_once("$IP/extensions/Renameuser/SpecialRenameuser.php");
require_once("$IP/extensions/wikihow/FacebookPage.php");
require_once("$IP/extensions/wikihow/Categoryhelper.php");
require_once("$IP/extensions/wikihow/CheckJS.php");
require_once("$IP/extensions/wikihow/Sugg.php");
require_once("$IP/extensions/wikihow/ManageRelated/ManageRelated.php");
require_once("$IP/extensions/wikihow/Monitorpages.php");
require_once("$IP/extensions/wikihow/Changerealname.php");
require_once("$IP/extensions/ConfirmEdit/ConfirmEdit.php");
require_once("$IP/extensions/ConfirmEdit/FancyCaptcha.php");
require_once("$IP/extensions/ParserFunctions/ParserFunctions.php");
require_once("$IP/extensions/wikihow/AutotimestampTemplates.php");
require_once("$IP/extensions/ImportFreeImages/ImportFreeImages.php");
require_once("$IP/extensions/PopBox/PopBox.php");
require_once("$IP/extensions/wikihow/EmbedVideo.php");
require_once("$IP/extensions/wikihow/catsearch/CatSearch.php");
require_once("$IP/extensions/wikihow/catsearch/CatSearchUI.php");
require_once("$IP/extensions/wikihow/cattool/Categorizer.php");
require_once("$IP/extensions/wikihow/articledata/ArticleData.php");
require_once("$IP/extensions/wikihow/catsearch/CategoryInterests.php");
require_once("$IP/extensions/wikihow/Mypages.php");
require_once("$IP/extensions/wikihow/WikiHowHooks.php");
require_once("$IP/extensions/wikihow/WikiHow_i18n.class.php");
require_once("$IP/extensions/wikihow/HtmlSnips.class.php");
require_once("$IP/extensions/wikihow/MarkFeatured.php");
require_once("$IP/extensions/SyntaxHighlight_GeSHi/SyntaxHighlight_GeSHi.php");
require_once("$IP/extensions/wikihow/UserTalkTool.php");
require_once("$IP/extensions/wikihow/Welcome.php");
require_once("$IP/extensions/wikihow/Authorleaderboard.php");
require_once("$IP/extensions/wikihow/AuthorEmailNotification.php");
require_once("$IP/extensions/wikihow/Charityleaderboard.php");
require_once("$IP/extensions/wikihow/Avatar.php");
require_once("$IP/extensions/wikihow/ProfileBox.php");
require_once("$IP/extensions/wikihow/QuickNoteEdit.php");
require_once("$IP/extensions/wikihow/eiu/Easyimageupload.php");
require_once("$IP/extensions/wikihow/Leaderboard.php");
require_once("$IP/extensions/wikihow/mobile/MobileWikihow.php");
require_once("$IP/extensions/wikihow/mqg/MQG.php");
require_once("$IP/extensions/OpenID/OpenID.setup.php");
require_once("$IP/extensions/wikihow/FollowWidget.php");
require_once("$IP/extensions/wikihow/Videoadder.php");

// We create a triaged form of wikiHow if WIKIHOW_LIMITED is defined 
// in LocalSettings.php, which requires fewer resources and pings 
// our servers less.
if (!defined('WIKIHOW_LIMITED')) {
	require_once("$IP/extensions/wikihow/RCBuddy.php");
	require_once("$IP/extensions/wikihow/RCWidget.php");
	require_once("$IP/extensions/wikihow/dashboard/CommunityDashboard.php");
	require_once("$IP/extensions/wikihow/TwitterReplier/TwitterReplier.php");
	require_once("$IP/extensions/wikihow/BounceTimeLogger.php");
}

require_once("$IP/extensions/wikihow/WikihowCSSDisplay.php");
require_once("$IP/extensions/wikihow/StatsList.php");
require_once("$IP/extensions/wikihow/AdminResetPassword.php");
require_once("$IP/extensions/wikihow/AdminMarkEmailConfirmed.php");
require_once("$IP/extensions/wikihow/AdminRemoveAvatar.php");
require_once("$IP/extensions/wikihow/AdminLookupPages.php");
require_once("$IP/extensions/wikihow/AdminEnlargeImages.php");
require_once("$IP/extensions/wikihow/AdminEditInfo.php");
require_once("$IP/extensions/wikihow/AdminBounceTests.php");
require_once("$IP/extensions/wikihow/AdminSearchResults.php");
require_once("$IP/extensions/wikihow/AdminConfigEditor.php");
require_once("$IP/extensions/wikihow/Bloggers.php");
require_once("$IP/extensions/wikihow/LoginReminder.php");
require_once("$IP/extensions/wikihow/NewHowtoArticles.php");
require_once("$IP/extensions/wikihow/fbnuke/FBNuke.php");
require_once("$IP/extensions/wikihow/editfinder/EditFinder.php");
require_once("$IP/extensions/wikihow/ctalinks/CTALinks.php");
require_once("$IP/extensions/wikihow/dashboard/AdminCommunityDashboard.php");
require_once("$IP/extensions/wikihow/slider/Slider.php");
require_once("$IP/extensions/wikihow/starter/StarterTool.php");
require_once("$IP/extensions/wikihow/ProfileBadges.php");
require_once("$IP/extensions/wikihow/ImageHelper/ImageHelper.php");
require_once("$IP/extensions/wikihow/ImageCaptions.php");
require_once("$IP/extensions/wikihow/TitleIterator.php");
require_once("$IP/extensions/wikihow/nfd/NFDGuardian.php");
require_once("$IP/extensions/wikihow/gallery/GallerySlide.php");
require_once("$IP/extensions/wikihow/ArticleMetaInfo.class.php");
require_once("$IP/extensions/wikihow/TitleTests.class.php");
require_once("$IP/extensions/wikihow/GoodRevision.class.php");
require_once("$IP/extensions/wikihow/DailyEdits.class.php");
require_once("$IP/extensions/wikihow/ArticleWidgets/ArticleWidgets.php");
require_once("$IP/extensions/wikihow/spellchecker/Spellchecker.php");
require_once("$IP/extensions/wikihow/ToolSkip.class.php");
require_once("$IP/extensions/wikihow/wikihowAds/wikihowAds.class.php");
require_once("$IP/extensions/wikihow/WikihowShare.class.php");
require_once("$IP/extensions/wikihow/AdminNoIntroImage.php");

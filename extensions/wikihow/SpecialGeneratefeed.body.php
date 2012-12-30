<?php

class Generatefeed extends UnlistedSpecialPage {
	public function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('Generatefeed');
	}

	private static function addTargetBlank($source) {
		$preg = '/<a href=/';
		$source = preg_replace($preg, '<a target="_blank" href=', $source);
		return $source;
	}

	public static function getLastPatrolledRevision(&$title) {
		$a = null;
		$dbr =& wfGetDB( DB_SLAVE );
		$page_id = $title->getArticleID();
		$sql = "SELECT max(rc_this_oldid) as A from recentchanges WHERE rc_cur_id = $page_id and rc_patrolled = 1";
		$res = $dbr->query($sql);
		if (false !== $res
			&& $dbr->numRows($res) > 0
			&& $row = $dbr->fetchObject($res) )
		{
			if ($row->A) $a = new Article($title, $row->A);
		}
		$dbr->freeResult($res);

		// if that didn't work, get the last edit that's not in recentchanges
		if ($a == null) {
			$sql = "select max(rev_id) as A from revision where rev_page = $page_id and rev_id NOT IN (select rc_this_oldid from recentchanges where rc_cur_id = $page_id and rc_patrolled = 0);";
			$res = $dbr->query($sql);
			if (false !== $res) {
				if ($row = $dbr->fetchObject( $res ) ) {
					// why does this work in the line above? $row->A > 0 ????
					if ($row->A > 0) $a = new Article($title, $row->A);
				}
			}
		}
		if ($a == null) {
			$a = new Article(&$title);
		}
		return $a;
	}

	// I re-used this code without copying it in the mobile MobileWikihow
	// class. -Reuben
	public static function getArticleSummary(&$article, &$title) {
		global $wgParser;
		$summary = Article::getSection($article->getContent(true), 0);
		// remove templates from intro
		$summary = preg_replace('@\{\{[^}]*\}\}@', '', $summary);
		$summary = preg_replace('@\[\[Image:[^\]]*\]\]@', '', $summary);
		// parse summary from wiki text to html
		$output = $wgParser->parse($summary, $title, new ParserOptions() );
		// strip html tags from summary
		$summary = trim(strip_tags($output->getText()));
		return $summary;
	}

	function getImages(&$article, &$title) {
		global $wgParser;
		$content = $article->getContent(true);

		$images = array();

		$count = 0;
		preg_match_all("@\[\[Image[^\]]*\]\]@im", $content, $matches);
		foreach($matches[0] as $i) {
			$i = preg_replace("@\|.*@", "", $i);
			$i = preg_replace("@^\[\[@", "", $i);
			$i = preg_replace("@\]\]$@", "", $i);
			$i = urldecode($i);
			$image = Title::newFromText($i);
			if ($image && $image->getArticleID() > 0) {
				$file = wfFindFile($image);
				if (isset($file)) {
					/* UNCOMMENT TO USE REAL IMAGES RATHER THAN THUMBNAILS IN MRSS - GOOGLE ISSUE
					$images[$count]['src'] = $file->getUrl();
					$images[$count]['width'] = $file->getWidth();
					$images[$count]['height'] = $file->getHeight();
					*/
					$thumb = $file->getThumbnail(200);
					$images[$count]['src'] = $thumb->url;
					$images[$count]['width'] = $thumb->width;
					$images[$count]['height'] = $thumb->height;
					$images[$count]['size'] = $file->getSize();
					$images[$count]['mime'] = $file->getMimeType();
					$count++;
				} else {
					wfDebug("VOOO SKIN gallery can't find image $i \n");
				}
			} else {
				wfDebug("VOOO SKIN gallery can't find image title $i \n");

			}
		}

		return $images;
	}

	public function execute($par) {
		global $wgUser, $wgOut, $wgLang, $wgContLang, $wgTitle, $wgMemc;
		global $IP, $wgDBname, $wgParser;
		global $wgRequest, $wgSitename, $wgLanguageCode, $wgContLanguageCode;
		global $wgFeedClasses, $wgUseRCPatrol;
		global $wgScriptPath, $wgServer;
		global $wgSitename, $wgFeedClasses, $wgContLanguageCode;
		global $messageMemc, $wgDBname, $wgFeedCacheTimeout;
		global $wgFeedClasses, $wgTitle, $wgSitename, $wgContLanguageCode;

		$fname = 'wfSpecialGeneratefeed';

		$fullfeed = 0;
		$mrss = 0;
		if ($par == 'fullfeed') $fullfeed = 1;
		else if ($par == 'mrss') $mrss = 1;

		require_once("$IP/extensions/wikihow/FeaturedRSSFeed.php");
		require_once("$IP/extensions/wikihow/FeaturedArticles.php");
		require_once("$IP/extensions/wikihow/BlogPosts.php");

		header('Content-Type: text/xml');
		$wgOut->setSquidMaxage(60);
		$feedFormat = 'rss';
		$timekey = "$wgDBname:rcfeed:$feedFormat:timestamp";
		$key = "$wgDBname:rcfeed:$feedFormat:limit:$limit:minor:$hideminor";

		$feedTitle = wfMsg('Rss-feedtitle');
		$feedBlurb = wfMsg('Rss-feedblurb');
		$feed = new FeaturedRSSFeed(
			$feedTitle,
			$feedBlurb,
			"$wgServer$wgScriptPath/Main-Page"
		);

		if ($mrss) {
			$feed->outHeaderMRSS();
		} else {
			// Replace to get back to raw feed (not full and without mrss)
			// $feed->outHeader();
			$feed->outHeaderFullFeed();
		}

		// extract the number of days
		$days = 6;

		date_default_timezone_set('UTC');
		if ($wgRequest->getVal('micro', null) == 1) {
			$days = FeaturedArticles::getNumberOfDays($days, 'RSS-Microblog-Feed');
			$feeds = FeaturedArticles::getFeaturedArticles($days, 'RSS-Microblog-Feed');
		} else {
			$days = FeaturedArticles::getNumberOfDays($days);
			$feeds = FeaturedArticles::getFeaturedArticles($days);

			if (!$wgRequest->getVal( 'fa-only' )) {
				$posts = BlogPosts::getBlogPosts($days);
				
				//merge in the blog posts
				$feeds = array_merge($feeds,$posts);
				
				//order by the date
				$a = $feeds;
				foreach($a as $k=>$v) {
					$b[$k] = $v[1];
				}
				arsort($b);
				foreach($b as $k=>$v) {
					$c[] = $a[$k];
				}
				$feeds = $c;
			}
		}
				
		$now = time();
		$itemcount = 0;
		$itemcountmax = 6;
		foreach ($feeds as $f) {
			$url = $f[0];
			$d = $f[1];
			if ($d > $now) continue;

			$url = str_replace('http://wiki.ehow.com/', '', $url);
			$url = str_replace('http://www.wikihow.com/', '', $url);
			$url = str_replace($wgServer . $wgScriptPath . '/', '', $url);
			$title = Title::newFromURL(urldecode($url));
			$summary = '';
			$content = '';
			if ($title == null) {
				echo "title is null for $url";
				exit;
			}
			
			//from the blog?  format differently
			if ((!$wgRequest->getVal( 'fa-only' )) and ($f[4] == 'wikihowblog')) {
				$post_url = $f[0];
				$post_date = $f[1];
				$post_title = $f[2];
				$post_text = $f[3];
				$post_tag = $f[4];
				
				$post_content = '';
			
				$item = new FeedItem(
					$post_title,
					$post_text,
					$post_url,
					$post_date,
					null,
					$post_url
				);			
				
				$feed->outItem($item);
				//$feed->outItemFullFeed($item, $post_content, $images);
			}
			else {
			//from the Featured Articles
				if ($title->getArticleID() > 0) {
					$article = self::getLastPatrolledRevision($title);
					$summary = self::getArticleSummary($article, $title);
					$images = self::getImages($article, $title);

					//XXFULL FEED
					if (!$mrss) {
						$content = $article->getContent(true);
						$content = preg_replace('/\{\{[^}]*\}\}/', '', $content);
						$output = $wgParser->parse($content, $title, new ParserOptions() );
						$content = self::addTargetBlank($output->getText());
						$content = preg_replace('/href="\//', 'href="'.$wgServer.'/', $content);
						$content = preg_replace('/src="\//', 'src="'.$wgServer.'/', $content);
						$content = preg_replace('/<span id="gatEditSection" class="editsection1">(.*?)<\/span>/', '', $content);
						$content = preg_replace('/<h2> <a target="_blank" href="(.*?)>edit<\/a>/', '<h2>', $content);
						$content = preg_replace('/<img src="(.*?)\/skins\/common\/images\/magnify-clip.png"(.*?)\/>/', '', $content);

						$linkEmail = $wgServer .'/index.php?title=Special:EmailLink&target='. $title->getPrefixedURL() ;

						$backlinks = "\n<div id='articletools'><div class='SecL'></div><div class='SecR'></div><a name='articletools'></a><h2> <span>".wfMsg('RSS-fullfeed-articletools')."</span></h2> </div>";
						$backlinks .= "<ul>\n";
						$backlinks .= "<li type='square'><a target='_blank' href='".$wgServer."/".$title->getPrefixedURL()."'>".wfMsg('RSS-fullfeed-articletools-read')."</a></li>\n";
						$backlinks .= "<li type='square'><a target='_blank' href='".$linkEmail."'>".wfMsg('RSS-fullfeed-articletools-email')."</a></li>\n";
						$backlinks .= "<li type='square'><a target='_blank' href='".$wgServer.$title->getEditURL()."'>".wfMsg('RSS-fullfeed-articletools-edit')."</a></li>\n";
						$backlinks .= "<li type='square'><a target='_blank' href='".$wgServer."/".$title->getTalkPage()."'>".wfMsg('RSS-fullfeed-articletools-discuss')."</a></li>\n";
						$backlinks .= "<ul>\n";

						$content .= $backlinks;
					}
				} else {
					continue;
				}

				$talkpage = $title->getTalkPage();

				$title_text = $title->getPrefixedText();
				if (isset($f[2])
					&& $f[2] != null
					&& trim($f[2]) != '')
				{
					$title_text = $f[2];
				} else {
					$title_text = wfMsg('howto', $title_text);
					}

				$item = new FeedItem(
					$title_text,
					$summary,
					$title->getFullURL(),
					$d,
					null,
					$talkpage->getFullURL()
				);

				if ($mrss) {
					$feed->outItemMRSS($item, $images);
				} else {
					// Replace to get back to raw feed (not full and without mrss)
					// $feed->outItem($item);
					$feed->outItemFullFeed($item, $content, $images);
				}
			}
			$itemcount++;

		}
		$feed->outFooter();
	}
}


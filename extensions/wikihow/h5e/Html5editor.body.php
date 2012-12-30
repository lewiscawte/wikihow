<?

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/skins/WikiHowSkin.php");

class Html5editor extends SpecialPage {

	// the map of interwiki links
	var $mInterwiki = null;
	public static $spam_message = null;

    function __construct() {
        SpecialPage::SpecialPage( 'Html5editor' );
		$this->mInterwiki = array();
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('interwiki', array('iw_prefix', 'iw_url') );
		while ($row = $dbr->fetchObject($res)) {
			$this->mInterwiki[$row->iw_prefix] = $row->iw_url;
		}
    }

	function debug($filename, $text) {
		$handle = fopen("/tmp/$filename", "w");
		fwrite($handle, $text . "\n");
		fclose($handle);
	}

	function setSpamMsg($match = false) {
        self::$spam_message = wfMsg( 'spamprotectiontext' );
        if ( $match ) {
            self::$spam_message .= wfMsgExt( 'spamprotectionmatch', 'parse', wfEscapeWikiText( $match ) );
        }
		return true;
	}

	// converts link to either external or an interwiki link
	// depending on what we have set up for interwiki links
	function convertLink ($href, $text) {
		// check to see if it's interwiki or not!
		preg_match("@.*/@", $href, $matches);
		$base = $matches[0];
		foreach ($this->mInterwiki as $prefix=>$url) {
			preg_match("@.*/@", $url, $matches);
			if ($matches[0] == $base) {
				$x = preg_replace("@.*/@", "", $href);
				echo "$url - $x\n";
				return "[[{$prefix}:" . $x . "|$text]]";
			}
		}	
		return "[$href $text]";	
	}

	function handleLinks(&$doc, &$xpath, $oldtext) {
		// boundary case, self links, just remove the link
        $nodes = $xpath->query("//strong[@class='selflink']");
		foreach ($nodes as $node) {
           	$newnode  = $doc->createTextNode($node->nodeValue);
			$node->parentNode->replaceChild($newnode, $node);
		}

        $nodes = $xpath->query("//a");
		foreach ($nodes as $node) {
			$href = $node->getAttribute('href');
			$name = $node->getAttribute('name');
			$class = $node->getAttribute('class');
			$val = $node->nodeValue;
			// process images somewhere else
			if ($class == 'image') 
				continue;
			// ignore placeholders put in by the skin
			if (($name != '' && $href == '') || $name =='gatEditSection') {
				$node->parentNode->removeChild($node);
				continue;
			}
			if (preg_match("@^/@", $href)) {
				$href = substr($href, 1);
			}
			wfDebug("AXX: name: {$node->nodeName}, class: {$class} val: {$node->nodeValue}, href: $href name: $name \n");
			// handle external links
			if (preg_match("@external.*autonumber@", $class)) {
            		$newnode = $doc->createTextNode("[{$href}]");
					$node->parentNode->replaceChild($newnode, $node);
					continue;
			} else if (preg_match("@external@", $class)) {
				if ($val == $href) {
            		$newnode = $doc->createTextNode("{$val}");
				} else {
            		$newnode = $doc->createTextNode("[{$href} {$val}]");
				}
				$node->parentNode->replaceChild($newnode, $node);
				continue;
			} 
			$t = Title::newFromURL(urldecode($href));
			if ($t) {
				if ($t->getText() == $val) {
            		$newnode = $doc->createTextNode("[[{$val}]]");
					wfDebug("AXX: link: [[$val]]\n");
				} else {
            		$newnode = $doc->createTextNode("[[{$t->getText()}|$val]]");
					wfDebug("AXX: link: [[{$t->getText()}|$val]]\n");
				}
				$node->parentNode->replaceChild($newnode, $node);
				continue;
			}
		}
	}

	// returns whether or not there were any references to handle	
	function handleReferences(&$doc, &$xpath, $oldtext) {
		// get rid of the visual representation of the reference
        $nodes = $xpath->query("//sup");
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}
		// get rid of the "references have been removed part" 
        $nodes = $xpath->query("//span[@class='h5e-no-refs']");
		foreach ($nodes as $node) {
			$node->parentNode->removeChild($node);
		}

		// process Reuben's hidden links
		$refs = 0;
		$nodes = $xpath->query("//input"); 
		foreach ($nodes as $node) {	
			if (strpos($node->getAttribute('id'), "h5e-ref") !== false) {
				// this is the best way to create a <ref>http://google.com</ref> node
				$newnode = $doc->createElement("ref");
            	$child = $doc->createTextNode($node->getAttribute('value'));
            	$newnode->appendChild($child);
				$node->parentNode->replaceChild($newnode, $node);
				$refs++;
			}
		}
		return $refs;
		/* 
		/// THIS IS THE OLD CODE - let's keep this in for now, could be useful later.
		/// 
        //convert references back into wikitext before templates
        // first gather the references from the bottom  
        $nodes = $xpath->query("//ol[@class='references']");
        $refs = array();
        $index = 0;
        foreach ($nodes as $node) {
            $links = $xpath->query("li", $node);
            foreach ($links as $link) {
                $ref = "";
                foreach ($link->childNodes as $c) {
                    switch ($c->nodeName) {
                        case "#text":
                            $ref .= $c->nodeValue;
                            break;
                        case "a":
                            $class = $c->attributes->getNamedItem("class");
                            $href  = $c->attributes->getNamedItem("href");
                            if ($class->textContent == "external text" || $class->textContent == "extiw") {
                                #$ref .= "[{$href->textContent} {$c->textContent}]";
                                $ref .= $this->convertLink($href->textContent, $c->textContent);
                            }
                            break;
                    }
                }
                $refs[$index] = trim($ref);
                $index++;
            }
			$node->parentNode->removeChild($node);
        }
        $nodes = $xpath->query("//sup[@class='reference']");
        $index = 0;
        foreach ($nodes as $node) {
            $newnode = $doc->createElement("ref");
            $child = $doc->createTextNode($refs[$index]);
            $newnode->appendChild($child);
            $node->parentNode->replaceChild($newnode, $node);
            $index++;
        } 
		return sizeof($refs) > 0;
		*/
	}

	function handleImages(&$doc, &$xpath, $oldtext) {

		// find the old images, create an associate array mapping the image name to the 
		// wikitext that was used to include it in the article, use this when we can 
		// since the user can't edit/resize images right now using the HTML5 editor
		// now there could be a bug here if the same image is used multiple times in different sizes/formats
		// on the same page
		$oldimages = array();
		preg_match_all("@\[\[Image:[^\]]*\]\]@", $oldtext, $matches);
		foreach ($matches[0] as $m) {
			$name = preg_replace("@\[\[Image:@", "", $m);
			$name = preg_replace("@(\||\]).*@", "", $name);
			$oldimages[$name] = $m;
		}
	
        $nodes = $xpath->query("//div[@class='mwimg']");
        foreach ($nodes as $node) {
            #wfDebug("got node: ". $doc->saveXML($node) . "\n");
            $xml = $doc->saveXML($node);

            preg_match("@<img[^>]*>@i", $xml, $matches);
            $text = $matches[0];

			wfDebug("IMG: $text name: {$node->nodeName}, class: {$class} val: {$node->nodeValue}, href: $href name: $name \n");
            $thumb = preg_match("@/thumb@", $text);
            #$name = preg_replace("@.*(/thumb)?/[a-z]/[a-z0-9]*/@", "", $text);
            if ($thumb) {
                $name = preg_replace("@.*src=\"@", "", $text);
                $name = preg_replace("@/images/thumb/[a-z0-9]*/[0-9a-z]*/@", "", $name);
                $name = preg_replace("@/.*@", "", $name);
            } else {
                $name = preg_replace("@.*src=\"@", "", $text);
                $name = preg_replace("@\".*@", "", $name);
                $name = preg_replace("@.*/@", "", $name);
            }

			$title = Title::makeTitle(NS_IMAGE, urldecode($name));
			$name = $title->getText();   
			
			// what if the caption changed? 
            $caption = null;
            $caption_nodes = $xpath->query(".//span[@class='caption']", $node);
			$i = 0; 
            foreach ($caption_nodes as $c) { 
				// a stupid way of doing this to grab the caption
				$caption = $c->textContent; break;
            }

			/// this is a failover, and place holder for future expansion where 
			// users can edit existing images or add new ones
            #echo $text . "\n"; echo $name . "\n"; exit;
            $wikitext = "[[Image:";
            $align = "";
            if (preg_match("@floatright|tright@", $xml))
                $align = "right";
            else if (preg_match("@floatleft|tleft@", $xml))
                $align = "left";
            $wikitext .= $name;
            if ($thumb) {
                $wikitext .= "|thumb";
                $width = preg_replace("@.*width=\"(\d+)+\".*@", '$1', $text);
                if ($width && $width != "180")
                    $wikitext .= "|{$width}px";
            } 
            if ($align) $wikitext .= "|{$align}";
            if ($caption) $wikitext .= "|{$caption}";
            $wikitext .= "]]";
        	$newnode = $doc->createTextNode($wikitext);
            $node->parentNode->replaceChild($newnode, $node);
        }
	}

	function convertHTML2Wikitext($html, $oldtext) {
		$lang="en";
		$html = preg_replace("@\n@", "", $html); 

$articleText = <<<DONE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="$lang" lang="$lang">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset='utf-8'" />
</head>
<body>
$html
</body>
</html>
DONE;

		#wfDebug("html5: " . __LINE__ . " so far so good\n");
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->strictErrorChecking = false;
        $doc->recover = true;
        @$doc->loadHTML($articleText);
        #$doc->normalizeDocument();
        $xpath = new DOMXPath($doc);
		$this->debug("input.html", $doc->saveHTML());

		// handle references
		$hadrefs = $this->handleReferences(&$doc, &$xpath, &$oldtext);
		$this->handleLinks(&$doc, &$xpath, &$oldtext);

		// filter out templates, we add these back in from the old wikitext
		// any incoming ads from anons gets whacked!
        $nodes = $xpath->query("//div[@class='template']|//div[@class='wh_ad']|//div[@class='step_num']");
        foreach ($nodes as $node) {
            if ($node)
				$node->parentNode->removeChild($node);
        }
       
		// remove the table of contents, any scripts
		$nodes = $xpath->query("//table[@id='toc']|//script");
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
        $nodes = $xpath->query("//h2"); // remove <h2>Contents</h2> from TOC
        foreach ($nodes as $node) {
			wfDebug("AX:" . $node->nodeValue . "\n");
			if ($node->nodeValue == "Contents")
            	$node->parentNode->removeChild($node);
		}
      
      
		// handle the bold tags produced by the skin
		$nodes = $xpath->query("//b[@class='whb']");
        foreach ($nodes as $node) {
            $newnode = $doc->createElement("div", "");
			foreach ($node->childNodes as $c) {
				// do this instead of cloning the node if we can, the clone node puts extra padding in there
				// for some reason
				if ($c->nodeName == '#text')  {
					$x = $doc->createTextNode(trim($c->nodeValue));
					wfDebug("WHB: +{$doc->saveXML($x)}+\n");
					$val= $c->nodeValue;
					$newnode->appendChild($doc->createTextNode($val));
				} else {
					$newnode->appendChild($c->cloneNode(true));
				}
			}
            $node->parentNode->replaceChild($newnode, $node);
        }

        $nodes = $xpath->query("//li[@style='font-style: italic;']");
        foreach ($nodes as $node) {
			$xi = $doc->createElement("i");
			foreach ($node->childNodes as $p) {
				$xi->appendChild($p);
			}
			$node->removeAttribute("style");
			$li = $doc->createElement("li");
			$li->appendChild($xi);
            $node->parentNode->replaceChild($li, $node);
		}

		// convert h3 to ===
        $nodes = $xpath->query("//h3");
        foreach ($nodes as $node) {
            $newnode = $doc->createElement("div", "");
          	$newnode->appendChild($doc->createTextNode("=== "));
            foreach ($node->childNodes as $c) {
                $newnode->appendChild($c->cloneNode(true));
            }
            $node->parentNode->replaceChild($newnode, $node);
          	$newnode->appendChild($doc->createTextNode(" ===\n"));
        }

		// handle video, it's not editable, preserve it	
		#echo $doc->saveXML() . "\n\n";
        $nodes = $xpath->query("//div[@id='video']");
        foreach ($nodes as $node) {
			preg_match("@{{Video:[^}]*}}@", $oldtext, $matches);
			// because apparently we can't add children to a textNode, fucking stupid.
            $newnode = $doc->createTextNode(trim($matches[0]) . "\n");
            $node->parentNode->replaceChild	($newnode, $node);
        }
		
		// replace image nodes with their associated wikitext 
		//$this->debug("input-beforeimages.html", $doc->saveHTML());
		$this->handleImages($doc, $xpath, $oldtext);
		$this->debug("input-afterimages.html", $doc->saveHTML());
		
		$html = $doc->saveHTML();
		// get rid of the tags produced by the DOM stuff
		// not using preg_replace because it can cause a seg fault
		$index = stripos($html, "<body>");
		if ($index !== false) 
			$html = substr($html, $index + strlen("<body>"));
		$index = stripos($html, "</body>");
		if ($index !== false) 
			$html = substr($html, 0, $index);

		# remove the stuff that the skin adds in there
		$html = preg_replace('@<a name="[a-z]*" id="[a-z]*"></a>@im', "", $html);
		$html = preg_replace("@<div class=['|\"]clearall['|\"]></div>@", "", $html);
		$html = preg_replace("@<!--(\n|.)*-->@im", "", $html);
		$html = htmlspecialchars_decode($html);

		// break the text into parts and convert back into wikitext
		$htmlparts = preg_split("@(<[^>]*>)@im", $html,
                   0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$this->debug("input2.html", $doc->saveHTML());

		#print_r($htmlparts);
		$parts = array();
		$wikitext = "";
		$useprefix = false;
		while ($x = array_shift($htmlparts)) {
			$lx = strtolower($x);
			if (trim($x) == "") continue;
			if (preg_match("@<ol@", $lx)) {
				$wikitext .= "\n";
				array_push($parts, "#");	
				#echo "Pushing #" . implode("", $parts) . "\n";
				continue;
			} else if (preg_match("@<h2@", $lx)) {
				// eat up all of the html until we hit the end of the h2 tag
				$sectionname = "";
				while ($next = array_shift($htmlparts)) {
					if ($next == "</h2>") break;
					if ($next == "<span>") $sectionname = array_shift($htmlparts);
				}
				$wikitext .= "\n== " . trim($sectionname) . " ==\n";
				$parts = array(); // reset the prefix regardless
				continue;
			} else if (preg_match("@<a @", $lx)) {
				// links are now handled by this->handleLinks, keeep
				// this in here for now until we are sure we can ditch it
				preg_match("@href=['|\"]*[^'\"]*['|\"]@", $x, $matches);
				$link = $matches[0];
				if (strpos($link, "#") === 0) {
					array_shift($htmlparts);
					array_shift($htmlparts);
					continue;
				}
				if (!$link) continue; // happens for <a name='adsf'> which we ignore
				$text = array_shift($htmlparts);
				$link = urldecode(preg_replace("@^href=|['|\"](/)?@im", "", $link));
				if (preg_match("@class=['|\"]*external@i", $lx)) {
					// external link
					// TODO: may also have to check for non http://[a-z]*.wikihow.com links
					if (strcasecmp($link, $text) == 0)  
						$x = "[$link]";
					else
						$x = $this->convertLink($link, $text);
				} else {
					$link = urldecode(preg_replace("@href=['|\"]/|\"|'@im", "", $link));
					$r = Title::newFromURL($link);
					// sometimes tags can wind up in here - bad parsing
					if ($r) 
						$link = $r->getText();
					if ($text == $link) 
						$x = "[[{$text}]]";	
					else
						$x = "[[{$link}|{$text}]]";	
					wfDebug("AX: making $link with $text\n");
				}	
			} else if (preg_match("@</a>@", $lx)) {
				continue;
			} else if (preg_match("@<ul@", $lx)) {
				array_push($parts, "*");	
				#echo "Pushing * " . implode("", $parts) . "\n";
				$wikitext .= "\n";
				continue;
			} else if (preg_match("@<[/]?span@", $lx)) {
				continue;
			} else if (preg_match("@</ol>|</ul>@", $lx)) {
				$x = array_pop($parts);
				#echo "Popping $x: " . implode("", $parts) . "\n";
				continue;
			} else if (preg_match("@<i>@", $lx)) {
				$x = " ''";
			} else if (preg_match("@</i>@", $lx)) {
				$x = "'' ";
			} else if (preg_match("@<[/]?b>@", $lx)) {
				$x = "'''";
			} else if (preg_match("@<img@", $lx)) {
				// images should have been handled by this point, if not, why are we here? 
				wfDebug("got image: $lx\n");
				continue;
			} else if (preg_match("@<br[/]?>@", $lx)) {
				continue;
			} else if (preg_match("@<li@", $lx)) {
				$useprefix = true;
				wfDebug("html5: should be using prefix " . implode($parts,",") . "\n");
				continue;
			} else if (preg_match("@<[/]?p>@", $lx)) {
				continue;
			} else if (preg_match("@<[/]?div@", $lx)) { // skip divs for now
				continue;
			} else if (preg_match("@</li>@", $lx)) {
				$useprefix = false;
				$wikitext .= "\n"; continue;
			}
			$prefix = implode($parts, "");
			if ($useprefix) {
				$wikitext .= $prefix . "  " . trim($x);
			} else {
				$wikitext .= $x;	
			}
			$useprefix=false;
		}
		#echo "at the end parts was : " . implode("", $parts) . "\n";
		// get rid of extra white space, but add some above the sections
		$wikitext = preg_replace("@\n[\n]*@", "\n", $wikitext);
		$wikitext = preg_replace("@^==@im", "\n==", $wikitext);

		$newtext = $wikitext;

		// grab the non-video templates and shove them back in at the top
		preg_match_all("@{{[^}]*}}@", $oldtext, $matches);
		foreach ($matches[0] as $m) {
			if (strpos($m, "{{Video:") === 0 || $m == '{{reflist}}') continue;
			$newtext = "{$m}{$newtext}";
		}

		// make sure the categories and inter-wiki links are preserved
		preg_match_all("@\[\[Category:[^\]]*\]\]@", $oldtext, $matches);
		$cats = implode ("\n", $matches[0]);
		$newtext = preg_replace("@== Steps ==@", $cats . "\n== Steps ==", $newtext);
		preg_match("@\[\[[a-z]+:[^\]]*\]\]@", $oldtext, $matches);
		$newtext .= "\n" . implode("\n", $matches);

		// preserve reflist tag
		$sources = wfMsg('sources');
		if ($hadrefs) {
			if (preg_match("@==[ ]*{$sources}[ ]*==@", $newtext)) {
				$newtext = preg_replace("@==[ ]*{$sources}[ ]*==@i", "== $sources ==\n{{reflist}}", $newtext);
			} else {
				$newtext = trim($newtext) . "\n\n== $sources==\n{{reflist}}";
			}
		}
		$newtext = trim($newtext);

		// eliminate newlines that start with images
		$newtext = preg_replace("@\n\[\[Image@", "[[Image", $newtext);
		#$newtext = preg_replace("@(\[\[Image[^\]]*\]\])\n@", "$1", $newtext);
		
		// do some debugging
		$this->debug("old.txt", $oldtext);
		$this->debug("new.txt", $newtext);
		return $newtext;
	}

	function setRevCookie($t, $r) {
		global $wgCookiePrefix, $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
		if (!$r) return;
		setcookie( $wgCookiePrefix.'RevId' . $t->getArticleID(), 
				$r->mId, time() + 3600, $wgCookiePath, $wgCookieDomain, $wgCookieSecure );
	}

    function execute ($par) {
		global $wgOut, $wgRequest, $wgParser, $wgUser, $wgFilterCallback;

		$wgOut->disable();

		// build the article which we are about to save
		$t = Title::newFromUrl($wgRequest->getVal('target'));

		// process the edit update
		if ($wgRequest->getVal('eaction') == 'save-summary') {
			// this implementation could have a few problems			
			// 1. if a user is editing the article in separate windows, it will 
			//  	only update the last edit
			// 2. Could be easy to fake an edit summary save, but is limited to 
			// edits made by the user
			/// 3. There's no real 'paper' trail of the saved summary
			// grab the cookie with the rev_id
			global $wgCookiePrefix;
			if (isset( $_COOKIE["{$wgCookiePrefix}RevId". $t->getArticleID()] )) {
				$revid = $_COOKIE["{$wgCookiePrefix}RevId". $t->getArticleID()];
				wfDebug("AXX: updating revcomment {$revid} \n");
				$dbw = wfGetDB(DB_MASTER);
				$summary = "updating from html5 editor, " . $wgRequest->getVal('summary');
				$dbw->update('revision', 
					array('rev_comment'=>$summary),
					array('rev_id'=>$revid, 'rev_user_text' => $wgUser->getName()), 
					"Html5Editor::saveComment",
					array("LIMIT" => 1));
				$dbw->update('recentchanges', 
					array('rc_comment'=>$summary),
					array('rc_this_oldid'=>$revid, 'rc_user_text' => $wgUser->getName()), 
					"Html5Editor::saveComment",
					array("LIMIT" => 1));
			} else {
				wfDebug("AXX: NOT updating revcomment, why\n");
			}
			return;
		}
		$a = new Article(&$t);
		$oldtext = $a->getContent();
     
		// now ... let's convert the HTML back into wikitext... holy crap, we are nuts
		$html 		= $wgRequest->getVal('html');
		$newtext 	= $this->convertHTML2Wikitext($html, $oldtext);

		// filter callback? 
        if ( $wgFilterCallback && $wgFilterCallback( $t, $newtext, null) ) {
            # Error messages or other handling should be performed by the filter function
			$response = array('error' => self::$spam_message, 'html' =>  $html);
			print json_encode($response);
            return;
        }

		// do the save
		// TODO: check for conflicts (obviously)
		if ($a->doEdit($newtext, $wgRequest->getVal('summary') .  " (HTML5) ")) {
			$r = Revision::newFromTitle($t); 
			$this->setRevCookie($t, $r); 
			# try this parse, this is for debugging only
        	$popts = $wgOut->parserOptions();
        	$popts->setTidy(true);
        	$popts->enableLimitReport();
			#Html5WrapTemplates($a, &$newtext);
        	$parserOutput = $wgParser->parse( $newtext, $t, $popts, true, true, $a->getRevIdFetched() );
        	$popts->setTidy(false);
        	$popts->enableLimitReport( false );
			$html = WikiHowTemplate::mungeSteps($parserOutput->getText(), array('no-ads'));
			#$html = WikiHowTemplate::mungeSteps($wgOut->parse($newtext));
			$this->debug("output.html", $newtext . "\n\n-----------\n" . $html);
			$response = array(error => '', 'html' => $html);
			print json_encode($response);
			return;
		} else {
			$response = array(error => 'Error saving', 'html' => '');
			print json_encode($response);
			return;
		}
		return;
	}
}

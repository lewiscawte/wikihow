<?

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once("$IP/skins/WikiHowSkin.php");

class Html5editor extends SpecialPage {

	// the map of interwiki links
	var $mInterwiki = null;

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

	// returns whether or not there were any references to handle	
	function handleReferences(&$doc, &$xpath, $oldtext) {
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

			if (isset($oldimages[$name])) {
				$tag = $oldimages[$name];
				if ($caption) {
					if (preg_match("@.*\||@", $tag, $matches)) {
						// handle [[Image:name|thumb|caption]]
						$tag = $matches[0] . $caption . "]]";
					} else {
						// handle [[Image:name]]
						$tag = preg_replace("@\]\]$@", "", $tag) . "|{$caption}]]";	
					}					
				}	
        		$newnode = $doc->createTextNode($tag);
            	$node->parentNode->replaceChild($newnode, $node);
				continue;
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

		// filter out templates, we add these back in from the old wikitext
		// any incoming ads from anons gets whacked!
        $nodes = $xpath->query("//div[@class='template']|//div[@class='wh_ad']|//div[@class='step_num']");
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
      
		// handle the bold tags produced by the skin
		$nodes = $xpath->query("//b[@class='whb']");
        foreach ($nodes as $node) {
            $newnode = $doc->createElement("div", "");
			foreach ($node->childNodes as $c) {
				$newnode->appendChild($c->cloneNode(true));
			}
            $node->parentNode->replaceChild($newnode, $node);
        }

		// convert h3 to '''
        $nodes = $xpath->query("//h3");
        foreach ($nodes as $node) {
            $newnode = $doc->createElement("div", "");
          	$newnode->appendChild($doc->createTextNode("=== "));
            foreach ($node->childNodes as $c) {
                $newnode->appendChild($c->cloneNode(true));
            }
            $node->parentNode->replaceChild($newnode, $node);
          	$newnode->appendChild($doc->createTextNode(" ==="));
        }

		// handle video, it's not editable, preserve it	
		#echo $doc->saveXML() . "\n\n";
        $nodes = $xpath->query("//div[@id='video']");
        foreach ($nodes as $node) {
			preg_match("@{{Video:[^}]*}}@", $oldtext, $matches);
            $newnode = $doc->createTextNode($matches[0]);
            $node->parentNode->replaceChild($newnode, $node);
        	$other = $xpath->query("//table[@id='video_table']");
			//anons have their video wrapped in a table for ads, handle this
        	foreach ($other as $node1) {
            	$newnode = $doc->createTextNode($matches[0]);
            	$node1->parentNode->replaceChild($newnode, $node1);
				break;
			}
        }

		// replace image nodes with their associated wikitext 
		$this->handleImages($doc, $xpath, $oldtext);
		
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
				echo "Pushing #" . implode("", $parts) . "\n";
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
				}	
			} else if (preg_match("@</a>@", $lx)) {
				continue;
			} else if (preg_match("@<ul@", $lx)) {
				array_push($parts, "*");	
				echo "Pushing * " . implode("", $parts) . "\n";
				$wikitext .= "\n";
				continue;
			} else if (preg_match("@<[/]?span@", $lx)) {
				continue;
			} else if (preg_match("@</ol>|</ul>@", $lx)) {
				$x = array_pop($parts);
				echo "Popping $x: " . implode("", $parts) . "\n";
				continue;
			} else if (preg_match("@<[/]?i>@", $lx)) {
				$x = "''";
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
		echo "at the end parts was : " . implode("", $parts) . "\n";
		// get rid of extra white space, but add some above the sections
		$wikitext = preg_replace("@\n[\n]*@", "\n", $wikitext);
		$wikitext = preg_replace("@^==@im", "\n==", $wikitext);

		$newtext = $wikitext;

		// grab the non-video templates and shove them back in at the top
		preg_match("@{{[^}]*}}@", $oldtext, $matches);
		foreach ($matches as $m) {
			if (strpos($m, "{{Video:") === 0) continue;
			$newtext = "{$m}{$newtext}";
		}

		// make sure the categories and inter-wiki links are preserved
		preg_match_all("@\[\[Category:[^\]]*\]\]@", $oldtext, $matches);
		$cats = implode ("\n", $matches[0]);
		$newtext = preg_replace("@== Steps ==@", $cats . "\n== Steps ==", $newtext);
		preg_match("@\[\[[a-z]+:[^\]]*\]\]@", $oldtext, $matches);
		$newtext .= "\n" . implode("\n", $matches);

		// preserve reflist tag
		if ($hadrefs)
			$newtext = preg_replace("@==[ ]*Sources and Citations[ ]*==@", "== Sources and Citations ==\n{{reflist}}", $newtext);
		$newtext = trim($newtext);
		
		// do some debugging
		$this->debug("old.txt", $oldtext);
		$this->debug("new.txt", $newtext);
		return $newtext;
	}

    function execute ($par) {
		global $wgOut, $wgRequest, $wgParser;

		$wgOut->disable();

		// build the article which we are about to save
		$t = Title::newFromUrl($wgRequest->getVal('target'));
		$a = new Article(&$t);
		$oldtext = $a->getContent();
     
		// now ... let's convert the HTML back into wikitext... holy crap, we are nuts
		$html 		= $wgRequest->getVal('html');
		$newtext 	= $this->convertHTML2Wikitext($html, $oldtext);

		// do the save
		// TODO: check for conflicts (obviously)
		// TODO: grab the summary from the user somehow
		if (true || $a->doEdit($newtext, "updating from html5 editor")) {
			# try this parse, this is for debugging only
        	$popts = $wgOut->parserOptions();
        	$popts->setTidy(true);
        	$popts->enableLimitReport();
        	$parserOutput = $wgParser->parse( $newtext, $t, $popts, true, true, $a->getRevIdFetched() );
        	$popts->setTidy(false);
        	$popts->enableLimitReport( false );
			$html = WikiHowTemplate::mungeSteps($parserOutput->getText());
			#$html = WikiHowTemplate::mungeSteps($wgOut->parse($newtext));
			$this->debug("output.html", $newtext . "\n\n-----------\n" . $html);
			echo $html;
			return;
		} else {
			echo "Uh oh had an error saving;";
			$wgRequest->response()->header( 'HTTP/1.1 500 Edit failed');
			return;
		}
		return;
	}
}

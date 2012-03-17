<?php
/*
 * Removes unwanted external links from the site.
 * Requires two files:
 *   1. Matches links to article Ids. Each line begins with a domain
 *      and following that is a tab separated list of article Ids that
 *      contain that domain. Contains all external domains on site,
 *      good and bad. (eg - domainToIds-dev.txt)
 *   2. List of domains to delete. (eg - external_domains_known_spam-dev.txt)
 *   3. Maximum number of links to delete. If no number is inlcuded it
 *      deletes all in the given file.
 *
 * 
 */

require_once('../commandLine.inc');
require_once('../../extensions/wikihow/WikiHow.php');

if ($argv[0] == null) {
	echo "Missing filename for file with link <-> article Ids\n";
	return;
}
if ($argv[1] == null) {
	echo "Missing filename for list of domains to be deleted\n";
	return;
}
if ($argv[2] != null)
	$maxLinks = intval($argv[2]);
else
	$maxLinks = -1;

global $wgUser, $stillNeedRemoval;

$oldUser = $wgUser;
$wgUser = User::newFromName("LinkRemovalBot");
$stillNeedRemoval = array();

$sources = "sources and citations";

echo "Parsing domain -> IDs file\n";
$fi = fopen($argv[0], 'r');

if($fi === false) {
	echo $argv[0] . " doesn't exist\n";
	return;
}


while ( !feof( $fi ) ) {
	$fcontent = fgets($fi);
	$tcontent = trim($fcontent);
	$data = split("\t", $tcontent);

	$domain = trim($data[0]);

	$domains[$domain] = array();
	for ($i = 1; $i < sizeof($data); $i++) {
		$domains[$domain][$data[$i]] = $data[$i];
	}
}

fclose($fi);

echo "Done parsing input domains\n";

//now grab the list of domains to delete
$domainList = $argv[1];

$fi = fopen($domainList, 'r');

$count = 0;
$done = false;
while (!$done && !feof( $fi ) ) {
	$domain = trim( fgets($fi) );

	if (empty($domains[$domain])) {
		//echo $domain . " has no matches\n";
		continue;
	}
	foreach ($domains[$domain] as $id){
		$count += removeLinkFromSourcesCitations($domain, $id, $sources);
		//currently only removing from sources and citations
		//removeLink($domain, $id);
		
		if ($maxLinks != -1 && $count >= $maxLinks) {
			$done = true;
			break;
		}

	}
	
}

echo "Done removing links!!\n\n\n\n";

foreach ($stillNeedRemoval as $item) {
    echo "Remove " . $item[1] . " from " . $item[0] . "\n";
}

$wgUser = $oldUser;

/**
 *
 * This function takes a domain and a specific articleId
 * and removes all external links in that article's
 * "sources and citations" section that
 * match the given domain.
 *
 */

function removeLinkFromSourcesCitations($domain, $articleId, $sources) {
        global $stillNeedRemoval;

	$title = Title::newFromID($articleId);

	if ($title && $title->getNamespace() == NS_MAIN) {
		$article = new Article($title);

		$wikihow = new WikiHow();
		$wikihow->loadFromArticle($article);

		$escapedDomain = preg_quote($domain);

		if ($wikihow->hasSection($sources)) {
			$sectionText = $wikihow->getSection($sources);

			if (preg_match('@' . $escapedDomain . '@i', $sectionText) > 0) {
				//replace the whole line containing the domain
				$newText = trim( preg_replace('@.*' . $escapedDomain . '[^\n\*]*$@im', '', $sectionText) );
				//now get rid of any double line breaks
				$newText = trim( preg_replace('@\n{2,}@', "\n", $newText) );

				$index = $wikihow->getSectionNumber($sources);
				if ($index != -1) {
					if ($newText != "")
						$newText = "== " . wfMsg("sources") . " ==" . "\n" . $newText;

					$newText = $article->replaceSection($index, $newText);
					if($article->doEdit($newText, "removing unwanted links")){
						echo "Removed links from " . $title->getFullUrl('action=history') . "\n";
						return 1;
					}
				}
			}

		}
                else {
                    $articleText = $article->getContent();

                    if (preg_match('@' . $escapedDomain . '@i', $articleText) > 0) {
                        $stillNeedRemoval[] = array($title->getFullUrl(), $domain);
                    }
                }
                

	}
	return 0;
}

/**
 *
 * This function takes a domain and a specific articleId
 * and removes all external links in that article that
 * match the given domain.
 *
 */
function removeLink($domain, $articleId){
	
	$title = Title::newFromID($articleId);
	
	if ($title && $title->getNamespace() == NS_MAIN) {
		$article = new Article($title);
		$articleText = $article->getContent();
		
		//look for all matches
		$escapedDomain = preg_quote($domain);
		//first check for links in images and external links
		$newText = preg_replace_callback('@\[?\[[^\]]*' . $escapedDomain . '[^\]]*\]\]?@i', 'handleLinkCallback', $articleText);//preg_replace('&\[' . $escapedDomain . '.*\]&', '', $articleText);
		//now check for links in pre-tags, then remove the whole <pre></pre>
		$newText = preg_replace('@<pre>[^<]*' . $escapedDomain . '[^<]*</pre>@i', '', $newText);
		//now check for links in ref-tags, then remove the whole <ref></ref>
		$newText = preg_replace('@<ref>[^<]*' . $escapedDomain . '[^<]*</ref>@i', '', $newText);
		//now check for links by themselves
		$newText = preg_replace('@[^\s]*' . $escapedDomain . '[^\s]*@i', '', $newText);

		if($article->doEdit($newText, "removing unwanted links")){
			echo "Removed " . $title->getText() . "\n";
		}

	}
}

function handleLinkCallback($matches){
	//$matches[0] = whole match
	//$matches[1] = domain
	//first check to see if its an image
	if (preg_match('@^\[\[Image@', $matches[0])) {
		return preg_replace('@\|([^\]\|]*' . preg_quote($matches[1]) . '[^\]\|]*)@', '', $matches[0]);
	}
	else {
		//its just an external link (ala [[link text]] ) so delete the whole thing
		return "";
	}
	
}

/**
 * http://bebeth.wikidiy.com/Fight-Trigeminal-Neuralgia-and-Other-Facial-and-Head-Pain (has links without anything else)
 * http://www.wikihow.com/Make-a-Denim-Rose-Accessory (has [link text] in sources section)
 * http://bebeth.wikidiy.com/Break-in-a-Baseball-Cap (has link in image)
 * http://bebeth.wikidiy.com/Convert-PowerPoint-to-Flash-Using-Open-Source-Tools (has link in <pre>link</pre>
 *
 */

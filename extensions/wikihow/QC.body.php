<?

require_once('skins/WikiHowSkin.php');

abstract class QCRule {

	// flexibility if we want to track different namespaces
	var $mValidNamespaces = array(NS_MAIN);
	var	$mArticle	= null;
	var $mAction	= '';
	var $mKey		= '';
	var $mResult	= null; // action item to patrol, a row from the qc table
	var $mTitle		= null;

	function __construct($article) {
		$this->mArticle 	= $article;
	}	

	function textRemoved($part, $oldtext, $newtext) {
		if (preg_match("@{$part}@i", $oldtext) && !preg_match("@{$part}@", $newtext)) {
			return true;
		}
		return false;
	}

	function textAdded($part, $oldtext, $newtext) { 
		if (!preg_match("@{$part}@i", $oldtext) && preg_match("@{$part}@", $newtext)) {
			return true;
		}
		return false;
	}

	function hasText($part, $text) {
		return preg_match("@{$part}@i", $text);
	}

	function hasEntry($articleID) {
		$dbr = wfGetDB(DB_SLAVE);
		$hasEntry = $dbr->selectField('qc', 'count(*)', 
				array('qc_page'=> $$articleID, 'qc_patrolled'=>0, 'qc_key'=>$this->mKey)) > 0;
		return $hasEntry;
	}

	function textAddedOrRemoved($part, $oldtext, $newtext) {
		return self::textAdded($part, $oldtext, $newtext) || self::textRemoved($part, $oldtext, $newtext);	
	}

	function textChanged($part, $oldtext, $newtext) {
		preg_match_all("@" . $part . "@iU", $oldtext, $matches1);
		preg_match_all("@" . $part . "@iU", $newtext, $matches2); 
		return !($matches1 == $matches2); 
	}

	function process() {
		if ($this->flagAction()) {
			$this->logQCEntry();
		}
	}

	function getEntryOptions() {
		return array();
	}
	
	function getKey() {
		return $this->mKey;
	}
	
	function getAction() {
		return $this->mAction;
	}
	
	abstract public function getYesVotesRequired();
	abstract public function getNoVotesRequired();

	function deleteBad($qc_page) {
		// is there something we can delete ? 
		$dbw = wfGetDB(DB_MASTER);
		$page_title = $dbw->selectField('page', 'page_title', array('page_id'=>$qc_page));
		if (!$page_title) {
			$dbw->delete('qc', array('qc_page'=>$qc_page));
		}
	}

	function getTitleFromQCID($qcid) {

		$dbr = wfGetDB(DB_MASTER);
		$page_id = $dbr->selectField('qc', array('qc_page'), array('qc_id'=>$qcid)); 

        // construct the HTML to reply
        // load the page
        $t = Title::newFromID($page_id); 
		return $t;
	}

	function markPreviousAsPatrolled() {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as patrolled for this entry
		$dbw->update("qc", array("qc_patrolled"=>1), array("qc_page"=> $this->mArticle->getID(), "qc_key"=>$this->getKey()));
	}

	public static function markAllAsPatrolled($title) {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as patrolled for this entry
		$dbw->update("qc", array("qc_patrolled"=>1), array("qc_page"=> $title->getArticleID()));
	}

	function logQCEntry () {
		global $wgUser;
		$opts = array(	"qc_key" => $this->getKey(), 
						"qc_action" => $this->getAction(),
						"qc_timestamp" => wfTimestampNow(),	
						"qc_user" => $wgUser->getID(),
						"qc_user_text" => $wgUser->getName(),
						"qc_yes_votes_req" 	=> $this->getYesVotesRequired(),
						"qc_no_votes_req" 	=> $this->getNoVotesRequired(),
						"qc_page" => $this->mArticle->getID(),
				);
		$opts = array_merge($this->getEntryOptions(), $opts);

		$this->markPreviousAsPatrolled(); 

		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('qc', $opts);

		#print_r($dbw); exit;
	}

	/***** 
  	 * Functions for displaying the QC entry to the patrolling user and accepting votes
	 *
	 ****/

	function markQCAsViewed($qcid) {
		global $wgMemc, $wgUser; 
		$userid = $wgUser->getID();
		$key = wfMemcKey("qcuserlog");
		$log = $wgMemc->get($key); 
		if (!$log) {
			$log = array(); 
		}
		if (!isset($log[$userid])) {
			$log[$userid] = array(); 
		}
		$log[$userid][] = $qcid;
		$wgMemc->set($key, $log); 
	}

	function getPreviouslyViewed() {
		global $wgMemc, $wgUser; 
		$userid = $wgUser->getID();
		$key = wfMemcKey("qcuserlog");

		$log = $wgMemc->get($key); 
		if (!$log) {
			return "";
		}
		if (!isset($log[$userid])) {
			return "";
		}
		$good = array();
		foreach ($log[$userid] as $u) {
			if (!preg_match("@[^0-9]@", $u) && $u != "") {
				$good[] = $u;
			}
		}
		$str = preg_replace("@,$@", "", implode(",", array_unique($good))); 	
		#echo $str; exit;
		return $str;
	}

	public static function getNextToPatrol($type) {
		global $wgUser; 

		$key = strtolower(preg_replace("@/@", "_", $type));

		// grab the next one
		$dbw = wfGetDB(DB_MASTER);
		$expired = wfTimestamp(TS_MW, time() - 3600); 


		$sql = "SELECT * from qc left join qc_vote ON qc_id=qcv_qcid AND qcv_user = {$wgUser->getID()} "
			. " WHERE ( qc_checkout_time < '{$expired}' OR qc_checkout_time = '')
				AND qc_patrolled = 0
				AND qc_user != {$wgUser->getID()} 
				AND qcv_qcid is NULL ";
		//$opts = array("( qc_checkout_time < '{$expired}' OR qc_checkout_time = '')", "qc_patrolled"=>0, "qc_user != " . $wgUser->getID(),
		//				'qcv_qcid=qc_id', 'qcv_qcid is NULL', 'qcv_user'=>$wgUser->getID());
		if (!empty($type)) {
			$sql .= " AND qc_key = '$key' "; // $opts["qc_key"] = $key;
		}

		$previous = self::getPreviouslyViewed();
		if ($previous) {
			$sql .= " AND  qc_id NOT IN ({$previous})"; 
		}

		$sql .= " LIMIT 1";	
		$res = $dbw->query($sql);	
		$result = $dbw->fetchObject($res);	
		#$result = $dbw->selectRow(array('qc', 'qc_vote'), array('*'), $opts);

#echo $dbw->lastQuery(); 
		if (!$result) {
			return null;
		}

		// if we have one, check it out of the queue so multiple people don't get the same item to review
		if ($result) {
			// mark this as checked out
	#debug
			$dbw->update('qc', array('qc_checkout_time'=>wfTimestampNow(), 'qc_checkout_user'=>$wgUser->getID()), array('qc_id' => $result->qc_id));
		} 

		$c = null;
		$key = $result->qc_key;
		$c = self::newRuleFromKey($key);
		$c->mResult = $result;
		$c->mTitle = Title::newFromID($c->mResult->qc_page);
		return $c; 
	}

	public static function newRuleFromKey($key) {
		$c = null;
		if (preg_match("@changedtemplate_@", $key)) {
			$template = preg_replace("@changedtemplate_@", "", $key); 
			$c = new QCRuleTemplateChange($template);
		} else if ($key == "changedvideo") {
			$c = new QCRuleVideoChange();
		} else if ($key == "changedintroimage") {
			$c = new QCRuleIntroImage();
		}
		return $c;
	}

	function releaseQC($qcid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('qc', array('qc_checkout_time'=> "", 'qc_checkout_user'=> 0), array('qc_id' => $qcid));
		return true;
	}

	function markQCPatrolled($qcid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('qc', array('qc_patrolled' => 1), array('qc_id'=>$qcid));
		return true; 
	}


	public static function vote($qcid, $vote) {
		global $wgUser;
		$dbw = wfGetDB(DB_MASTER); 

		// have they already voted on this?  if so, forget about it, release the current one back to the queue and get out of here
		$count = $dbw->selectField('qc_vote', array('count(*)'), array('qcv_user'=>$wgUser->getID(), 'qcv_qcid'=>$qcid));
		if ($count > 0) {
			self::releaseQC($qcid);
			return;
		}

		$opts = array(); 
		if ($vote == 1) {
			$opts[] = "qc_yes_votes = qc_yes_votes + 1";
			$voteint = 1;
		} else {
			$opts[] = "qc_no_votes = qc_no_votes + 1";
			$voteint = 0;
		}

		$dbw->update('qc', $opts, array('qc_id'=>$qcid));
		$dbw->insert('qc_vote', array('qcv_user'=>$wgUser->getID(), 'qcv_vote'=>$voteint, 'qcv_qcid'=>$qcid, 'qc_timestamp' => wfTimestampNow()));

		// check, do we have to mark it as patrolled, or roll the change back? 
		$row = $dbw->selectRow('qc', array('qc_page', 'qc_rev_id', 'qc_yes_votes', 'qc_no_votes', 'qc_yes_votes_req', 'qc_no_votes_req'),array('qc_id'=>$qcid));
		$key = $dbw->selectField('qc', 'qc_key', array('qc_id'=>$qcid));
		
		if ($vote) {
			if ($row->qc_yes_votes >= $row->qc_yes_votes_req) {
				self::markQCPatrolled($qcid);
				$c = self::newRuleFromKey($key);
				$c->applyChange($qcid);
			}
		} else {
			if ($row->qc_no_votes >= $row->qc_no_votes_req) {
				// what kind of rule are we ? figure it out so we can roll it back
				$c = self::newRuleFromKey($key);
				$c->rollbackChange($qcid);
				self::markQCPatrolled($qcid);
			}
		}
		self::markQCAsViewed($qcid);	
		self::releaseQC($qcid);

		// log page entry
		$title = Title::newFromID($row->qc_page);
		$log = new LogPage( 'qc', false );

		$vote_param = $vote > 0 ? "yesvote" : "novote"; 
		
		# Generate a diff link
		$skin = $wgUser->getSkin();
		$bits[] = 'oldid=' . urlencode( $row->qc_rev_id );
		$bits[] = 'diff=prev';
		$bits = implode( '&', $bits );
		$diff = "[[{$title->getText()}]]"; // $skin->makeKnownLinkObj( $title, $title->getText(), $bits );
 
		$msg = wfMsgHtml("qcrule_log_{$key}_{$vote_param}", $diff);	
		$log->addEntry('qc', $title, $msg, array($vote, $row->qc_rev_id, $key)); 
		wfRunHooks("QCVoted", array($wgUser, $title, $vote)); 
	}

	// user skips it, so add this to the stuff they have viewed
	function skip($qcid) {
		self::markQCAsViewed($qcid);	
	}

	// these are specific to the rule that is being used
	abstract public function getPrompt(); 
	abstract public function rollbackChange($qcid);

	// since this is specific to only 1 class, template changes, make it non-abstract and just return true
	function applyChange($qcid) {
		return true;
	}

	function getHeader($t, $action_str) {
        $html = "<table width='100%'><tr><td style='vertical-align: top; font-size: 1.4em; font-weight: bold;'>";
		$html .= "<a href='{$t->getFullURL()}' target='new'>" . wfMsg('howto', $t->getText()) . "</a></td>";
		$u = User::newFromName($this->mResult->qc_user_text);
		$html .= "<td style='text-align: right; v-align:top; '>{$action_str} ";
		if ($u) {
			$av = Avatar::getPicture($u->getName(), true, true);
			$html .= "<a target='new' href='{$u->getUserPage()->getFullURL()}'>{$u->getName()}</a><br/>{$av}";
		} else {
			$html .= "<a target='new' href='{$this->mResult->qc_user_text}'>{$this->mResult->qc_user_text}</a>";
		}	
		$html .= "</td></tr></table>";
		return $html;	
	}


}

/***********************
 *
 *  An abstract class that groups together some functions that are relevant only to text chagnes
 *  Some rules may not involve text changes (patrolling an edit for example)
 *
***********************/
abstract class QCRuleTextChange extends QCRule {
	var	$mTemplate 	= null;
	var $mRevision	= null;
	var $mLastRevid	= null;

	function __construct($template, $revision, $article) {
		$this->mTemplate	= $template; 
		$this->mRevision	= $revision;
		$this->mArticle		= $article;
	}

	function getLastRevID() {
		if (!$this->mLastRevid) {
			$dbr = wfGetDB(DB_SLAVE); 
			$revid = $this->mRevision->getID();
			$pageid = $this->mRevision->getPage(); 
			$lastrev = $dbr->selectField('revision', 'max(rev_id)', array('rev_page'=>$pageid, 'rev_id<' . $revid));
			if (!$lastrev) return null;
			$this->mLastRevid = $lastrev;
		}
		return $this->mLastRevid;
	}

	function getLastRevisionText() {
		$lastrev = $this->getLastRevID();
		$r = Revision::newFromID($lastrev); 
		if (!$r) return null;
		return $r->getText();	
	}
	
	function getEntryOptions() {
		$opts = array("qc_rev_id" => $this->mRevision->getID());
		$old_rev = $this->getLastRevID();
		if ($old_rev) {
			$opts['qc_old_rev_id'] = $old_rev;
		}
		return $opts;
	}
	
}

/***********************
 *
 *  The rule for when an intro image gets added
 *
***********************/
class QCRuleIntroImage extends QCRuleTextChange {

	function __construct($revision = null, $article = null) {
		$this->mAction = "added";
		$this->mKey			= "changedintroimage";
		parent::__construct($template, $revision, $article);
	}

	function getPart() {
		return "\[\[Image:.*[\|\]]";
	}
	
	function getYesVotesRequired() {
		global $wgQCIntroImageVotesRequired;
		return $wgQCIntroImageVotesRequired["yes"];
	}

	function getNoVotesRequired() {
		global $wgQCIntroImageVotesRequired;
		return $wgQCIntroImageVotesRequired["no"];
	}

	function flagAction() {

		// check for a revision
		if (!$this->mRevision) {
			return false;
		}

		// check the title
		$title = $this->mArticle->getTitle();
		if (!$title || !in_array($title->getNamespace(), $this->mValidNamespaces)) {
			return false;
		}

		$part	  = $this->getPart();
		$oldtext = Article::getSection($this->getLastRevisionText(), 0);
		$newtext = Article::getSection($this->mRevision->getText(), 0);

		$ret = false;
		if ($oldtext == null && $this->hasText($part, $newtext)) {
			$ret = true;
			$this->mAction = "added";
		} else if ($this->textRemoved($part, $oldtext, $newtext)) {	
			$this->markPreviousAsPatrolled();
		} else if ($this->textAdded($part, $oldtext, $newtext) || $this->textChanged($part, $oldtext, $newtext)) {
			$ret = true;
			$this->mAction = "added";
		}
	
		wfDebug("QC: intro image added " . print_r($ret, true) . "\n");
		return $ret;
	}

	function getPrompt() {
		return wfMsg('qcprompt_introimage');
	}	

	function rollbackChange($qcid) {
		// remove the intro image from this article
		$t = self::getTitleFromQCID($qcid);
		$r = Revision::newFromTitle($t); 
		if (!$r) {
			return false;
		}
	
		$text = $r->getText(); 
		$intro = Article::getSection($text, 0); 
		$newintro = preg_replace("@\[\[Image:[^\]]*\]\]@", "", $intro);

		$a = new Article($t); 
		$newtext = $a->replaceSection($intro, $newintro);
		if ($a->doEdit($newtext, wfMsg('qc_editsummary_introimage'))) {
			return true;
		}
			
		return false;
	}

    function getPicture($text) {
        preg_match("@\[\[Image:[^\]]*\]\]@im", $text, $matches);
        $img = "";
        if (sizeof($matches) > 0) {
            $img = preg_replace("@\[\[Image:@", "", $matches[0]);
            $img = preg_replace("@\|.*@", "", $img);
            $img = preg_replace("@\]\]@", "", $img);
            $imgtitle = Title::makeTitle(NS_IMAGE, $img);
            $x = wfFindFile($imgtitle);
			return $x;
        }
        return null;
    }


	function getNextToPatrolHTML() {
        global $wgOut;

        if (!$this->mResult) {
            // nothing to patrol
            return null;
        }
        
		// construct the HTML to reply
        // load the page
        $t = Title::newFromID($this->mResult->qc_page);
        if (!$t) {
			$this->deleteBad($this->mResult->qc_page);
            return "<!--{$this->mResult->qc_page}-->error creating title, oops, please <a href='#' onclick='window.location.reload()'>refresh</a>";
        }

        // get current revsion
        $r = Revision::newFromTitle($t);
        if (!$r) {
            return "Error creating revision";
        }

		// grab the intro image
		$text = $r->getText(); 
		$intro = Article::getSection($text, 0); 
		$html = self::getHeader($t, "Image changed by: "); 

		$pic = self::getPicture($intro); 
		if ($pic) {
			$html .= "<center><img src='" . $pic->getURL() . "'";
			if ($pic->width > 500) {
				$html .= " style='width: 500px;' ";
			}
			$html .= "/></center>";
		} else {
			$html .= "Hmm..., it has likely already been removed.";
		} 	
		$html .= "<br/><br/><hr/>";
		$html .= WikiHowTemplate::mungeSteps($wgOut->parse($text));
        $html .= "<input type='hidden' name='qc_id' value='{$this->mResult->qc_id}'/>";
        return $html;
	}

}

/***********************
 *
 *  The rule for when a video is added, chagned or removed
 *
***********************/
class QCRuleVideoChange extends QCRuleTextChange {
	
	function __construct($revision = null, $article = null) {
		$this->mKey		= "changedvideo";
		$this->mValidNamespaces = array(NS_MAIN, NS_VIDEO);
		parent::__construct($template, $revision, $article);
	}

	function getPart() {
		return "\{\{Video:.*[\|\}]";
	}
	
	function getYesVotesRequired() {
		global $wgQCVideoChangeVotesRequired;
		return $wgQCVideoChangeVotesRequired["yes"];
	}

	function getNoVotesRequired() {
		global $wgQCVideoChangeVotesRequired;
		return $wgQCVideoChangeVotesRequired["no"];
	}

	function flagAction() {

		// check for a revision
		if (!$this->mRevision) {
			return false;
		}

		// check the title
		$title = $this->mArticle->getTitle();
		if (!$title || !in_array($title->getNamespace(), $this->mValidNamespaces)) {
			return false;
		}

		// deal with the situation where the Video: page has been changed
		// TODO: can we narrow it down to just the video changing? probably not. if 
		// a video namespace page has changed, we can assume the video has changed
		if ($title->getNamespace() == NS_VIDEO) {
			if ($this->getLastRevisionText() == null) {
				$this->mAction = "added";
			} 
			// do we already have an entry in the QC log for the main namespace article 
			// for this type of rule? do we need to check? I guess we do.
			$mainTitle = Title::newFromText($title->getText());
			$hasEntry = $this->hasEntry($mainTitle->getArticleID());
			if ($hasEntry) {
				return false;
			}
			$this->mArticle = new Article($mainTitle);
			return true;
		}

		// we may have already put this in for a video namespace edit
		$hasEntry = $this->hasEntry($title->getArticleID());
		if ($hasEntry)  {
			return false; 
		}

		// deal with the situation where the main namespace video has been changed
		$part	  = $this->getPart();
		$oldtext = $this->getLastRevisionText();
		$newtext = $this->mRevision->getText();

#$test = $this->hasText($part, $newtext); echo var_dump($test); exit;
 		$ret = false;
		if ($newtext == null && $this->hasText($part, $newtext)) {
			$ret = true;
			$this->mAction = "added";
		} else if ($this->textRemoved($part, $oldtext, $newtext)) {	
			$this->markPreviousAsPatrolled();
		} else if ($this->textAdded($part, $oldtext, $newtext) || $this->textChanged($part, $oldtext, $newtext)) {
			$ret = true;
			$this->mAction = "added";
		}
	
		wfDebug("QC: video change " . print_r($ret, true) . "\n");
		return $ret;
	}

	function getPrompt() {
		return wfMsg('qcprompt_video');
	}	

	function getVideoSection($text) {
		$index = 0;
		$vidsection = null;
		while ($section = Article::getSection($text, $index)) {
			if (preg_match("@^==\s*" . wfMsg('video') . "@", $section)) {
				$vidsection = $section;
				break;
			}
			$index++;
		}
		return trim($vidsection);
	}

	function rollbackChange($qcid) {
		// remove the video from this article
        // remove the intro image from this article
        $t = self::getTitleFromQCID($qcid);
        $r = Revision::newFromTitle($t);
        if (!$r) {
            return false;
        }
   
        $text = $r->getText();
		$vidsection = $this->getVideoSection($text);
		if (!$vidsection) {
			return true;
		}

        $a = new Article($t);

		# replace section doesn't work for some reason for the Video section
		$newtext = str_replace($vidsection, "", $text);
		
        if ($a->doEdit($newtext, wfMsg('qc_editsummary_video'))) {
            return true;
        }

        return false;
	}

	function getNextToPatrolHTML() {
        global $wgOut;

        if (!$this->mResult) {
            // nothing to patrol
            return null;
        }

        // construct the HTML to reply
        // load the page
        $t = Title::newFromID($this->mResult->qc_page);
        if (!$t) {
			// is there something we can delete ? 
			$this->deleteBad($this->mResult->qc_page);
            return "<!--{$this->mResult->qc_page}-->error creating title, oops, please <a href='#' onclick='window.location.reload()'>refresh</a>";
        }

        // get current revsion
        $r = Revision::newFromTitle($t);
        if (!$r) {
            return "Error creating revision";
        }

		$vidsection = $this->getVideoSection($r->getText());

		$html = self::getHeader($t, "Video changed by: "); 
		if ($vidsection) {
        	$html .= "<center>" . $wgOut->parse($vidsection) . "</center><br clear='all'/>";
		} else {
			$html .= "Hmm... didn't seem to find an image... uh oh!";
		} 	
        $html .= "<input type='hidden' name='qc_id' value='{$this->mResult->qc_id}'/>";
        $html .= "<br/><br/><hr/>";
        $html .= WikiHowTemplate::mungeSteps($wgOut->parse($text));
        return $html;
	}
}

/***********************
 *
 *  The rule for additions/removal of templates like stub and copyedit
 *
***********************/
class QCRuleTemplateChange extends QCRuleTextChange {

	function __construct($template, $revision = null, $article = null) {
		parent::__construct($template, $revision, $article);
		$this->mKey	= "changedtemplate_" . strtolower($this->mTemplate); 
	}

	function getPart() {
		return "\{\{" . $this->mTemplate;
	}

	function flagAction() {

		// check for a revision
		if (!$this->mRevision) {
			return false;
		}

		// check the title
		$title = $this->mArticle->getTitle();
		if (!$title || !in_array($title->getNamespace(), $this->mValidNamespaces)) {
			return false;
		}

		$part	 = $this->getPart();
		$oldtext = $this->getLastRevisionText();
		$newtext = $this->mRevision->getText();

		$ret = false;
		if ($this->textRemoved($part, $oldtext, $newtext)) {	
			$ret = true;
			$this->mAction = "removed";
		} else if ($this->textAdded($part, $oldtext, $newtext)) {
			$ret = true;
			$this->mAction = "added";
		}

		wfDebug("QC: template change " . print_r($ret, true) . "\n");
		return $ret;
	}

	function getYesVotesRequired() {
		global $wgTemplateChangedVotesRequired;
		return $wgTemplateChangedVotesRequired[$this->mAction]["yes"];
	}

	function getNoVotesRequired() {
		global $wgTemplateChangedVotesRequired;
		return $wgTemplateChangedVotesRequired[$this->mAction]["no"];
	}

	function getNextToPatrolHTML() {
		global $wgOut;

		if (!$this->mResult) {
			// nothing to patrol
			return null;
		}

		// construct the HTML to reply
		// load the page
		$t = Title::newFromID($this->mResult->qc_page); 
		if (!$t) {
			$this->deleteBad($this->mResult->qc_page);
            return "<!--{$this->mResult->qc_page}-->error creating title, oops, please <a href='#' onclick='window.location.reload()'>refresh</a>";
		}

		// get current revsion
		$r = Revision::newFromTitle($t); 
		if (!$r) {
			return "Error creating revision";
		}

		$html = "<input type='hidden' name='qc_id' value='{$this->mResult->qc_id}'/>";
		$html = self::getHeader($t, "Template changed by: "); 
		$html .= WikiHowTemplate::mungeSteps($wgOut->parse($r->getText()), array('no-ads'=>1));
		return $html;
	}
	
	function getPrompt() {
		return wfMsg('qcprompt_template', preg_replace("@changedtemplate_@", "", $this->getKey()));
	}	

	// in this case, we want to apply the template to the page because it's been voted "yes" on
	function applyChange($qcid) {
		$dbr = wfGetDB(DB_SLAVE); 

		// load the revision text
		$pageid = $dbr->selectField('qc', array('qc_page'), array('qc_id'=> $qcid));
		$t = Title::newFromID($pageid);
		if (!$t) {
			return false;
		}

		$r = Revision::newFromTitle($t); 
		if (!$r) {
			return false;
		}

		$text = $r->getText(); 
		if (preg_match("@\{\{" . $this->mTemplate . "@", $text)) {
			return true;
		} 

		// add the template  since it doesn't already have it
		$a = new Article($t);
		$text = "{{{$this->mTemplate}}}" . $text;
		return $a->doEdit($text, wfMsg('qc_editsummary_template_add', $this->mTemplate));
	}

	function rollbackChange($qcid) {
		// roll back the chagne from the db
		$dbr = wfGetDB(DB_SLAVE); 

		// load the revision text
		$pageid = $dbr->selectField('qc', array('qc_page'), array('qc_id'=> $qcid));
		$t = Title::newFromID($pageid);
		if (!$t) {
			return false;
		}

		$r = Revision::newFromTitle($t); 
		if (!$r) {
			return false;
		}

		$text = $r->getText(); 
		$text = preg_replace("@\{\{" . $this->mTemplate . "[^\}]*\}\}@U", "", $text); 

		$a = new Article($t);
		return $a->doEdit($text, wfMsg('qc_editsummary_template', $this->mTemplate));
	}
}

/***********************
 *
 *  The admin page for dealing with entries in the QC queue
 *
***********************/
class QC extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'QC' );
	}

	function getQuickEditLink($title) {
		global $wgServer;
        $editURL = $wgServer . '/Special:Newarticleboost?type=editform&target=' . urlencode($title->getFullText());
        $class = "class='button white_button_100' style='float: right;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);'";
        $link =  "<a title='" . wfMsg("Editold-quick") . "' accesskey='e' href='' $class onclick=\"return initPopupEdit('".$editURL."') ;\">" .
            htmlspecialchars( wfMsg( 'Editold-quick' ) ) . "</a> ";
		return $link;
	}

	function getNextInnards($qc_type) {
		// grab the next check
		$result = array(); 
		$c = QCRule::getNextToPatrol($qc_type); 
		if ($c)  {
			// qc_vote, qc_skip
			$result['title'] 		= $c->getPrompt(); 
			$result['quickedit'] 	= $this->getQuickEditLink($c->mTitle); 
			$result['html'] 		= $c->getNextToPatrolHTML();
			$result['qc_id'] 		= $c->mResult->qc_id;
		} else {
			$result['done'] = 1;
			$result['msg'] = wfMsg('qc_congrats');
		}
		return $result;
	} 


	function execute ($par) {
		global $wgUser, $wgOut, $wgQCRulesToCheck, $wgRequest;
	
		if ( !in_array( 'qc', $wgUser->getRights() ) and !in_array('sysop',$wgUser->getGroups() ) ) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		wfLoadExtensionMessages("QC"); 

		if ($wgRequest->getVal('fetchInnards')) {
			$wgOut->disable(); 
			$result = self::getNextInnards($wgRequest->getVal('qc_type'));
			print_r(json_encode($result));
			return;
		} else if ($wgRequest->wasPosted()) {
			if ($wgRequest->getVal('qc_skip', 0) == 1) {
				QCRule::skip($wgRequest->getVal('qc_id'));				
			} else {
				QCRule::vote($wgRequest->getVal('qc_id'), $wgRequest->getVal('qc_vote'));				
			}
			$wgOut->disable(); 
			$result = self::getNextInnards($wgRequest->getVal('qc_type'));
			print_r(json_encode($result));
			return;
		}

		/** 
	   	 * This is the shell of the page, has the buttons, etc. 
	   	 */ 
		$wgOut->addScript("<script type='text/javascript' src='/extensions/wikihow/qc.js'></script>");
		$wgOut->addScript("<script type='text/javascript' src='extensions/wikihow/popupEdit.js'></script>");
 		$wgOut->addHTML(QuickNoteEdit::displayQuickEdit());

		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		// generate the HTML for the rule selector drop down menu	
		// UI work needed here
		$html = wfMsg('qc_rulestocheck') . "<select name='' onchange='changeQCTarget();' id='qcrule_select'><option value=''>All</option>"; 
		foreach ($wgQCRulesToCheck as $rule) {
			if ($rule == $target) {
				$html .= "<option value='{$rule}' SELECTED>" . wfMsg('qcrule_' . strtolower($rule)) . "</option>\n";
			} else {
				$html .= "<option value='{$rule}'>" . wfMsg('qcrule_' . strtolower($rule)) . "</option>\n";
			}
		}
		$html .= "</select>";
		$wgOut->addHTML($html);

		// UI work needed here, buttons for yes,skip,no
		$wgOut->addHTML("<center>
					<input type='button' id='qc_yes' value='Yes' onclick='return qcVote(true);'> | 
					<input type='button' id='qc_no' value='No' onclick='return qcVote(false);'/> | 
					<input type='button' id='qc_skip' value='Skip' onclick='return qcSkip();'/><div style='float: right;' id='quickeditlink'></div>
					</center><br/><br/>");
		$wgOut->addHTML("<div id='qccontents'></div>"); 

		// add standings widget
		$group= new QCStandingsGroup();
		$indi = new QCStandingsIndividual();
		
		$indi->addStatsWidget(); 
		$group->addStandingsWidget();

	}
   
}


<?

require_once('skins/WikiHowSkin.php');
require_once('includes/SpecialContributions.php');


/***********************
 *
 *  A class to assist with articles that have added/sutracted NFD templates
 *
***********************/
class NFDRuleTemplateChange {

	var	$mArticle	= null;
	var $mAction	= '';
	var $mResult	= null; // action item to patrol, a row from the nfd table
	var $mTitle		= null;
	var $mTemplate	= null;
	var	$mTemplatePart 	= "nfd";
	var $mRevision	= null;
	var $mLastRevid	= null;

	function __construct($revision = null, $article = null) {
		$this->mRevision	= $revision;
		$this->mArticle		= $article;
	}

	/**
	 *  Check to see if the specific $part has been removed from the old text
	 */
	function textRemoved($part, $oldtext, $newtext) {
		if (preg_match("@{$part}@i", $oldtext) && !preg_match("@{$part}@", $newtext)) {
			return true;
		}
		return false;
	}

	/**
	 *  Check to see if the specific $part has been added to the old text
	 */
	function textAdded($part, $oldtext, $newtext) {
		if (!preg_match("@{$part}@i", $oldtext) && preg_match("@{$part}@", $newtext)) {
			$this->mTemplate = $this->getFullTemplateFromText($newtext);
			$nfdReason = $this->extractReason($this->mTemplate);
			if($nfdReason['type'] != "dup")
				return true;
			else
				return false;
		}
		return false;
	}

	/**
	 *
	 * Given an NFD template in the form {{nfd|rea|date}}, extracts
	 * the specific reason given (3 letter code) and also checks for
	 * the existence of a duplicate article title.
	 */
	function extractReason($nfdTemplate){
		$nfdReasons = array();
		$nfdReasons['type'] = "none";

		$parts = explode('|', $nfdTemplate);
		if(count($parts) > 2){
			//reason given
			$nfdReasons['type'] = $parts[1];
			if($nfdReasons['type'] == 'dup')
				$nfdReasons['article'] = $parts[2];
		}

		return $nfdReasons;
	}

	/**
	 * Processes the given NFDRule. Checks to see if a
	 * NFD template was either added or removed from
	 * the article, and if so, handles accordingly
	 */
	function process() {
		if ($this->flagAction()) {
			$this->logNFDEntry();
		}
		else if($this->getAction() == "removed"){
			$this->removeNFDEntry();
		}
	}

	/****
	 * Takes an article in its current state and
	 * checks to see if it should be in the NFD tool
	 *
	 */
	function processCurrentRevision($addToDb = true){
		$text = $this->mRevision->getText();

		if($this->textAdded($this->getPart(), "", $text)){
			if($addToDb){
				//yes, it has an NFD template on it
				$this->setFirstEdit();
				$this->mTemplate = $this->getFullTemplateFromText($text);
				$this->logNFDEntry();
			}
			return true;
		}
		else{
			//no it doesn't, so make sure its not in the tool
			NFDRuleTemplateChange::markPreviousAsInactive($this->mArticle->getID());
			return false;
		}
	}

	function getAction() {
		return $this->mAction;
	}

	/**
	 *
	 * Returns the full NFD template for this article
	 * (eg: {{nfd|acc|date}}
	 */
	function getFullTemplate($nfdid = 0){
		if($this->mTemplate != null){
			return $this->mTemplate;
		}
		else{
			$dbr = wfGetDB(DB_SLAVE);
			$template = $dbr->selectField('nfd', 'nfd_template', array('nfd_id' => $nfdid));
			return $template;
		}
	}

	/**
	 *  Removes an unneeded NFD entry from the nfd table
	 *  if the title doesn't exist in the db
	 */
	function deleteBad($nfd_page) {
		// is there something we can delete ?
		$dbw = wfGetDB(DB_MASTER);
		$page_title = $dbw->selectField('page', 'page_title', array('page_id'=>$nfd_page));
		if (!$page_title) {
			$dbw->delete('nfd', array('nfd_page'=>$nfd_page));
		}
	}

	function getTitleFromNFDID($nfdid) {

		$dbr = wfGetDB(DB_MASTER);
		$page_id = $dbr->selectField('nfd', array('nfd_page'), array('nfd_id'=>$nfdid));
		$t = Title::newFromID($page_id);
		return $t;
	}

	static function markPreviousAsPatrolled($id) {
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as patrolled for this entry
		$dbw->update("nfd", array("nfd_patrolled"=>1), array("nfd_page"=> $id));
	}

	static function markAsPatrolled($nfdid, $id){
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as patrolled for this entry
		$dbw->update("nfd", array("nfd_patrolled"=>1), array("nfd_id"=> $nfdid));
		self::markPreviousAsInactive($id);
	}

	/*
	 * Marks all articles with given page_id as inactive, meaning that they are
	 * no longer in the tool, but haven't necessarily been "patrolled" by the tool
	 * (a decision wasn't made, it was just removed from the tool)
	 */
	static function markPreviousAsInactive($id){
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as inactive for this entry
		$dbw->update("nfd", array("nfd_status"=>  NFDGuardian::NFD_INACTIVE), array("nfd_page"=> $id));
	}

	static function markPreviousAsAdvanced($id){
		$dbw = wfGetDB(DB_MASTER);
		//mark any existing entries as inactive for this entry
		$dbw->update("nfd", array("nfd_status"=>  NFDGuardian::NFD_ADVANCED), array("nfd_page"=> $id));
	}

	function logNFDEntry () {
		global $wgUser;
		$fullTemplate = $this->getFullTemplate();
		$nfdReason = $this->extractReason($fullTemplate);
		if(NFDGuardian::hasBeenDecided($this->mArticle->getTitle()) || NFDGuardian::hasBeenPatrolled($this->mArticle->getID())){
			$nfd_status = NFDGuardian::NFD_ADVANCED;
		}
		else
			$nfd_status = NFDGuardian::NFD_AVAILABLE;
		$opts = array(	"nfd_action" => $this->getAction(),
						"nfd_template" => $fullTemplate,
						"nfd_reason" => $nfdReason['type'],
						"nfd_timestamp" => wfTimestampNow(),
						"nfd_fe_timestamp" => $this->mFirstEdit,
						"nfd_user" => $wgUser->getID(),
						"nfd_user_text" => $wgUser->getName(),
						"nfd_page" => $this->mArticle->getID(),
						"nfd_status" => $nfd_status
				);
		$opts = array_merge($this->getEntryOptions(), $opts);

		NFDRuleTemplateChange::markPreviousAsInactive($this->mArticle->getID());

		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert('nfd', $opts);
	}

	function removeNFDEntry () {
		$dbr = wfGetDB(DB_SLAVE);
		$dbw = wfGetDB(DB_MASTER);
		$row = $dbr->selectRow('nfd', '*', array('nfd_page' => $this->mArticle->getID(), 'nfd_patrolled' => '0'), __METHOD__, array('ORDER BY' => 'nfd_timestamp DESC'));

		if($row){
			//Just mark it as patrolled, instead of actually deleting
			$dbw->update('nfd', array('nfd_status' => NFDGuardian::NFD_INACTIVE), array('nfd_id' => $row->nfd_id));
		}
	}

	/*****
  	 * Functions for displaying the nfd entry to the patrolling user and accepting votes
	 *
	 ****/

	static function markNFDAsViewed($nfdid) {
		global $wgMemc, $wgUser;
		$userid = $wgUser->getID();
		$key = wfMemcKey("nfduserlog");
		$log = $wgMemc->get($key);
		if (!$log) {
			$log = array();
		}
		if (!isset($log[$userid])) {
			$log[$userid] = array();
		}
		$log[$userid][] = $nfdid;
		$wgMemc->set($key, $log);
	}

	static function getPreviouslyViewed() {
		global $wgMemc, $wgUser;
		$userid = $wgUser->getID();
		$key = wfMemcKey("nfduserlog");

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

		return $str;
	}

	public static function getNextToPatrol($type) {
		global $wgUser;

		// grab the next one
		$dbw = wfGetDB(DB_MASTER);
		$expired = wfTimestamp(TS_MW, time() - 3600);

		$sql = "SELECT * from nfd left join nfd_vote ON nfd_id=nfdv_nfdid AND nfdv_user = {$wgUser->getID()} "
			. " WHERE ( nfd_checkout_time < '{$expired}' OR nfd_checkout_time = '')
				AND nfd_patrolled = 0
				AND nfd_status = '" . NFDGuardian::NFD_AVAILABLE . "'
				AND nfd_user != {$wgUser->getID()}
				AND nfdv_nfdid is NULL ";

		if($type != 'all' && $type != "")
			$sql .= " AND nfd_reason = '{$type}' ";

		$previous = self::getPreviouslyViewed();
		if ($previous) {
			$sql .= " AND  nfd_id NOT IN ({$previous})";
		}

		$sql .= " ORDER BY nfd_fe_timestamp ASC";

		$sql .= " LIMIT 1";
		$res = $dbw->query($sql);
		$result = $dbw->fetchObject($res);

		if (!$result) {
			return null;
		}

		// if we have one, check it out of the queue so multiple people don't get the same item to review
		if ($result) {
			// mark this as checked out
	#debug
			$dbw->update('nfd', array('nfd_checkout_time'=>wfTimestampNow(), 'nfd_checkout_user'=>$wgUser->getID()), array('nfd_id' => $result->nfd_id));
		}

		$c = null;
		$c = new NFDRuleTemplateChange();
		$c->mResult = $result;
		$c->mTitle = Title::newFromID($c->mResult->nfd_page);
		return $c;
	}

	function releaseNFD($nfdid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('nfd', array('nfd_checkout_time'=> "", 'nfd_checkout_user'=> 0), array('nfd_id' => $nfdid));
		return true;
	}

	function markNFDPatrolled($nfdid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('nfd', array('nfd_patrolled' => 1), array('nfd_id'=>$nfdid));
		return true;
	}

	public static function save($nfdid, &$t) {
		global $wgUser, $wgLang;
		$dbw = wfGetDB(DB_MASTER);
		// have they already voted on this?  if so, forget about it, release the current one back to the queue and get out of here
		$count = $dbw->selectField('nfd_vote', array('count(*)'), array('nfdv_user'=>$wgUser->getID(), 'nfdv_nfdid'=>$nfdid));
		if ($count > 0) {
			self::releaseNFD($nfdid);
			return;
		}

		//delete all the delete votes
		$dbw->delete('nfd_vote', array('nfdv_nfdid' => $nfdid, 'nfdv_vote' => 1));
		$dbw->update('nfd', array('nfd_delete_votes' => 0, 'nfd_admin_delete_votes' => 0) , array('nfd_id' => $nfdid));

		//now mark a keep vote
		$opts = array();
		$voteCount = 0;
		if($wgUser->isSysOp()){
			$voteCount = 2;
			$opts[] = "nfd_admin_keep_votes = nfd_admin_keep_votes + 1";
		}
		else{
			$voteCount = 1;
		}

		$opts[] = "nfd_keep_votes = nfd_keep_votes + " . $voteCount;
		$voteint = 0;
		$dbw->update('nfd', $opts, array('nfd_id'=>$nfdid));
		$dbw->insert('nfd_vote', array('nfdv_user'=>$wgUser->getID(), 'nfdv_vote'=>$voteint, 'nfdv_nfdid'=>$nfdid, 'nfdv_timestamp' => wfTimestampNow()));

		// check, do we have to mark it as patrolled, or roll the change back?
		$row = $dbw->selectRow('nfd', '*',array('nfd_id'=>$nfdid));

		if ($row->nfd_admin_keep_votes >= NFDRuleTemplateChange::getAdminKeepVotesRequired() && $row->nfd_keep_votes >= NFDRuleTemplateChange::getKeepVotesRequired()) {
			// what kind of rule are we ? figure it out so we can roll it back
			$c = new NFDRuleTemplateChange();
			$c->keepArticle($nfdid);
			self::markNFDPatrolled($nfdid);
		}
		else{
			//not enough votes to keep, so just mark about the save
			//post on discussion page
			$discussionTitle = $t->getTalkPage();
			$userName = $wgUser->getName();
			$dateStr = $wgLang->date(wfTimestampNow());

			$comment = wfMsgHtml('nfd_save_message', "[[User:$userName|$userName]]", $dateStr);
			$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, "NFD-Voter-Tool", "NFD Voter Tool", $comment);

			if ($discussionTitle->getArticleId() > 0) {
				$r = Revision::newFromTitle($discussionTitle);
				$text = $r->getText();
			}

			$article = new Article($discussionTitle);
			$text .= "\n\n$formattedComment\n\n";
			$article->doEdit($text, "");
		}

		self::markNFDAsViewed($nfdid);
		self::releaseNFD($nfdid);

		// log page entry
		$title = Title::newFromID($row->nfd_page);
		if($title){
			$log = new LogPage( 'nfd', false );

			$vote_param = "keepvote";

			$msg = wfMsgHtml("nfdrule_log_{$vote_param}", "[[{$title->getText()}]]");
			$log->addEntry('vote', $title, $msg, array($vote));
			wfRunHooks("NFDVoted", array($wgUser, $title, '0'));
		}
	}


	public static function vote($nfdid, $vote) {
		global $wgUser;
		$dbw = wfGetDB(DB_MASTER);
		// have they already voted on this?  if so, forget about it, release the current one back to the queue and get out of here
		$count = $dbw->selectField('nfd_vote', array('count(*)'), array('nfdv_user'=>$wgUser->getID(), 'nfdv_nfdid'=>$nfdid));
		if ($count > 0) {
			self::releaseNFD($nfdid);
			return;
		}
		$opts = array();
		$voteCount = 0;
		if($wgUser->isSysOp()){
			$voteCount = 2;
			if($vote == 1){
				$opts[] = "nfd_admin_delete_votes = nfd_admin_delete_votes + 1";
			}
			else{
				$opts[] = "nfd_admin_keep_votes = nfd_admin_keep_votes + 1";
			}
		}
		else{
			$voteCount = 1;
		}
		if ($vote == 1) {
			$opts[] = "nfd_delete_votes = nfd_delete_votes + " . $voteCount;
			$voteint = 1;
		} else {
			$opts[] = "nfd_keep_votes = nfd_keep_votes + " . $voteCount;
			$voteint = 0;
		}

		$dbw->update('nfd', $opts, array('nfd_id'=>$nfdid));
		$dbw->insert('nfd_vote', array('nfdv_user'=>$wgUser->getID(), 'nfdv_vote'=>$voteint, 'nfdv_nfdid'=>$nfdid, 'nfdv_timestamp' => wfTimestampNow()));

		$row = $dbw->selectRow('nfd', '*',array('nfd_id'=>$nfdid));
		// log the vote
		$title = Title::newFromID($row->nfd_page);
		if($title){
			$log = new LogPage( 'nfd', false );

			$vote_param = $vote > 0 ? "deletevote" : "keepvote";

			$msg = wfMsgHtml("nfdrule_log_{$vote_param}", "[[{$title->getText()}]]");
			$log->addEntry('vote', $title, $msg, array($vote));
			wfRunHooks("NFDVoted", array($wgUser, $title, $vote));
		}

		// check, do we have to mark it as patrolled, or roll the change back?
		if ($vote) {
			if ($row->nfd_admin_delete_votes >= NFDRuleTemplateChange::getAdminDeleteVotesRequired() && $row->nfd_delete_votes >= NFDRuleTemplateChange::getDeleteVotesRequired($row->nfd_keep_votes)) {
				self::markNFDPatrolled($nfdid);
				$c = new NFDRuleTemplateChange();
				$nfdReason = self::extractReason($row->nfd_template);
				$c->deleteArticle($nfdid, $nfdReason);
				self::markNFDPatrolled($nfdid);
			}
		} else {
			if ($row->nfd_admin_keep_votes >= NFDRuleTemplateChange::getAdminKeepVotesRequired() && $row->nfd_keep_votes >= NFDRuleTemplateChange::getKeepVotesRequired()) {
				// what kind of rule are we ? figure it out so we can roll it back
				$c = new NFDRuleTemplateChange();
				$c->keepArticle($nfdid);
				self::markNFDPatrolled($nfdid);
			}
		}
		self::markNFDAsViewed($nfdid);
		self::releaseNFD($nfdid);

	}

	// user skips it, so add this to the stuff they have viewed
	function skip($nfdid) {
		self::markNFDAsViewed($nfdid);
	}

	/***Start text change ****/

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
		$opts = array("nfd_rev_id" => $this->mRevision->getID());
		$old_rev = $this->getLastRevID();
		if ($old_rev) {
			$opts['nfd_old_rev_id'] = $old_rev;
		}
		return $opts;
	}

	/*****end text*****/

	function getPart() {
		return "\{\{" . $this->mTemplatePart;
	}

	function getFullTemplateFromText($newText){
		$matches = array();
		$count = preg_match('/{{nfd[^{{]*}}/i', $newText, $matches);
		if(count($matches) > 0){
			return $matches[0];
		}
		else{
			//none given
			return "none";
		}
	}

	function flagAction() {

		// check for a revision
		if (!$this->mRevision) {
			return false;
		}

		// check the title
		$title = $this->mArticle->getTitle();
		if (!$title) {
			return false;
		}

		$part	 = $this->getPart();
		$oldtext = $this->getLastRevisionText();
		$newtext = $this->mRevision->getText();
		$this->setFirstEdit();

		$ret = false;
		if ($this->textRemoved($part, $oldtext, $newtext)) {
			$ret = false;
			$this->mAction = "removed";
		} else if ($this->textAdded($part, $oldtext, $newtext)) {
			$ret = true;
			$this->mAction = "added";
			
		}

		wfDebug("NFD: template change " . print_r($ret, true) . "\n");
		return $ret;
	}

	function setFirstEdit(){
		$dbr = wfGetDB(DB_SLAVE);

		$this->mFirstEdit = $dbr->selectField('firstedit', array('fe_timestamp'), array('fe_page'=> $this->mArticle->getID()));
	}

	static function getDeleteVotesRequired($currentKeepVotes) {
		global $wgNfdVotesRequired;

		if($currentKeepVotes > 0)
			return $wgNfdVotesRequired["advanced_delete"];
		else
			return $wgNfdVotesRequired["delete"];
	}

	static function getAdminDeleteVotesRequired() {
		global $wgNfdVotesRequired;
		return $wgNfdVotesRequired["admin_delete"];
	}

	static function getKeepVotesRequired() {
		global $wgNfdVotesRequired;
		return $wgNfdVotesRequired["keep"];
	}

	static function getAdminKeepVotesRequired() {
		global $wgNfdVotesRequired;
		return $wgNfdVotesRequired["admin_keep"];
	}

	function getNextToPatrolHTML() {
		global $wgOut;

		if (!$this->mResult) {
			// nothing to patrol
			return null;
		}

		// construct the HTML to reply
		// load the page
		$t = Title::newFromID($this->mResult->nfd_page);
		if (!$t) {
			$this->deleteBad($this->mResult->nfd_page);
			return "<!--{$this->mResult->nfd_page}-->error creating title (id# {$this->mResult->nfd_page}) , oops, please <a href='#' onclick='window.location.reload()'>refresh</a>";
		}

		// get current revsion
		$r = Revision::newFromTitle($t);
		if (!$r) {
			return "Error creating revision";
		}

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'titleUrl' => $t->getFullURL(),
			'title' => $t->getText(),
			'nfdId' => $this->mResult->nfd_id,
			'articleHtml' => WikiHowTemplate::mungeSteps($wgOut->parse($r->getText()), array('no-ads'=>1)),
			'articleInfo' => $this->getArticleInfoBox()
		));

		$html = $tmpl->execute('NFDarticle.tmpl.php');
        return $html;
	}

	function getArticleInfoBox(){
		global $wgOut;

		$articleInfo = $this->getArticleInfo();
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		if(intval($articleInfo->fe_user) > 0){
			$u = User::newFromId($articleInfo->fe_user);
			$userLink = $u->getUserPage()->getInternalURL();
			$userName = $u->getName();

			$cp = new ContribsPager($userName);
			$uEdits = $cp->getNumRows();
		}
		else{
			$u = User::newFromName($articleInfo->fe_user_text);

			$userLink = "/User:" . $articleInfo->fe_user_text;
			$userName = $articleInfo->fe_user_text;

			$cp = new ContribsPager($articleInfo->fe_user_text);
			$uEdits = $cp->getNumRows();
		}
		$nfdReasons = NFDGuardian::getNfdReasons();
		$nfdReason = self::extractReason($this->mResult->nfd_template);
		$nfdLongReason = $nfdReasons[$nfdReason['type']];
		self::replaceTemplatesInText($nfdLongReason, $this->mResult->nfd_page);
		if($nfdReason['type'] == 'dup' && $nfdReason['article'] != ""){
			$t = Title::newFromText($nfdReason['article']);
			if($t)
				$nfdLongReason .= " with [[" . $t->getText() . "]]";
		}

		$t = Title::newFromID($this->mResult->nfd_page);
		if($t){
			$a = new Article($t);
			$pageHistory = new PageHistory($a);
			$pager = new PageHistoryPager( $pageHistory );
			$edits = $pager->getNumRows();

			$discussionTitle = Title::newFromText($t->getText(), NS_TALK);
			if($discussionTitle){
				$discussionArticle = new Article($discussionTitle);
				$pageHistory = new PageHistory($discussionArticle);
				$pager = new PageHistoryPager( $pageHistory );
				$discussion = $pager->getNumRows();
			}
			else{
				$discussion = 0;
			}
		}

		$tmpl->set_vars(array(
			'age' => wfTimeAgo($this->mResult->nfd_fe_timestamp),
			'authorUrl' => $userLink,
			'authorName' => $userName,
			'views' => $articleInfo->page_counter,
			'nfd' => $wgOut->parse($nfdLongReason),
			'edits' => $edits,
			'userEdits' => $uEdits,
			'nfdVotes' => $this->getTotalVotes($this->mResult->nfd_id),
			'discussion' => $discussion
		));

		$html = $tmpl->execute('NFDinfo.tmpl.php');
        return $html;
	}

	static function replaceTemplatesInText(&$text, $pageId){
		$t = Title::newFromID($pageId);
		if($t){
			//check for talk page
			$talk = $t->getTalkPage();
			if($talk){
				$talkLink = "Discussion:" . $talk->getText();;
				$text = str_replace("{{TALKSPACEE}}:{{PAGENAME}}", $talkLink, $text);
			}
		}
	}

	function getTotalVotes($nfd_id){

		$dbr = wfGetDB(DB_SLAVE);

		$keeps = array();
		$deletes = array();
		$admin = true;

		NFDGuardian::getDeleteKeep($deletes, $keeps, $nfd_id);

		$html = "";
		if(count($deletes) == 0 && count($keeps) == 0){
			$html .= "There have been no votes yet.";
		}
		else{
			if(count($deletes) > 0){
				$i = 0;
				foreach($deletes as $delete){
					if($i > 0)
						$html .= ", ";
					$html .= NFDGuardian::getUserInfo($delete);
					$i++;
				}
				$html .= " voted to delete. ";
			}
			else{
				$html .= "There have been no votes to delete. ";
			}
			if(count($keeps) > 0){
				$i = 0;
				foreach($keeps as $keep){
					if($i > 0)
						$html .= ", ";
					$html .= NFDGuardian::getUserInfo($keep);
					$i++;
				}
				$html .= " voted to keep. ";
			}
			else{
				$html .= "There have been no votes to keep. ";
			}
		}

		return $html;

	}

	function getArticleInfo(){
		$dbr = wfGetDB(DB_SLAVE);

		$row = $dbr->selectRow(array('page', 'firstedit'), '*', array('fe_page=page_id', 'page_id' => $this->mResult->nfd_page));
		return $row;
	}

	function deleteArticle($nfdid, $nfdReason) {
		global $wgUser, $wgLang;
		wfLoadExtensionMessages("NFDGuardian");

		// keep the article
		$dbr = wfGetDB(DB_SLAVE);

		// load the revision text
		$pageid = $dbr->selectField('nfd', array('nfd_page'), array('nfd_id'=> $nfdid));
		$t = Title::newFromID($pageid);
		if (!$t) {
			return false;
		}

		$r = Revision::newFromTitle($t);
		if (!$r) {
			return false;
		}

		$dateStr = $wgLang->date(wfTimestampNow());
		$votes = $this->getVotes($nfdid, $dbr);
		$comment = wfMsgHtml('nfd_delete_message', $dateStr, $nfdReason['type'], $votes['deleteUsers'], $votes['keepUsers'], "[[".$t->getText()."]]");

		$foundDup = false;
		if($nfdReason['type'] == "dup"){
			//check if it was a duplicate
			$dupTitle = Title::newFromText($nfdReason['article']);
			if($dupTitle){
				$dupRev = Revision::newFromTitle($dupTitle);
				if($dupRev){
					//the duplicate title exists, so turn the current article into a redirct
					$a = new Article($t);
					$editSuccess = $a->doEdit("#REDIRECT [[" . $nfdReason['article'] . "]]", $comment);
					$foundDup = true;

					//log same delete in the nfd table
					$log = new LogPage('nfd', false);
					$log->addEntry('redirect', $t, $comment);
				}
			}
		}

		//if we haven't found a duplicate, then go ahead and do the delete
		if(!$foundDup){
			$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, "NFD-Voter-Tool", "NFD Voter Tool", $comment);

			$discussionTitle = $t->getTalkPage();
			$text = "";
			$article = "";
			if ($discussionTitle->getArticleId() > 0) {
				$r = Revision::newFromTitle($discussionTitle);
				$text = $r->getText();
			}

			$article = new Article($discussionTitle);
			$text .= "\n\n$formattedComment\n\n";
			$article->doEdit($text, "");

			$a = new Article($t);
			$editSuccess = $a->doDeleteArticle($comment);


			//no need to log in the deletion table b/c doDeleteArticle does it for you

			//log same delete in the nfd table
			$log = new LogPage('nfd', false);
			$log->addEntry('delete', $t, $comment);
		}

	}

	function getVotes($nfdid, &$dbr){
		$votes = array();
		$votes['keepUsers'] = "";
		$votes['deleteUsers'] = "";
		$res = $dbr->select('nfd_vote', '*', array('nfdv_nfdid' => $nfdid));
		while($result = $dbr->fetchObject($res)){
			$user = User::newFromId($result->nfdv_user);
			if($user){
				if($result->nfdv_vote == 0){
					if($votes['keepUsers'] != "")
						$votes['keepUsers'] .= ", ";
					$userName = $user->getName();
					$votes['keepUsers'] .= "[[User:$userName|$userName]]";
				}
				else{
					if($votes['deleteUsers'] != "")
						$votes['deleteUsers'] .= ", ";
					$userName = $user->getName();
					$votes['deleteUsers'] .= "[[User:$userName|$userName]]";
				}
			}
		}

		if($votes['keepUsers'] == "")
			$votes['keepUsers'] = "No one";
		if($votes['deleteUsers'] == "")
			$votes['deleteUsers'] = "No one";

		return $votes;
	}

	function keepArticle($nfdid) {
		global $wgUser, $wgLang;
		wfLoadExtensionMessages("NFDGuardian");

		// keep the article
		$dbr = wfGetDB(DB_SLAVE);

		// load the revision text
		$pageid = $dbr->selectField('nfd', array('nfd_page'), array('nfd_id'=> $nfdid));
		$t = Title::newFromID($pageid);
		if (!$t) {
			return false;
		}

		$r = Revision::newFromTitle($t);
		if (!$r) {
			return false;
		}

		//remove the template
		$text = $r->getText();
		$text = preg_replace("@\{\{" . $this->mTemplatePart . "[^\}]*\}\}@i", "", $text);

		$a = new Article($t);
		$editSuccess = $a->doEdit($text, wfMsg('nfd_keep_summary_template', $this->mTemplatePart));

		//now add a discussion message
		if($editSuccess){
			$dateStr = $wgLang->date(wfTimestampNow());
			$text = "";
			$article = "";
			$discussionTitle = $t->getTalkPage();

			$votes = $this->getVotes($nfdid, $dbr);

			$fullTemplate = $this->getFullTemplate($nfdid);
			$nfdReason = $this->extractReason($fullTemplate);
			$keepTemplate = "{{" . $this->mTemplatePart . "|" . $nfdReason['type'] . "|result=keep}}";

			$comment = $keepTemplate . wfMsgHtml('nfd_keep_message', $dateStr, $votes['keepUsers'], $votes['deleteUsers']);
			$formattedComment = wfMsg('postcomment_formatted_comment', $dateStr, "NFD-Voter-Tool", "NFD Voter Tool", $comment);

			if ($discussionTitle->getArticleId() > 0) {
				$r = Revision::newFromTitle($discussionTitle);
				$text = $r->getText();
			}

			$article = new Article($discussionTitle);
			$text .= "\n\n$formattedComment\n\n";
			$article->doEdit($text, "");

			//log same delete in the nfd table
			$keepLogComment = wfMsgHtml('nfd_keep_log_message', $dateStr, $votes['keepUsers'], $votes['deleteUsers'], "[[".$t->getText()."]]");
			$log = new LogPage('nfd', false);
			$log->addEntry('keep', $t, $keepLogComment);
		}
		else{
			return false;
		}
	}
}

/***********************
 *
 *  The admin page for dealing with entries in the NFD queue
 *
***********************/
class NFDGuardian extends SpecialPage {

	const NFD_AVAILABLE = 0;
	const NFD_INACTIVE = 1;
	const NFD_ADVANCED = 2;

	function __construct() {
		SpecialPage::SpecialPage( 'NFDGuardian' );
	}

	function getNextInnards($nfd_type) {
		// grab the next check
		$result = array();
		$c = NFDruletemplatechange::getNextToPatrol($nfd_type);
		if ($c)  {
			// nfd_vote, nfd_skip
			$result['title'] 		= wfMsg('nfd');
			$result['html'] 		= $c->getNextToPatrolHTML();
			$result['nfd_id'] 		= $c->mResult->nfd_id;
			$result['nfd_page']		= $c->mResult->nfd_page;
			$result['nfd_reasons_link'] = $this->getNfdReasonsLink();
			$result['nfd_reasons']	= $this->getNfdReasonsDropdown($nfd_type);
			$result['nfd_discussion_count'] = $this->getDiscussionCount($c->mResult->nfd_page);
		} else {
			$result['done'] 		= 1;
			$result['title'] 		= wfMsg('nfd');
			$result['msg'] 			= "<div id='nfd_options'></div>
										<div id='nfd_head'>
										<p class='nfd_alldone'>".wfMsg('nfd_congrats')."</p>
										<p>".wfMsg('nfd_congrats_2')."</p>
										</div>
										<div id='nfd_box'></div>";

			$result['nfd_reasons_link'] = $this->getNfdReasonsLink();
			$result['nfd_reasons']	= $this->getNfdReasonsDropdown($nfd_type);
		}
		return $result;
	}

	function execute ($par) {
		global $wgUser, $wgOut, $wgRequest, $wgTitle;

		if ($wgUser->isBlocked()) {
			$wgOut->blockedPage();
			return;
		}

		if ( !($wgUser->isSysop() || in_array( 'newarticlepatrol', $wgUser->getRights()) ) ) {
			$wgOut->setRobotpolicy( 'noindex,nofollow' );
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		wfLoadExtensionMessages("NFDGuardian");

		if ($wgRequest->getVal('fetchInnards')) {
			$wgOut->disable();
			$result = self::getNextInnards($wgRequest->getVal('nfd_type'));
			print_r(json_encode($result));
			return;

		} else if ($wgRequest->getVal('getVoteBlock')) {
			$wgOut->setArticleBodyOnly(true);
			$wgOut->addHTML(self::getVoteBlock($wgRequest->getVal('nfd_id')));
			return;

		} else if( $wgRequest->getVal('edit') ) {
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('articleId'));
			if($t){
				$a = new Article($t);
				$editor = new EditPage( $a );
				$editor->edit();

				//Old code for when we wanted to remove
				//the nfd template from the edit window
				/*$content = $wgOut->getHTML();
				$wgOut->clearHTML();

				//grab the edit form
				$data = array();
				$data['form'] = $content;

				//then take out the template
				$c = new NFDRuleTemplateChange();
				$template = $c->getFullTemplate($wgRequest->getVal('nfd_id'));
				$articleContent = $a->getContent();
				$articleContent = str_replace($template, "", $articleContent);
				$data['newContent'] = $articleContent;
				print_r(json_encode($data));*/
			}
			return;
		} else if( $wgRequest->getVal('discussion')) {
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('articleId'));
			if($t){
				$tDiscussion = $t->getTalkPage();
				if($tDiscussion){
					$a = new Article($tDiscussion);
					$content = $a->getContent();
					$wgOldTitle = $wgTitle;
					$wgTitle = $tDiscussion;
					$wgOut->addHTML($wgOut->parse($content));
					$wgOut->addHTML(Postcomment::getForm(true, $tDiscussion, true));
					$wgTitle = $wgOldTitle;
				}
			}
			return;
		} else if ($wgRequest->getVal( 'confirmation' )) {
			$wgOut->setArticleBodyOnly(true);
			echo $this->confirmationModal($wgRequest->getVal('articleId')) ;
			return;

		} else if($wgRequest->getVal('history')) {
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('articleId'));
			if($t){
				$a = new Article($t);
				$pageHistory = new PageHistory($a);
				$pageHistory->history();
				return;
			}
		} else if($wgRequest->getVal('diff')){
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromID($wgRequest->getVal('articleId'));
			if($t){
				$a = new Article($t);
				$wgOut->addHtml('<div class="article_inner">');
				$a->view();
				$wgOut->addHtml('</div>');
			}
			return;
		} else if($wgRequest->getVal('article')) {
			$wgOut->setArticleBodyOnly(true);
			$t = Title::newFromId($wgRequest->getVal('articleId'));
			if($t){
				$r = Revision::newFromTitle($t);
				if($r){
					echo WikiHowTemplate::mungeSteps($wgOut->parse($r->getText()), array('no-ads'=>1));
				}
			}
			return;

		} else if ($wgRequest->wasPosted()) {
			$wgOut->setArticleBodyOnly(true);
			if( $wgRequest->getVal('submitEditForm')){
				$wgOut->disable();
				$this->submitEdit();
				$result = self::getNextInnards($wgRequest->getVal('nfd_type'));
				print_r(json_encode($result));
				return;
			} else{
				if ($wgRequest->getVal('nfd_skip', 0) == 1) {
					NFDRuleTemplateChange::skip($wgRequest->getVal('nfd_id'));
				} else {
					NFDRuleTemplateChange::vote($wgRequest->getVal('nfd_id'), $wgRequest->getVal('nfd_vote'));
				}
				$wgOut->disable();
				$result = self::getNextInnards($wgRequest->getVal('nfd_type'));
				print_r(json_encode($result));
				return;
			}
		}

		/**
	   	 * This is the shell of the page, has the buttons, etc.
	   	 */
		$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('nfdGuardian.js'), 'extensions/wikihow/nfd', false));
		$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('clientscript.js'), 'skins/common', false));
		$wgOut->addScript(HtmlSnips::makeUrlTags('css', array('nfdGuardian.css'), 'extensions/wikihow/nfd', false));

		$wgOut->addHtml("<div class='waiting'><img src='" . wfGetPad('/extensions/wikihow/rotate.gif') . "' alt='' /></div>");
		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$wgOut->addHTML($tmpl->execute('NFDdelete.tmpl.php'));

		// add standings widget
		$group= new NFDStandingsGroup();
		$indi = new NFDStandingsIndividual();

		$indi->addStatsWidget();
		$group->addStandingsWidget();

	}

	function confirmationModal($articleId){
		$t = Title::newFromID($articleId);
		if($t){
			$tmpl = new EasyTemplate( dirname(__FILE__) );
			$tmpl->set_vars(array(
				'titleUrl' => $t->getLocalURL(),
				'title' => $t->getText(),
			));

			$html = $tmpl->execute('NFDconfirmation.tmpl.php');
			return $html;
		}
	}

	//formatted sidenav box for QG voting
	function getVoteBlock($nfd_id) {
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('nfd', array('*'), array('nfd_id' => $nfd_id), 'nfd::getVoteBlock');
		$row = $dbr->fetchObject($res);

		$html .= self::getDeleteKeepVotes($nfd_id, $row->nfd_delete_votes, $row->nfd_keep_votes, $row->nfd_admin_delete_votes, $row->nfd_admin_keep_votes, $row->nfd_page);

		return $html;
	}

	function submitEdit(){
		global $wgRequest;

		$nfd_id = $wgRequest->getVal('nfd_id');
		$t = Title::newFromID($wgRequest->getVal('articleId'));
		if($t){
			$a = new Article($t);
			//log it
			$params = array();
			$log = new LogPage( 'nfd', true ); // false - dont show in recentchanges
			$msg = wfMsgHtml('nfd_edit_log_message', "[[{$t->getText()}]]");
			$log->addEntry('edit', $t, $msg, $params);

			//Old code for removing template in edit window
			//$c = new NFDRuleTemplateChange();
			//$template = $c->getFullTemplate($wgRequest->getVal('nfd_id'));

			//put the old template back in. (was removed for the edit)
			//$text = $template . " " . $wgRequest->getVal('wpTextbox1');
			$text = $wgRequest->getVal('wpTextbox1');
			$summary = $wgRequest->getVal('wpSummary');

			//check to see if there is still an nfd tag
			$c = new NFDRuleTemplateChange();
			if(preg_match("@{$c->getPart()}@i", $text)){
				//there is an NFD tag still, so lets make sure its the same one
				$fullTemplate = $c->getFullTemplateFromText($nfd_id);
				if(strpos($text, $fullTemplate) === false){
					//nfd template has changed
					$newFullTemplate = $c->getFullTemplateFromText($text);
					$nfdReason = $c->extractReason($newFullTemplate);

					$dbw = wfGetDB(DB_MASTER);
					$dbw->update('nfd', array('nfd_template' => $newFullTemplate, 'nfd_reason' => $nfdReason['type']), array('nfd_id' => $nfd_id));
				}
			}

			if($a){
				//save the edit
				$a->doEdit($text, $summary);
			}

			if($wgRequest->getval('removeTemplate') == 'true'){
				//they vote to remove template, which is the same as vote to keep
				NFDRuleTemplateChange::save($wgRequest->getVal('nfd_id'), $t);
			}
			else{
				//they didn't want to remove template, so that's like a skip
				NFDRuleTemplateChange::skip($wgRequest->getVal('nfd_id'));
			}
		}
	}

	//get the yes/no boxes for voters
	function getDeleteKeepVotes($nfd_id, $act_d, $act_k, $act_d_a, $act_k_a, $nfd_page) {
		$t = NFDRuleTemplateChange::getTitleFromnfdID($nfd_id);

		$req_d = NFDRuleTemplateChange::getDeleteVotesRequired($act_k);
		$req_d_a = NFDRuleTemplateChange::getAdminDeleteVotesRequired();
		$req_k = NFDRuleTemplateChange::getKeepVotesRequired();
		$req_k_a = NFDRuleTemplateChange::getAdminKeepVotesRequired();

		$dbr = wfGetDB(DB_SLAVE);

		if($t)
			$link = "<a href='{$t->getFullURL()}' target='new'>" . wfMsg('howto', $t->getText()) . "</a>";
		else{
			//the article has been deleted, so grab out of the archive
			$title = $dbr->selectField('archive', 'ar_title', array('ar_page_id' => $nfd_page), __METHOD__, array('ORDER BY' => 'ar_timestamp DESC', 'LIMIT' => '1'));
			if($title)
				$link = wfMsg('howto', str_replace( '-', ' ', $title ));
		}


		$delete = array();
		$keep = array();
		$status = '';


		self::getDeleteKeep($delete, $keep, $nfd_id);

		$html .= "<div id='nfd_vote_1'><div class='nfd_vote_head'>Keep Votes</div>";

		//get keep boxes
		$foundAdmin = $req_k_a > 0 ? false : true;
		for($i = 0; $i < count($keep); $i++){
			$html .= self::getActualAvatar($keep[$i], $foundAdmin);
		}
		for ($i=$act_k; $i<($req_k); $i++) {
			$html .= self::getNeededAvatar($foundAdmin);
		}

		$html .= "</div><div id='nfd_vote_2'>";

		//get left arrow
		if ($act_k >= $req_k && $act_k_a >= $req_k_a) {
			$html .= "<div class='nfd_arrow nfd_left_win'></div>";
			$status = 'removed';
		}
		else {
			$html .= "<div class='nfd_arrow nfd_left'></div>";
		}
		//get right arrow
		if ($act_d >= $req_d && $act_d_a >= $req_d_a) {
			$html .= "<div class='nfd_arrow nfd_right_win'></div>";
			$status = 'approved';
		}
		else {
			$html .= "<div class='nfd_arrow nfd_right'></div>";
		}
		$html .= "</div><div id='nfd_vote_3'><div class='nfd_vote_head nfd_head_no'>Delete Votes</div>";

		//get delete boxes
		$foundAdmin = $req_d_a > 0 ? false : true;
		for($i = 0; $i < count($delete); $i++){
			$html .= self::getActualAvatar($delete[$i], $foundAdmin);
		}
		
		for ($i=$act_d; $i<($req_d); $i++) {
			$html .= self::getNeededAvatar($foundAdmin);
		}

		$html .= '</div>';

		if (($status == '') && (count($delete) +  count($keep) > 1)) {
			$status = 'tie';
		}

		//grab main image
		$img = "<div class='nfd_vote_img nfd_img_$status'></div>";


		$top = "<div id='nfd_vote_text'>$img" . wfMsg('nfdvote_'.$status, $link) . "</div>";

		//add it all up
		$html = "$top<div id='nfd_votes'>$html</div><div class='clearall'></div>";

		return $html;
	}

	function getDeleteKeep(&$delete, &$keep, $nfd_id){
		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select('nfd_vote', array('nfdv_user','nfdv_vote', 'nfdv_timestamp'), array('nfdv_nfdid' => $nfd_id), __METHOD__, array('ORDER BY' => 'nfdv_timestamp DESC'));

		while($row = $dbr->fetchObject($res)){
			if ($row->nfdv_vote == '1')
				array_push($delete,$row->nfdv_user);
			else
				array_push($keep,$row->nfdv_user);
		}
	}

	function getActualAvatar($user_id, &$foundAdmin){
		if($user_id){
			$u = new User();
			$u->setID($user_id);

			if($u->loadFromDatabase())
				$foundAdmin = $foundAdmin || $u->isSysop();

			$img = Avatar::getAvatarURL($u->getName());
			if ($img == '') {
				$img = Avatar::getDefaultPicture();
			}
			else {
				$img = "<img src='$img' />";
			}
			$avatar = "<div class='nfd_avatar'><a href='{$u->getUserPage()->getFullURL()}' target='_blank' class='tooltip'>{$img}</a>";
			$avatar .= "<span class='tooltip_span'>Hi, I'm {$u->getName()}</span></div>";
		}
		return $avatar;
	}

	function getNeededAvatar(&$foundAdmin){
		$avatar = "<div class='nfd_emptybox'>" . ($foundAdmin?"":"Admin") . "</div>";
		$foundAdmin = $foundAdmin || true;

		return $avatar;
	}

	function getUserInfo($user_id) {
		if ($user_id) {
			$u = new User();
			$u->setID($user_id);

			$img = Avatar::getAvatarURL($u->getName());
			if ($img == '') {
				$img = Avatar::getDefaultPicture();
			}
			else {
				$img = "<img src='$img' />";
			}
			$avatar = "<span><a href='{$u->getUserPage()->getFullURL()}' target='_blank' class='tooltip'>{$img}</a>";
			$avatar .= "<span class='tooltip_span'>Hi, I'm {$u->getName()}</span></span>";
			$avatar .= "<a target='new' href='{$u->getUserPage()->getFullURL()}'>{$u->getName()}</a>";
		}
		return $avatar;
	}

	function getDiscussionCount($pageId){
		$t = Title::newFromId($pageId);
		if($t){
			$dt = Title::newFromText($t->getText(), NS_TALK);
			if($dt){
				$article = new Article($dt);
				return substr_count($article->getContent(), "de_user");
			}
		}
		return 0;
	}

	function getNfdReasonsDropdown($defaultValue='all'){
		$html = "<div id='nfd_reasons'><span>" . wfMsg('nfd_dropdown_text') . "<select>";
		$html .= "<option value='all'>all</option>";

		$reasons = $this->getNfdReasons();
		foreach($reasons as $key => $value){
			if($key != "dup"){
				$selected = $key == $defaultValue ? " selected='yes' " : "";
				$html .= "<option value='{$key}'{$selected}>{$key}</option>";
			}
		}

		$html .= "</select></span><a id='nfdrules_submit' class='button white_button' href='#'>Done</a>";
		$html .= "<div class='clearall'></div></div>";
		return $html;
	}

	function getNfdReasonsLink($defaultValue='all'){
		$html = "<span id='nfd_reasons_link'>(<a href='#' class='nfd_options_link'>Change Options</a>)</span>";
		return $html;
	}

	static function hasBeenDecided($title){
		if($title){
			$discussionTitle = Title::newFromText($title->getText(), NS_TALK);
			if($discussionTitle){
				$discussionArticle = new Article($discussionTitle);
				$content = $discussionArticle->getContent();
				$matches = array();
				$count = preg_match('/{{nfd.*[^{{]}}/i', $content, $matches);
				if($count > 0){
					if(stristr($matches[0], "result=keep") === false)
						return false;
					else
						return true;
				}
			}
		}

		return false;
	}

	static function hasBeenPatrolled($page_id){
		$dbr = wfGetDB(DB_SLAVE);

		$count = $dbr->selectField('nfd', 'count(*)', array('nfd_page' => $page_id, 'nfd_patrolled' => "1"));

		return $count > 0;
	}

	static function getNfdReasons(){
		global $wgMemc;

		$key = wfMemcKey("nfdreasons");
		$result = $wgMemc->get($key);
		if ($result) {
			return $result;
		}
		$reasons = array();
		$t = Title::makeTitle(NS_TEMPLATE, "Nfd");
		$r = Revision::newFromTitle($t);
		preg_match_all("@\| [a-z]+ = .*@m", $r->getText(), $matches);
		$reasons = array();
		foreach($matches[0] as $match){
			preg_match_all('@^| ([a-z]+[^\[]) = \[\[[^\]]*\]\](.*)$@', $match, $m);
			//now grab the short code from the match
			$shortReason = $m[1][1];
			//now grab the long reason from the match
			$longReason = $m[2][1];

			if($shortReason)
				$reasons[$shortReason] = $longReason;
		}

		$wgMemc->set($key, $reasons);

		return $reasons;
	}

	/*************
	 * This function is used in the maintenance script
	 * (/maintenance/importNfdArticles.php)
	 * to import all NFD articles into the NFD tables.
	 */
	public static function importNFDArticles(){

		$dbr = wfGetDB(DB_SLAVE);

		$count = 0;

		$resultsNFD = array();

		$res = $dbr->select(array('templatelinks', 'page'), 'page_id', array('tl_from = page_id', "page_namespace"=> '0', "tl_title IN ('NFD', 'Nfd')"));
		while($result = $dbr->fetchObject($res)){
			$results[] = $result;
		}
		foreach($results as $result){
			$hasEntry = $dbr->selectField('nfd', 'count(*)', array('nfd_page'=> $result->page_id, 'nfd_patrolled'=>0)) > 0;
			if(!$hasEntry){
				$t = Title::newFromID($result->page_id);
				if($t){
					$article = new Article($t);
					$revision = Revision::newFromTitle($t);
					if($article && $revision){
						$l = new NFDRuleTemplateChange($revision, $article);
						if($l->processCurrentRevision(true)){
							echo "Importing " . $t->getText() . "\n";
							$count++;
						}
					}
				}
			}
		}
		echo "Imported a total of " . $count . " articles.\n";
	}

	public static function checkArticlesInNfdTable(){
		$dbr = wfGetDB(DB_SLAVE);

		$count = 0;

		$results = array();
		$res = $dbr->select('nfd', array('nfd_id', 'nfd_page', 'nfd_reason'), array('nfd_patrolled' => '0', 'nfd_status' => NFDGuardian::NFD_AVAILABLE));
		while($result = $dbr->fetchObject($res)){
			$results[] = $result;
		}

		foreach($results as $result){
			$t = Title::newFromID($result->nfd_page);
			if($t){
				$a = new Article($t);
				if($result->nfd_reason == "dup"){
					NFDRuleTemplateChange::markPreviousAsInactive($result->nfd_page);
					echo "Removing Dup: " . $t->getText() . "\n";
					$count++;
				}
				else if($a->isRedirect()){
					//check if its a redirect
					NFDRuleTemplateChange::markPreviousAsInactive($result->nfd_page);
					echo "Removing Redirect: " . $t->getText() . "\n";
					$count++;
				}
				else{
					//check to see if its already been processed in the discussion section
					if(NFDGuardian::hasBeenDecided($t)){
						NFDRuleTemplateChange::markPreviousAsAdvanced($result->nfd_page);
						echo "Marking Advanced: " . $t->getText() . "\n";
						$count++;
					}

					//
					$discussionTitle = Title::newFromText($t->getText(), NS_TALK);
					if($discussionTitle){
						$discussionArticle = new Article($discussionTitle);
						$content = $discussionArticle->getContent();
						$matches = array();
						$count = preg_match('/NFD Voter Tool/i', $content, $matches);
						if($count > 0){
							NFDRuleTemplateChange::markPreviousAsAdvanced($result->nfd_page);
							echo "Marking Advanced: " . $t->getText() . "\n";
							$count++;
						}
					}

					//check to see if it still has an NFD tag
					$revision = Revision::newFromTitle($t);
					if($a && $revision){
						$l = new NFDRuleTemplateChange($revision, $a);
						if(!$l->processCurrentRevision(false)){
							echo "No longer has NFD: " . $t->getText() . "\n";
							$count++;
						}
					}
				}
			}
			else{
				NFDRuleTemplateChange::markPreviousAsInactive($result->nfd_page);
				echo "Title no longer exists: " . $result->nfd_page . "\n";
				$count++;
			}
		}

		echo "Removed a total of " . $count . " articles from tool.\n";
	}

}

class NFDAdvanced extends SpecialPage {

	function __construct() {
        SpecialPage::SpecialPage( 'NFDAdvanced' );
    }

    function execute ($par) {
		list( $limit, $offset ) = wfCheckLimits();
    	$llr = new ListNFDAdvanced();
    	return $llr->doQuery( $offset, $limit );
	}
}

class ListNFDAdvanced extends QueryPage {

	function getName() {
		return "NFDAdvanced";
	}

	function isExpensive() {
		# page_counter is not indexed
		return true;
	}
	function isSyndicated() { return false; }

	function getSQL() {
		return "SELECT nfd_page, nfd_fe_timestamp as value FROM nfd WHERE nfd_status = " . NFDGuardian::NFD_ADVANCED . " GROUP BY nfd_page";
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgContLang;
		$title = Title::newFromID($result->nfd_page);
		$link = $skin->makeKnownLinkObj( $title, htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );
		return $link;
	}
}
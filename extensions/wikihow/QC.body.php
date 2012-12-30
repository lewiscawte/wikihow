<?

class QCRule {

	// flexibility if we want to track different namespaces
	var $mValidNamespaces = array(NS_MAIN);
	var	$mArticle	= null;

	function __construct($article) {
		$this->mArticle 	= $article;
	}	

	function textChanged($part, $oldtext, $newtext) {
		if (preg_match("@{$part}@i", $oldtext) && !preg_match("@{$part}@", $newtext)) {
			return true;
		}
		if (!preg_match("@{$part}@i", $oldtext) && preg_match("@{$part}@", $newtext)) {
			return true;
		}
		#echo "$part <br/><br/> $oldtext <br/><br> $newtext <br/>"; echo "preg match failed"; // exit;
		return false;
	}

	function process() {
		if ($this->flagAction()) {
			$this->logQCEntry();
		}
	}

	function getKey() {
		// abstract ? 
		return "qclog_" . rand(1000, 9999); 
	}

	function getEntryOptions() {
		return array();
	}
	
	function logQCEntry () {
		global $wgUser;
		$opts = array(	"qc_key" => $this->getKey(), 
						"qc_timestamp" => wfTimestampNow(),	
						"qc_user" => $wgUser->getID(),
						"qc_user_text" => $wgUser->getName(),
						"qc_page" => $this->mArticle->getID(),
				);
		$opts = array_merge($this->getEntryOptions(), $opts);
		$dbw = wfGetDB(DB_MASTER);
		
		//mark any existing entries as patrolled for this entry
		$dbw->update("qc", array("qc_patrolled"=>1), array("qc_page"=> $this->mArticle->getID(), "qc_key"=>$this->getKey()));
		
		$dbw->insert('qc', $opts);

		#print_r($dbw); exit;
	}
}

class QCRuleTemplateChange extends QCRule {

	var	$mTemplate 	= null;
	var $mRevision	= null;
	var $mLastRevid	= null;

	function __construct($template, $revision, $article) {
		$this->mTemplate	= $template; 
		$this->mRevision	= $revision;
		$this->mArticle		= $article;
	}

	function getKey() {
		return "changedtemplate_" . strtolower($this->mTemplate); 
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
#echo "getting reivsion from $lastrev<br/>";
		$r = Revision::newFromID($lastrev); 
		if (!$r) return null;
		return $r->getText();	
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

		$oldtext = $this->getLastRevisionText();
		$newtext = $this->mRevision->getText();
		$part = "\{\{" . $this->mTemplate;

		$ret = $this->textChanged($part, $oldtext, $newtext);
		wfDebug("QC: template change " . print_r($ret, true) . "\n");
		return $ret;
	}

	function getEntryOptions() {
		$opts = array("qc_rev_id" => $this->mRevision->getID(), "qc_old_rev_id"=>$this->getLastRevID());
		return $opts;
	}
	
}

class QC extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'QC' );
	}

	function execute ($par) {
		global $wgUser;

		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) {
			$wgOut->errorpage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
	}
   
}


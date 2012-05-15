<?
/*
* 
*/
class TitusQueryTool extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('TitusQueryTool');
	}

	function execute($par) {
		global $wgOut, $wgUser, $wgRequest, $isDevServer;
		$user = $wgUser->getName();
		$userGroups = $wgUser->getGroups();
		if (!(IS_SPARE_HOST || $isDevServer) || $wgUser->isBlocked() || !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->errorpage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($wgRequest->wasPosted()) {
			$this->configureDB();
			$this->handleQuery();
		} else {
			$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('download.jQuery.js'), 'extensions/wikihow/common', false));
			$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('jquery.sqlbuilder-0.06.js'), 'extensions/wikihow/titus', false));
			$wgOut->setPageTitle('Dear Titus...');
			$wgOut->addHtml($this->getToolHtml());
		}
	}

	// Use the spare DB for the query tool to reduce load on production dbs
	function configureDB() {
		global $wgDBservers;

		if (IS_SPARE_HOST) {
			$wgDBservers[1] = array(
				'host'     => WH_DATABASE_BACKUP,
				'dbname'   => WH_DATABASE_NAME,
				'user'     => WH_DATABASE_USER,
				'password' => WH_DATABASE_PASSWORD,
				'load'     => 1
			);
		}
	}

	function getHeaderRow(&$res, $delimiter = "\t") {
		$n = mysql_num_fields($res->result);
		$fields = array();
		for( $i = 0; $i < $n; $i++ ) {
			$meta = mysql_fetch_field( $res->result, $i );
			$field =  new MySQLField($meta);
			$fields[] = $field->name();
		}
		return implode($delimiter, $fields) . "\n";
	}

	function getTitusFields() {
		$data = array();
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->query("SELECT * FROM titus LIMIT 1");
		$n = mysql_num_fields($res->result);
		for( $i = 0; $i < $n; $i++ ) {
			$meta = mysql_fetch_field( $res->result, $i );
			$field =  new MySQLField($meta);
			$data[] = array(
				'field' => 'titus.' . $field->name(), 
				'name' => $field->name(), 
				'id'  => $i, 
				'ftype' => $field->type(),
				'defaultval' => '[enter val]');
			
		}
		return json_encode($data);
	}	

	function handleQuery() {
		global $wgRequest; 

		$sql = $this->buildSQL();
		
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->query($sql);
		$output = $this->getHeaderRow($res);
		while ($row = $dbr->fetchObject($res)) {
			$output .= $this->outputRow($row);
		}
		$this->outputFile("titus_query.titus", $output);
	} 

	function buildSQL() {
		global $wgRequest;

		$ids = array();
		if($wgRequest->getVal('page-filter') == 'urls') {
			$ids = $this->getIdsFromUrls(trim(urldecode($wgRequest->getVal('urls'))));
		}

		$sql = urldecode($wgRequest->getVal('sql'));
		if (sizeof($ids)) {
			$pageCondition = "ti_page_id IN (" . implode(",", $ids) . ")";
			if (stripos($sql, "WHERE ")) {
				$sql = preg_replace("@WHERE (.+)$@", "WHERE (\\1) AND $pageCondition", $sql);
			} else {
				$sql .= " WHERE $pageCondition";
			}
		}
		return $sql;
	}

	function outputRow(&$row, $delimiter = "\t") {
		$data = get_object_vars($row);
		// Stupid hack because people can't make a url from a title
		if($data['ti_page_title']) {
			$data['ti_page_title'] = 'http://www.wikihow.com/' . $data['ti_page_title'];
		}
		return implode($delimiter, array_values($data)) . "\n";
	}

	function getIdsFromUrls(&$urls) {
		$ids = array();
		$urls = explode("\n", trim($urls));
		foreach ($urls as $url) {
			$t = WikiPhoto::getArticleTitle($url);
			if ($t && $t->exists()) {
				$ids[] = $t->getArticleId();
			}
		}
		return $ids;
	}

	function outputFile($filename, &$output, $mimeType  = 'text/tsv') {
		global $wgOut, $wgRequest;
		$wgOut->setArticleBodyOnly(true);
		$wgRequest->response()->header('Content-type: ' . $mimeType);
		$wgRequest->response()->header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
		$wgOut->addHtml($output);
	}

	function getToolHtml() {
		EasyTemplate::set_path(dirname(__FILE__).'/');
		$vars = array('dbfields' => $this->getTitusFields());
	 	return EasyTemplate::html('titusquerytool.tmpl.php', $vars);
	}
}

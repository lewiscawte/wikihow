<?
/*
* 
*/
class TitusQueryTool extends UnlistedSpecialPage {
	var $titus = null;

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('TitusQueryTool');
	}

	function execute($par) {
		global $wgOut, $wgUser, $wgRequest, $isDevServer, $IP, $wgLoadBalancer;
		$user = $wgUser->getName();
		$userGroups = $wgUser->getGroups();
		if (!(IS_SPARE_HOST || $isDevServer) || $wgUser->isBlocked() || !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->errorpage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		require_once("$IP/extensions/wikihow/titus/Titus.class.php");
		$this->titus = new TitusDB(false); 

		if ($wgRequest->wasPosted()) {
			$this->handleQuery();
		} else {
			$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('download.jQuery.js'), 'extensions/wikihow/common', false));
			$wgOut->addScript(HtmlSnips::makeUrlTags('js', array('jquery.sqlbuilder-0.06.js'), 'extensions/wikihow/titus', false));
			$wgOut->setPageTitle('Dear Titus...');
			$wgOut->addHtml($this->getToolHtml());
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
		$titus = $this->titus;
		$res = $titus->performTitusQuery("SELECT * FROM " . TitusDB::TITUS_TABLE_NAME . " LIMIT 1");
		$n = mysql_num_fields($res->result);
		for( $i = 0; $i < $n; $i++ ) {
			$meta = mysql_fetch_field( $res->result, $i );
			$field =  new MySQLField($meta);
			$data[] = array(
				'field' => TitusDB::TITUS_TABLE_NAME . '.' . $field->name(), 
				'name' => $field->name(), 
				'id'  => $i, 
				'ftype' => $field->type(),
				'defaultval' => '[enter val]');
			
		}
		return json_encode($data);
	}	

	function handleQuery() {
		global $wgRequest; 

		$ids = array();
		if($wgRequest->getVal('page-filter') == 'urls') {
			$ids = $this->getIdsFromUrls(trim(urldecode($wgRequest->getVal('urls'))));
		}

		try { 
			$this->checkForErrors($ids);
		} catch (Exception $e) {
			$this->outputFile("titus_error.titus", $e->getMessage());
			return;
		}

		$sql = $this->buildSQL($ids);
		
		$titus = $this->titus;
		$res = $titus->performTitusQuery($sql);
		$output = $this->getHeaderRow($res);
		foreach ($res as $row) {
			$output .= $this->outputRow($row);
		}
		$this->outputFile("titus_query.titus", $output);
	} 

	function checkForErrors(&$ids) {
		// Check that there aren't any redirects
		$pageUrls = array();
		if (sizeof($ids)) {
			$sql = "SELECT page_id FROM page where page_namespace = 0 and page_is_redirect = 1 and page_id IN (" . implode(",", $ids) . ")";
			$dbr = wfGetDB(DB_SLAVE);
			$res = $dbr->query($sql);
			while ($row = $dbr->fetchObject($res)) {
				$t = Title::newFromId($row->page_id);
				$pageUrls[] = "http://www.wikihow.com" . $t->getLocalUrl();
			}
		}

		if (sizeof($pageUrls)) {
			$error = "ERROR: Following urls are redirects\n";
			$error .= implode("\n", $pageUrls);
			throw new Exception($error);
		}
	}

	function buildSQL(&$ids) {
		global $wgRequest;

		$sql = urldecode($wgRequest->getVal('sql'));
		if (false === stripos($sql, TitusDB::TITUS_TABLE_NAME)) {
			$sql = "SELECT * FROM " . TitusDB::TITUS_TABLE_NAME;
		}

		if (sizeof($ids)) {
			$pageCondition = "ti_page_id IN (" . implode(",", $ids) . ")";
			$orderBy = " ORDER BY FIELD(ti_page_id, " . implode(",", $ids) . ")";
			if (stripos($sql, "WHERE ")) {
				$sql = preg_replace("@WHERE (.+)$@", "WHERE (\\1) AND $pageCondition $orderBy", $sql);
			} else {
				$sql .= " WHERE $pageCondition $orderBy";
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

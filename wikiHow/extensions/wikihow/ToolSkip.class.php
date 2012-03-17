<?

if ( !defined('MEDIAWIKI') ) die();

/**
* A utility class of static functions that produce html snippets
*/
class ToolSkip {
	
	var $skippedKey = null;
	var $inUseKey = null;
	var $toolTable = null;
	var $checkoutTimeField = null;
	var $checkoutUserField = null;
	var $checkoutItemField = null;
	
	const DEFAULT_VALUE = "default";
	const ONE_WEEK = 604800;
	
	function ToolSkip($toolName = DEFAULT_VALUE, $toolTable = DEFAULT_VALUE, $checkoutTimeField = DEFAULT_VALUE, $checkoutUserField = DEFAULT_VALUE, $checkoutItemField = DEFAULT_VALUE) {
		global $wgUser;
		
		$id = $wgUser->getID();
		$this->skippedKey = $toolName . "_" . $id . "_skipped";
		$this->inUseKey = $toolName . "_inUse";
		$this->toolTable = $toolTable;
		$this->checkoutTimeField = $checkoutTimeField;
		$this->checkoutUserField = $checkoutUserField;
		$this->checkoutItemField = $checkoutItemField;
	}
	
	function skipItem($itemId = 0) {
		global $wgMemc;
		$key = $this->skippedKey;
		$val = $wgMemc->get($key);
		
		if ($val) {
			$val[] = $itemId;	
			$wgMemc->set($key, $val, ONE_WEEK);
		} else {
			$val = array($itemId);
			$wgMemc->set($key, $val, ONE_WEEK);
		}
		
	}
	
	function useItem($itemId = 0) {
		global $wgUser;
		
		$dbw = wfGetDB(DB_MASTER);
		if($this->checkoutTimeField != DEFAULT_VALUE && $this->checkoutUserField != DEFAULT_VALUE && $this->toolTable != DEFAULT_VALUE) {
			$dbw->update($this->toolTable, array($this->checkoutTimeField =>wfTimestampNow(), $this->checkoutUserField=>$wgUser->getID()), array($this->checkoutItemField => $itemId));
		}
	}
	
	function unUseItem($itemId = 0) {
		$dbw = wfGetDB(DB_MASTER);
		if($this->checkoutTimeField != DEFAULT_VALUE && $this->checkoutUserField != DEFAULT_VALUE && $this->toolTable != DEFAULT_VALUE) {
			$dbw->update($this->toolTable, array($this->checkoutTimeField => "", $this->checkoutUserField => ""), array($this->checkoutItemField => $itemId));
		}
	}
	
	function getSkipped() {
		global $wgMemc;
		
		$key = $this->skippedKey;
		$val = $wgMemc->get($key);
		
		return $val;
	}
	
	function clearSkipCache(){
		global $wgMemc;
		
		$wgMemc->delete($this->skippedKey);
	}
	
}


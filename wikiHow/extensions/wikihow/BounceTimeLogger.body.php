<?
/*
* Ajax end-point for logging "bounce times" (time between
* page-load and when the user navigates away.) 
* This is for a small experiment, and not for use with more than
* a few pages.
*
* @author Ryo
*/
class BounceTimeLogger extends UnlistedSpecialPage {

	function __construct() {
		UnlistedSpecialPage::UnlistedSpecialPage('BounceTimeLogger');

		//this page gets requested onUnload. set ignore_user_abort()
		//to make sure this script finishes executing even if the
		//client disconnects mid-way.
		ignore_user_abort(true);
	}

	function getBuckets(){
		return array(
				'0-10s'	=> 0,
				'11-30s' => 11,
				'31-60s' => 31,
				'1-3m'	=> 60,
				'3-10m'	=> 180,
				'10-30m' => 600,
				'30+m'	=> 1800
			);
	}

	function bucketize($n){
		$buckets = $this->getBuckets();
		$b = false; 
		foreach($buckets as $label=>$threshold){
			//find highest bucket that $n is above
			if ($n>=$threshold) $b = $label;
		}
		return $b;
	}

	function execute($par) {
		global $wgRequest, $wgOut;

		$priority = $wgRequest->getVal('_priority');
		$domain = $wgRequest->getVal('_domain');
		$message = $wgRequest->getVal('_message');
		$v = $wgRequest->getVal('v');


		$wgOut->setArticleBodyOnly(true);

		if ($v!=6){
			echo 'wrong version';
			return;
		}
		if (!is_numeric($priority) || $priority<0 || $priority>3){
			echo 'bad priority';
			return;
		}


		$parts = explode(' ',$message);
		if (count($parts)<2){
			echo "Bad message";
			return;
		}

		if ($parts[1]=='ct'){
			$msg = $message;
		}else if ($parts[1]=='btraw' && is_numeric($parts[2])){
			$msg = '';
			$bucket = $this->bucketize($parts[2]);
			if (!$bucket) return; //bad bucket
			$msg = $parts[0].' bt '.$bucket;
			$msg .= ' '.$parts[2];	
		}else if ($parts[1]=='bt'){
			$msg = $message;
		}else{
			echo "Bad message";
			return;
		}

		$msg = "$priority $domain $msg\r\n";
		echo $msg;
		//error_log($msg, 3, '/tmp/wh_bouncetime'.$v.'.log');

		$this->logwrite($msg);
		
	}


	private function logwrite($msg){

		$fp = fsockopen("127.0.0.1", 30302);
		if (!$fp) return;


		fwrite($fp, $msg);
		fclose($fp);
	} 

}

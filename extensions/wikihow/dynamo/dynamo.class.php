<?php

/***
 * 
 * This is a class 
 * 
 ***/

require_once("$IP/extensions/wikihow/common/sdk/sdk.class.php");

class dynamo {
	var $dbr;
	var $maxWrites; //Write throughput as set in the DynamoDB management console
	var $maxReads;  //Read throughput as set in the DynamoDB management console
	var $itemSize;  //Size of 1 rows worth of data
	var $dynamodb;
	var $batchArray;
	var $batchCount;
	
	function __construct() {
		global $table_name, $IP;
		
		if(IS_PROD_EN_SITE)
			$table_name		= 'live_titus';
		else
			$table_name		= 'dev_titus';
		
		echo "Using " . $table_name . " in DynamoDB\n";
		
		$this->dbr = wfGetDB(DB_SLAVE);
		$this->maxWrites = 20;
		$this->maxReads = 10;
		$this->itemSize = 800;
		
		$this->dynamodb = new AmazonDynamoDB();
	}
	
	public function insertDaysData() {
		$articles = array();
		$this->batchArray = array();
		$this->batchCount = 0;
		
		/*$time = wfTimestamp(TS_UNIX, $timestamp);
		$startTime = wfTimestamp(TS_MW, strtotime('-0 days', $time));
		$endTime = wfTimestamp(TS_MW, strtotime('+1 days', $time));
		
		echo "Interval: " . $startTime . " " . $endTime . "\n";*/
		
		echo "Starting " . __METHOD__ . " at " . wfTimestamp(TS_MW) . "\n";
		
		$res = $this->dbr->select('titus', '*', array(), __METHOD__);
		
		
		while($row = mysql_fetch_assoc($res->result)) {
			$row['ti_page_id'] = intval($row['ti_page_id']);
			$articles[] = $row;
		}
		
		echo "Putting " . count($articles) . " rows into dynamodb\n";
		
		$this->batchArray[] = array();
		$this->batchCount++;
		foreach($articles as $articleData) {
			$this->addDataToBatch($articleData, "PutRequest");
		}
		
		for($i = 0; $i < $this->batchCount; $i++) {
			$startTime = microtime(true);
			
			$this->processBatch($this->batchArray[$i], "PutRequest");
			$finishTime = microtime(true);
			
			echo "batch #{$i} " . ($finishTime - $startTime) . "s, ";
			while($finishTime - $startTime < 1) {
				//need to wait till a second is up.
				$finishTime = microtime(true);
			}
		}
		
		echo "\nFinished " . __METHOD__ . " at " . wfTimestamp(TS_MW) . "\n";
	}
	
	private function addDataToBatch(&$data, $requestType) {
		
		$attributes = $this->dynamodb->attributes($data);

		$this->addAttributesToBatch($attributes, $requestType);
	}
	
	private function addAttributesToBatch(&$attibuteArray, $requestType) {
		if(count($this->batchArray[$this->batchCount - 1]) >= $this->maxWrites) {
			$this->batchArray[] = array();
			$this->batchCount++;
		}
		
		$this->batchArray[$this->batchCount - 1][] = array(
			$requestType => array(
				'Item' => $attibuteArray
			)
		);
	}
	
	private function processBatch(&$batch, $requestType) {
		global $table_name;
		
		$response = $this->dynamodb->batch_write_item(
			array('RequestItems' => array(
				$table_name => $batch
			) )
		);
		
		$unprocessedItems = $response->body->UnprocessedItems->to_array();
		
		if($unprocessedItems->count() > 0) {
			echo "There are unprocessed items in this batch. Adding them back into a queue\n";
			$this->handleUnprocessedItems($unprocessedItems, $requestType);
		}

		// Check for success...
		if ($response->isOK())
		{
			//echo "The data has been added to the table.";
		}
			else
		{
			print_r($response);
		}
	}
	
	private function handleUnprocessedItems($unprocessedItems, $requestType) {
		$this->batchArray[] = array();
			$this->batchCount++;
		foreach($unprocessedItems['page_titus']['page_titus'] as $index => $value) {
			$this->addAttributesToBatch($value[$requestType]['Item'], $requestType);
		}
		
	}
}
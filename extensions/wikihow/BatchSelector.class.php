<?php

/**
 * This class is designed to take a large select statement and perform
 * in batches to decrease the load on the db. 
 */

class BatchSelector {
	
	const BATCHSIZE = 2000;		//number of records to pull at once
	const SLEEPTIME = 500000;	//measured in microseconds = .5 seconds
	
	/**
	 *
	 * Essentially a wrapper for the database select function. Accepts the same parameters
	 * as Database::select, with an additional optional parameter for the batch size.
	 * Function selects rows in batches of BATCHSIZE and sleeps for SLEEPTIME microseconds
	 * between each batch. All rows are returned in an array of row objects.
	 * 
	 */
	public static function select($table, $fields, $conditions = '', $options = array(), $batchSize = BatchSelector::BATCHSIZE) {
		$dbr = wfGetDB(DB_SLAVE);
		
		if( !is_array( $options ) ) {
			$options = array( $options );
		}
		$options['LIMIT'] = $batchSize;
		
		$rows = array();
		$batchNum = 0;

		while(1) {
			$options['OFFSET'] = $batchNum*$batchSize;
			$res = $dbr->select($table, $fields, $conditions, __METHOD__, $options);

			if($dbr->numRows($res) == 0)
				break;

			while($row = $dbr->fetchObject($res)) {
				$rows[] = $row;
			}

			usleep(BatchSelector::SLEEPTIME);

			$batchNum++;
		}
		
		return $rows;
	}
}
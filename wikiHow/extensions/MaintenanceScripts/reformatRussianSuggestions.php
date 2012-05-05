<?php
/**
 * Reformat Russian suggestions.
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/MaintenanceScripts and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );

require_once( 'Maintenance.php' );

class ReformatRussianSuggestions extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Reformat Russian suggestions.';
		$this->addArg( 'file', 'File to read; entries should be separated by newlines' );
	}

	public function execute() {
		$file = $this->getArg( 0 );
		if ( !$file ) {
			$this->error(
				'The filename must be supplied as an argument to this script!',
				true
			);
		}
		$f = file_get_contents( $file );
		$lines = explode( "\n", $f );
		foreach( $lines as $line ) {
			$parts = explode( ';', $line );
			$title = array_shift( $parts );
			$this->output( "{$title}\t" . implode( $parts, ';' ) . "\n" );
		}
	}
}

$maintClass = 'ReformatRussianSuggestions';
require_once( RUN_MAINTENANCE_IF_MAIN );
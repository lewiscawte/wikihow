<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @ingroup Maintenance
 */


if(php_sapi_name() != 'cli'){
		die("This script can only be run from the command line.\n");
}

require_once(dirname(__FILE__).'/Maintenance.php');

class CheckProxy extends Maintenance {
		public function __construct(){
			parent::__construct();
			$this->mDescription = "Check for an open proxy at a specified location.";
			$this->addArg( 'ip', 'IP to connect to', true);
			$this->addArg( 'port', 'Port to connect on', true);
			$this->addArg( 'url', 'Target URL', true);

		  }

	      public function getDbType() {
		return Maintenance::DB_NONE;
	}


		public function execute(){
			      $ip = $this->getArg(0);
			      $port = $this->getArg(1);
			      $url = $this->getArg(2);
			      
			      $host = trim(`hostname`);
			      $this->output("Connecting to $ip:$port -- Target: $url\n");
			      $sock = @fsockopen($ip,$port,$errno,$errstr,5);
			      
			      if(!$errno){
					   $this->output("Connected.\n");
					   $request = "GET $url HTTP/1.0\r\n";
					   $request .= "\r\n";
					   @fputs($sock,$request);
					   $response = fgets($sock,65536);
					   $this->output($response);
					   @fclose($sock);
				      } else {
					    $this->output("No connection.");
				}
		      }
}

$maintClass = "CheckProxy";
require_once( RUN_MAINTENANCE_IF_MAIN );

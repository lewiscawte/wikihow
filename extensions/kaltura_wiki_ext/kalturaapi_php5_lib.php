<?php
/*
This file is part of the Kaltura Collaborative Media Suite which allows users
to do with audio, video, and animation what Wiki platfroms allow them to do with
text.

Copyright (C) 2006-2008  Kaltura Inc.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

// this will be used as a global parameter
$ks = null;
class kalturaService
{
	const KALTURA_API_VERSION = "1.0";

	const KALTURA_SERIVCE_FORMAT_JSON  = 1;
	const KALTURA_SERIVCE_FORMAT_XML  = 2;
	const KALTURA_SERIVCE_FORMAT_PHP  = 3;

	private $partner_id ;
	private $subp_id;
	private $format;
	private $ks;

	static $s_kaltura_services = array();

	private static function signature ( $params )
	{
		ksort($params);
		$str = "";
		foreach ($params as $k => $v)
		{
			$str .= $k.$v;
		}
		return  md5($str);
	}

	public static function getInstance ( $kaltura_user , $admin = false , $use_cache = true )
	{
		// TODO - optimize - don't go twice if have a live ks - one per type !
		$ticket_cache = $admin ? 1 : 0;
		if( $use_cache && @self::$s_kaltura_services[$ticket_cache] )
		{
			return self::$s_kaltura_services[$ticket_cache];
		}

		global $partner_id;
		$kaltura_services = new kalturaService( $partner_id );
		$result = $kaltura_services->start( $kaltura_user , $admin );

		$error = @$result["error"];
		if ( ! $error )
		{
			// cache only in case of a successful call 
			self::$s_kaltura_services[$ticket_cache] = $kaltura_services;
		}
		return $kaltura_services;
	}

	public function kalturaService ( $partner_id , $format = self::KALTURA_SERIVCE_FORMAT_PHP /*self::KALTURA_SERIVCE_FORMAT_XML */)
	{
		global $subp_id;
		$this->partner_id = $partner_id;
		$this->subp_id = $subp_id;
		$this->format = $format;
	}


	public function getKs()
	{
		return $this->ks;
	}

	public function hit ($method, $kaltura_user , $params)
	{
		$start_time = microtime (true );
		global $log_kaltura_services;

if ( $log_kaltura_services ) kalturaLog ( "\n\n[" . SERVICE_URL . "]" );

		// append the basic params
		$global_params = array();
		$global_params["kaltura_api_version"] = self::KALTURA_API_VERSION;
		$global_params['partner_id'] = $this->partner_id;
		$global_params['subp_id'] = $this->subp_id;
		$global_params['format'] = $this->format;
		$global_params['uid'] = $kaltura_user->puser_id;
		$global_params['user_name'] = $kaltura_user->puser_name ;

		$params = array_merge( $global_params , $params ); 
		if ( $this->ks ) $params['ks'] = $this->ks;

		$signature = self::signature ( $params );
		$params['kalsig'] = $signature ;

		if ( $log_kaltura_services ) kalturaLog ( "\n\n--> $method|{$kaltura_user->puser_id}|" . print_r ( $params , true ) );
	 	$result = self::do_http (  SERVICE_URL . $method , $params );
		if ( $log_kaltura_services ) kalturaLog ( "<-- " . print_r ( $result , true ) );
		
/*
		$result = curl_exec($ch);
		curl_close($ch);
*/
		if ( $this->format == self::KALTURA_SERIVCE_FORMAT_PHP )
		{
			if ( $log_kaltura_services ) kalturaLog ( "-PHP-> strlen " .strlen ( $result ) );
			$final_result = @unserialize($result);
			if ( $result == null || $result = "" )
			{
				// error - simulate an error 
				// TODO - throw an execption
				$final_result = array ( "error" => array( array ( "code" => "KALTURA_SERVICE_ERROR" , "desc" => "kaltura service error" ) ) );
				if ( $log_kaltura_services ) kalturaLog ( "<-PHP- count" . print_r ( $final_result , true )  );
			}
			elseif ( is_array ( $final_result ))
			{
				if ( $log_kaltura_services ) kalturaLog ( "<-PHP- count" . print_r ( $final_result , true )  );
			}
			else
			{
				if ( $log_kaltura_services ) kalturaLog ( "<-PHP- ??" );
			}
		}
		else
		{
			$final_result = $result;
		}

		$end_time = microtime (true );

		if ( $log_kaltura_services ) kalturaLog ( "<--> $method|{$kaltura_user->puser_id}| time [" . ( $end_time - $start_time ) . "]" );
		return $final_result;
	}

	function do_http ( $url, $params ,  $optional_headers = null)
	{
		if ( function_exists('curl_init') )
			return self::do_curl ( $url, $params ,  $optional_headers  );
		else
			return self::do_post_request ( $url, $params ,  $optional_headers  );
	}

	function do_curl ( $url, $params ,  $optional_headers = null )
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, '');
		curl_setopt($ch, CURLOPT_TIMEOUT, 5 );

		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	// TODO- make sure the $data is infact an array object
	function do_post_request($url, $data, $optional_headers = null)
	{
		$formatted_data = http_build_query($data , "", "&");
		$params = array('http' => array(
		             'method' => 'POST',
		             "Accept-language: en\r\n".
      					"Content-type: application/x-www-form-urlencoded\r\n",
		             'content' => $formatted_data
		          ));
		if ($optional_headers !== null) {
		   $params['http']['header'] = $optional_headers;
		}
		$ctx = stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $ctx);
		if (!$fp) {
			$php_errormsg = "";
		   throw new Exception("Problem with $url, $php_errormsg");
		}
		$response = @stream_get_contents($fp);
		if ($response === false) {
		   throw new Exception("Problem reading data from $url, $php_errormsg");
		}
		return $response;
	}


	public function start ( $kaltura_user , $admin = false )
	{
		global $secret , $admin_secret;
		global $ks;

		if ( ! $kaltura_user instanceof kalturaUser )
		{
			throw new Exception ( __CLASS__ . ":" . "kaltura user must be of type 'kaltura_user'" );
		}
		// the puser_id is mandatory (therfore part of the funciton)
		// the puser_name is optional and will help create the proper name in the puser_kuser table
		if ( $admin )
		{
			$params = array( "secret" => $admin_secret , "admin" => "1" );
		}
		else
			$params = array( "secret" => $secret );

		$params["privileges"] = "*" ; //"edit:*,view:*"; // allow all 
		$generic_result = $this->hit ( "startsession" , $kaltura_user , $params );
		$error = 	@$generic_result["error"];
		$result = 	@$generic_result["result"];
		$debug =  	@$generic_result["debug"];

		$this->ks = @$result["ks"];
		$ks = $this->ks ;

		// TODO - fixme !!
		return $generic_result;

		if ( $this->ks ) return true;

		throw new Exception ( $error[0] );
	}


	public function getuser ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "getuser" , $kaltura_user, $params );
		return $generic_result;
	}

	public function getkshow ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "getkshow" , $kaltura_user, $params );
		return $generic_result;
	}

	// expect kshow_id
	public function addkshow ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "addkshow" , $kaltura_user, $params );
		return $generic_result;
	}

	public function rollbackkshow ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "updatekshow" , $kaltura_user, $params );
		return $generic_result;
	}


	public function updatekshow ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "updatekshow" , $kaltura_user, $params );
		return $generic_result;
	}

	public function addentry ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "addentry" , $kaltura_user, $params );
		return $generic_result;
	}

	public function deleteentry ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "deleteentry" , $kaltura_user, $params );
		return $generic_result;
	}

	// requires:
	//	admin_ticket
	// 	kshow_id
	public function deletekshow ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "deletekshow" , $kaltura_user, $params );
		return $generic_result;
	}


	public function getallentries ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "getallentries" , $kaltura_user, $params );
		return $generic_result;
	}

	public function registerpartner ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "registerpartner" , $kaltura_user, $params );
		return $generic_result;
	}

	public function getpartner ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "getpartner" , $kaltura_user, $params );
		return $generic_result;
	}
	
	public function addwidget ( $kaltura_user , $params = null)
	{
		if ( $params == null )			$params = array();

		$generic_result = $this->hit ( "addwidget" , $kaltura_user, $params );
		return $generic_result;
	}	
	
}


?>

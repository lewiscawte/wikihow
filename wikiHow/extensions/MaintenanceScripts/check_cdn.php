<?

require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/common/nusoap/nusoap.php");

define('SERVER_URL', 'https://ws.pingdom.com/soap/PingdomAPI.wsdl');
define('PINGDOM_API_STATUS_OK', 0);
define('CHECK_NAME', '');
define('BAD_CHECK_LOCAL', 300);
define('BAD_CHECK_REMOTE', 500); 
define('BAD_CHECK_DEFAULT', 300);

$badCheckTimes = array( 
		"Stockholm, Sweden" 		=> BAD_CHECK_REMOTE,  
		"Montreal, Canada" 			=> BAD_CHECK_LOCAL, 
		"London, UK" 				=> BAD_CHECK_REMOTE, 
		"Dallas 4, TX" 				=> BAD_CHECK_LOCAL, 
		"Herndon, VA" 				=> BAD_CHECK_LOCAL, 
		"Houston 3, TX" 			=> BAD_CHECK_LOCAL, 
		"Amsterdam 2, Netherlands" 	=> BAD_CHECK_REMOTE, 
		"London 2, UK" 				=> BAD_CHECK_REMOTE, 
		"Dallas 5, TX" 				=> BAD_CHECK_LOCAL, 
		"Dallas 6, TX" 				=> BAD_CHECK_LOCAL, 
		"Los Angeles, CA" 			=> BAD_CHECK_LOCAL, 
		"Frankfurt, Germany" 		=> BAD_CHECK_REMOTE, 
		"Atlanta, Georgia" 			=> BAD_CHECK_LOCAL, 
		"New York, NY" 				=> BAD_CHECK_LOCAL, 
		"Chicago, IL" 				=> BAD_CHECK_LOCAL, 
		"Copenhagen, Denmark" 		=> BAD_CHECK_REMOTE, 
		"Tampa, Florida" 			=> BAD_CHECK_LOCAL, 
		"Seattle, WA" 				=> BAD_CHECK_LOCAL, 
		"Washington, DC" 			=> BAD_CHECK_LOCAL, 
		"Madrid, Spain" 			=> BAD_CHECK_REMOTE, 
		"Las Vegas, NV" 			=> BAD_CHECK_LOCAL, 
		"Denver, CO" 				=> BAD_CHECK_LOCAL, 
		"San Francisco, CA" 		=> BAD_CHECK_LOCAL, 
		"Paris, France" 			=> BAD_CHECK_REMOTE, 
		"Manchester, UK" 			=> BAD_CHECK_REMOTE,
); 

$maxBadChecks = 2;
$reportsEmail = "alerts@wikihow.com";

// {{{ Creation of soap client
$client = new soapclient(
	SERVER_URL,
	array(
		'trace' => 1,
		'exceptions' => 0
	)
);
// }}}

$err = $client->getError();
if ($err || $client->fault) {
	print "there was an error: $err\n";
	exit;
}

$login_data->username = WH_PINGDOM_USERNAME;
$login_data->password = WH_PINGDOM_PASSWORD;
$login_response = $client->Auth_login(WH_PINGDOM_API_KEY, $login_data);
if (PINGDOM_API_STATUS_OK != $login_response->status)
{
	print_r($login_response);
	print "Unable to login\n";
	exit;
}

//Without this value you wont be able to call any other Pingdom API function
$sessionId = $login_response->sessionId;

$get_list_response = $client->Check_getList(WH_PINGDOM_API_KEY, $sessionId);
if (PINGDOM_API_STATUS_OK != $get_list_response->status)
{
	print "Error occurred while trying to get list of checks\n";
	exit;
}
$list_of_checks = $get_list_response->checkNames;


#$locations = $client->Location_getList(WH_PINGDOM_API_KEY, $sessionId)->locationsArray; print_r($locations); exit;
/*
$locations = $client->Location_getList(WH_PINGDOM_API_KEY, $sessionId)->locationsArray;
$params->resolution = "DAILY";
$params->from = date("c", time() - 3600);
$params->to = date("c");
foreach ($list_of_checks as $check_name) {
	foreach ($locations as $location) {
		#$params->locations = $locations;
		$params->locations = array($location);
		$params->checkName = $check_name;
		$response_times = $client->Report_getResponseTimes(WH_PINGDOM_API_KEY, $sessionId, $params);
		#print_r($response_times); exit;
		$response_time = $response_times->responseTimesArray[0]->responseTime;
		if ($response_time > 500)
			$rows .= "<tr><td>$check_name</td>$location</td><td>{$response_times->responseTimesArray[0]->responseTime}</td></tr>";

	}
}
*/

$body = "";
$rawRequest->from 			= date("c", time() - 3600);
$rawRequest->to 			= date("c");
$rawRequest->resultsPerPage = 50;
$rawLoc						= array();
foreach ($list_of_checks as $check_name) {
	$rawRequest->checkName = $check_name;
	$rawdata = array();
	for ($i = 0; $i < 3; $i++) {
		$rawRequest->pageNumber = $i + 1;
		$rawdata = array_merge($rawdata, $client->Report_getRawData(WH_PINGDOM_API_KEY, $sessionId, $rawRequest)->rawDataArray); 
	}
	foreach($rawdata as $r) {
		if (!isset($rawLoc[$r->location]))
			$rawLoc[$r->location] = array();
		$rawLoc[$r->location][] = $r;
	}
	foreach ($rawLoc as $location=>$vals) {
		$bad = 0;
		$badCheckTime = isset($badCheckTimes[$location]) ? $badCheckTimes[$location] : BAD_CHECK_DEFAULT; 
		foreach ($vals as $r ) {
			if ($r->responseTime > $badCheckTime)
				$bad++;
		}
		if ($bad > $maxBadChecks) {
			$body .= "<h3>$check_name / $location has $bad responses over $badCheckTime ms </h3> ";// . print_r($vals, true);
			$body .= "<table width='80%' align='center'>";
			$i = 0;
			foreach ($vals as $r) {
				$c = "";
				if ($i % 2 == 1)
					$c = "style='background-color: #eee;'" ;
				if ($r->responseTime > $badCheckTime) 
					$body .= "<tr><td $c>{$r->checkTime}</td><td $c>{$r->checkState}</td><td $c><b>{$r->responseTime}</b></td></tr>\n";
				else
					$body .= "<tr><td $c>{$r->checkTime}</td><td $c>{$r->checkState}</td><td $c>{$r->responseTime}</td></tr>\n";
				$i++;
			}
			$body .= "</table>";
		}
	}
}

//logout
$logout_response = $client->Auth_logout(WH_PINGDOM_API_KEY, $sessionId);
if (PINGDOM_API_STATUS_OK != $logout_response->status) {
	print "Error occurred while closing connection\n";
	exit;
}

if ($body != "")  {
	print "sending mail...\n";
	mail($reportsEmail, "Pingdom checks for " . date("r"), $body, "Content-type: text/html;\nFrom: $reportsEmail;");
}

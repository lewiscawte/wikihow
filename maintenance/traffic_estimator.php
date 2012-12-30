<?php
// Copyright 2009, Google Inc. All Rights Reserved.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

/** This code sample checks a keyword to see whether it will get any traffic.*/
require_once('commandLine.inc');
require_once('soapclientfactory.php');
# Define SOAP headers.
  //'<clientEmail>' . WH_AW_CLIENT_EMAIL . '</clientEmail>' .
$headers =
  '<email>' . WH_AW_EMAIL . '</email>'.
  '<password>' . WH_AW_PASS . '</password>' .
  '<useragent>' . WH_AW_USER_AGENT. '</useragent>' .
  '<developerToken>' . WH_AW_DEV_TOKEN . '</developerToken>' .
  '<applicationToken>' . WH_AW_APP_TOKEN . '</applicationToken>';

# Set up service connection. To view XML request/response, change value of
# $debug to 1. To send requests to production environment, replace
# "sandbox.google.com" with "adwords.google.com".
$namespace = 'https://adwords.google.com/api/adwords/v13';
$estimator_service = SoapClientFactory::GetClient(
  $namespace . '/TrafficEstimatorService?wsdl', 'wsdl');
$estimator_service->setHeaders($headers);
$debug = 0;

# Create keyword structure.

$dbr = wfGetDB(DB_SLAVE);
$res = $dbr->query("select st_title from suggested_titles where st_used=0 order by rand() limit 1000");

echo "<Table width='75%'>\n";
while ($row = $dbr->fetchObject($res)) {

	$t = Title::makeTitle(NS_MAIN, $row->st_title);

# Create keyword structure.
$keyword =
  '<keywordText>' . htmlspecialchars($t->getFullText()) . '</keywordText>' .
  '<keywordType>Exact</keywordType>' .
  '<language>en</language>';

	# Check keyword traffic.
# Check keyword traffic.
$request_xml =
  '<checkKeywordTraffic>' .
  '<requests>' . $keyword . '</requests>' .
  '</checkKeywordTraffic>';

	$estimates = $estimator_service->call('checkKeywordTraffic', $request_xml);
	$estimates = $estimates['checkKeywordTrafficReturn'];
	if ($debug) show_xml($estimator_service);
	if ($estimator_service->fault) show_fault($estimator_service);
	
	# Display estimate.
	echo "<tr><td>{$t->getText()}</td><td>"  . $estimates . "</tr>\n";
	
}
echo "</table>";
	
function show_xml($service) {
  echo $service->request;
  echo $service->response;
  echo "\n";
}

function show_fault($service) {
  echo "\n";
  echo 'Fault: ' . $service->fault . "\n";
  echo 'Code: ' . $service->faultcode . "\n";
  echo 'String: ' . $service->faultstring . "\n";
  echo 'Detail: ' . $service->faultdetail . "\n";
  exit(0);
}
?>


<?php
// Copyright 2008, Google Inc. All Rights Reserved.
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

/** This code sample retrieves variations for a seed keyword. */
require_once('commandLine.inc');
require_once('maintenance/lib/nusoap.php');


# Define SOAP headers.
$headers =
  '<email>' . WH_AW_EMAIL . '</email>'.
  '<password>' . WH_AW_PASS . '</password>' .
  '<clientEmail>' . WH_AW_CLIENT_EMAIL . '</clientEmail>' .
  '<useragent>' . WH_AW_USER_AGENT. '</useragent>' .
  '<developerToken>' . WH_AW_DEV_TOKEN . '</developerToken>' .
  '<applicationToken>' . WH_AW_APP_TOKEN . '</applicationToken>';

# Set up service connection. To view XML request/response, change value of
# $debug to 1. To send requests to production environment, replace
# "sandbox.google.com" with "adwords.google.com".
$namespace = 'https://adwords.google.com/api/adwords/v11';
$keyword_tool_service =
  new soapclient($namespace . '/KeywordToolService?wsdl', 'wsdl');
$keyword_tool_service->setHeaders($headers);
$debug = 0;

# Create seed keyword structure.
$seed_keyword =
  '<negative>false</negative>' .
  '<text>how to run</text>' .
  '<type>Broad</type>';
$use_synonyms = '<useSynonyms>false</useSynonyms>';

# Get keyword variations.
$request_xml =
  '<getKeywordVariations>' .
  '<seedKeywords>' . $seed_keyword . '</seedKeywords>' .
  $use_synonyms .
  '<languages>en</languages>' .
  '<countries>US</countries>' .
  '</getKeywordVariations>';
$variation_lists =
  $keyword_tool_service->call('getKeywordVariations', $request_xml);
$variation_lists = $variation_lists['getKeywordVariationsReturn'];
if ($debug) {
		show_xml($keyword_tool_service);
	print_r($keyword_tool_service);
}
if ($keyword_tool_service->fault) show_fault($keyword_tool_service);

# Display keyword variations.
$to_consider = $variation_lists['additionalToConsider'];
echo 'List of additional keywords to consider has ' . count($to_consider) .
  ' variation(s).' . "\n";

$more_specific = $variation_lists['moreSpecific'];
echo 'List of popular queries with given seed has ' . count($more_specific) .
  ' variation(s).' . "\n";

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

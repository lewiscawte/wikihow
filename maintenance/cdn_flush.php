<?php
//
// Remove / refresh an object on the CDN (CDNetworks). Must pass in a username (email
// address of portal user), their password, and an object / URL to clear. Wildcards
// can be used in the object to clear, but should be used with caution because a
// cache clear of a bunch of objects could cause a whole bunch of requests to
// our varnish servers.
//

require_once('commandLine.inc');

function main($params) {
	$params = parseParams($argv);
	$html = doCDnetworksApiCall($params);

	print "response from CDN:\n";
	print $html . "\n";
}

function doCDnetworksApiCall($params) {
	$url = 'https://openapi.us.cdnetworks.com/purge/rest/doPurge';
	$ch = curl_init($url);

	$object = preg_replace('@http://[^/]+@', '', $params['url']);
	$type = strpos($object, '*') !== false ? 'wildcard' : 'item';
	$sendParams = array(
		'pad' => 'pad1.whstatic.com',
		'user' => $params['user'],
		'pass' => $params['password'],
		'type' => $type,
		'path' => $object,
		'output' => 'json',
	);
	$sendParamsStr = '';
	$first = true;
	foreach ($sendParams as $k => $v) {
		if ($first) {
			$first = false;
		} else {
			$sendParamsStr .= '&';
		}
		$sendParamsStr .= $k . '=' . urlencode($v);
	}

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $sendParamsStr);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$ret = curl_exec($ch);
	curl_close($ch);

	return $ret;
}

function parseParams($argv) {
	$opts = getopt('u:p:l:', array('user:', 'password:', 'location:'));

	$user = isset($opts['u']) ? $opts['u'] : @$opts['user'];
	$password = isset($opts['p']) ? $opts['p'] : @$opts['password'];
	$url = isset($opts['l']) ? $opts['l'] : @$opts['location'];

	if (!$user || !$password || !$url) {
		die("usage: php cdn_flush.php --user=<cdnetworks-email-login> --password=<cdnetworks-password> --location=<url-to-flush>\n");
	}

	return array(
		'user' => $user,
		'password' => $password,
		'url' => $url,
	);
}

main($argv);


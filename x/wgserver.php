<?

$isDevServer = strpos(@$_SERVER['SERVER_NAME'], 'wikidiy.com') !== false;
if (@$_SERVER['SERVER_NAME'] == 'm.wikihow.com' ||
    @$_SERVER['SERVER_NAME'] == 'carrot.wikihow.com' ||
    @$_SERVER['SERVER_NAME'] == 'testers.wikihow.com' ||
    @$_SERVER['SERVER_NAME'] == 'html5.wikihow.com' ||
    strpos(@$_SERVER['SERVER_NAME'], 'apache') === 0 ||
    strpos(@$_SERVER['SERVER_NAME'], 'squid') === 0 ||
    @$_SERVER['SERVER_PORT'] == 8000 ||
    $isDevServer)
{
    $portStr = (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '');
    $wgServer = 'http://' . $_SERVER['SERVER_NAME'] . $portStr;
    if ($isDevServer) $wgCookieDomain = 'wikidiy.com';
} else {
    $wgServer = 'http://www.wikihow.com';
}

header("Content-type: text/plain;"); 

echo $wgServer . "\n\n";

print_r($_SERVER); 


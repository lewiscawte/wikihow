<?
echo "Test!";
print_r($_COOKIE);
function hostname() {
        if ( function_exists( 'posix_uname' ) ) {
                // This function not present on Windows
                $uname = @posix_uname();
        } else {
                $uname = false;
        }
        if( is_array( $uname ) && isset( $uname['nodename'] ) ) {
                return $uname['nodename'];
        } else {
                # This may be a virtual server.
                return $_SERVER['SERVER_NAME'];
        }
}
echo "Server: " . hostname() . "\n";
  // check to see what's happening
$filepath = ini_get('session.save_path').'/sess_'. $_COOKIE['wikidb_16_session']; // session_id();
echo $filepath;  
if(file_exists($filepath))
{
   $filetime = filemtime ($filepath);
   $timediff = mktime() - $filetime;
   echo 'session established '.$timediff.' seconds ago<br><br>';
	chmod($filepath, 0777);
}
phpinfo();
## TEST COMMENT
?>

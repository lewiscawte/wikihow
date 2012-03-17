<?php
/***************************************************************************
 *                                sessions.php
 *                            -------------------
 *   begin                : Saturday, Feb 13, 2001
 *   copyright            : (C) 2001 The phpBB Group
 *   email                : support@phpbb.com
 *
 *   $Id: sessions.php,v 1.17 2008/09/10 11:54:17 cvsuser Exp $
 *
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

//
// Adds/updates a new session to the database for the given userid.
// Returns the new session ID on success.
//
function session_begin($user_id, $user_ip, $page_id, $auto_create = 0, $enable_autologin = 0)
{
	global $db, $board_config;
	global $HTTP_COOKIE_VARS, $HTTP_GET_VARS, $SID;
	
	$cookiename = $board_config['cookie_name'];
	$cookiepath = $board_config['cookie_path'];
	$cookiedomain = $board_config['cookie_domain'];
	$cookiesecure = $board_config['cookie_secure'];


	if ( isset($HTTP_COOKIE_VARS[$cookiename . '_sid']) || isset($HTTP_COOKIE_VARS[$cookiename . '_data']) )
	{
		$session_id = isset($HTTP_COOKIE_VARS[$cookiename . '_sid']) ? $HTTP_COOKIE_VARS[$cookiename . '_sid'] : '';
		$sessiondata = isset($HTTP_COOKIE_VARS[$cookiename . '_data']) ? unserialize(stripslashes($HTTP_COOKIE_VARS[$cookiename . '_data'])) : array();
		$sessionmethod = SESSION_METHOD_COOKIE;
	}
	else
	{
		$sessiondata = array();
		$session_id = ( isset($HTTP_GET_VARS['sid']) ) ? $HTTP_GET_VARS['sid'] : '';
		$sessionmethod = SESSION_METHOD_GET;
	}

		
	//
	if (!preg_match('/^[A-Za-z0-9]*$/', $session_id)) 
	{
		$session_id = '';
	}

	$last_visit = 0;
	$current_time = time();
	//$expiry_time = $current_time - $board_config['session_length'];
	$expiry_time = $current_time - (60 * 60 * 24 * 28); // 28 days


	
	//
	// Try and pull the last time stored in a cookie, if it exists
	//
	
	$old_user_data = $userdata;
	
	$userdata = getUserOptions($user_id);

	if ( $user_id != ANONYMOUS && !is_array($userdata) )
	{
		message_die(CRITICAL_ERROR, 'Could not obtain lastvisit data from user table', '', __LINE__, __FILE__, $sql);
	}

	
	if ($user_id != ANONYMOUS) {
		$login = 1;
		$enable_autologin = 1;
	} else {
		$login = 0;
		$enable_autologin = 0;
	}
	/*
	if ( $user_id != ANONYMOUS )
	{
	
		$auto_login_key = $userdata['user_password'];

		if ( $auto_create )
		{
			if ( isset($sessiondata['autologinid']) && $userdata['user_active'] )
			{
				// We have to login automagically
				if( $sessiondata['autologinid'] === $auto_login_key )
				{
					// autologinid matches password
					$login = 1;
					$enable_autologin = 1;
				}
				else
				{
					// No match; don't login, set as anonymous user
					$login = 0; 
					$enable_autologin = 0; 
					$user_id = $userdata['user_id'] = ANONYMOUS;
				}
			}
			else
			{
				// Autologin is not set. Don't login, set as anonymous user
				$login = 0;
				$enable_autologin = 0;
				$user_id = $userdata['user_id'] = ANONYMOUS;
			}
		}
		else
		{
			$login = 1;
		}
	}
	else
	{
		$login = 0;
		$enable_autologin = 0;
	}
	*/

	
	//
	// Initial ban check against user id, IP and email address
	//
	preg_match('/(..)(..)(..)(..)/', $user_ip, $user_ip_parts);
	$sql = "SELECT ban_ip, ban_userid, ban_email 
		FROM " . BANLIST_TABLE . " 
		WHERE ban_ip IN ('" . $user_ip_parts[1] . $user_ip_parts[2] . $user_ip_parts[3] . $user_ip_parts[4] . "', '" . $user_ip_parts[1] . $user_ip_parts[2] . $user_ip_parts[3] . "ff', '" . $user_ip_parts[1] . $user_ip_parts[2] . "ffff', '" . $user_ip_parts[1] . "ffffff')
			OR ban_userid = $user_id";
	if ( $user_id != ANONYMOUS )
	{
		$sql .= " OR ban_email LIKE '" . str_replace("\'", "''", $userdata['user_email']) . "' 
			OR ban_email LIKE '" . substr(str_replace("\'", "''", $userdata['user_email']), strpos(str_replace("\'", "''", $userdata['user_email']), "@")) . "'";
	}
	if ( !($result = $db->sql_query($sql)) )
	{
		message_die(CRITICAL_ERROR, 'Could not obtain ban information', '', __LINE__, __FILE__, $sql);
	}

	if ( $ban_info = $db->sql_fetchrow($result) )
	{
		if ( $ban_info['ban_ip'] || $ban_info['ban_userid'] || $ban_info['ban_email'] )
		{
			message_die(CRITICAL_MESSAGE, 'You_been_banned');
		}
	}
// XXADDED MEDIAWIKI TIE IN
	$client_ip = $user_ip;
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $sql = "SELECT * 
                FROM wikidb_112.ipblocks WHERE ipb_address  = '$client_ip' OR ipb_user = $user_id;";
        if ( !($result = $db->sql_query($sql)) )
        {
                message_die(CRITICAL_ERROR, 'Could not obtain ban information', '', __LINE__, __FILE__, $sql);
        }
        if ( $ban_info = $db->sql_fetchrow($result) )
        {
                if ( $ban_info['ipb_address'] || $ban_info['ipb_user'] )
                {
                        message_die(CRITICAL_MESSAGE, 'You_been_banned');
                }
        }

	//
	// Create or update the session
	//
	$sql = "UPDATE " . SESSIONS_TABLE . "
		SET session_user_id = $user_id, session_start = $current_time, session_time = $current_time, session_page = $page_id, session_logged_in = $login
		WHERE session_id = '" . $session_id;
	//. "' 			AND session_ip = '$user_ip'"; we don't care about this. 
	if ( !$db->sql_query($sql) || !$db->sql_affectedrows() )
	{
		$session_id = md5(uniqid($user_ip));

		$sql = "INSERT INTO " . SESSIONS_TABLE . "
			(session_id, session_user_id, session_start, session_time, session_ip, session_page, session_logged_in)
			VALUES ('$session_id', $user_id, $current_time, $current_time, '$user_ip', $page_id, $login)";
		if ( !$db->sql_query($sql) )
		{
			message_die(CRITICAL_ERROR, 'Error creating new session', '', __LINE__, __FILE__, $sql);
		}

	}
	

	$expiry_time = $current_time - (60 * 60 * 24 * 7);
	$sql = "DELETE FROM " . SESSIONS_TABLE . " 
		WHERE session_time < $expiry_time 
       		AND session_id <> '$session_id'";
	#$db->sql_query($sql);

	if ( $user_id != ANONYMOUS )
	{// ( $userdata['user_session_time'] > $expiry_time && $auto_create ) ? $userdata['user_lastvisit'] : ( 
		$last_visit = ( $userdata['user_session_time'] > 0 ) ? $userdata['user_session_time'] : $current_time; 
		
		$user_options = array();
		$user_options["user_session_time"] = $current_time;
		$user_options["user_session_page"] = $page_id;
		$user_options["user_lastvisit"] = $last_visit;
		$user_options["user_session_time"] = $current_time;
		
		
		$lastupdate = $HTTP_COOKIE_VARS['lastupdate'];
		
		
		if ($lastupdate == "") {
			if ( !updateUserOptions($user_id, $user_options) )
			{
				message_die(CRITICAL_ERROR, 'Error updating last visit time', '', __LINE__, __FILE__, $sql);
			} 

			$userdata['user_lastvisit'] = $last_visit;
			setcookie("lastupdate", $current_time, 0, $cookiepath, $cookiedomain, $cookiesecure);
		}
			
		$sessiondata['autologinid'] = ( $enable_autologin && $sessionmethod == SESSION_METHOD_COOKIE ) ? $auto_login_key : '';
		$sessiondata['userid'] = $user_id;
		

	}

	$userdata['session_id'] = $session_id;
	$userdata['session_ip'] = $user_ip;
	$userdata['session_user_id'] = $user_id;
	$userdata['session_logged_in'] = $login;
	$userdata['session_page'] = $page_id;
	$userdata['session_start'] = $current_time;
	$userdata['session_time'] = $current_time;

	
	
	setcookie($cookiename . '_data', serialize($sessiondata), $current_time + 31536000, $cookiepath, $cookiedomain, $cookiesecure);

	setcookie($cookiename . '_sid', $session_id, $current_time + 60*60*24*30, $cookiepath, $cookiedomain, $cookiesecure);

	$SID = 'sid=' . $session_id;

	return $userdata;
}

//
// Checks for a given user session, tidies session table and updates user
// sessions at each page refresh
//
function session_pagestart($user_ip, $thispage_id)
{
	global $db, $lang, $board_config;
	global $HTTP_COOKIE_VARS, $HTTP_GET_VARS, $SID;
	
	$cookiename = $board_config['cookie_name'];
	$cookiepath = $board_config['cookie_path'];
	$cookiedomain = $board_config['cookie_domain'];
	$cookiesecure = $board_config['cookie_secure'];
					
	$current_time = time();
	unset($userdata);

	$user_name = $HTTP_COOKIE_VARS['wiki_sharedUserName'];
	$user_id = $HTTP_COOKIE_VARS['wiki_sharedUserID'];
	
	//echo "$user_name $user_id <BR>";
	//print_r($HTTP_COOKIE_VARS);
	//exit;
	
	if ($user_name != "" && $user_id != "") {
			$sql = "SELECT u.user_id, u.user_name, u.user_options
				FROM  user u
				WHERE u.user_id = $user_id AND u.user_name = '$user_name'; ";
			/*$sql = "SELECT u.user_id, u.user_name, ur.ur_rights, u.user_options
				FROM  user u, user_rights ur
				WHERE ur.ur_user = u.user_id AND u.user_id = $user_id AND u.user_name = '$user_name'; ";
	*/
			if ( !($result = $db->sql_query($sql)) )
			{
				message_die(CRITICAL_ERROR, 'Error doing DB query userdata row fetch', '', __LINE__, __FILE__, $sql);
			}
	
			$userdata = $db->sql_fetchrow($result);
			$userdata = fillInOldUserColumns($userdata); // at this point it's just a row
				
			$sql ="select count(*) as C from wikidb_112.user_groups where ug_user=$user_id and ug_group='sysop';";
            if ( !($result = $db->sql_query($sql)) )
            {
                message_die(CRITICAL_ERROR, 'Error doing DB query userdata row fetch', '', __LINE__, __FILE__, $sql);
            }
			$row = $db->sql_fetchrow($result);
			if ($row['C'] == "1") {
				$userdata['user_level'] = ADMIN;
				$userdata['ur_rights'] = 'sysop';
			} else {
				$userdata['user_level'] = USER;;
			}
//print_r($userdata); exit;
//print_r($userdata); 

							
// do the same 	

			$options = getUserOptions($userdata['user_id']);
		
			if (is_array($userdata) && is_array($options))
				$userdata = array_merge($options, $userdata);
			$userdata['username'] = $userdata['user_name'];
			
			//print_r($userdata);
			//exit;
			
			$userdata['session_logged_in'] = 1;
			
			//$userdata['user_lastvisit'] = time();
			//updateUserOptions($user_id, $userdata);
			
			/*
			$userdata['session_id'] = $session_id;
			$userdata['session_ip'] = $user_ip;
			$userdata['session_user_id'] = $user_id;
			$userdata['session_page'] = $page_id;
			$userdata['session_start'] = $current_time;
			$userdata['session_time'] = $current_time;
			*/
	}		

			
	/*
	if ( isset($HTTP_COOKIE_VARS[$cookiename . '_sid']) || isset($HTTP_COOKIE_VARS[$cookiename . '_data']) )
	{
		$sessiondata = isset( $HTTP_COOKIE_VARS[$cookiename . '_data'] ) ? unserialize(stripslashes($HTTP_COOKIE_VARS[$cookiename . '_data'])) : array();
		$session_id = isset( $HTTP_COOKIE_VARS[$cookiename . '_sid'] ) ? $HTTP_COOKIE_VARS[$cookiename . '_sid'] : '';
		$sessionmethod = SESSION_METHOD_COOKIE;
	}
	else
	{
		$sessiondata = array();
		$session_id = ( isset($HTTP_GET_VARS['sid']) ) ? $HTTP_GET_VARS['sid'] : '';
		$sessionmethod = SESSION_METHOD_GET;
	}

	// 
	if (!preg_match('/^[A-Za-z0-9]*$/', $session_id))
	{
		$session_id = '';
	}

	
	
	//
	// Does a session exist?
	//
	if ( !empty($session_id) )
	{
		//
		// session_id exists so go ahead and attempt to grab all
		// data in preparation
		//
		//
		// Did the session exist in the DB?
		//
		if ( isset($userdata['user_id']) )
		{
			//
			// Do not check IP assuming equivalence, if IPv4 we'll check only first 24
			// bits ... I've been told (by vHiker) this should alleviate problems with 
			// load balanced et al proxies while retaining some reliance on IP security.
			//
			$ip_check_s = substr($userdata['session_ip'], 0, 6);
			$ip_check_u = substr($user_ip, 0, 6);

			
			// TDEROUIN: we want the user to be able to change their IP
			//if ($ip_check_s == $ip_check_u)
			if (true)
			{
				$SID = ($sessionmethod == SESSION_METHOD_GET || defined('IN_ADMIN')) ? 'sid=' . $session_id : '';

							
				//
				// Only update session DB a minute or so after last update
				//
				
				
				if ( $current_time - $userdata['session_time'] > 60 )
				{
					$sql = "UPDATE " . SESSIONS_TABLE . " 
						SET session_time = $current_time, session_page = $thispage_id 
						WHERE session_id = '" . $userdata['session_id'] . "'";
					if ( !$db->sql_query($sql) )
					{
						message_die(CRITICAL_ERROR, 'Error updating sessions table', '', __LINE__, __FILE__, $sql);
					}

					if ( $userdata['user_id'] != ANONYMOUS )
					{
						$options = array();
						$options["user_session_time"] = $current_time;
						$options["user_session_page"] = $thispage_id;
						
						
						if ( !updateUserOptions($user_id, $options) == false )
						{
							message_die(CRITICAL_ERROR, 'Error updating user table', '', __LINE__, __FILE__, $sql);
						}
					}

					//
					// Delete expired sessions
					//
					//$expiry_time = $current_time - $board_config['session_length'];
					$expiry_time = $current_time - (60 * 60 * 24 * 28);
					
					$sql = "DELETE FROM " . SESSIONS_TABLE . " 
						WHERE session_time < $expiry_time 
							AND session_id <> '$session_id'";
					if ( !$db->sql_query($sql) )
					{
						message_die(CRITICAL_ERROR, 'Error clearing sessions table', '', __LINE__, __FILE__, $sql);
					}

					setcookie($cookiename . '_data', serialize($sessiondata), $current_time + 31536000, $cookiepath, $cookiedomain, $cookiesecure);
					setcookie($cookiename . '_sid', $session_id, 0, $cookiepath, $cookiedomain, $cookiesecure);
				}

				
				return $userdata;
			}
		}
	}

	*/

	//
	// If we reach here then no (valid) session exists. So we'll create a new one,
	// using the cookie user_id if available to pull basic user prefs.
	//
	
	/* 
	$user_id = ( isset($sessiondata['userid'])  ) ? intval($sessiondata['userid']) : ANONYMOUS;

	*/

					//
					// Delete expired sessions
					//
					//$expiry_time = $current_time - $board_config['session_length'];
					$expiry_time = $current_time - (60 * 60 * 24 * 28);
					
					$sql = "DELETE FROM " . SESSIONS_TABLE . " 
						WHERE session_time < $expiry_time 
							AND session_id <> '$session_id'";

					if ( !$db->sql_query($sql) )
					{
						message_die(CRITICAL_ERROR, 'Error clearing sessions table', '', __LINE__, __FILE__, $sql);
					}
	if ($user_id == "")
		$user_id = ANONYMOUS;
		
	if ( !($u = session_begin($user_id, $user_ip, $thispage_id, TRUE)) )
	{
		message_die(CRITICAL_ERROR, 'Error creating user session', '', __LINE__, __FILE__, $sql);
	}

	if (is_array($userdata))
		$u = array_merge($u, $userdata);


	return $u;

}

//
// session_end closes out a session
// deleting the corresponding entry
// in the sessions table
//
function session_end($session_id, $user_id)
{
	global $db, $lang, $board_config;
	global $HTTP_COOKIE_VARS, $HTTP_GET_VARS, $SID;

	$cookiename = $board_config['cookie_name'];
	$cookiepath = $board_config['cookie_path'];
	$cookiedomain = $board_config['cookie_domain'];
	$cookiesecure = $board_config['cookie_secure'];

	$current_time = time();

	//
	// Pull cookiedata or grab the URI propagated sid
	//
	if ( isset($HTTP_COOKIE_VARS[$cookiename . '_sid']) )
	{
		$session_id = isset( $HTTP_COOKIE_VARS[$cookiename . '_sid'] ) ? $HTTP_COOKIE_VARS[$cookiename . '_sid'] : '';
		$sessionmethod = SESSION_METHOD_COOKIE;
	}
	else
	{
		$session_id = ( isset($HTTP_GET_VARS['sid']) ) ? $HTTP_GET_VARS['sid'] : '';
		$sessionmethod = SESSION_METHOD_GET;
	}

	if (!preg_match('/^[A-Za-z0-9]*$/', $session_id))
	{
		return;
	}
	
	//
	// Delete existing session
	//
	$sql = "DELETE FROM " . SESSIONS_TABLE . " 
		WHERE session_id = '$session_id' 
			AND session_user_id = $user_id";
	if ( !$db->sql_query($sql) )
	{
		message_die(CRITICAL_ERROR, 'Error removing user session', '', __LINE__, __FILE__, $sql);
	}

	setcookie($cookiename . '_data', '', $current_time - 31536000, $cookiepath, $cookiedomain, $cookiesecure);
	setcookie($cookiename . '_sid', '', $current_time - 31536000, $cookiepath, $cookiedomain, $cookiesecure);

	return true;
}

//
// Append $SID to a url. Borrowed from phplib and modified. This is an
// extra routine utilised by the session code above and acts as a wrapper
// around every single URL and form action. If you replace the session
// code you must include this routine, even if it's empty.
//
function append_sid($url, $non_html_amp = false)
{
	global $SID;

	if ( !empty($SID) && !preg_match('#sid=#', $url) )
	{
		$url .= ( ( strpos($url, '?') != false ) ?  ( ( $non_html_amp ) ? '&' : '&amp;' ) : '?' ) . $SID;
	}

	return $url;
}

?>

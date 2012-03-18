<?php
/**
 * Internationalization file for the AdminRemoveAvatar extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English */
$messages['en'] = array(
	'adminremoveavatar' => 'Remove Avatar',
	'adminremoveavatar-enter-username' => 'Enter username of avatar to remove',
	'adminremoveavatar-error' => "Error: either user '$1' not found or '$1' didn't have an avatar",
	'adminremoveavatar-loading' => 'Loading ...',
	'adminremoveavatar-log-entry' => '$1 removed avatar for username: $2',
	'adminremoveavatar-removed' => "Avatar for '$1' removed from user page.
This change will be visible to non-cookied users within $2 hours and will be visible to cookied users immediately.

See results: [[User:$1|$1]]",
	'adminremoveavatar-reset' => 'reset',
	'adminremoveavatar-rules' => 'The only images you should remove are those with nudity, obscenity, violence, or expressions of hate - everything else is fair game',
	// For Special:ListGroupRights
	'right-adminremoveavatar' => "Remove other user's avatars",
);
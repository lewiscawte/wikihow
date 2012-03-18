<?php
/**
 * Internationalization file for the AdminResetPassword extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English */
$messages['en'] = array(
	'adminresetpassword' => 'Reset Password',
	'adminresetpassword-enter' => 'Enter username of account to reset',
	'adminresetpassword-error' => "Error: user '$1' not found",
	'adminresetpassword-reset' => 'reset',
	'adminresetpassword-success' => "Account '$1' has been reset. No e-mail has been sent to the user.
New password: $2
User can login here: [$3 $3]",
	// For Special:ListGroupRights
	'right-adminresetpassword' => "Reset other users' passwords"
);
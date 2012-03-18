<?php
/**
 * Translations of the namespaces introduced by ThankAuthors.
 *
 * @file
 */

$namespaceNames = array();

// For wikis where the ThankAuthors extension is not installed.
if( !defined( 'NS_USER_KUDOS' ) ) {
	define( 'NS_USER_KUDOS', 18 );
}

if( !defined( 'NS_USER_KUDOS_TALK' ) ) {
	define( 'NS_USER_KUDOS_TALK', 19 );
}

/** English */
$namespaceNames['en'] = array(
	NS_USER_KUDOS => 'User_kudos',
	NS_USER_KUDOS_TALK => 'User_kudos_talk',
);
<?php
/**
 * Translations of the namespaces introduced by RequestTopic.
 *
 * @file
 */

$namespaceNames = array();

// For wikis where the RequestTopic extension is not installed.
if( !defined( 'NS_ARTICLE_REQUEST' ) ) {
	define( 'NS_ARTICLE_REQUEST', 16 );
}

if( !defined( 'NS_ARTICLE_REQUEST_TALK' ) ) {
	define( 'NS_ARTICLE_REQUEST_TALK', 17 );
}

/** English */
$namespaceNames['en'] = array(
	NS_ARTICLE_REQUEST => 'Request',
	NS_ARTICLE_REQUEST_TALK => 'Request_talk',
);
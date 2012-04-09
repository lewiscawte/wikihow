<?php
/**
 * Internationalization file for the Welcome extension.
 *
 * @file
 * @ingroup Extensions
 */
$messages = array();

/** English */
$messages['en'] = array(
	'welcome-email-body' => '',
	'welcome-email-subject' => 'Welcome to {{SITENAME}}!',
	'welcome-email-fromname' => '{{SITENAME}} Admin <support@$1>',
	'welcome-invalid-request' => 'Sorry, invalid request.',
);

/** Finnish (Suomi)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fi'] = array(
	'welcome-email-subject' => 'Tervetuloa {{GRAMMAR:illative|{{SITENAME}}}}!',
	'welcome-email-fromname' => '{{GRAMMAR:genitive|{{SITENAME}}}} ylläpito <support@$1>',
	'welcome-invalid-request' => 'Virhe, pyyntö ei kelpaa.',
);
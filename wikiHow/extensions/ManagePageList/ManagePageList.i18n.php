<?php
/**
 * Internationalisation file for the ManagePageList extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Travis Derouin
 */
$messages['en'] = array(
	'managepagelist' => 'Manage page list', // @todo CHECKME: used or not?
	'managepagelist-add-page' => 'Add article to this list by URL or title:',
	'managepagelist-confirm' => 'Do you really want to remove this article?',
	'managepagelist-create-new' => 'Create a new list: (Example: ID risingstar Name: Rising Stars, every list needs 1+ article)',
	'managepagelist-creation-summary' => 'creating new page list', // used as the edit summary when creating a new page list
	'managepagelist-error-already-listed' => 'Oops! This title is already in the list!',
	'managepagelist-error-make-title' => "Couldn't make title out of $1 $2",
	'managepagelist-error-page-id' => "Error: Couldn't find article ID for $1",
	'managepagelist-id' => 'ID: ',
	'managepagelist-invalid-name' => 'Invalid name for a page list.',
	'managepagelist-name' => 'Name: ',
	'managepagelist-page' => 'Page: ',
	'managepagelist-page-added' => '$1 has been added to the list.',
	'managepagelist-page-count' => 'There {{PLURAL:$1|is one page|are $1 pages}} in this list.',
	'managepagelist-page-removed' => '$1 has been removed from the list.',
	'managepagelist-template' => '{{Rising-star-discussion-msg-2|[[User:$1|$1]]|[[User:$2|$2]]}}',
	'managepagelist-title' => 'Manage page list - $1',
	'managepagelist-view-list' => 'View list:',
	'right-managepagelist' => 'Manage lists of pages', // For Special:ListGroupRights
);
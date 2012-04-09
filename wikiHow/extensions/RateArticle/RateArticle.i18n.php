<?php
/**
 * Internationalization file for the RateArticle extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English */
$messages['en'] = array(
	'listratings' => 'List Rated Pages',
	'listratings-title' => 'List Ratings - Accuracy Patrol',
	'accuracylogpage' => 'Accuracy Log',
	'clearratings' => 'Reset Accuracy Ratings',
	'clearratings-title' => 'Clear Ratings - Accuracy Patrol',
	'clearratings-input-title' => 'Enter the title of another article:',
	'clearratings-no-such-title' => 'Error: no article exists for the title "$1"',
	'clearratings-only-main' => 'Only main namespace articles are allowed to be rated.',
	'clearratings-number-votes' => "'''Number of votes:''' $1",
	'clearratings-avg-rating' => '<b>Average rating:</b>',
	'clearratings-reason' => '<b>Reason:</b>',
	'clearratings-clear-submit' => 'Clear ratings',
	'clearratings-clear-confirm' => 'Confirm',
	'clearratings-clear-confirm-prompt' => 'Are you sure you want to clear the ratings for $1?',
	'clearratings-clear-finished' => 'Ratings cleared.',
	'clearratings-previous-clearings' => '<h2>Previous clearings:</h2>',
	'clearratings-previous-clearings-none' => '<i>None</i>',
	'clearratings-previous-clearings-entry' => '$1 cleared $2 ratings on $3',
	'clearratings-previous-clearings-restore' => 'Restore',
	'clearratings-clear-restored' => '<i><b>Ratings cleared by $1 on $2 restored.</b></i>',
	'clearratings-logsummary' => 'Cleared $3 ratings for [[$2]], reason: $1',
	'accuracypatrol' => 'Accuracy Patrol',
	'accuracypatrol-result-line' => '[[$1]] - ($2 {{PLURAL:$2|vote|votes}}, average: $3% - [[Special:ClearRatings|clear]])',
	'accuracypatrol-return-to' => 'Return to accuracy patrol',
	'accuracypatrol-list-low-ratings-text' => 'Below are the pages with 6 or more votes which have an accuracy score of 40% or less',
	'clearratings-submit' => 'Submit',
	'clearreating-reason-restore' => 'Please enter a reason for restoring these ratings.',
	'clearratings-logrestore' => 'Restored $3 ratings for [[$2]], reason $1',
	'clearratings-restore' => 'Restore',
	'clearratings-no-title' => 'No title specified.',
	'ratearticle-deletion-summary' => 'Deleting page',
	'ratearticle-rated' => 'Thanks. Your vote has been counted.',
	'ratearticle-notrated' => 'Thanks. Please <a href="/$2:$1#post">click here</a> to provide specific details on how we can improve this article.',
	'ratearticle-talkpage' => 'Discussion',
	'ratearticle-question' => 'Was this article accurate?',
	'ratearticle-yes-button' => 'Yes',
	'ratearticle-no-button' => 'No',
);
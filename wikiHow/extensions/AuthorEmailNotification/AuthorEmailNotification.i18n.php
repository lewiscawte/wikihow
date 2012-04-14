<?php
/**
 * Internationalization file for the AuthorEmailNotification extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English */
$messages['en'] = array(
	'aen-rs-subject' => 'Your article "$1" has been marked as a Rising Star!',
	'aen-featured-subject' => 'Your article "$1" will be featured on {{SITENAME}} today!',
	'aen-viewership-subject' => 'Your article "$1" has been viewed more than $2 times.',
	'aen-mod-subject' => 'Your article "$1" has been updated by the {{SITENAME}} Community.',
	'aen-mod-subject-categorization' => 'Your {{SITENAME}} article “$1” has been categorized',
	'aen-mod-body-categorization1' => 'Hi $1,<br /><br />

Your {{SITENAME}} article <a href="$2">$6</a> was categorized by $3. <br /><br />

Congrats on creating an article that another editor wanted to bring more readers to by categorizing it. You can see how this page has changed by looking at its <a href="$4">"diff page"</a> in page history. If you think this article was categorized incorrectly, you can re-categorize it by simply <a href="$5">editing it</a>.
<br /><br />
Thanks for contributing to the wiki,<br />
The {{SITENAME}} Team<br />
<a href="{{SERVER}}?utm_source=n_views_email&utm_medium=email&utm_term=wikihow_home&utm_campaign=n_views_email">{{SITENAME}}</a><br /><br />

<hr /><br />

<font size=-2>You are receiving this e-mail because you signed-up to be notified when your article gets updated. If you wish to not receive e-mails like these in the future, go to your [{{fullurl:{{#Special:AuthorEmailNotification}}|utm_source=n_views_email&utm_medium=email&utm_term=change_prefs&utm_campaign=n_views_email}} author e-mail preferences], and disable e-mail notifications for this article.</font>',
	'aen-mod-subject-image' => 'Your {{SITENAME}} article “$1” just received a new image',
	'aen-mod-body-image1' => 'Hi $1,<br /><br />

Your {{SITENAME}} article <a href="$2">$5</a> just received a new image selected by $3. <br /><br />

Thank you for creating an article that has drawn the attention of other {{SITENAME}} editors. Take a look at the photo and if you think it doesn’t perfectly match the article, please change or remove it by <a href="$4">editing the article</a> and deleting the image code. Explaining why you changed or removed the photo in the edit summary will also help future editors understand your reasoning.<br /><br />

Thanks for contributing to the wiki,<br />
The {{SITENAME}} Team<br />
<a href="{{SERVER}}?utm_source=n_views_email&utm_medium=email&utm_term=wikihow_home&utm_campaign=n_views_email">{{SITENAME}}</a><br /><br />

<hr /><br />

<font size=-2>You are receiving this e-mail because you signed-up to be notified when your article gets updated. If you wish to not receive e-mails like these in the future, go to your [{{fullurl:{{#Special:AuthorEmailNotification}}|utm_source=n_views_email&utm_medium=email&utm_term=change_prefs&utm_campaign=n_views_email}} author e-mail preferences], and disable e-mail notifications for this article.</font>',
	'aen-mod-subject-video' => 'Your {{SITENAME}} article “$1” just received a new video',
	'aen-mod-body-video1' => 'Hi $1,<br /><br />

Your {{SITENAME}} article <a href="$2">$5</a> just received a new video selected by $3. <br /><br />

Congrats on creating an article that has drawn the attention of other {{SITENAME}} editors. Please watch the video and if you think it doesn’t perfectly match the article, please change or remove it by <a href="$4">editing the article</a>. Explaining why you changed or removed the video in the edit summary will also help future editors understand your reasoning.<br /><br />

Thanks for contributing to the wiki,<br />
The {{SITENAME}} Team<br />
<a href="{{SERVER}}?utm_source=n_views_email&utm_medium=email&utm_term=wikihow_home&utm_campaign=n_views_email">{{SITENAME}}</a><br /><br />

<hr /><br />

<font size=-2>You are receiving this e-mail because you signed-up to be notified when your article gets updated. If you wish to not receive e-mails like these in the future, go to your [{{fullurl:{{#Special:AuthorEmailNotification}}|utm_source=n_views_email&utm_medium=email&utm_term=change_prefs&utm_campaign=n_views_email}} author e-mail preferences], and disable e-mail notifications for this article.</font>',
	'aen-mod-subject-edit' => 'Your {{SITENAME}} article “$1” has been edited',
	'aen-mod-body-edit' => 'Hi $1,<br /><br />

Your {{SITENAME}} article <a href="$2">$6</a> was recently edited$3. <br /><br />

Congrats on creating an article that has drawn the attention of other {{SITENAME}} editors. You can see how your article was changed by looking at the <a href="$4">"diff page"</a> in page history. Please feel free to further improve your article by <a href="$5">editing it</a>.<br /><br />
 
Thanks for contributing to the wiki,<br />
The {{SITENAME}} Team<br />
<a href="{{SERVER}}?utm_source=n_views_email&utm_medium=email&utm_term=wikihow_home&utm_campaign=n_views_email">{{SITENAME}}</a><br /><br />

<hr /><br />

<font size=-2>You are receiving this e-mail because you signed-up to be notified when your article gets updated. If you wish to not receive e-mails like these in the future, go to your [{{fullurl:{{#Special:AuthorEmailNotification}}|utm_source=n_views_email&utm_medium=email&utm_term=change_prefs&utm_campaign=n_views_email}} author e-mail preferences], and disable e-mail notifications for this article.</font>',
	'aen-usertalk-subject' => '$2 left you a talk page message on {{SITENAME}}...',
	'aen-usertalk-body' => '$1 sent you a new talk page message<br /><br />

&quot;$4&quot;<br /><br />

You can view this message by visiting your <a href="$3">talk page</a>. <br /><br />

Or, you can respond directly to the message by visiting <a href="$6">$1\'s talk page</a>.

<br /><br />

<hr /><br />

<font size=-2>If you wish to turn off talk page notifications, go to your [{{fullurl:{{#Special:Preferences}}|utm_source=talk_page_message&utm_medium=email&utm_term=change_prefs&utm_campaign=talk_page_message}} email preferences], and uncheck &quot;E-mail me when a user sends me a talk page message&quot;.</font>',
	'aen-thumbs-subject' => 'Your edit on the article "$1" has been given a thumbs up!',
	'aen-thumbs-body' => 'Hi $1,<br /><br />

Congratulations! The <a href="$4">edit</a> you made on the article $2 has been given a thumbs up by $3. This means the {{SITENAME}} community thinks this edit is one of the best contributions recently made on {{SITENAME}}. We appreciate you taking the time to post your how-to expertise on {{SITENAME}} and hope that you will continue to contribute new articles in the future.<br /><br />

Keep up the good work!<br /><br /> 
 
Sincerely,<br />
The {{SITENAME}} Team<br />
<a href="{{SERVER}}?utm_source=thumbs_up_email&utm_medium=email&utm_term=wikihow_home&utm_campaign=thumbs_up_email">{{SITENAME}}</a><br /><br />

<hr /><br />

<font size=-2>You are receiving this email because you signed-up to be notified when you receive a thumbs up. If you wish to not receive e-mails like these in the future, go to your [{{fullurl:{{#Special:AuthorEmailNotification}}|utm_source=rising_star_email&utm_medium=email&utm_term=change_prefs&utm_campaign=rising_star_email}} author e-mail preferences], and disable e-mail notifications for this article.</font>',
	'aen-createpage-msg' => 'E-mail me when this article gets updated:',
	'aen-from' => '{{SITENAME}} Team <support@wikihow.com>',
	'aen-emailn-title' => '<h3>Author e-mail notifications</h3>
Please select the articles below that you would like to receive e-mail notifications for.',
	'aen-form-email' => 'E-mail',
	'aen-form-title' => 'Article title',
	'aen-form-created' => 'Date created',
	'aen-save-btn' => 'Save',
	'aen-no-login' => 'Not logged in',
	'aen-no-login-text' => 'You must be <span class="plainlinks">[{{fullurl:{{#Special:UserLogin}}|returnto=$1}} logged in]</span> to use this special page.',
);
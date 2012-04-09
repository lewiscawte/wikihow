<?php
/**
 * Internationalization file for ThankAuthors extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Travis Derouin
 */
$messages['en'] = array(
	'thankauthors' => 'Thank the Authors',
	'thankauthors-comment-said' => '[[User:$1|$2]] said about [[$3]]:',
	'thankauthors-date' => 'On $1',
	'thankauthors-done' => 'Done.',
	'thankauthors-enjoyed-reading-article' => "If you enjoyed reading '''[[$1|How to $1]]''', feel free to send some \"fan mail\" to the authors that contributed to the article. Countless editors volunteer their time to create this free how-to manual, so your positive feedback is greatly appreciated!

If you have any questions or constructive criticism, please don't post them here. Instead, [[$2|click here to leave a note on the article's discussion page]] on how to improve the article.",
	'thankauthors-error' => 'No target specified. In order to thank a group of authors, a page must be provided.',
	'thankauthors-no-urls' => 'Due to issues with spam, spam we cannot send your message because it contains an email address or domain name. Please press the back button on your browser and remove the following problematic text:<br /><br />

<b>$1</b><br /><br />

If you press the submit button again, your message will be sent. Thanks!',
	'thankauthors-thank-you-kudos' => "'''Thank you for your words of praise!'''
Your message has been posted on the '[[Thank the Authors of a wikiHow Page|kudos]]' page of each author that contributed to '''$2''', found at the bottom of each article.


{{SITENAME}} authors can check their [[Special:MyPage/Fanmail|kudos pages]] and can view your messages of thanks there.
Your appreciation and feedback truly matters, and will help to keep our authors inspired and motivated to continue writing great articles.


[[$1|Click here to return to $2]]",
);

/** Finnish (Suomi)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fi'] = array(
	'thankauthors' => 'Kiitä tekijöitä',
	'thankauthors-comment-said' => '[[User:$1|$2]] sanoi artikkelista [[$3]]:',
	'thankauthors-date' => '$1',
	'thankauthors-done' => 'Valmis.',
	'thankauthors-enjoyed-reading-article' => "Jos nautit '''[[$1|kuinka $1]] -artikkelin lukemisesta, voit vapaasti lähettää artikkelin tekemiseen osallistuneille kirjoittajille \"fanipostia\". Lukematon määrä vapaaehtoiskirjoittajia uhraa aikaansa tämän ilmaisen how-to -käsikirjan luomiseen, joten positiivinen palautteesi on suuresti arvostettua!

Jos sinulla on joitakin kysymyksiä tai rakentavaa kritiikkiä, ole hyvä äläkä jätä niitä tänne. Sen sijaan [[$2|napsauta tästä jättääksesi huomautus artikkelin keskustelusivulle]] kuinka artikkelia voisi parantaa.",
	'thankauthors-error' => 'Kohdetta ei määritelty. Kiittääksesi tekijäjoukkoa, sivun nimi täytyy antaa.',
	'thankauthors-no-urls' => 'Roskapostiongelmien tähden emme voi lähettää viestiäsi, sillä se sisältää sähköpostiosoitteen tai domainnimen. Paina selaimesi takaisin-painiketta ja poista seuraava ongelmallinen teksti:<br /><br />

<b>$1</b><br /><br />

Jos painat lähetä-painiketta uudestaan, viestisi lähetetään. Kiitos!',
	'thankauthors-thank-you-kudos' => "'''Kiitos kehuistasi!'''
Viestisi on lähetetty jokaisen artikkelin \"$2\" tekemiseen osallistuneen tekijän '[[Thank the Authors of a wikiHow Page|kiitossivulle]]'.


Artikkelin $2 tekijät voivat tarkistaa [[Special:MyPage/Fanmail|kiitossivunsa]] ja lukea kiitosviestisi sieltä.
Arvostuksellasi ja palautteellasi on todella merkitystä ja se inspiroi sekä motivoi kirjoittajiamme kirjoittamaan lisää hienoja artikkeleita.


[[$1|Napsauta tästä palataksesi sivulle $2]]",
);
<?php
/**
 * Internationalization file for the ArticleStats extension.
 *
 * @file
 * @ingroup Extensions
 */
$messages = array();

/** English
 * @author Travis Derouin
 */
$messages['en'] = array(
	'articlestats' => 'Article Stats',
	'articlestats-nosucharticle' => 'No article exists for this title.',
	'articlestats-notitle' => 'Error: No title specified.',
	'articlestats-notintopten' => '<a href="http://www.google.com/search?q=$1" target="new">Not in the top 10 results</a>',
	'articlestats-indexrank' => '<a href="http://www.google.com/search?q=$1" target="new">$2</a>', # do not duplicate or translate this message into other languages!
	'articlestats-notcheckedyet' => "<a href='http://www.google.com/search?q=$1' target='new'>Hasn't been checked yet</a>",
	'articlestats-lastchecked' => ' (Last checked: $1)',
	'articlestats-yes' => 'yes',
	'articlestats-no' => 'no',
	'articlestats-notenoughvotes' => 'Not enough votes to determine accuracy yet',
	'articlestats-rating' => '$1% of $2 votes',
	'articlestats-accuracy' => 'Accuracy',
	'articlestats-hasphotoinintro' => 'Has photo in introduction',
	'articlestats-stepbystepphotos' => 'Has step-by-step photos in over half of steps',
	'articlestats-numinboundlinks' => 'Number of inbound links',
	'articlestats-outboundlinks' => 'Outbound weaved links and related wikiHows',
	'articlestats-isfeatured' => 'Has been selected as a Featured Article?',
	'articlestats-pageviews' => 'Page views',
	'articlestats-pageviewsrank' => '(Rank: $1)',
	'articlestats-rankingoogle' => 'Rank in Google',
	'articlestats-sources' => 'Number of Sources',
	'articlestats-langlinks' => 'Number of Languages',
	'articlestats-footer' => '', # do not duplicate or translate this message into other languages!
);

/** Finnish (Suomi)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fi'] = array(
	'articlestats' => 'Artikkelien tilastot',
	'articlestats-nosucharticle' => 'Tälle otsikolle ei ole olemassa artikkelia.',
	'articlestats-notitle' => 'Virhe: otsikkoa ei annettu.',
	'articlestats-notintopten' => '<a href="http://www.google.com/search?q=$1" target="new">Ei 10 parhaan tuloksen joukossa</a>',
	'articlestats-notcheckedyet' => '<a href="http://www.google.com/search?q=$1" target="new">Ei ole tarkistettu vielä</a>',
	'articlestats-lastchecked' => ' (Viimeisin tarkistus: $1)',
	'articlestats-yes' => 'kyllä',
	'articlestats-no' => 'ei',
	'articlestats-notenoughvotes' => 'Ei tarpeeksi ääniä tarkkuuden määrittämiseen vielä',
	'articlestats-rating' => '$1% $2 äänestä',
	'articlestats-accuracy' => 'Tarkkuus',
	'articlestats-hasphotoinintro' => 'Esittelyosiossa on kuva',
	'articlestats-isfeatured' => 'On valittu suositelluksi artikkeliksi?',
	'articlestats-pageviews' => 'Sivunkatselukertoja',
	'articlestats-pageviewsrank' => '(Sijoitus: $1)',
	'articlestats-rankingoogle' => 'Sijoitus Googlessa',
	'articlestats-sources' => 'Lähteiden määrä',
	'articlestats-langlinks' => 'Kielten määrä',
);

/** French (Français)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fr'] = array(
	'articlestats-no' => 'non',
	'articlestats-yes' => 'oui',
);
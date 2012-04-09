<?php
/**
 * Internationalization file for Sitemap extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Travis Derouin
 */
$messages['en'] = array(
	'sitemap' => 'Site Map',
	'sitemap-article' => 'Project:Categories',
	'sitemap-excluded-categories' => 'Other
wikiHow', // these top-level categories are ignored when generating the list. Do not translate this message into other languages!
	'sitemap-not-defined' => "The site map hasn't been set up properly.
Please contact an administrator and ask them to set up the site map by editing the page [[{{int:sitemap-article}}]].",
);

/** Finnish (Suomi)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fi'] = array(
	'sitemap' => 'Sivustokartta',
	'sitemap-article' => 'Project:Luokat',
	'sitemap-not-defined' => 'Sivustokarttaa ei ole määritetty oikein.
Ota yhteyttä ylläpitäjään ja pyydä häntä määrittämään sivustokartta muokkaamalla sivua [[{{int:sitemap-article}}]].',
);

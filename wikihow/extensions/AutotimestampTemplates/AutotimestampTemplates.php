<?php
/**
 * An extension that allows users to rate articles.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'name' => 'AutotimestampTemplates',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Provides a way of automatically adding a timestamp to a template',
	'url' => 'http://www.mediawiki.org/wiki/Extension:AutotimestampTemplates'
);

$wgHooks['ArticleSave'][] = 'wfAutotimestampTemplates';

function wfAutotimestampTemplates( &$article, &$user, &$text, &$summary, $minor, $watch, $sectionanchor, &$flags, &$status ) {
	if ( strpos( $text, '{{' ) !== false ) {
		$t1 = preg_replace( '/\<nowiki\>.*<\/nowiki>/', '', $text );
		preg_match_all( '/{{[^}]*}}/im', $t1, $matches );

		$templateMessage = wfMessage( 'templates_needing_autotimestamps' )->inContentLanguage();
		if( $templateMessage->isBlank() ) {
			return true;
		}

		$templates = explode( ' ', strtolower( $templateMessage->text() ) );
		$templates = array_flip( $templates );

		foreach( $matches[0] as $m ) {
			$mm = preg_replace( '/\|[^}]*/', '', $m );
			$mm = preg_replace( '/[}{]/', '', $mm );
			if ( isset( $templates[strtolower( $mm )] ) ) {
				// @todo FIXME: make the following code more international,
				// it's likely that in a Finnish wiki the template date param
				// isn't called "date" but "päiväys" instead...also the date
				// format below is very American
				if ( strpos( $m, 'date=' ) === false ) {
					$m1 = str_replace( '}}', '|date=~~#}}', $m );
					$text = str_replace( $m, $m1, $text );
				} else {
					preg_match( '/date=(.*)}}/', $m, $mmatches );
					$mmm = $mmatches[1];
					if( $mmm !== date( 'Y-m-d', strtotime( $mmm ) ) ) {
						$text = str_replace( $mmm, date( 'Y-m-d', strtotime( $mmm ) ), $text );
					}
				}
			} else {
				//echo "wouldn't substitute on $m<br/>";
			}
		}
	}

	return true;
}
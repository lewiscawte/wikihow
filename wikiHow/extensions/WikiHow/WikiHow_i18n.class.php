<?php

class WikiHow_i18n {

	/**
	 * Generates and returns a string of JavaScript that can be embedded in
	 * your HTML to make MediaWiki messages available via the wfMsg() call.
	 * Requires that jQuery is already loaded.
	 *
	 * Example usage:
	 * <?php
	 *   $langKeys = array( 'done-button', 'welcome', 'some-random-message-key' );
	 *   $js = WikiHow_i18n::genJSMsgs( $langKeys );
	 *   echo $js;
	 * ?>
	 * <script> alert( 'my message: ' + wfMsg( 'welcome' ) ); </script>
	 */
	public static function genJSMsgs( $langKeys ) {
		$js = "
<script>
	if ( typeof WH == 'undefined' ) WH = {};
	if ( typeof WH.lang == 'undefined' ) WH.lang = {};
	jQuery.extend(WH.lang, {
";
		$len = count( $langKeys );
		foreach ( $langKeys as $i => $key ) {
			$msg = preg_replace( '@([\'\\\\])@', '\\\\$1', wfMsg( $key ) );
			$js .= "'$key': '$msg'" . ( $i == $len - 1 ? '' : ',' );
			if ( $i % 5 == 4 && $i < $len - 1 ) {
				$js .= "\n";
			}
		}
		$js .= "
	});
</script>
";

		return $js;
	}

	public static function genCSSMsgs( $langKeys ) {
		// TODO
	}
}
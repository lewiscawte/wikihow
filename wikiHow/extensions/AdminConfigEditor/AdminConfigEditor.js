// remove a URL from the list
$( 'body' ).on( 'click', 'a.remove_link', function() {
	var rmvid = $( this ).attr( 'id' );
	$( this ).hide();
	$.post(
		mw.config.get( 'wgArticlePath' ).replace( '$1', 'Special:AdminConfigEditor' ),
		{
			'action': 'remove-line',
			'config-key': $( '#config-key' ).val(),
			'id': rmvid
		},
		function( data ) {
			if ( data['error'] != '' ) {
				alert( mw.msg( 'adminconfigeditor-error', data['error'] ) );
			}
			$( '#url-list' ).html( data['result'] );
		},
		'json'
	);
	return false;
});

( function( $ ) {
	$( document ).ready( function() {
		$( '#config-save' ).click( function() {
			var dispStyle = $( '#display-style' ).val();
			$('#admin-result' ).html( mw.msg( 'adminconfigeditor-saving' ) );
			$.post(
				mw.config.get( 'wgArticlePath' ).replace( '$1', 'Special:AdminConfigEditor' ),
				{
					'action': 'save-config',
					'config-key': $( '#config-key' ).val(),
					'config-val': $( '#config-val' ).val(),
					'hidden-val': $( '#config_hidden_val' ).html(),
					'style': dispStyle
				},
				function( data ) {
					$( '#admin-result' ).html( data['result'] );
					if ( dispStyle == 'url' ) {
						$( '#url-list' ).html( data['val'] );
						$( '#config-val' ).val( '' );
					} else {
						$( '#config-val' ).val( data['val'] ).focus();
					}
				},
				'json'
			);
			return false;
		});

		$( '#config-val' ).keydown( function() {
			$( '#config-save' ).prop( 'disabled', '' );
		});

		$( '#config-key' ).change( function() {
			var configKey = $( '#config-key' ).val();
			var dispStyle = $( '#display-style' ).val();
			if ( configKey ) {
				$( '#admin-result' ).html( mw.msg( 'adminconfigeditor-loading' ) );
				$.post(
					mw.config.get( 'wgArticlePath' ).replace( '$1', 'Special:AdminConfigEditor' ),
					{
						'action': 'load-config',
						'config-key': configKey,
						'style': dispStyle
					},
					function( data ) {
						$( '#admin-result' ).html( '' );
						if ( dispStyle == 'url' ) {
							$( '#url-list' ).html( data['result'] );
							$( '#config-val' ).val( '' );
						} else {
							$( '#config-val' ).val( data['result'] ).focus();
						}
						$( '#config-save' ).prop( 'disabled', '' );
					},
					'json'
				);
			} else {
				$( '#config-val' ).val( '' );
			}

			return false;
		});
	});
})( jQuery );
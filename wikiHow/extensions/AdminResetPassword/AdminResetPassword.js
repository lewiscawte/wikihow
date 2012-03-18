(function( $ ) {
	$( document ).ready( function() {
		$( '#reset-go' )
			.attr( 'disabled', '' )
			.click( function() {
				$( '#reset-result' ).html( mw.msg( 'adminresetpassword-loading' ) );
				$.post(
					mw.config.get( 'wgScript' ),
					{
						'title': 'Special:AdminResetPassword',
						'username': $( '#reset-username' ).val()
					},
					function( data ) {
						$( '#reset-result' ).html( data['result'] );
						$( '#reset-username' ).focus();
					},
					'json'
				);
				return false;
			});
		$( '#reset-username' ).focus().keypress( function( evt ) {
			if ( evt.which == 13 ) { // if user hits 'enter' key
				$( '#reset-go' ).click();
				return false;
			}
		});
	});
})( jQuery );
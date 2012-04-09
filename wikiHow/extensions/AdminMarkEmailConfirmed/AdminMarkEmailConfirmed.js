(function( $ ) {
	$( document ).ready( function() {
		$( '#action-go' )
			.prop( 'disabled', false )
			.click( function() {
				$( '#action-result' ).html( mw.msg( 'adminmarkemailconfirmed-loading' ) );
				$.post(
					mw.config.get( 'wgScript' ),
					{
						'title': 'Special:AdminMarkEmailConfirmed',
						'username': $( '#action-username' ).val()
					},
					function( data ) {
						$( '#action-result' ).html( data['result'] );
						$( '#action-username' ).focus();
					},
					'json'
				);
				return false;
			});
		$( '#action-username' ).focus().keypress( function( evt ) {
			if ( evt.which == 13 ) { // if user hits 'enter' key
				$( '#action-go' ).click();
				return false;
			}
		});
	});
})( jQuery );
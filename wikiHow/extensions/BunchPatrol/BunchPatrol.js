function checkAll( selected ) {
	for ( var i = 0; i < document.checkform.elements.length; i++ ) {
		var e = document.checkform.elements[i];
		if ( e.type == 'checkbox' ) {
			e.checked = selected;
		}
	}
}

jQuery( document ).ready( function() {
	jQuery( '#check-all' ).click( function() {
		checkAll( true );
	} );
	jQuery( '#check-none' ).click( function() {
		checkAll( false );
	} );
} );
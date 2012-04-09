function checkForm() {
	if ( document.requesttopic.category.value == '' ) {
		alert( mw.msg( 'requesttopic-choose-category' ) );
		return false;
	}
	return true;
}

jQuery( document ).ready( function() {
	if ( jQuery( 'form#requesttopic' ).data( 'onsubmit' ) == 'true' ) {
		jQuery( 'form#requesttopic' ).submit( checkForm );
	}
} );
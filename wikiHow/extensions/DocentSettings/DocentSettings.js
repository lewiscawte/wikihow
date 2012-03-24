/**
 * JavaScript for DocentSettings extension
 */
function showhide( id ) {
	var box = document.getElementById( id );
	var style = box.getAttribute( 'style' );
	if( style == 'display: none;' ) {
		box.setAttribute( 'style', 'display: inline;' );
	} else {
		box.setAttribute( 'style', 'display: none;' );
	}
}

jQuery( document ).ready( function() {
	var showHideLink = jQuery( 'a.docentsettings-showhide-link' );
	showHideLink.click( function() {
		showhide( 'subcats_' + showHideLink.data( 'cat-id' ) );
	} );
} );
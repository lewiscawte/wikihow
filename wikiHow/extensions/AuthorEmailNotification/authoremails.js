var AuthorEmailNotification = {
	sendTest: function( item ) {
		var url = mw.config.get( 'wgArticlePath' ).replace( '$1',
			'Special:AuthorEmailNotification?target=' + item + '&action=testsend'
		);
		jQuery.get(
			url,
			function( data ) {
				alert( 'E-mail submitted. '+ url );
			}
		);

		return false;
	},

	aeNotification: function( obj, pageId ) {
		var watch = '';

		if ( obj.checked == true ) {
			watch = '1';
		} else {
			watch = '0';
		}

		if ( mw.config.get( 'wgUserName' ) == null ) {
			// don't know what we have
			//alert( 'invalid call parameters' );
			return false;
		}

		jQuery.get(
			mw.config.get( 'wgScript' ), {
				title: 'Special:AuthorEmailNotification',
				target: pageId,
				action: 'update',
				watch: watch
			},
			function( data ) {}
		);

		return false;
	},

	reorder: function( obj ) {
		if ( document.getElementById( 'icon_navi_down' ) ) {
			window.location = mw.config.get( 'wgArticlePath' ).replace(
				'$1', 'Special:AuthorEmailNotification?orderby=time_asc'
			);
		} else {
			window.location = mw.config.get( 'wgArticlePath' ).replace(
				'$1', 'Special:AuthorEmailNotification'
			);
		}
	},

	getCookie: function( c_name ) {
		var c_start, c_end;
		if ( document.cookie.length > 0 ) {
			c_start = document.cookie.indexOf( c_name + '=' );
			if ( c_start != -1 ) {
				c_start = c_start + c_name.length + 1;
				c_end = document.cookie.indexOf( ';', c_start );
				if ( c_end == -1 ) {
					c_end = document.cookie.length;
				}
				return unescape( document.cookie.substring( c_start, c_end ) );
			}
		}
		return '';
	},

	deleteCookie: function( name ) {
		if ( this.getCookie( name ) ) {
			document.cookie = name + '=' + ';expires=Thu, 01-Jan-1970 00:00:01 GMT';
		}
	}
};

jQuery( document ).ready( function() {
	// For testing only; these are disabled in the PHP body file, too
	/*
	jQuery( 'input[name="aen_rs_email"]' ).click( function() {
		AuthorEmailNotification.sendTest( 'rs' );
	} );
	jQuery( 'input[name="aen_mod_email"]' ).click( function() {
		AuthorEmailNotification.sendTest( 'mod' );
	} );
	jQuery( 'input[name="aen_featured_email"]' ).click( function() {
		AuthorEmailNotification.sendTest( 'featured' );
	} );
	jQuery( 'input[name="aen_viewership"]' ).click( function() {
		AuthorEmailNotification.sendTest( 'viewership' );
	} );
	*/
	jQuery( 'a#aen_date' ).click( function() {
		AuthorEmailNotification.reorder( this );
	} );
} );
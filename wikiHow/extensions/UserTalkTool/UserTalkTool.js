var UserTalkTool = {
	send: function() {
		jQuery( '#formdiv' ).hide();
		jQuery( '#resultdiv' ).text( mw.msg( 'usertalktool-sending' ) + '<br />' );

		var liArray = document.getElementById( 'ut_ol' ).childNodes;
		var i = 0;

		while( liArray[i] ) {
			if ( document.getElementById( liArray[i].id ) ) {
				if ( liArray[i].getAttribute( 'id' ).match( /^ut_li_/ ) ) {
					document.forms['utForm'].utuser.value = liArray[i].getAttribute( 'id' ).replace( 'ut_li_', '' );

					$.ajax({
						async: false,
						type: 'POST',
						url: mw.config.get( 'wgArticlePath' ).replace( '$1', 'Special:UserTalkTool' ),
						data: {
							utuser: jQuery( '#utuser' ).val(),
							utmessage: jQuery( '#utmessage' ).val()
						}
					}).done(
						function( data ) {
							document.getElementById( 'resultdiv' ).innerHTML += data + '<br />';
							// @todo FIXME
							if ( data.match( /Completed posting for - / ) ) {
								var u = data.replace( /Completed posting for - /, '' );
								document.getElementById( 'ut_li_' + u ).innerHTML +=
									'  <img src="' + mw.config.get( 'wgExtensionAssetsPath' ) +
									'/UserTalkTool/light_green_check.png" height="15" width="15" alt="" />';
							}
						}
					).fail(
						function( data ) {
							document.getElementById( 'resultdiv' ).innerHTML +=
								mw.msg( 'usertalktool-send-error', liArray[i].id ) + '<br />';
						}
					);

				}
			}
			i++;
		}

		return false;
	}
};

// Initialize when the DOM is ready
jQuery( document ).ready( function() {
	jQuery( '#postcommentbutton' ).click( function() {
		UserTalkTool.send();
		return false;
	} );
} );
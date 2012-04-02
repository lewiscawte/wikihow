/*
 * Edit Finder Class
 */
var editfinder_preview = false;
var g_bEdited = false;

var EF_WIDGET_LEADERBOARD_REFRESH = 10 * 60;

function EditFinder() {
	this.m_title = '';
	this.m_searchterms = '';
}

EditFinder.prototype.init = function() {
	editFinder.getArticle();

	// bind skip link
	jQuery( '#editfinder_skip a' ).click( function( e ) {
		e.preventDefault();
		if ( !jQuery( this ).hasClass( 'clickfail' ) ) {
			editFinder.disableTopButtons()
			editFinder.getArticle();
		}
	});

	var interests = g_eftype == 'Topic';
	/* category choosing */
	jQuery( '.editfinder_choose_cats' ).click( function( e ) {
		e.preventDefault();
		if ( interests ) {
			editFinder.getThoseInterests();
		} else {
			editFinder.getThoseCats();
		}
	});

	if ( interests ) {
		editFinder.getUserInterests();
	} else {
		editFinder.getUserCats();
	}
}

EditFinder.prototype.getThoseInterests = function() {
	jQuery( '#dialog-box' ).html( '' );
	jQuery( '#dialog-box' ).load( mw.util.wikiScript() + '?title=Special:CatSearchUI&embed=1', function() {
		jQuery( '#dialog-box' ).dialog({
			width: 325,
			modal: true,
			title: mw.msg( 'editfinder-interests' ),
			close: function( event, ui ) {
				// Only auto-show this dialog once. Use this cookie as a variable to control
				$.cookie( 'ef_int', '1', {expires: 365 * 10, path: '/'} );
				window.location.reload();
			}
		});
	});
}

EditFinder.prototype.getThoseCats = function() {
	jQuery( '#dialog-box' ).html( '' );
	jQuery( '#dialog-box' ).load(
		mw.util.wikiScript() + '?title=Special:SuggestCategories&type=' + g_eftype, function() {
		if ( g_eftype !== '' ) {
			jQuery( '#suggest_cats' ).attr( 'action',
				mw.util.wikiScript() + '?title=Special:SuggestCategories&type=' + g_eftype
			);
		}
		jQuery( '#dialog-box' ).dialog( 'option', 'position', 'center' );
		jQuery( '#dialog-box td' ).each( function() {
			var myInput = $( this ).find( 'input' );
			var position = $( this ).position();
			$( myInput ).css( 'top', position.top + 10 + 'px' );
			$( myInput ).css( 'left', position.left + 10 + 'px' );
			$( this ).click( function() {
				editFinder.choose_cat( $( this ).attr( 'id' ) );
			})
		})
		jQuery( '#check_all_cats' ).click( function() {
			var cats = jQuery( 'form input:checkbox' );
			var bChecked = jQuery( this ).prop( 'checked' );
			for ( i = 0; i < cats.length; i++ ) {
				var catid = cats[i].id.replace( 'check_', '' );
				editFinder.choose_cat( catid, bChecked );
			}
		});
	});
	jQuery( '#dialog-box' ).dialog({
		width: 826,
		modal: true,
		title: mw.msg( 'editfinder-categories' )
	});
}

EditFinder.prototype.choose_cat = function( key, bChoose ) {
	safekey = key.replace( '&', 'and' );
 	var e = $( '#' + safekey );

	// forcing it or based off the setting?
	if ( bChoose == null ) {
		bChoose = ( e.hasClass( 'not_chosen' ) ) ? true : false;
	}

 	if ( bChoose ) {
 		e.removeClass( 'not_chosen' );
 		e.addClass( 'chosen' );
 		document.suggest_cats.cats.value += ', ' + key;
		jQuery( '#check_' + safekey ).prop( 'checked', true );
 	} else {
 		e.removeClass( 'chosen' );
 		e.addClass( 'not_chosen' );
 		var reg = new RegExp( key, 'g' );
 		document.suggest_cats.cats.value = document.suggest_cats.cats.value.replace( reg, '' );
		jQuery( '#check_' + safekey ).prop( 'checked', false );
		jQuery( '#check_all_cats' ).prop( 'checked', false );
 	}
}

EditFinder.prototype.getArticle = function( the_id ) {
	var url = mw.config.get( 'wgArtcilePath' ).replace( '$1',
		'Special:EditFinder?fetchArticle=1'
	);
	var e = jQuery( '#editfinder_article_inner a' );
	if ( e.html() ) {
		url += '&skip=' + encodeURIComponent( e.html() );
	}
	var title = '';

	// add the edit type
	if ( g_eftype !== '' ) {
		url += '&edittype=' + g_eftype;
	}

	// add the article ID if we need a specific one
	if ( the_id ) {
		url += '&id=' + the_id;
	}

	jQuery( '#editfinder_article_inner' ).fadeOut( 'fast' );
	jQuery( '#editfinder_preview' ).fadeOut( 'fast', function() {
		jQuery( '#editfinder_spinner' ).fadeIn();

		jQuery.get( url, function( data ) {
			var json = jQuery.parseJSON( data );

			aid = json['aid'];
			title = json['title'];
			aURL = json['url'];

			editFinder.display( title, aURL, aid, 'editfinder_preview', 'intro' );
		});
	});

}

EditFinder.prototype.display = function( title, url, id, DIV, origin, currentStep ) {
	this.m_title = title;
	this.m_product = 'editfinder';
	this.m_textAreaID = 'summary';
	this.m_currentStep = 0;

	// set up post- dialog load callback
	var showBox = this.m_currentStep !== 0;
	var that = this;

	var urlget = mw.config.get( 'wgArticlePath' ).replace( '$1',
		'Special:EditFinder?show-article=1&aid=' + id
	);

	// add the edit type
	if ( g_eftype !== '' ) {
		urlget += '&edittype=' + g_eftype;
	}

	jQuery.get( urlget, function( data ) {
		jQuery( '#' + DIV ).html( data );

		// stop spinning and show stuff
		jQuery( '#editfinder_spinner' ).fadeOut( 'fast', function() {
			// fill in the blanks
			if ( title == undefined ) {
				editFinder.disableTopButtons();
				titlelink = '[' + mw.msg( 'editfinder-no-pages-found' ) + ']';
			} else {
				titlelink = '<a href="' + url + '">' + title + '</a>';
				editFinder.resetTopButtons();
				jQuery( '#editfinder_yes' ).unbind( 'click' );
				jQuery( '#editfinder_yes' ).click( function( e ) {
					e.preventDefault();
					if ( !jQuery( this ).hasClass( 'clickfail' ) ) {
						editFinder.edit( id );
					}
				});
			}
			jQuery( '#editfinder_article_inner' ).html( titlelink );

			jQuery( '#editfinder_article_inner' ).fadeIn();
			jQuery( '#' + DIV ).fadeIn();
		});
	});

}

EditFinder.prototype.edit = function( id, title ) {
	var url = mw.config.get( 'wgArticlePath' ).replace( '$1',
		'Special:EditFinder?edit-article=1&aid=' + id
	);

	jQuery.ajax({
		url: url,
		success: function( data ) {
			document.getElementById( 'editfinder_preview' ).innerHTML = data;
			jQuery( '#weave_button' ).css( 'display', 'none' );
			jQuery( '#easyimageupload_button' ).css( 'display', 'none' );
			jQuery( '#editfinder_preview' ).css( 'height', '950px' );
			restoreToolbarButtons();
			// Preview button
			jQuery( '#wpPreview' ).click( function() {
				editfinder_preview = true;
			});
			// Publish button
			jQuery( '#wpSave' ).click( function() {
				editfinder_preview = false;
			});
			// form submit
			jQuery( '#editform' ).submit( function( e ) {
				e.preventDefault();
				// just a preview?
				if ( editfinder_preview ) {
					editFinder.showPreview( id );
					jQuery( 'html, body' ).animate( {scrollTop: 0} );
				} else {
					// pop conf modal
					if ( g_eftype == 'Topic' ) {
						editFinder.closeConfirmation( true );
						return false;
					} else {
						editFinder.displayConfirmation( id );
					}
				}
			});

			// pre-fill summary
			jQuery( '#wpSummary' ).val(
				mw.msg( 'editfinder-edit-summary-js',
					mw.msg( 'editfinder-app-name' ),
					g_eftype.toUpperCase()
				)
			);

			// make Cancel do the right thing
			jQuery( '.editButtons a:last-child' ).unbind( 'click' );
			jQuery( '.editButtons a:last-child' ).click( function( e ) {
				e.preventDefault();
				// do we need to make the preview disappear?
				if ( editfinder_preview ) {
					jQuery( '#editfinder_preview_updated' ).fadeOut( 'fast' );
				}
				editFinder.cancelConfirmationModal( id );
			});

			// disable edit/skip choices
			editFinder.disableTopButtons();

			// throw cursor in the textarea
			jQuery( '#wpTextbox1' ).change( function() {
				g_bEdited = true;
			});

			// add the ID to the action URL
			jQuery( '#editform' ).attr( 'action',
				jQuery( '#editform' ).attr( 'action' ) + '&aid=' + id + '&type=' + g_eftype
			);
		}
	});
}

EditFinder.prototype.showPreview = function( id ) {
	var editform = jQuery( '#wpTextbox1' ).val();
	var url = mw.config.get( 'wgScriptPath' ) +
		'/index.php?action=submit&wpPreview=true&live=true';

	jQuery.ajax({
		url: url,
		type: 'POST',
		data: 'wpTextbox1=' + editform,
		success: function( data ) {
			var XMLObject = data;
			var previewElement = jQuery( data ).find( 'preview' ).first();

			/* Inject preview */
			var previewContainer = jQuery( '#editfinder_preview_updated' );
			if ( previewContainer && previewElement ) {
				previewContainer.html( previewElement.first().text() );
				previewContainer.slideDown( 'slow' );
			}
		}
	});
}

EditFinder.prototype.upTheStats = function() {
	var edittype = g_eftype.toLowerCase();
	var statboxes = '#iia_stats_today_repair_' + edittype +
		',#iia_stats_week_repair_' + edittype + ',#iia_stats_all_repair_' +
		edittype + ',#iia_stats_group';
	$( statboxes ).each( function( index, elem ) {
			$( this ).fadeOut( function() {
				var cur = parseInt( $( this ).html());
				$( this ).html( cur + 1 );
				$( this ).fadeIn();
			});
		}
	);
}

EditFinder.prototype.displayConfirmation = function( id ) {
	var url = mw.config.get( 'wgArticlePath' ).replace( '$1',
		'Special:EditFinder?confirmation=1&type=' + g_eftype + '&aid=' + id
	);

	jQuery( '#img-box' ).load( url, function() {
		jQuery( '#img-box' ).dialog({
			width: 450,
			modal: true,
			title: mw.msg( 'editfinder-dialog-title' ),
			closeOnEscape: true,
			position: 'center'
		});
	});
}

EditFinder.prototype.closeConfirmation = function( bRemoveTemplate ) {
	// removing the template?
	if ( bRemoveTemplate ) {
		var text = jQuery( '#wpTextbox1' ).val();
		var reg = new RegExp( '{{' + g_eftype + '[^\r\n]*}}', 'i' );
		jQuery( '#wpTextbox1' ).val( text.replace( reg, '' ) );
	}

	// close modal window
	jQuery( '#img-box' ).dialog( 'close' );
	editFinder.resetTopButtons();

	jQuery( '#editfinder_article_inner' ).fadeOut( 'fast' );
	jQuery( '#editfinder_preview' ).fadeOut( 'fast' );
	jQuery( '#editfinder_preview_updated' ).fadeOut( 'fast', function() {
		jQuery( '#editfinder_spinner' ).fadeIn();
		jQuery( 'html, body' ).animate( {scrollTop: 0} );
	});

	// submit
	jQuery.ajax({
		type: 'POST',
		url: jQuery( '#editform' ).attr( 'action' ),
		data: jQuery( '#editform' ).serialize()
	});

	editFinder.upTheStats();

	// next!
	editFinder.getArticle();
}

EditFinder.prototype.cancelConfirmationModal = function( id ) {
	var url = mw.config.get( 'wgArticlePath' ).replace( '$1',
		'Special:EditFinder?cancel-confirmation=1&aid=' + id
	);

	if ( g_bEdited ) {
		jQuery( '#img-box' ).load( url, function( data ) {
			// changes; get the box
			jQuery( '#img-box' ).dialog({
				width: 450,
				modal: true,
				title: mw.msg( 'editfinder-dialog-title' ),
				closeOnEscape: true,
				position: 'center'
			});

			// initialize buttons
			jQuery( '#efcc_yes' ).unbind( 'click' );
			jQuery( '#efcc_yes' ).click( function( e ) {
				e.preventDefault();
				jQuery( '#img-box' ).dialog( 'close' );
				jQuery( 'html, body' ).animate( {scrollTop: 0} );
				editFinder.resetTopButtons();
				editFinder.getArticle( id );

			});
			jQuery( '#efcc_no' ).click( function() {
				jQuery( '#img-box' ).dialog( 'close' );
			});
		});
	} else {
		// no change; go back
		jQuery( 'html, body' ).animate( {scrollTop: 0} );
		editFinder.resetTopButtons();
		editFinder.getArticle( id );
		return;
	}
}

EditFinder.prototype.disableTopButtons = function() {
	// disable edit/skip choices
	jQuery( '#editfinder_yes' ).addClass( 'clickfail' );
	jQuery( '#editfinder_skip a' ).addClass( 'clickfail' );
	jQuery( '#editfinder_skip_arrow' ).css( 'background-position', '-165px -13px' );
	return;
}

EditFinder.prototype.resetTopButtons = function() {
	// disable edit/skip choices
	jQuery( '#editfinder_yes' ).removeClass( 'clickfail' );
	jQuery( '#editfinder_skip a' ).removeClass( 'clickfail' );
	jQuery( '#editfinder_skip_arrow' ).css( 'background-position', '-165px 0' );
	return;
}

// grab an abbreviated list of a user's chosen interests
EditFinder.prototype.getUserInterests = function() {
	var url = mw.config.get( 'wgArticlePath' ).replace( '$1',
		'Special:CategoryInterests?a=get'
	);
	var cats = '';

	$.getJSON( url, function( data ) {
		cats = data.interests.join( ', ' );
		cats = cats.replace( /-/g, ' ' );
		if ( cats.length == 0 ) {
			if ( !$.cookie( 'ef_int' ) ) {
				editFinder.getThoseInterests();
			}

			cats = mw.msg( 'editfinder-no-interests-selected' );
		}

		if ( cats.length > 50 ) {
			cats = cats.substring( 0, 50 ) + '...';
		}

		jQuery( '#user_cats' ).html( cats );
	});
	return;
}

//grab an abbreviated list of a user's chosen categories
EditFinder.prototype.getUserCats = function() {
	var url = mw.config.get( 'wgArticlePath' ).replace( '$1',
		'Special:SuggestCategories?getusercats=1'
	);
	var cats = '';

	jQuery.ajax({
		url: url,
		success: function( data ) {
			cats = data;
			if ( cats.length > 50 ) {
				cats = cats.substring( 0, 50 ) + '...';
			}

			jQuery( '#user_cats' ).html( cats );
		}
	});
	return;
}

var editFinder = new EditFinder();

// kick it
editFinder.init();

// For Special:EditFinder, show-article view
jQuery( document ).ready( function() {
	jQuery( 'a.editfinder-more-categories' ).click( editFinder.getThoseCats );
	jQuery( 'a.editfinder-more-interests' ).click( editFinder.getThoseInterests );

	jQuery( '#editfinder-confirmbutton-yes' ).click( function() {
		editFinder.closeConfirmation( true );
		return false;
	} );
	jQuery( '#editfinder-confirmbutton-no' ).click( function() {
		editFinder.closeConfirmation( false );
		return false;
	} );
} );

// stat stuff
updateStandingsTable = function() {
	var url = mw.config.get( 'wgArticlePath' ).replace( '$1', 'Special:Standings/EditFinderStandingsGroup?type=' + g_eftype );
	jQuery.get(
		url,
		function( data ) {
			jQuery( '#iia_standings_table' ).html( data['html'] );
		},
		'json'
	);
	$( '#stup' ).html( EF_WIDGET_LEADERBOARD_REFRESH / 60 );
	window.setTimeout( updateStandingsTable, 1000 * EF_WIDGET_LEADERBOARD_REFRESH );
}

window.setTimeout( updateWidgetTimer, 60 * 1000 );
window.setTimeout( updateStandingsTable, 100 );

function updateWidgetTimer() {
	updateTimer( 'stup' );
	window.setTimeout( updateWidgetTimer, 60 * 1000 );
}
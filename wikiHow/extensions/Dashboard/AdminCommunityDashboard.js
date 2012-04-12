( function( $ ) {

	$( function() {
		pingRefreshScript();
		$( 'tr.out:even' ).addClass( 'odd' );

		$( '.save' ).prop( 'disabled', false ).click( function() {
			var checked = $( 'table input:checkbox' ).filter( ':checked' );
			if ( checked.length > 3 ) {
				alert( mw.msg( 'admincommunitydashboard-js-choose-3' ) );
				return false;
			}
			$( '.save' ).prop( 'disabled', true );
			$.post(
				mw.config.get( 'wgArticlePath' ).replace( '$1',
					'Special:AdminCommunityDashboard/save-settings'
				),
				{ settings: serializeSettings() },
				function( data ) {
					if ( data && !data['error'] ) {
						// reload page
						window.location.href = window.location.href;
					} else {
						$( '.save' ).prop( 'disabled', false );
						var err = data ? data['error'] : mw.msg( 'admincommunitydashboard-js-network-error' );
						alert( mw.msg( 'admincommunitydashboard-js-saving-error', err ) );
					}
				},
				'json'
			);
			return false;
		});

		$( '.refresh' ).click( function() {
			pingRefreshScript();
			return false;
		});

		$( '.restart' ).click( function() {
			$( '.status span' ).html( mw.msg( 'admincommunitydashboard-loading' ) );
			$( '.restart' ).replaceWith( '<i>' + mw.msg( 'admincommunitydashboard-js-restarting-script' ) + '</i>' );
			$.post(
				mw.config.get( 'wgArticlePath' ).replace( '$1',
					'Special:AdminCommunityDashboard/refresh-stats-restart'
				),
				function( data ) {
					if ( data && !data['error'] ) {
						$( '.status span' ).html( data['status'] );
					} else {
						var err = data ? data['error'] : mw.msg( 'admincommunitydashboard-js-network-error' );
						$( '.status span' ).html( mw.msg( 'admincommunitydashboard-js-error-occurred', err ) );
					}
				},
				'json'
			);
			return false;
		});
	});

	function pingRefreshScript() {
		$( '.status span' ).html( mw.msg( 'admincommunitydashboard-loading' ) );
		$.post(
			mw.config.get( 'wgArticlePath' ).replace( '$1',
				'Special:AdminCommunityDashboard/refresh-stats-status'
			),
			function( data ) {
				if ( data && !data['error'] ) {
					$( '.status span').html( data['status'] );
				} else {
					var err = data ? data['error'] : mw.msg( 'admincommunitydashboard-js-network-error' );
					$( '.status span' ).html( mw.msg( 'admincommunitydashboard-js-error-occurred', err ) );
				}
			},
			'json'
		);
	}

	function serializeSettings() {
		var rows = $( 'tr.out' );

		// get priorities
		var prio = [];
		$( rows ).each( function() {
			var id = $( '.wid-id', this ).text();
			var ispri = $( 'input:checkbox', this ).is( ':checked' );
			var order = $( 'td:first input:text', this ).val();
			order = castInt( order );
			prio.push({
				id: id,
				ispri: ispri,
				order: order
			});
		});

		// sort by priority (and order if both things are a priority)
		prio = prio.sort( function( a, b ) {
			if ( a['ispri'] && b['ispri'] ) {
				return a['order'] - b['order'];
			}
			if ( a['ispri'] ) {
				return -1;
			}
			if ( b['ispri'] ) {
				return 1;
			}
			return 0;
		});

		// construct priorities output array
		var priorities = [];
		$( prio ).each( function() {
			if ( this['ispri'] ) {
				priorities.push( this['id'] );
			}
		});

		// construct thresholds output array
		var thresholds = {};
		$( rows ).each( function() {
			var id = $( '.wid-id', this ).text();
			var low = $( '.lowmax', this ).val();
			var med = $( '.medmax', this ).val();
			var high = $( '.highmax', this ).val();
			thresholds[id] = {
				low: castInt( low ),
				med: castInt( med ),
				high: castInt( high )
			};
		});

		// construct baselines output array
		var baselines = {};
		$( rows ).each( function() {
			var id = $( '.wid-id', this ).text();
			var base = $( '.base:checked', this ).val();
			var custom = $( '.custbase', this ).val();
			if ( base == 'custom' ) {
				var baseline = castInt( custom );
			} else {
				var baseline = 0;
			}
			baselines[id] = baseline;
		});

		return $.toJSON({
			priorities: priorities,
			thresholds: thresholds,
			baselines: baselines
		});
	}

	// my version of parseInt
	function castInt( n ) {
		n = parseInt( n, 10 );
		if ( isNaN( n ) ) {
			n = 0;
		}
		return n;
	}

})( jQuery );
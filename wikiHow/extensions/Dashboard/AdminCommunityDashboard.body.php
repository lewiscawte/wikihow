<?php

class AdminCommunityDashboard extends UnlistedSpecialPage {

	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AdminCommunityDashboard' );
	}

	/**
	 * Save the global opts settings chosen by the admin.
	 */
	private function saveSettings( $settings ) {
		if (
			!isset( $settings['priorities'] ) ||
			!isset( $settings['thresholds'] ) ||
			!isset( $settings['baselines'] )
		)
		{
			return wfMessage( 'admincommunitydashboard-settings-fmt-error' )->text();
		}

		$opts = array(
			'cdo_priorities_json' => json_encode( $settings['priorities'] ),
			'cdo_thresholds_json' => json_encode( $settings['thresholds'] ),
			'cdo_baselines_json' => json_encode( $settings['baselines'] ),
		);
		$this->dashboardData->saveStaticGlobalOpts( $opts );

		$resp = $this->clientRequest( 'restart' );
		return $resp['error'];
	}

	/**
	 * Pass this request on to the right API that will answer the
	 * Dashboard Refresh Stats Script question.
	 */
	private function clientRequest( $req ) {
		$result = array( 'error' => '' );
		$url = 'http://' . WH_COMDASH_API_HOST . '/Special:AdminCommunityDashboard/refresh-stats-' . $req . '-server';
		$params = 'k=' . WH_COMDASH_SECRET_API_KEY;

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_USERPWD, WH_DEV_ACCESS_AUTH );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$ret = curl_exec( $ch );
		$curlErr = curl_error( $ch );

		if ( $curlErr ) {
			$result['error'] = wfMessage( 'admincommunitydashboard-curl-error', $curlErr )->text();
		} else {
			$result = json_decode( $ret, true );
		}

		return $result;
	}

	/**
	 * Answer a request about the Dashboard Refresh Stats Script.
	 */
	private function serverResponse( $req, $key ) {
		if ( $key != WH_COMDASH_SECRET_API_KEY ) {
			exit;
		}

		if ( $req == 'restart' ) {
			$cmd = "/usr/local/wikihow/suid-wrap /usr/local/wikihow/control-dashboard-refresh.$req.sh";
			exec( $cmd ); // no output since restart is daemonized
			$msg = wfMessage( 'admincommunitydashboard-reset-cmd-dispatched' )->parse();
			$result = array( 'error' => '', 'status' => $msg );
		} elseif ( $req == 'status' ) {
			$cmd = "/usr/local/wikihow/control-dashboard-refresh.$req.sh";
			exec( $cmd, $output );
			$result = array( 'error' => '', 'status' => join( "\n", $output ) );
		} else {
			exit;
		}

		return $result;
	}

	/**
	 * Show the special page
	 *
	 * @param $targed Mixed: name of the action to take (save-settings, etc.)
	 */
	public function execute( $target ) {
		global $wgRequest, $wgOut, $wgUser;

		if ( !$target ) {
			$target = $wgRequest->getVal( 'target', '' );
		}

		// don't do access control here if it's a *-server call -- these
		// calls have their own access control and $wgUser isn't set for them
		if ( !$wgRequest->wasPosted() || !preg_match( '@-server$@', $target ) ) {
			// access control -- privileged users only
			if ( !$wgUser->isAllowed( 'admincommunitydashboard' ) ) {
				$wgOut->permissionRequired( 'admincommunitydashboard' );
				return;
			}
		}

		$this->dashboardData = new DashboardData();

		if ( $wgRequest->wasPosted() ) {
			$wgOut->disable();

			$resp = array();
			if ( $target == 'save-settings' ) {
				$err = '';
				$settings = $wgRequest->getVal( 'settings', '[]' );
				$settings = json_decode( $settings, true );
				if ( $settings ) {
					$ret = $this->saveSettings( $settings );
					if ( $ret ) {
						$err = $ret;
					}
				} else {
					$err = wfMessage( 'admincommunitydashboard-got-bad-settings' )->text();
				}

				$resp = array( 'error' => $err );
			} elseif ( $target == 'refresh-stats-status' ) {
				$resp = $this->clientRequest( 'status' );
			} elseif ( $target == 'refresh-stats-restart' ) {
				$resp = $this->clientRequest( 'restart' );
			} elseif ( $target == 'refresh-stats-status-server' ) {
				$key = $wgRequest->getVal( 'k', '' );
				$resp = $this->serverResponse( 'status', $key );
			} elseif ( $target == 'refresh-stats-restart-server' ) {
				$key = $wgRequest->getVal( 'k', '' );
				$resp = $this->serverResponse( 'restart', $key );
			}

			print json_encode( $resp );
			return;
		}

		$this->showSettingsForm();
	}

	/**
	 * Display the admin settings form.
	 */
	private function showSettingsForm() {
		global $wgOut, $wgWidgetList;

		$opts = $this->dashboardData->loadStaticGlobalOpts();
		$titles = DashboardData::getTitles();

		$priorities = json_decode( $opts['cdo_priorities_json'], true );
		if ( !is_array( $priorities ) ) {
			$priorities = array();
		}
		$thresholds = json_decode( $opts['cdo_thresholds_json'], true );
		$baselines = json_decode( $opts['cdo_baselines_json'], true );

		$rwidgets = array_flip( $wgWidgetList );
		$order = $priorities;
		foreach ( $priorities as $widget ) {
			unset( $rwidgets[$widget] );
		}
		foreach ( $rwidgets as $widget => $i ) {
			$order[] = $widget;
		}

		$widgets = $this->dashboardData->getWidgets();
		$current = array();
		$dbr = wfGetDB( DB_SLAVE );
		foreach ( $widgets as $widget ) {
			$current[$widget->getName()] = $widget->getCount( $dbr );
		}

		$wgOut->setHTMLTitle( wfMessage( 'admincommunitydashboard-page-title' )->text() );

		// Add the JS stuff
		$wgOut->addModules( 'ext.adminCommunityDashboard' );

		// Load main UI template, set variables and output everything
		include( 'admin-community-dashboard.tmpl.php' );
		$tmpl = new AdminCommunityDashboardTemplate;
		$tmpl->set( 'widgets', $order );
		$tmpl->set( 'titles', $titles );
		$tmpl->set( 'priorities', array_flip( $priorities ) );
		$tmpl->set( 'thresholds', $order );
		$tmpl->set( 'baselines', $baselines );
		$tmpl->set( 'current', $current );

		$wgOut->addTemplate( $tmpl );
	}

}


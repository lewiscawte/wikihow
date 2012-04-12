<?php
/**
 * Maintenance script to generate a dashboard report containing various metrics.
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/Dashboard/maintenance and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../../maintenance' );

require_once( 'Maintenance.php' );

class UpdateDashboard extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Generate a dashboard report, containing various metrics (user (in)activity, edits, etc.)';
		$this->addArg( 'start', 'Start timestamp in UNIX format, if any' );
	}

	function selectField( $dbw, $sql ) {
		$res = $dbw->query( $sql, __METHOD__ );
		$row = $dbw->fetchObject( $res );
		if ( $row ) {
			foreach( $row as $k => $v ) {
				return $v;
			}
		}
		return null;
	}

	function getNumCreatedThenDeleted( $dbw, $cutoff, $cutoff2 = null ) {
		$options = array( 'GROUP BY' => 'ar_page_id' );

		if ( $cutoff2 ) {
			$options['HAVING'] = "M >= '$cutoff' AND M < '{$cutoff2}'";
		} else {
			$options['HAVING'] = "M >= '$cutoff'";
		}

		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			'archive',
			array( 'ar_title', 'ar_page_id', 'MIN(ar_timestamp) AS M' ),
			array( 'ar_namespace' => 0 ),
			__METHOD__,
			$options
		);

		return $dbw->numRows( $res );
	}

	function getUserToolLinks( $u ) {
		global $wgUser;
		if ( !$u ) {
			return '';
		}
		$ret = $wgUser->getSkin()->userToolLinks( $u->getID(), $u->getName() );
		$ret = str_replace( 'href="/', 'href="http://www.wikihow.com/', $ret );
		return $ret;
	}

	function getDateStr( $ts ) {
		return date( 'Y-m-d', wfTimestamp( TS_UNIX, $ts ) );
	}

	function getWikiBirthdays( $start, $w_cutoff, $dbw ) {
		$dbw = wfGetDB( DB_MASTER );
		$result = '';
		$thisyear = date( 'Y' ) - 1;
		$ago = 1;
		while ( $thisyear > 2004 ) {
			$ts1 = preg_replace( '@^20[0-9]{2}@', $thisyear, $w_cutoff );
			$ts2 = preg_replace( '@^20[0-9]{2}@', $thisyear, $start );
			$result .= 'Between ' . $this->getDateStr( $ts1 ) . ' and ' .
				$this->getDateStr( $ts2 ) . ' <ol>';
			$res = $dbw->select(
				'user',
				array( 'user_name', 'user_registration' ),
				array(
					"user_registration > '$ts1'",
					"user_registration <= '$ts2'",
					'user_editcount >= 200'
				),
				__METHOD__
			);
			foreach ( $res as $row ) {
				$x = User::newFromName( $row->user_name );
				$result .= '<li> ' . $this->getUserLink( $x ) .
					' (' . $this->getDateStr( $row->user_registration ) .
					') - ' . $this->getUserToolLinks( $x ) . '</li>';
			}
			$result .= '</ol>';
			$thisyear--;
		}
		return $result;
	}

	function newlyActiveUsers( $cutoff, $start, $dbw, $tdstyle, $editcount, $period ) {
		$result = '<ol>';

		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->select(
			array( 'revision', 'page' ),
			array(
				'rev_user', 'rev_user_text', 'MAX(rev_timestamp) AS M',
				'COUNT(*) AS C'
			),
			array( 'page_namespace' => NS_MAIN ),
			__METHOD__,
			array(
				'GROUP BY' => 'rev_user'
				'HAVING' => "C >= $editcount AND M >'{$cutoff}' AND M <'{$start}'"
			),
			array(
				'page' => array( 'LEFT JOIN', 'page_id = rev_page' )
			)
		);
		foreach ( $res as $row ) {
			$count = $this->selectField(
				$dbw,
				"SELECT COUNT(*) FROM revision LEFT JOIN page ON page_id = rev_page WHERE rev_user=" .
					$row->rev_user . " AND page_namespace = 0 AND rev_timestamp < '" .
					$cutoff . "'"
			);
			if ( $count < $editcount ) {
				$x = User::newFromName( $row->rev_user_text );
				$c = $this->nf( $row->C );
				$result .= '<li> ' . $this->getUserLink( $x ) .
					" - {$c} total edits, {$count} before this $period " .
					$this->getUserToolLinks( $x ) . "</li>\n";
			}
		}
		$result .= '</ol>';
		$result .= "</td><td {$tdstyle}>";
		return $result;
	}

	function articleStats( $dbw, $cutoff, $cutoff2 = null ) {
		global $wgBotIds;

		$notbot = '';
		// @todo FIXME: see the usage of this var to see what I mean
		if ( !empty( $wgBotIds ) ) {
			$notbot = ' NOT IN (' . implode( $wgBotIds ) . ')';
		}

		$dbw = wfGetDB( DB_MASTER );
		$result = '';
		$result .= "\n<ul><li>Articles that have been deleted : "  .
			$this->nf(
				$dbw->selectField(
					'logging',
					array( 'COUNT(*)' ),
					array(
						'log_type' => 'delete',
						"log_timestamp > '{$cutoff}'",
						$cutoff2 ? "log_timestamp < '{$cutoff2}'" : '1 = 1',
						'log_namespace' => 0
					),
					__METHOD__
				)
			);

		$d = $this->getNumCreatedThenDeleted( $dbw, $cutoff, $cutoff2 );
		$result .= "\n</li><li> Articles that have been created : "  .
			$this->nf(
				$dbw->selectField(
					'newarticlepatrol',
					array( 'COUNT(*)' ),
					array(
						"nap_timestamp > '{$cutoff}'",
						$cutoff2 ?  "nap_timestamp < '{$cutoff2}'" : '1 = 1',
					),
					__METHOD__
				) + $d
			);

		$result .= '- (' . $this->nf( $d ) . " deleted) \n</li><li>New articles that have been boosted: ".
			$this->nf(
				$dbw->selectField(
					array( 'recentchanges', 'newarticlepatrol' ),
					array( 'COUNT(*)' ),
					array(
						'rc_new = 1',
						'rc_namespace = '. NS_MAIN,
						"rc_timestamp > '{$cutoff}'",
						'nap_page = rc_cur_id',
						'nap_patrolled = 1'
					),
					__METHOD__
				)
			);

		$result .= "\n<li> Videos that have been embedded: "  .
			$this->nf(
				$dbw->selectField(
					array( 'revision', 'page' ),
					array( 'COUNT(*)' ),
					array(
						"rev_timestamp > '{$cutoff}'",
						$cutoff2 ? "rev_timestamp < '{$cutoff2}'" : '1 = 1',
						'page_id = rev_page',
						'page_namespace' => NS_VIDEO
					),
					__METHOD__
				)
			);

		$result .= "\n</li><li> Photos uploaded: "  .
			$this->nf(
				$dbw->selectField(
					'logging',
					array( 'COUNT(*)' ),
					array(
						'log_type' => 'upload',
						"log_timestamp > '{$cutoff}'",
						$cutoff2 ? "log_timestamp < '{$cutoff2}'" : '1 = 1',
					),
					__METHOD__
				)
			);

		$result .= "\n</li><li>Main namespace edits : "  .
			$this->nf(
				$dbw->selectField(
					array( 'revision', 'page' ),
					array( 'count(*)' ),
					array(
						"rev_timestamp > '{$cutoff}'",
						$cutoff2 ? "rev_timestamp < '{$cutoff2}'" : '1 = 1',
						'page_id = rev_page',
						'page_namespace' => NS_MAIN,
						'rev_user ' . $notbot,
					)
				)
			);

		$result .= "\n</li><li> User talk namespace edits : "  .
			$this->nf(
				$dbw->selectField(
					array( 'revision', 'page' ),
					array( 'COUNT(*)' ),
					array(
						"rev_timestamp > '{$cutoff}'",
						$cutoff2 ? "rev_timestamp < '{$cutoff2}'" : '1 = 1',
						'page_id = rev_page',
						'page_namespace' => NS_USER_TALK,
						'rev_user ' . $notbot,
					),
					__METHOD__
				)
			);

		$result .= "\n</li><li> Reverted main namespace edits : "  .
			$this->nf(
				$dbw->selectField(
					array( 'revision', 'page' ),
					array( 'COUNT(*)' ),
					array(
						"rev_timestamp > '{$cutoff}'",
						$cutoff2 ? "rev_timestamp < '{$cutoff2}'" : '1 = 1',
						'page_id = rev_page',
						'page_namespace' => NS_MAIN,
						"rev_comment LIKE 'Reverted%'" // @todo FIXME
					),
					__METHOD__
				)
			);

		$result .= "\n</li><li> User registrations : " .
			$this->nf(
				$dbw->selectField(
					array( 'user' ),
					array( 'COUNT(*)' ),
					array(
						"user_registration> '{$cutoff}'",
						$cutoff2 ? "user_registration < '{$cutoff2}'" : '1 = 1',
						"user_name NOT LIKE 'Anonymous%'"
					),
					__METHOD__
				)
			);

		$result .= '</ul>';
		return $result;
	}

	function getActivityChange( $dbw, $c1, $c2, $decline ) {
		$dbw = wfGetDB( DB_MASTER );

		// how many edits in previous period?
		$res = $dbw->select(
			array( 'revision', 'page' ),
			array( 'rev_user', 'rev_user_text', 'COUNT(*) AS C' ),
			array(
				"rev_timestamp < '{$c1}'",
				"rev_timestamp > '{$c2}'",
				'page_namespace' => NS_MAIN
			),
			__METHOD__,
			array(
				'GROUP BY' => 'rev_user',
				'HAVING' => 'C >= 100'
			),
			array( 'page' => array( 'LEFT JOIN', 'page_id = rev_page' ) )
		);
		foreach ( $res as $row ) {
			// how many edits in current period?
			$add = false;
			$old = $row->C;
			$new = $this->selectField(
				$dbw,
				"SELECT COUNT(*) FROM revision LEFT JOIN page ON page_id = rev_page WHERE rev_user=" .
					$row->rev_user . " AND rev_timestamp > '" . $c1 . "' AND page_namespace = 0;"
			);
			if ( $decline ) {
				if ( $new == 0 || $new / $old <= 0.5 ) {
					$add = true;
				}
			} else {
				if ( $new > 0 && $new / $old >= 1.5 ) {
					$add = true;
				}
			}
			if ( $add ) {
				$x = User::newFromName( $row->rev_user_text );
				$new = $this->nf( $new );
				$old = $this->nf( $old );
				$result .= '<li> ' . $this->getUserLink( $x ) .
					" - {$old} &rarr; {$new} " . $this->getUserToolLinks( $x ) .
					"</li>\n";
/*
				if ( $decline ) {
					$this->output( "Decling {$x->getName()}, old $old new $new\n" );
				} else {
					$this->output( "Increase {$x->getName()}, old $old new $new\n" );
				}
*/
			}
		}
		return $result;
	}

	function debugMsg( $msg ) {
		$this->output( "\n\n<!--" . date( 'r' ) . ': ' . $msg . "--->\n" );
	}

	function getTopCreators( $dbw, $cutoff, $start ) {
		global $wgBotIds;

		$dbw = wfGetDB( DB_MASTER );
		$result = '<ol>';
		$sql = "SELECT fe_user, fe_user_text, COUNT(*) AS C FROM firstedit WHERE fe_timestamp > '{$cutoff}' AND fe_timestamp < '{$start}' "
				. " AND fe_user NOT IN (0, " . implode( ', ', $wgBotIds ) . ") GROUP BY fe_user ORDER BY C DESC LIMIT 20;";
		$res = $dbw->query($sql);
		wfDebug("Dashboard top creators: $sql\n");
		foreach ( $res as $row ) {
			$x = User::newFromName( $row->fe_user_text );
			$c = $this->nf( $row->C );
			if ( !$x ) {
				$result .= "<li>{$row->user_text} - {$c} new articles created</li>\n";
			} else {
				$result .= '<li> ' . $this->getUserLink( $x ) .
					" - {$c} new articles created" .
					$this->getUserToolLinks( $x ) . "</li>\n";
			}
		}

		$result .= '</ol>';
		return $result;
	}

	// old function
	function getTopCreators2( $dbw, $cutoff, $start ) {
		$dbw = wfGetDB( DB_MASTER );
		$result = '<ol>';
		$sql = "SELECT nap_page FROM newarticlepatrol LEFT JOIN page ON nap_page = page_id WHERE page_namespace = 0
				AND nap_timestamp > '{$cutoff}' AND nap_timestamp < '{$start}'; ";
		wfDebug("Dashboard top creators: $sql\n");

		$this->debugMsg( "getting nap $nap " );
		$res = $dbw->query($sql);
		$pages = array();
		$revisions = array();
		foreach ( $res as $row ) {
			$pages[] = $row->nap_page;
		}
		$this->debugMsg( 'getting min revisions on pages ' . sizeof( $pages ) . ' pages ' );
		$count = 0;
		foreach ( $pages as $page_id ) {
			$revisions[$page_id] = $this->selectField(
				'revision',
				array( 'MIN(rev_id)' ),
				array( 'rev_page' => $page_id )
			);
			$count++;
			if ( $count % 100 == 0 ) {
				$this->debugMsg( "done $count" );
			}
		}
		$users = array();
		$this->debugMsg( 'getting users on newly created pages ' . sizeof( $revisions ) . ' revisions ' );
		$count = 0;
		foreach ( $revisions as $page_id => $rev_id ) {
			if ( empty( $rev_id ) ) {
				#$this->output( "<!---uh oh: {$page_id} has no min rev!-->" );
				continue;
			}
			$u = $this->selectField(
				$dbw,
				"SELECT rev_user_text FROM revision WHERE rev_id={$rev_id}"
			);
			if( !isset( $users[$u] ) ) {
				$users[$u] = 1;
			} else {
				$users[$u]++;
			}
			$count++;
			if ( $count % 100 == 0 ) {
				$this->debugMsg( "done $count" );
			}
		}
		$this->debugMsg( 'sorting ' . sizeof( $users ) . ' users' );
		asort( $users, SORT_NUMERIC);
		$users = array_reverse( $users );
		array_splice( $users, 20 );
		$yy = 0;
		$this->debugMsg( 'outputting all of this now ' . sizeof( $users ) . ' users' );
		foreach ( $users as $u => $c) {
			$x = User::newFromName( $u );
			$c = $this->nf( $c );
			if ( !$x ) {
				$result .= "<li>{$u} - {$c} new articles created</li>\n";
			} else {
				$result .= '<li> ' . $this->getUserLink( $x ) .
					" - {$c} new articles created" .
					$this->getUserToolLinks( $x ) . "</li>\n";
			}
			$yy++;
			if ( $yy == 20 ) {
				break;
			}
		}
		$result .= '</ol>';
		return $result;
	}

	function nf( $c ) {
		return number_format( $c, 0, '.', ',' );
	}

	function getUserLink( $x ) {
		if ( !$x ) {
			return 'no user page';
		}
		return "<a href='http://www.wikihow.com/{$x->getUserPage()->getPrefixedUrl()}'>{$x->getName()}</a>";
	}

	public function execute() {
		define( 'REPORTS_HOST', 'spare1.wikihow.com' );
		define( 'REPORTS_DIR', '/x/dashboard' );

		$tdstyle = ' style="vertical-align: top; padding-bottom: 20px; border: 1px solid #eee; background: #CFDDDD;" ';
		$wgTitle = Title::newMainPage();

		/**
		 * Main execution area
		 */
		$dbw = wfGetDB( DB_MASTER );

		// get the cutoff dates which we are going to run the report for
		$start = time();
		$arg = $this->getArg( 0 );
		if ( $arg ) {
			$start = wfTimestamp( TS_UNIX, $arg . '000000' );
		}
		$w_cutoff 	= wfTimestamp( TS_MW, $start - 60 * 60 * 24 * 7 ); // 7 days
		$ww_cutoff 	= wfTimestamp( TS_MW, $start - 60 * 60 * 24 * 14 ); // 14 days
		$wf_cutoff	= wfTimestamp( TS_MW, $start + 60 * 60 * 24 * 7 ); // 7 days forward
		$m_cutoff 	= wfTimestamp( TS_MW, $start - 60 * 60 * 24 * 30 ); // 30 days
		$mm_cutoff 	= wfTimestamp( TS_MW, $start - 60 * 60 * 24 * 60 ); // 60 days
		$start 		= wfTimestamp( TS_MW, $start ); // convert it over to a TS_MW
		$now  		= wfTimestampNow();

		// a list of bots, because we want to exclude them from the report
		$wgBotIds = array();
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'user_groups',
			array( 'ug_user' ),
			array( 'ug_group' => 'Bot' ),
			__METHOD__
		);
		foreach ( $res as $row ) {
			$wgBotIds[] = $row->ug_user;
		}

		$d = $this->getNumCreatedThenDeleted( $dbw, $w_cutoff, $start );
		$this->output( '<body id="body" style="font-family: Arial;">' );

		$this->output( "\n\n<!-- " . date( 'r' ) . " starting ... --->\n" );

		// create a temporary table with just the main namespace edits from logged in users
		// it'll make it quicker to do lookups than to do a lookup on the whole revision table
		if ( $wgServer != "http://wiki112.wikidiy.com" && $table == "rev_tmp" ) {
			$sql = "
			create temporary table rev_tmp (
				rev_id int(8) unsigned NOT NULL,
				`rev_page` int(8) unsigned NOT NULL default '0',
				`rev_user` int(5) unsigned NOT NULL default '0',
				`rev_user_text` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
				`rev_timestamp` varchar(14) character set latin1 collate latin1_bin NOT NULL default '',
				KEY `rev_id` (`rev_id`),
				KEY `rev_page` (`rev_page`),
				KEY `rev_timestamp` (`rev_timestamp`),
				KEY `user_timestamp` (`rev_user`,`rev_timestamp`),
				KEY `usertext_timestamp` (`rev_user_text`,`rev_timestamp`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			$this->output( $sql );
			#$dbw->query($sql);

			$sql = "insert into rev_tmp
				select rev_id, rev_page, rev_user, rev_user_text, rev_timestamp from
				revision, page where page_id=rev_page and page_namespace=0 and rev_user > 0
				and rev_user NOT IN (" . implode(",", $wgBotIds) . "); ";
			#$dbw->query($sql);
			$this->output( $sql ); exit;
		}

		#$rowCount = $dbw->selectField(array('rev_tmp'), array('count(*)'), array());
		# $this->output( "rev_tmp has $rowCount rows.\n" );

		$this->output( "\n\n<!-- " . date( 'r' ) . " user stats ... --->\n" );
		// get the group of "very active users", they are the ones with 500+ edits
		$users = array();
		wfDebug( "Dashboard getting users with > 500 edits\n" );
		$res = $dbw->select(
			array( 'revision', 'page' ),
			array( 'rev_user', 'COUNT(*) AS C' ),
			array( 'page_namespace' => NS_MAIN ),
			__METHOD__,
			array(
				'GROUP BY' => 'rev_user',
				'HAVING' => 'C > 500',
				'ORDER BY' => 'C DESC'
			),
			array( 'page' => array( 'LEFT JOIN', 'page_id = rev_page' ) )
		);
		foreach ( $res as $row ) {
			$users[$row->rev_user] = $row->C;
		}

		$this->output( '<a href="http://' . REPORTS_HOST . REPORTS_DIR . '/' . date( 'Ymd' ) . '.html">Full report</a>' );
		$this->output( "<h1>User stats</h1>\n" );
		$this->output( "<table class='dashboard' style='font-family: Arial; margin-left:auto; margin-right:auto; width: 90%;'><tr><td {$tdstyle} colspan='2'>" );
		$this->output( '<h2>Very active users who have 10+ edits in the past week</h2><ol>' );
		foreach ( $users as $u => $c ) {
			$count = $this->selectField(
				$dbw,
				"SELECT COUNT(*) FROM revision LEFT JOIN page ON page_id = rev_page WHERE rev_timestamp > '" .
					$w_cutoff . "' AND rev_user=" . $u
			);
			if ( $count >= 10 ) {
				$x = User::newFromID( $u );
				$count = $this->nf( $count );
				$this->output(
					'<li>' . $this->getUserLink( $x ) . ' - ' . $this->nf( $c ) .
					" - {$count} " . $this->getUserToolLinks( $x ) . "</li>\n"
				);
			}
		}
		$this->output( "</ol></td></tr><tr><td {$tdstyle}>" );

		$this->output( '<h2>Users who have 100+ edits in the past month</h2><ol>' );
		$sql = "SELECT rev_user_text, COUNT(*) AS C FROM revision
			LEFT JOIN page ON page_id = rev_page
			WHERE rev_timestamp > '{$m_cutoff} ' AND rev_timestamp < '{$start}' AND page_namespace = 0
			GROUP BY rev_user_text HAVING C >= 100 ORDER BY C DESC;";
		wfDebug( "Dashboard 100+ edits in past month: $sql\n" );
		$res = $dbw->query( $sql, __METHOD__ );
		foreach ( $res as $row ) {
			$x = User::newFromName( $row->rev_user_text );
			$this->output(
				'<li> ' . $this->getUserLink( $x ) . ' - ' .
					$this->nf( $row->C ) . $this->getUserToolLinks( $x ) .
				"</li>\n"
			);
		}
		$this->output( "</ol></td><td {$tdstyle}>" );

		$this->output( '<h2>Users who have 25+ edits in the past week</h2><ol>' );
		$sql = "SELECT rev_user_text, COUNT(*) AS C FROM revision
			LEFT JOIN page ON page_id = rev_page
			WHERE rev_timestamp > '{$w_cutoff}' AND rev_timestamp < '{$start}' AND page_namespace = 0
			GROUP BY rev_user_text HAVING C >= 25 ORDER BY C DESC;";
		wfDebug( "Dashboard 25+ edits in past week: $sql\n" );
		$res = $dbw->query( $sql, __METHOD__ );
		foreach ( $res as $row ) {
			$x = User::newFromName( $row->rev_user_text );
			$this->output(
				'<li> ' . $this->getUserLink( $x ) . ' - ' .
				$this->nf( $row->C ) . $this->getUserToolLinks( $x ) . "</li>\n"
			);
		}
		$this->output( "</ol></td></tr><tr><td {$tdstyle}>" );

		$this->output( '<h2>Top 100 editors for the past month</h2><ol>' );
		$sql = "select rev_user_text, count(*) as C from revision LEFT JOIN page ON page_id = rev_page where rev_timestamp > '$m_cutoff' and rev_timestamp < '$start' AND page_namespace = 0 group by rev_user_text order by C desc LIMIT 100;";
		wfDebug( "Dashboard top 100 editors in past month: $sql\n" );
		$res = $dbw->query( $sql, __METHOD__ );
		foreach ( $res as $row ) {
			$x = User::newFromName( $row->rev_user_text );
			$this->output(
				'<li> ' . $this->getUserLink( $x ) . ' - ' .
				$this->nf( $row->C ) . $this->getUserToolLinks( $x ). "</li>\n"
			);
		}
		$this->output( "</ol></td><td {$tdstyle}>" );

		$this->output( '<h2>Top 50 editors for the past week</h2><ol>' );
		$sql = "select rev_user_text, count(*) as C from revision LEFT JOIN page ON page_id = rev_page where rev_timestamp > '{$w_cutoff}' AND page_namespace = 0 group by rev_user_text order by C desc LIMIT 50;";
		wfDebug("Dashboard top 50 editors in past week: $sql\n");
		$res = $dbw->query($sql);
		foreach ( $res as $row ) {
			$x = User::newFromName( $row->rev_user_text );
			$this->output(
				'<li> ' . $this->getUserLink( $x ) . ' - ' .
				$this->nf( $row->C ) . $this->getUserToolLinks( $x ) . "</li>\n"
			);
		}
		$this->output( "</ol></td><tr></tr><td {$tdstyle}>" );

		/**
		 * Changes in activity level
		 */
		// who had 100+ edits 2 months ago?
		$this->output( "\n\n<!-- " . date( 'r' ) . " changes in activity levels --->\n" );
		$this->output( "<h2> Editors with a declining activity level this month</h2>" );
		wfDebug( 'Dashboard decling activity this month: ' );
		$this->output( $this->getActivityChange( $dbw, $m_cutoff, $mm_cutoff, true ) );
		$this->output( "</td><td {$tdstyle}>" );

		$this->output( '<h2>Editors with a declining activity level this week</h2>' );
		wfDebug( 'Dashboard decling activity this week: ' );
		$this->output( $this->getActivityChange( $dbw, $w_cutoff, $ww_cutoff, true ) );
		$this->output( "</td><tr></tr><td {$tdstyle}>" );

		$this->output( "<h2>Editors with a increasing activity level this month</h2>50% more activity than last month\n" );
		wfDebug( 'Dashboard decling activity this increasing activity level this month: ' );
		$this->output( $this->getActivityChange( $dbw, $m_cutoff, $mm_cutoff, false ) );
		$this->output( "</td><td {$tdstyle}>" );

		$this->output( "<h2>Editors with a increasing activity level this week</h2>50% more activity than last week\n" );
		wfDebug( 'Dashboard decling activity this increasing activity level this week: ' );
		$this->output( $this->getActivityChange( $dbw, $w_cutoff, $ww_cutoff, false ) );
		$this->output( "</td><tr></tr><td {$tdstyle}>" );

		/**
		 * Users becoming active
		 */
		$this->output( "\n\n<!-- " . date( 'r' ) . " users becoming active --->\n" );
		$this->output( "<h2>New editors who became active this month </h2>Users who made their 25th edit this month\n" );
		$this->output( $this->newlyActiveUsers( $m_cutoff, $start, $dbw, $tdstyle, 25, 'month' ) );

		$this->output( "<h2>New editors who became active this week </h2>Users who made their 10th edit this week\n" );
		$this->output( $this->newlyActiveUsers( $w_cutoff, $start, $dbw, $tdstyle, 10, 'week' ) );

		$this->output( "</tr><tr><td {$tdstyle}>" );

		/**
		 * Top article creators
		 */
		$this->output( "\n\n<!-- " . date( 'r' ) . " top article creators --->\n" );
		$this->output( '<h2>Top 20 authors who started articles this month</h2>' );
		wfDebug( 'Dashboard top 20 authors who started articles this month ' );
		$this->output( $this->getTopCreators( $dbw, $m_cutoff, $start ) );
		$this->output( "</td><td {$tdstyle}>" );

		$this->output( '<h2>Top 20 authors who started articles this week</h2>' );
		wfDebug( 'Dashboard top 20 authors who started articles this week ' );
		$this->output( $this->getTopCreators( $dbw, $w_cutoff, $start ) );
		$this->output( '</td></tr></table>' );

		$this->output( "\n\n<!-- " . date( 'r' ) . " article stats --->\n" );
		$this->output( '<h2>Article stats</h2>' );

		// get number of users who had 5+ edits this week
		$sql = "select rev_user_text, count(*) as C from revision LEFT JOIN page ON page_id = rev_page where rev_timestamp > '$w_cutoff' and rev_timestamp < '{$start}' AND page_namespace = 0 group by rev_user_text having C >= 5 order by C desc;";
		wfDebug( "Dashboard active 5 edits or more: $sql\n " );
		$res = $dbw->query( $sql, __METHOD__ );
		$active_five_edits_more = $dbw->numRows( $res );

		$sql = "SELECT COUNT(DISTINCT(page_id)) FROM templatelinks LEFT JOIN page ON tl_from = page_id AND tl_title IN ('Stub', 'Copyedit', 'Merge', 'Format', 'Cleanup', 'Accuracy');";
		wfDebug( "Dashboard articles in problem categories $sql \n");
		$this->output(
			'<ul><li>Articles in problem categories (as of ' .
			$this->getDateStr( $now ) . ') ' .
			$this->nf( $this->selectField( $dbw, $sql ) ) . '</li></ul>'
		);
		$this->output(
			'<ul><li>wikiHow contributors who participated 5 or more times this week: ' .
			$this->nf( $active_five_edits_more ) . '</li></ul>'
		);
		$sql = "SELECT COUNT(*) FROM templatelinks WHERE tl_title='Rising-star-discussion-msg-2';";
		wfDebug( "Dashboard number of rising starts $sql \n" );
		$this->output(
			'<ul><li>Number of Rising Stars: (as of ' .
			$this->getDateStr( $now ) . ') ' .
			$this->nf( $this->selectField( $dbw, $sql ) ) . '</li></ul>'
		);

		$this->output( "<table class='dashboard' style='font-family: Arial; margin-left:auto; margin-right:auto; width: 90%;'><tr><td {$tdstyle}>" );
		$this->output( '<h3>Article stats for the past week</h3>' . $this->articleStats( $dbw, $w_cutoff, $start ) . "\n" );
		$this->output( "</td><td {$tdstyle}>" );
		$this->output( '<h3>Article stats for the past month</h3>' . $this->articleStats( $dbw, $m_cutoff, $start ) . "\n" );
		$this->output( '</td></tr></table>' );

		/**
		 * Wiki birthdays
		 */
		$this->output( "\n\n<!-- " . date( 'r' ) . " wiki birthdays --->\n" );
		$this->output( "<table class='dashboard' style='font-family: Arial; margin-left:auto; margin-right:auto; width: 90%;'><tr><td {$tdstyle}>" );
		$this->output( '<h3>Wiki Birthdays</h3>For users with 200+ edits<br /><br />' );
		// Switch order of inputs since now we're looking forward
		$this->output( $this->getWikiBirthdays( $wf_cutoff, $start, $dbw ) );
		$this->output( "</td><td {$tdstyle}>" );
		$this->output( '</td></tr></table>' );

		$this->output( '</body>' );
	}
}

$maintClass = 'UpdateDashboard';
require_once( RUN_MAINTENANCE_IF_MAIN );
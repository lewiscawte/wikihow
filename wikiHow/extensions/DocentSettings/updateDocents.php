<?php
/**
 * Maintenance script to warn inactive docents and eventually remove inactive
 * ones.
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/DocentSettings and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );

require_once( 'Maintenance.php' );

class UpdateDocents extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Send a warning e-mail to inactive docents and eventually drop them as docents.';
		$this->addOption( 'debug', 'Display debug output and effectively perform a dry run instead of doing anything' );
		$this->addOption( 'email', 'The e-mail address to send out the e-mails from', true /* required? */, true /* withArg? */, 'e' );
	}

	function dropDocent( $user ) {
		$projectPage = Title::newFromText(
			wfMessage( 'docentsettings-help-page' )->inContentLanguage()->text()
		);

		if ( !$this->getOption( 'debug' ) ) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->delete(
				'docentcategories',
				array( 'dc_user' => $user->getId() ),
				__METHOD__
			);
		}

		$params = array( $user->getID() );
		$log = new LogPage( 'doc', false );
		$log->addEntry(
			'doc',
			$projectPage,
			wfMsg( 'docentsettings-log-summary', $user->getName() ),
			$params
		);
	}

	function getQuietDocents( $cutoff ) {
		$minedits = 5;
		$dbr = wfGetDB( DB_SLAVE );
		$removing = array();

		# Get all of the docents
		$users = array();
		$res = $dbr->select(
			'docentcategories',
			'DISTINCT(dc_user) AS dc_user',
			array(),
			__METHOD__
		);
		foreach ( $res as $row ) {
			$users[] = $row->dc_user;
		}

		foreach ( $users as $u ) {
			$count = $dbr->selectField(
				array( 'revision', 'page' ),
				array( 'COUNT(*)' ),
				array(
					'page_id = rev_page',
					'rev_user' => $u,
					"rev_timestamp > '{$cutoff}'"
				),
				__METHOD__
			);
			$newuser = $dbr->selectField(
				'user',
				array( 'COUNT(*)' ),
				array( 'user_id' => $u, "user_registration > '{$cutoff}'" ),
				__METHOD__
			);
			if ( $newuser == 1 ) {
				if ( $this->getOption( 'debug' ) ) {
					$this->output( "$u ....is a new user, skipping\n" );
				}
				continue;
			}
			if ( $count < $minedits ) {
				// remove them
				if ( $this->getOption( 'debug' ) ) {
					$this->output( "$u .... has .... $count edits... - adding to list\n" );
				}
				$removing[] = $u;
			} else {
				if ( $this->getOption( 'debug' ) ) {
					$this->output( "$u .... has .... $count edits... - ignoring \n" );
				}
			}
		}

		return $removing;
	}

	public function execute() {
		$start = 1236367281;

		# 60 days
		$offset = 60 * 60 * 24 * 60;
		$cutoff = wfTimestamp( TS_MW, time() - $offset );

		$dbw = wfGetDB( DB_MASTER );
		$dbr = wfGetDB( DB_MASTER );

		$senderEmail = $this->getOption( 'email' );
		if ( !$senderEmail ) {
			$this->error(
				'You must supply the e-mail address where the e-mails will be sent out from!',
				true/* die? */
			);
		}
		$from = new MailAddress( $senderEmail );
		$docentsPage = Title::newFromText(
			wfMessage( 'docentsettings-help-page' )->inContentLanguage()->text()
		)->getFullURL();

		$offset = 60 * 60 * 24 * 30;
		$cutoff = wfTimestamp( TS_MW, time() - $offset );
		$lastfifteen = wfTimestamp( TS_MW, time() - 60 * 60 * 24 * 15 );
		$warnings = $this->getQuietDocents( $cutoff );
		$warn = 0;
		$noemail = 0;
		$subject = wfMsg( 'docentsettings-email-about-to-be-dropped-subject' );

		foreach ( $warnings as $id ) {
			$user = User::newFromId( $id );
			if ( $user->mRegistration > $cutoff ) {
				if ( $this->getOption( 'debug' ) ) {
					$this->output(
						"User {$user->getName()} registered on {$user->mRegistration} after cutoff $cutoff so ignored...\n"
					);
				}
				continue;
			}

			$count = $dbr->selectField(
				'docentwarnings',
				array( 'COUNT(*)' ),
				array( 'dw_user' => $id, "dw_timestamp > '$lastfifteen'" ),
				__METHOD__
			);
			if ( $count > 0 ) {
				if ( $this->getOption( 'debug' ) ) {
					$this->output( "skipping $id - received warning in last 15 days....\n" );
				}
				continue;
			}

			// has an e-mail address?
			$email = $user->getEmail();
			if ( $email == '' ) {
				$noemail++;
				continue;
			}
			$to = new MailAddress( $email );
			$cats = '';
			$res = $dbr->select(
				'docentcategories',
				array( 'dc_to' ),
				array( 'dc_user' => $id ),
				__METHOD__
			);
			foreach ( $res as $row ) {
				$t = Title::makeTitle( NS_CATEGORY, $row->dc_to );
				$cats .= '* ' . $t->getText() . "\n";
			}
			$name = $user->getRealName() != '' ? $user->getRealName() : $user->getName();
			$body = wfMsg(
				'docentsettings-email-about-to-be-dropped',
				$name,
				$cats,
				$docentsPage
			);
			if ( !$this->getOption( 'debug' ) ) {
				UserMailer::send( $to, $from, $subject, $body );
			}
			$dbw->insert(
				'docentwarnings',
				array(
					'dw_user' => $user->getId(),
					'dw_timestamp' => wfTimestamp( TS_MW, time() )
				),
				__METHOD__
			);
			if ( $this->getOption( 'debug' ) ) {
				$this->output( "Warning {$name} ($email) \n" );
			}
			$warn++;
		}

		/*if ( time() < ( $start + 60 * 60 * 24 * 15 ) ) {
			if ( $this->getOption( 'debug' ) ) {
				$this->output( "returning early\n" );
			}
			$this->output(
				wfTimestamp( TS_MW, time() ) .
				" - warned {$warn} users ($noemail no e-mailed), 0 dropped\n"
			);
			return;
		}*/

		$noemail = 0;
		$subject = wfMsg( 'docentsettings-email-dropped-subject' );
		$dropped = 0;
		$offset = 60 * 60 * 24 * 45;
		$cutoff = wfTimestamp( TS_MW, time() - $offset );
		$removing = $this->getQuietDocents( $cutoff );

		foreach ( $removing as $id ) {
			$user = User::newFromId( $id );
			if ( $user->mRegistration > $cutoff ) {
				if ( $this->getOption( 'debug' ) ) {
					$this->output(
						"User {$user->getName()} registered on {$user->mRegistration} after cutoff $cutoff so ignored...\n"
					);
				}
				continue;
			}

			// has an e-mail address?
			$email = $user->getEmail();
			if ( $email == '' ) {
				$this->dropDocent( $user );
				$noemail++;
				$dropped++;
			}
			$to = new MailAddress( $email );
			$cats = '';
			$res = $dbr->select(
				'docentcategories',
				array( 'dc_to' ),
				array( 'dc_user' => $id ),
				__METHOD__
			);

			foreach ( $res as $row ) {
				$t = Title::makeTitle( NS_CATEGORY, $row->dc_to );
				$cats .= '* ' . $t->getText() . "\n";
			}
			$name = $user->getRealName() != '' ? $user->getRealName() : '';
			$body = wfMsg(
				'docentsettings-email-dropped',
				$name,
				$cats,
				$docentsPage
			);
			if ( !$this->getOption( 'debug' ) ) {
				UserMailer::send( $to, $from, $subject, $body );
			}
			$this->dropDocent( $user );
			$dropped++;
			# TODO log in special log somewhere
		}

		$this->output(
			wfTimestamp( TS_MW, time() ) .
			" - warned {$warn} users ($noemail have no email), $dropped dropped ($noemail had no email)\n"
		);
	}
}

$maintClass = 'UpdateDocents';
require_once( RUN_MAINTENANCE_IF_MAIN );
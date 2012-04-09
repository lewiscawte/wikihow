<?php
/**
 * Creates customized feed for users based on activities, topics and users they
 * engage with on the site.
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Travis Derouin <travis@wikihow.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
	'name' => 'Follow',
	'version' => '1.0',
	'author' => 'Travis Derouin',
	'description' => 'Creates customized feed for users based on activities, topics and users they engage with on the site',
);

// Autoload the main class of this extension
$wgAutoloadClasses['Follow'] = dirname( __FILE__ ) . '/Follow.class.php';

$wgHooks['ArticleSaveComplete'][] = 'wfTrackThingsToFollow';
$wgHooks['MarkPatrolledComplete'][] = 'wfTrackMarkPatrolled';
$wgHooks['IntroImageAdderUploadComplete'][] = 'wfTrackIntroImageUpload';
$wgHooks['QCVoted'][] = 'wfTrackQCVoted';
$wgHooks['EditFinderArticleSaveComplete'][] = 'wfTrackEditFinder';

function wfTrackMarkPatrolled( &$rcid, &$user ) {
	if ( rand( 0, 25 ) == 12 ) {
		Follow::followActivity( 'rcpatrol', $user );
	}
	return true;
}

function wfTrackIntroImageUpload( $title, $imgtag, $user ) {
	if ( rand( 0, 10 ) == 7 ) {
		Follow::followActivity( 'introimage', $user );
	}
	return true;
}

function wfTrackQCVoted( $user, $title, $vote ) {
	if ( rand( 0, 25 ) == 12 ) {
		Follow::followActivity( 'qcvote', $user );
	}
	return true;
}

function wfTrackEditFinder( $article, $text, $summary, $user, $type ) {
	if ( rand( 0, 5 ) == 3 ) {
		Follow::followActivity( 'editfinder', $user );
	}
	return true;
}

function wfTrackThingsToFollow( &$article, &$user, $text, $summary ) {
	if ( $user->getID() == 0 || preg_match( '@Reverted edits by@', $summary ) ) {
		// anons can't follow things, for now, and ignore rollbacks
		return true;
	}

	$t = $article->getTitle();
	if ( !$article->mLastRevision ) {
		$article->loadLastEdit( true );
	}

	$last_rev = $article->mLastRevision;
	$this_rev = $article->mRevision;
	if ( $t->getNamespace() == NS_USER_TALK ) {
		// did the user post a non-talk page message?
		$follow = false;
		if ( !$last_rev && !preg_match( "@\{\{@", $text ) ) {
			$follow = true;
		} elseif ( $last_rev ) {
			$oldtext = $last_rev->loadText();
			// how many templates in the old one?
			$oldCount = preg_match_all( "@\{\{[^\}]*\}\}@U", $oldtext, $matches );
			$newCount = preg_match_all( "@\{\{[^\}]*\}\}@U", $text, $matches );
			if ( $newCount <= $oldCount ) {
				$follow = true;
			} else {
				return true;
			}
		}

		$u = User::newFromName( $t->getText() );
		if ( $u && $u->getID() > 0 ) {
			$follow = true;
		} else {
			return true;
		}

		if ( !$follow ) {
			return true;
		}

		$dbw = wfGetDB( DB_MASTER );
		// This is so ugly because the Database class doesn't support
		// ON DUPLICATE KEY UPDATE natively... :-(
		$sql = "INSERT INTO follow (fo_user, fo_user_text, fo_type, fo_target_id, fo_target_name, fo_weight, fo_timestamp) "
				. " VALUES ({$user->getID()}, " . $dbw->addQuotes( $user->getName() ) . ", 'usertalk', {$u->getID()}, "
				. $dbw->addQuotes( $u->getName() ) . ", 1, " . $dbw->addQuotes( wfTimestampNow() ) . ") ON DUPLICATE KEY UPDATE  "
				. " fo_weight = fo_weight + 1, fo_timestamp = " . $dbw->addQuotes( wfTimestampNow() )
				;
		$dbw->query( $sql, __METHOD__ );
	} elseif ( $t->getNamespace() == NS_MAIN ) {
		global $wgContLang;
		$categoryNamespaceName = $wgContLang->getNsText( NS_CATEGORY );
		// check for change in categories
		preg_match_all( "@\[\[$categoryNamespaceName:.*\]\]@Ui", $text, $newcats );
		$oldtext = $last_rev->loadText();
		if ( $oldtext ) {
			preg_match_all( "@\[\[$categoryNamespaceName:.*\]\]@Ui", $oldtext, $oldcats );
			foreach ( $newcats[0] as $n ) {
				if ( !in_array( $n, $oldcats[0] ) ) {
					Follow::followCat( $t, $n );
				}
			}
		} else {
			foreach ( $newcats as $n ) {
				Follow::followCat( $t, $n );
			}
		}
	}

	return true;
}
<?php
/**
 * Adds links to articles as User:LinkTool.
 *
 * @file
 * @ingroup Maintenance
 */

/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/MaintenanceScripts/ and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );

require_once( 'Maintenance.php' );

class LinkToolExactReplacement extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Adds links to articles as User:LinkTool';
		$this->addArg( 'limit', 'Check this many articles; default is 1000' );
	}

	public function execute() {
		global $wgUser, $wgTitle;

		$wgUser = User::newFromName( 'LinkTool' );
		$dbw = wfGetDB( DB_MASTER );

		# get a list of things to ignore
		# ex: How to Bowl is an article, but "Bowl" is ambiguious
		$ignorePhrasesMessage = wfMessage( 'linktool_ignore_phrases' )->inContentLanguage()->text();
		$ignoreTitlesMessage = wfMessage( 'linktool_ignore_articles' )->inContentLanguage()->text();
		$ignore_phrases = array_flip( explode( "\n", strtolower( $ignorePhrasesMessage ) ) );
		$ignore_titles	= array_flip( explode( "\n", strtolower( $ignoreTitlesMessage ) ) );

		# default: check 1000 articles
		$limit = $this->getArg( 0, 1000 );

		# get a list of articles to check
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'page',
			array( 'page_title', 'page_namespace'),
			array( 'page_is_redirect' => 0, 'page_namespace' => 0 ),
			__METHOD__,
			array( 'ORDER BY' => 'page_counter DESC', 'LIMIT' => $limit )
		);
		$titles = array();
		foreach ( $res as $row ) {
			$title = Title::makeTitle( $row->page_namespace, $row->page_title );
			if ( isset( $ignore_titles[strtolower( $title->getText() )] ) ) {
				continue;
			}
			if ( $title ) {
				$titles[] = $title;
			}
		}

		$this->output( 'got ' . sizeof( $titles ) . " titles\n" );
		$count = 0;
		$updated = 0;
		foreach ( $titles as $t ) {
			# skip titles that have been recently updated
			$recentlyEditedByMe = $dbw->selectField(
				'recentchanges',
				array( 'COUNT(*)' ),
				array( 'rc_title' => $t->getDBkey(), 'rc_user_text' => 'LinkTool' ),
				__METHOD__
			);
			if ( $recentlyEditedByMe > 0 ) {
				$this->output( "skipping {$t->getText()} because LinkTool recently edited this article\n" );
				continue;
			}
			$r = Revision::newFromTitle( $t );
			if ( !$r ) {
				continue;
			}

			$replacements = 0;
			$text = $r->getText();
			$this->output( "checking {$t->getText()}\n" );
			foreach ( $titles as $x ) {
				# don't link to yourself, silly!
				if ( $x->getText() == $t->getText() ) {
					continue;
				}
				if ( isset( $ignore_phrases[strtolower( $x->getText() )] ) ) {
					continue;
				}

				$search = strtolower( $x->getText() );
				$search = str_replace( '/', '\/', $search );
				$search = str_replace( '(', '\(', $search );
				$search = str_replace( ')', '\)', $search );
				#echo "trying $search\n";

				# fake word boundary
				$fb = '[^a-zA-Z0-9_|\[\]]';
				$newtext = '';
				$i = $j = $y = 0;
				$now = 0; // # the number of replacements, limit it to 2 per article
				// walk the article ignoring links
				while ( ( $i = strpos( $text, '[', $i ) ) !== false ) {
					if ( substr( $text, $i + 1, 1 ) == '[' ) {
						$i++;
					}
					$stext = substr( $text, $j, $i - $j );
					#echo "\n\n--------data - search $search-----\n$stext\n";
					if ( $now < 2 ) {
						$newtext .= preg_replace(
							"/($fb)($search)($fb)/im",
							"$1[[{$x->getText()}|$2]]$3",
							$stext,
							1,
							&$y
						);
					} else {
						$newtext .= $stext;
					}
					#echo "\n\n--------new text - search $search $y replacements-----\n$newtext\n";
					$now += $y;
					#echo "now $now, y $y\n";
					$j = $i;
					if ( $i > strlen( $text ) ) {
						$this->output( "$i is longer than " . strlen( $text ) . " exiting\n" );
						exit;
					}
					$i = strpos( $text, ']', $i );
					if ( $i !== false ) {
						$newtext .= substr( $text, $j, $i - $j );
						$j = $i;
					}
				}
				if ( $now < 2 ) {
					$newtext .= preg_replace(
						"/($fb)($search)($fb)/im",
						"$1[[{$x->getText()}|$2]]$3",
						substr( $text, $j, strlen( $text ) - $j ),
						1,
						&$y
					);
				} else {
					$newtext .= substr( $text, $j, strlen( $text ) - $j );
				}

				$text = $newtext;
				if ( $now > 0 ) {
					echo "Adding link to {$t->getText()} pointing to {$x->getText()}\n";
				}
				$replacements += $now;
				#echo "now $now\n";
				$count++;
				#if ( $replacements > 1 )
				#	break;
				$dbw->update(
					'recentchanges',
					array( 'rc_patrolled' => 1 ),
					array( 'rc_user_text' => 'LinkTool' ),
					__METHOD__
				);
			}
			if ( $replacements > 0 ) {
				$wgTitle = $t;
				$a = new Article( $t );
				if ( !$a->doEdit( $text, 'LinkTool is sprinkling some links', EDIT_MINOR ) ) {
					$this->output( "couldn't update article {$t->getText()}, exiting...\n" );
					exit;
				}
				$this->output( "updated {$t->getText()}\n" );
				$wgTitle = null;
				$updated++;
			}
			if ( $updated == 100 ) {
				$this->output( "updated $updated articles, breaking...\n" );
				break;
			}
		}
		$this->output( 'checked ' . number_format( $count ) . " articles\n" );
	}
}

$maintClass = 'LinkToolExactReplacement';
require_once( RUN_MAINTENANCE_IF_MAIN );
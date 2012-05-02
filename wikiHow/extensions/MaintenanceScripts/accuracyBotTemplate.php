<?php
/**
 * This script grabs all articles that meet the following conditions:
 * 1) Has less than 10,000 views
 * 2) Has {{accuracy}} template OR is included in Special:AccuracyPatrol
 *
 * Then it adds {{Accuracy-bot}} template to the article.
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

class AccuracyBotTemplate extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Adds {{Accuracy-bot}} template to articles that' .
			' have less than 10,000 vies and either have {{Accuracy}} template' .
			'OR are included in Special:AccuracyPatrol';
		$this->addArg( 'do', 'Do what? Valid values are "update" and "remove"' );
	}

	public function execute() {
		// first get a list of articles that have
		// the {{Accuracy}} template on them.
		$dbr = wfGetDB( DB_SLAVE );

		$wgUser = User::newFromName( 'Miscbot' );

		$accuracyTemplate = '{{Accuracy-bot}}';

		if( $this->getArg( 0 ) == 'update' ) {
			// this section hasn't been fully tested as we
			// didn't end up using it.
			$articles = array();

			$res = $dbr->select(
				'templatelinks',
				'tl_from',
				array( 'tl_title' => 'Accuracy-bot' ),
				__METHOD__,
				array( 'LIMIT' => 1 )
			);
			echo $dbr->lastQuery();
			foreach ( $res as $row ) {
				$articles[] = $row->tl_from;
			}

			foreach( $articles as $articleId ) {
				// check to see if it has the {{Accuracy}} template on it
				$title = Title::newFromID( $articleId );
				if( $title ) {
					$article = new Article( $title );
					$revision = Revision::newFromTitle( $title );
					$this->output( 'Checking ' . $title->getText() . "\n" );
					if( $revision ) {
						$text = $revision->getText();
						$count = 0;
						$newText = preg_replace( '@{{accuracy\|[^}]*}}@i', '', $text, -1, $count );

						$this->output( 'Found ' . $count . "\n" );
						// The community template should be put in
						if( $count > 0 ) {
							$newText = preg_replace( '@{{accuracy-bot}}@i', '{{accuracy-bot|community}}', $newText );
							$article->doEdit( $newText, 'Fixing Accuracy-bot template' );
							$this->output( 'Fixed ' . $title->getFullURL() . "\n" );
							continue;
						}

						$res = $dbr->select(
							'rating_low',
							'*',
							array( 'rl_page' => $articleId ),
							__METHOD__
						);
						foreach ( $res as $row ) {
							$percent = $row->rl_avg * 100;
							$template = "{{accuracy-bot|patrol|{$percent}|{$row->rl_count}}}";
							$newText = preg_replace( '@{{Accuracy-bot}}@i', $template, $newText );
							$articleDo->edit( $newText, 'Fixing Accuracy-bot template' );
							$this->output( 'Fixed ' . $title->getFullURL() . "\n" );
							continue;
						}
					}
				}
			}

			return;
		} elseif ( $this->getArg( 0 ) == 'remove' ) {
			$articles = array();

			$res = $dbr->select(
				'templatelinks',
				'tl_from',
				array( 'tl_title' => 'Accuracy-bot' ),
				__METHOD__
			);
			echo $dbr->lastQuery() . "\n";
			foreach ( $res as $row ) {
				$articles[] = $row->tl_from;
			}

			$this->output( 'Getting ready to remove template from ' . count( $articles ) . " articles\n" );
			foreach( $articles as $articleId ) {
				// check to see if it has the {{Accuracy-bot}} template on it
				$title = Title::newFromID( $articleId );
				if( $title ) {
					$article = new Article( $title );
					$revision = Revision::newFromTitle( $title );
					if( $revision ) {
						$text = $revision->getText();
						$count = 0;
						$newText = str_ireplace( '{{Accuracy-bot}}', '', $text );

						$article->doEdit( $newText, 'Removing Accuracy-bot template' );
						$this->output( 'Fixed ' . $title->getFullURL() . "\n" );
						continue;
					}
				}
			}

			return;
		}

		$this->output( 'Starting first query at ' . microtime( true ) . "\n" );

		$res = $dbr->select(
			array( 'page', 'templatelinks' ),
			array( 'page_counter', 'page_id' ),
			array(
				'tl_from = page_id',
				'tl_title' => 'Accuracy',
				'page_namespace' => '0'
			),
			__METHOD__
		);

		$this->output( 'Finished last query at ' . microtime( true ) . "\n" );

		$articles = array();
		foreach ( $res as $row ) {
			if( $row->page_counter < 10000 ) {
				$articles[$row->page_id] = $row->page_id;
			}
		}

		$this->output( 'Starting second query at ' . microtime( true ) . "\n" );

		$res = $dbr->select(
			array( 'page', 'rating_low' ),
			array( 'page_counter', 'page_id' ),
			array( 'rl_page = page_id', 'page_namespace' => 0 ),
			__METHOD__
		);

		$this->output( 'Finished second query at ' . microtime( true ) . "\n" );

		foreach ( $res as $row ) {
			if( $row->page_counter < 10000 ) {
				$articles[$row->page_id] = $row->page_id;
			}
		}

		$this->output( 'Getting ready to add template to ' . count( $articles ) . " articles\n" );
		$this->output( "\n\n" );

		foreach( $articles as $id ) {
			$title = Title::newFromID( $id );
			if( $title ) {
				$revision = Revision::newFromTitle( $title );
				$article = new Article( $title );
				$text = $revision->getText();
				$text = '{{Accuracy-bot}} ' . $text;
				$article->doEdit( $text, 'Marking article with Accuracy-bot template' );

				$this->output( 'Added template to ' . $title->getFullURL() . "\n" );
			}
		}
	}
}

$maintClass = 'AccuracyBotTemplate';
require_once( RUN_MAINTENANCE_IF_MAIN );
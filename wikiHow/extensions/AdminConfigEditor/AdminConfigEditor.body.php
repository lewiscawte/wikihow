<?php

class AdminConfigEditor extends UnlistedSpecialPage {
	/**
	 * Constructor -- set up the new special page
	 */
	public function __construct() {
		parent::__construct( 'AdminConfigEditor', 'adminconfigeditor' );
	}

	private static function validateInput( $key, $val ) {
		$err = '';
		if ( $key == 'wikiphoto-article-exclude-list' || $key == 'wikihow-watermark-article-list' ) {
			$list = self::parseURLlist( $val );
			foreach ( $list as $item ) {
				if ( !$item['title'] || !$item['title']->isKnown() ) {
					$err .= $item['url'] . "\n";
				}
			}
		}
		return $err;
	}

	/**
	 * Parse the input field into an array of URLs and Title objects
	 */
	private static function parseURLlist( $pageList ) {
		$pageList = preg_split( '@[\r\n]+@', $pageList );
		$urls = array();
		foreach ( $pageList as $url ) {
			$url = trim( $url );
			if ( !empty( $url ) ) {
				$title = WikiPhoto::getArticleTitleNoCheck( urldecode( $url ) );
				$urls[] = array(
					'url' => $url,
					'title' => $title
				);
			}
		}
		return $urls;
	}

	private static function translateValues( $values, $listType ) {
		$result = '';
		$list = self::parseURLlist( $values );

		foreach ( $list as $item ) {
			$value = '';
			
			if ( $item['title'] ) {
				if ( $listType == 'id' ) {
					$value = $item['title']->getArticleID();
					if ( !empty( $value ) ) {
						$result .= $value . "\r\n";
					}
				} elseif ( $listType == 'url' ) {
					$value = $item['title']->getDBkey();
					if ( !empty( $value ) ) {
						$artid = $item['title']->getArticleID();
						$result .= '<tr>
									<td>' . $item['title']->getFullURL() . '</td>
									<td class="x"><a href="#" class="remove_link" id="' . $artid . '">x</a></td>
								</tr>';
						$hidden .= $item['title']->getFullURL() . "\r\n";
					}
				}
			}
		}
		
		if ( $listType == 'url' ) {
			$result = '<table>' . $result . '</table>
					<div id="config_hidden_val">' . $hidden . '</div>';
		}

		return $result;
	}
	
	private function removeLine( $key, $id ) {
		$err = '';
		if ( !empty( $id ) ) {
			$val = ConfigStorage::dbGetConfig( $key );
			$pageList = preg_split( '@[\r\n]+@', $val );
			
			$id_pos = array_search( $id, $pageList );
			if ( $id_pos === false ) {
				$err = wfMessage( 'adminconfigeditor-error-not-in-list' )->text();
			} else {
				unset( $pageList[$id_pos] );
				$val = implode( "\r\n", $pageList );
				ConfigStorage::dbStoreConfig( $key, $val );
				
				// now let's return the whole thing back
				$result = $this->translateValues( $val, 'url' );
			}
		} else {
			$err = wfMessage( 'adminconfigeditor-error-bad-aid' )->text();
		}
		return array( 'result' => $result, 'error' => $err );
 	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the special page or null
	 */
	public function execute( $par ) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		// Check restrictions
		if ( !$this->userCanExecute( $wgUser ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Can't use the special page if database is locked...
		if ( wfReadOnly() ) {
			$wgOut->readOnlyPage();
			return;
		}

		// No access for blocked users
		if ( $wgUser->isBlocked() ) {
			$wgOut->blockedPage();
			return;
		}

		if ( strtolower( $par ) == 'url' ) {
			$style = 'url';
		} else {
			$style = '';
		}

		if ( $wgRequest->wasPosted() ) {
			$action = $wgRequest->getVal( 'action' );
			$wgOut->setArticleBodyOnly( true );

			if ( $action == 'load-config' ) {
				$key = $wgRequest->getVal( 'config-key', '' );
				$val = ConfigStorage::dbGetConfig( $key );

				$style = $wgRequest->getVal( 'style', '' );
				if ( $style == 'url' ) {
					// translate IDs to readable URLs
					$val = $this->translateValues( $val, $style );
				}

				$result = array( 'result' => $val );
			} elseif ( $action == 'save-config' ) {
				$errors = '';
				$key = $wgRequest->getVal( 'config-key', '' );
				$val = $wgRequest->getVal( 'config-val', '' );

				$style = $wgRequest->getVal( 'style', '' );
				if ( $style == 'url' ) {
					// add the hidden values to the new ones
					$val = $wgRequest->getVal( 'hidden-val', '' ) . $val;
					// validate for errors
					$errors = self::validateInput( $key, $val );
					// translate the good URLs back to IDs for storage purposes
					$val = $this->translateValues( $val, 'id' );
				}

				ConfigStorage::dbStoreConfig( $key, $val );
				$errors .= self::validateInput( $key, $val );
				$output = wfMessage( 'adminconfigeditor-saved-and-checked'  )->text() . '<br /><br />';
				if ( $errors ) {
					$output .= wfMessage( 'adminconfigeditor-errors', str_replace( "\n", "<br />\n", $errors ) )->parse();
				} else {
					$output .= wfMessage( 'adminconfigeditor-no-errors' )->text();
				}

				if ( $style == 'url' ) {
					// translate back to URLs for updated display
					$val = $this->translateValues( $val, 'url' );
				}
				
				$result = array( 'result' => $output, 'val' => $val );
			} elseif ( $action == 'remove-line' ) {
				$key = $wgRequest->getVal( 'config-key', '' );
				$id = $wgRequest->getVal( 'id', '' );
				$result = $this->removeLine( $key, $id );
				$result = array(
					'result' => $result['result'],
					'error' => $result['error']
				);
			} else {
				$result = array(
					'error' => wfMessage( 'adminconfigeditor-bad-action' )->text()
				);
			}

			print json_encode( $result );
			return;
		}

		$wgOut->setHTMLTitle( wfMsg( 'pagetitle', wfMsg( 'adminconfigeditor-page-title' ) ) );
		$listConfigs = ConfigStorage::dbListConfigKeys();

		// Add JS
		$wgOut->addModules( 'ext.adminConfigEditor' );

		// Get the form HTML
		$tmpl = self::getGuts( $listConfigs, $style );

		// Output the form
		$wgOut->addHTML( $tmpl );
	}

	function getGuts( $configs, $style ) {
		ob_start();

		if ( $style == 'url' ) {
			echo '<h1>' . wfMessage( 'adminconfigeditor-url-config-editor' )->text() . '</h1>';
			$bURL = true;
		} else {
			$bURL = false;
		}
?>
		<style type="text/css">
		table { width: 100%; }
		td {
			background-color: #EEE;
			padding: 5px;
		}
		td.x { text-align: center; }
		#config_hidden_val { display: none; }
		</style>
		<form method="post" action="<?php echo $this->getTitle()->getFullURL() ?>">
		<h4><?php echo wfMessage( 'adminconfigeditor-instructions' )->text() ?></h4>
		<br />
		<select id="config-key">
			<option value="">--</option>
			<?php foreach ( $configs as $config ): ?>
				<option value="<?php echo $config ?>"><?php echo $config ?></option>
			<?php endforeach; ?>
		</select><br />
		<br/>
		<?php
		if ( $bURL ) {
			echo wfMessage( 'adminconfigeditor-add-new' )->parse();
		}
		?>
		<textarea id="config-val" type="text" rows="10" cols="70"></textarea>
		<button id="config-save" disabled="disabled"><?php echo wfMessage( 'adminconfigeditor-save' )->text() ?></button><br />
		<br/>
		<div id="admin-result"></div>
		<div id="url-list"></div>
		<input type="hidden" id="display-style" value="<?php echo $style ?>" />
		</form>
<?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}

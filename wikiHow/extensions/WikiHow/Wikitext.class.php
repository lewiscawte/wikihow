<?php
/**
 * Follows something like the active record pattern.
 */
class Wikitext {

	/**
	 * Get the first [[Image: ...]] tag from an article, and return it as a
	 * URL.
	 * @param string $text the wikitext for the article
	 * @return string The URL
	 */
	public static function getFirstImageURL( $text ) {
		$url = '';
		if ( preg_match( '@\[\[Image:([^\]|]*)(\|[^\]]*)?\]\]@s', $text, $m ) ) {
			$imgTitle = Title::newFromText( $m[1], NS_FILE );
			if ( $imgTitle ) {
				$file = wfFindFile( $imgTitle );
				if ( $file && $file->exists() ) {
					$url = $file->getUrl();
				}
			}
		}
		return $url;
	}

	/**
	 * Cut just the first step out of the Steps section wikitext.
	 * @param string $text Steps (only) section wikitext.
	 * @return string The text from the first step of the Steps section.  Note:
	 *   May contain wikitext markup in output.
	 */
	public static function cutFirstStep( $text ) {
		// remove alternate method title
		$text = preg_replace( '@^===[^=]*===@', '', $text );

		// cut just first step
		$text = preg_replace( '@^[#*\s]*([^#*]([^#]|\n)*)([#*](.|\n){0,1000})?$@', '$1', $text );
		$text = trim( $text );
		return $text;
	}

	/**
	 * Remove wikitext markup from a single section of an article to return
	 * the flattened text.  Removes some unicode characters, templates,
	 * links (leaves the descriptive text in the link though) and images.
	 * @param string $text wikitext to flatten
	 * @return string the flattened text
	 */
	public static function flatten( $text ) {
		// change Unicode quotes (from MS Word) to ASCII
		$text = preg_replace( '@[\x{93}\x{201C}\x{94}\x{201D}]@u', '"', $text );
		$text = preg_replace( '@[\x{91}\x{2018}\x{92}\x{2019}]@u', '\'', $text );

		// remove templates
		$text = preg_replace( '@{{[^}]+}}@', '', $text );

		// remove [[Image:foo.jpg]] images and [[Link]] links
		$text = preg_replace_callback(
			'@\[\[([^\]|]+(#[^\]|]*)?)((\|[^\]|]*)*\|([^\]|]*))?\]\]@',
			function( $m ) {
				// if the link text has Image: or something at the start,
				// we don't want it to be in the description
				if ( strpos( $m[1], ':' ) !== false ) {
					return '';
				} else {
					// if the link looks like [[Texas|The lone star state]],
					// we try to grab the stuff after the vertical bar
					if ( isset( $m[5] ) && strpos( $m[5], '|' ) === false ) {
						return $m[5];
					} else {
						return $m[1];
					}
				}
			},
			$text
		);

		// remove [http://link.com/ Link] links
		$text = preg_replace_callback(
			'@\[http://[^\] ]+( ([^\]]*))?\]@',
			function( $m ) {
				// if the link looks like [http://link/ Link], we try to
				// grab the stuff after the space
				if ( isset( $m[2] ) ) {
					return $m[2];
				} else {
					return '';
				}
			},
			$text
		);

		// remove multiple quotes since they're wikitext for bold or italics
		$text = preg_replace( '@[\']{2,}@', '', $text );

		// remove other special wikitext stuff
		// @todo FIXME: i18n support!
		$text = preg_replace( '@(__FORCEADV__|__TOC__|#REDIRECT)@i', '', $text );

		// convert special HTML characters into spaces
		$text = preg_replace( '@(<br[^>]*>|&nbsp;)+@i', ' ', $text );

		// replace multiple spaces in a row with just one
		$text = preg_replace( '@[[:space:]]+@', ' ', $text );

		// remove all HTML
		$text = strip_tags( $text );

		return $text;
	}

	/**
	 * Extract the intro from the wikitext of an article
	 */
	public static function getIntro( $wikitext ) {
		global $wgParser;
		$intro = $wgParser->getSection( $wikitext, 0 );
		return $intro;
	}

	/**
	 * Replace the intro in the wikitext
	 */
	public static function replaceIntro( $wikitext, $intro ) {
		global $wgParser;
		$wikitext = $wgParser->replaceSection( $wikitext, 0, $intro );
		return $wikitext;
	}

	/**
	 * Replace the Steps section in the wikitext.
	 */
	public static function replaceStepsSection( $wikitext, $sectionID, $stepsText, $withHeader = false ) {
		global $wgParser;
		static $stepsMsg = '';
		if ( empty( $stepsMsg ) ) {
			$stepsMsg = wfMsg( 'steps' );
		}

		if ( !$withHeader ) {
			$stepsText = "== $stepsMsg ==\n" . $stepsText;
		}
		$wikitext = $wgParser->replaceSection( $wikitext, $sectionID, $stepsText );
		return $wikitext;
	}

	public static function removeVideoSection( $wikitext ) {
		global $wgParser;
		$section = self::getVideoSection( $wikitext );
		if ( empty( $section[0] ) ) {
			throw new MWException( "couldn't find video section" );
		}
		$wikitext = $wgParser->replaceSection( $wikitext, $section[1], '' );
		return $wikitext;
	}

	/**
	 * Extract the video section from the wikitext of an article
	 */
	public static function getVideoSection( $wikitext, $withHeader = true ) {
		static $videoMsg = '';
		if ( empty( $videoMsg ) ) {
			$videoMsg = wfMsg( 'video' );
		}
		return self::getSection( $wikitext, $videoMsg, $withHeader );
	}

	/**
	 * Count alternate methods in the Steps section.
	 */
	public static function countAltMethods( $stepsText ) {
		$count = preg_match_all( '@^===@m', $stepsText, $m );
		return $count;
	}

	/**
	 * Count the number of tips in the Tips section.
	 */
	public static function countTips( $wikitext ) {
		list( $tips, ) = self::getSection( $wikitext, wfMsg( 'tips' ), true );
		$count = 0;
		if ( $tips ) {
			$count = preg_match_all( '@\s*\*@m', $tips, $m );
		}
		return $count;
	}

	/**
	 * Count the number of steps in the Steps section.
	 */
	public static function countSteps( $stepsText ) {
		$num_steps = 0;
		if ( $stepsText ) {
			// has steps section, so assume valid candidate for detailed title
			$num_steps = preg_match_all( '/^#[^*]/im', $stepsText, $m );
		}
		return $num_steps;
	}

	/**
	 * Count the number of images in a block of wikitext
	 */
	public static function countImages( $wikitext ) {
		$num_images = preg_match_all( '/\[\[Image:/im', $wikitext, $m );
		return $num_images;
	}

	/**
	 * Extract the given section from the wikitext of an article
	 */
	public static function getSection( $wikitext, $sectionMsg, $withHeader ) {
		global $wgParser;
		if ( empty( $sectionMsg ) ) {
			throw new MWException( 'Must provide the section message' );
		}

		$content = '';
		$id = 0;
		for ( $i = 1; $i < 10; $i++ ) {
			$section = $wgParser->getSection( $wikitext, $i );
			if ( empty( $section ) ) {
				break;
			}
			if ( preg_match( '@^\s*==\s*([^=\s]+)\s*==\s*$((.|\n){0,1000})@m', $section, $m ) ) {
				if ( $m[1] == $sectionMsg ) {
					$content = $withHeader ? $section : trim( $m[2] );
					$id = $i;
					break;
				}
			}
		}
		return array( $content, $id );
	}

	/**
	 * Extract the Steps section from the wikitext of an article
	 */
	public static function getStepsSection( $wikitext, $withHeader = false ) {
		static $stepsMsg = '';
		if ( empty( $stepsMsg ) ) {
			$stepsMsg = wfMsg( 'steps' );
		}
		return self::getSection( $wikitext, $stepsMsg, $withHeader );
	}

	/**
	 * Split an alternate method, or the Steps section, into different
	 * steps (returned as an array).
	 */
	public static function splitSteps( $wikitext ) {
		$steps = preg_split( '@^#@m', $wikitext );
		for ( $i = 1; $i < count( $steps ); $i++ ) {
			$steps[$i] = '#' . $steps[$i];
		}
		return $steps;
	}

 	/**
	 * Check if a piece of wikitext is a step (ie, starts with "#").
	 */
	/*public static function isStep( $wikitext ) {
		return preg_match( '@^#@m', $wikitext ) > 0;
	}*/

	/**
	 * Split a Steps section into different methods (returned as an array).
	 */
	public static function splitAltMethods( $wikitext ) {
		$parts = preg_split( '@^=@m', $wikitext );
		$methods = array();
		foreach ( $parts as $i => $part ) {
			if ( $i == 0 ) {
				if ( !empty( $part ) ) {
					$methods[] = $part;
				}
			} else {
				$methods[] = '=' . $part;
			}
		}
		return $methods;
	}

	/**
	 * Checks whether a string of wikitext starts with "# ...". If
	 * $checkTopLevel is true, returns true if the "..." doesn't indicate
	 * a sub-step or bullet point.
	 */
	public static function isStep( $stepText, $checkTopLevel = false ) {
		if ( preg_match( '@^(([#*]|\s)+)((.|\n)*)@m', $stepText, $m ) ) {
			if ( !$checkTopLevel ) {
				return true;
			}
			$stripped = preg_replace( '@\s+@', '', $m[1] );
			$levels = strlen( $stripped );
			if ( $levels == 1 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * If there are images in the step, replace them with placeholders and
	 * return the modified text.
	 */
	public static function cutImages( $stepText ) {
		$tokens = array();
		$output = preg_replace_callback( '@\[\[Image:[^\]]*\]\]@i',
			function ( $m ) use ( &$tokens ) {
				$token = 'IMG_' . Wikitext::genRandomString();
				$tokens[] = array( 'token' => $token, 'tag' => $m[0] );
				return $token;
			},
			$stepText
		);
		return array( $output, $tokens );
	}

	/**
	 * Change the size param in a list of image tag params. Params should
	 * have been parsed with the parseImageTag() function first.
	 *
	 * Details on understanding image params are here:
	 * http://en.wikibooks.org/wiki/Editing_Wikitext/Pictures/The_Quick_Course
	 *
	 * @param int $size size in pixels of new image tag. Must be positive.
	 * @param string $orientation new orientation of image. If emprty string
	 *   is given, don't change orientation.
	 */
	public static function changeImageTag( $tag, $size, $orientation ) {
		$positionOpts = array( 'right', 'left', 'center', 'none' );
		$typeOpts = array( 'thumb', 'frame', 'border' );
		$size = intval( $size );
		$sizePx = $size . 'px';

		if ( $orientation && !in_array( $orientation, $positionOpts ) ) {
			echo 'error: bad orientation given';
			return $tag;
		}
		if ( $size <= 0 ) {
			echo 'error: bad size given';
			return $tag;
		}

		$params = self::parseImageTag( $tag );
		if ( count( $params ) == 1 ) {
			if ( $orientation ) {
				$params[] = $orientation;
			}
			$params[] = $sizePx;
		}

		$needsSize = true;
		for ( $i = 1; $i < count( $params ); $i++ ) {
			$param = strtolower( $params[$i] );
			if ( $orientation ) {
				if ( in_array( $param, $positionOpts ) ) {
					$params[$i] = $orientation;
				} else {
					array_splice( $params, $i, 0, $orientation );
					$i++;
				}
				if (
					$i < count( $params ) &&
					in_array( strtolower( $params[$i] ), $typeOpts )
				)
				{
					array_splice( $params, $i, 1 );
				}
				$orientation = '';
				continue;
			}
			if ( preg_match( '@^\s*[0-9]+\s*px\s*$@', $param, $m ) ) {
				$params[$i] = $sizePx;
				$needsSize = false;
				break;
			}
		}
		if ( $needsSize ) {
			$last = count( $params );
			$param = strtolower( $params[$last - 1] );
			if (
				!in_array( $param, $positionOpts ) &&
				!in_array( $param, $typeOpts )
			)
			{
				$last--;
			}
			array_splice( $params, $last, 0, $sizePx );
		}

		$tag = self::buildImageTagFromParams( $params );
		return $tag;
	}

	/**
	 * Parse an image wikitext into params array.
	 */
	private static function parseImageTag( $tag ) {
		$noBookends = preg_replace( '@^\[\[(.*)\]\]$@', '$1', $tag );
		return explode( '|', $noBookends );
	}

	/**
	 * Build an image tag from the params array given. Params should
	 * have been parsed with the parseImageTag() function first.
	 */
	private static function buildImageTagFromParams( $params ) {
		return '[[' . join( '|', $params ) . ']]';
	}

	/**
	 * Generate a string of random characters of a given length.
	 */
	public static function genRandomString( $chars = 20 ) {
		$str = '';
		$set = array(
			'0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
			'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
			'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
			'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
		);
		for ( $i = 0; $i < $chars; $i++ ) {
			$r = mt_rand( 0, count( $set ) - 1 );
			$str .= $set[$r];
		}
		return $str;
	}

	/**
	 * Utility method to return the wikitext for an article
	 */
	public static function getWikitext( &$dbr, $title ) {
		global $wgTitle;
		if ( !$title ) {
			return false;
		}
		// an optimization if $title is $wgTitle
		if ( $wgTitle && $wgTitle->getText() == $title->getText() ) {
			$whow = WikiHow::newFromCurrent();
			$wikitext = $whow->mLoadText;
		} else {
			$rev = Revision::loadFromTitle( $dbr, $title );
			if ( !$rev ) {
				return false;
			}
			$wikitext = $rev->getText();
		}
		return $wikitext;
	}

	/**
	 * Utility method to save wikitext of an article
	 */
	public static function saveWikitext( $title, $wikitext, $comment ) {
		$saved = false;
		$article = new Article( $title );
		$saved = $article->doEdit( $wikitext, $comment );
		if ( !$saved ) {
			return 'Unable to save wikitext for article: ' . $title->getText();
		} else {
			return '';
		}
	}

	/**
	 * Enlarge the images in the wikitext for the given title objects.
	 * @return (array) first element any error string (empty if no error);
	 *   2nd element is number of images found/changed
	 */
	public static function enlargeImages( $title, $recenter, $px, $introPx = 0 ) {
		static $dbr = null;
		if ( !$dbr ) {
			$dbr = wfGetDB( DB_SLAVE );
		}

		$err = '';
		$numImages = 0;
		$stepsText = '';

		$wikitext = self::getWikitext( $dbr, $title );
//debugging
//$t = Title::newFromText('Make Chocolate Covered Peeps');
//$r = Revision::loadFromTitle($dbr, $t, 7544205);
//$wikitext = $r->getText();
		if ( $wikitext ) {
			list( $stepsText, $sectionID ) =
				self::getStepsSection( $wikitext, true );
		}

		if ( !$stepsText ) {
			$err = 'Unable to load wikitext';
		} else {
			list( $stepsText, $numImages, $err ) =
				self::enlargeImagesInWikitext( $stepsText, $recenter, $px, false );
			if ( !$err ) {
				$wikitext = self::replaceStepsSection( $wikitext, $sectionID, $stepsText, true );

				$comment = $recenter ?
					'Enlarging and centering Steps photos' :
					'Enlarging Steps photos to ' . $px . ' pixels';

				if ( $introPx ) {
					$intro = self::getIntro( $wikitext );
					list( $intro, $introImages, $err ) =
						self::enlargeImagesInWikitext( $intro, '', $introPx, true );
					$numImages += $introImages;
					$wikitext = self::replaceIntro( $wikitext, $intro );

					$comment .= '; enlarging intro image';
				}

				if ( !$err ) {
					$err = self::saveWikitext( $title, $wikitext, $comment );
				}
			}
		}

		return array( $err, $numImages );
	}

	/**
	 * Enlarge the images in a section of wikitext. Currently tested with
	 * both intro and steps sections.
	 */
	private static function enlargeImagesInWikitext( $text, $recenter, $px, $isIntro ) {
		$orientation = $recenter ? 'center' : '';

		$methods = self::splitAltMethods( $text );

		$numImages = 0;

		foreach ( $methods as &$method ) {
			if ( !$isIntro ) {
				$steps = self::splitSteps( $method );
			} else {
				$steps = array( $text );
			}

			foreach ( $steps as &$step ) {
				if ( $isIntro || self::isStep( $step, false ) ) {
					list( $tokenText, $images ) = self::cutImages( $step );

					$step = $tokenText;
					$numImages += count( $images );

					foreach ( $images as $image ) {
						$tag = $image['tag'];
						$modtag = self::changeImageTag( $tag, $px, $orientation );
						if ( $recenter ) {
							$step = str_replace( $image['token'], '', $step );
							$step = trim( $step );
							// make sure we don't re-add <br> tags in case
							// this article's images were already enlarged
							if ( !preg_match( '@<br><br>$@', $step ) ) {
								$step .= '<br><br>';
							}
							$step .= "$modtag\n";
						} else {
							$step = str_replace( $image['token'], $modtag, $step );
						}
					}
				}
			}

			self::ensureNewlineTerminatedStrings( $steps );
			$method = join( '', $steps );
		}

		self::ensureNewlineTerminatedStrings( $methods );
		$text = join( '', $methods );
		return array( $text, $numImages, $err );
	}

	/**
	 * Ensure that all strings in an array of strings are newline terminated.
	 */
	private static function ensureNewlineTerminatedStrings( &$arr ) {
		foreach ( $arr as &$str ) {
			$len = strlen( $str );
			if ( $len > 0 && $str{$len - 1} != "\n" ) {
				$str .= "\n";
			}
		}
	}
}
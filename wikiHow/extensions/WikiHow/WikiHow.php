<?php
/**
 * A class that represents a wikiHow article. Used to add special processing
 * on top of the Article class (but without adding any explicit database
 * access methods).
 */
class WikiHow {

	/*private*/
	var $mSteps, $mTitle, $mLoadText;
	var $section_array;
	var $section_ids;
	var $mSummary, $mCategories, $mLangLinks;

	/*private */
	var $mTitleObj, $mArticle;

	/*private*/
	var $mIsWikiHow, $mIsNew;

	private function __construct() {
		$this->mSteps = '';
		$this->mTitle = '';
		$this->mSummary = '';
		$this->mIsWikiHow = true;
		$this->mIsNew = true;
		$this->mCategories = array();
		$this->section_array = array();
		$this->section_ids = array();
		$this->mLangLinks = '';
		$this->mLoadText = '';
	}

	public static function newFromCurrent() {
		global $wgArticle;
		static $current = null;

		if ( !$current ) {
			$current = self::newFromArticle( $wgArticle );
		}
		return $current;
	}

	public static function newFromTitle( $title ) {
		$article = new Article( $title );
		return self::newFromArticle( $article );
	}

	public static function newFromArticle( $article ) {
		if ( !$article ) {
			return null;
		}

		$whow = new WikiHow();
		$whow->mArticle = $article;
		$whow->mTitleObj = $article->getTitle();

		// parse the article
		$text = $whow->mArticle->getContent( true );
		$whow->loadFromText( $text );

		// set the title
		$whow->mTitle = $whow->mTitleObj->getText();

		return $whow;
	}

	public function newFromText( $text ) {
		$whow = new WikiHow();
		$whow->loadFromText( $text );
		return $whow;
	}

	private function loadFromText( $text ) {
		global $wgContLang;

		$this->mLoadText = $text;

		$categoryName = $wgContLang->getNSText( NS_CATEGORY );
		// extract the category if there is one
		// TODO: make this an array

		$this->mCategories = array();

		// just extract 1 category for now
		//while ($index !== false && $index >= 0) { // fix for multiple categories
		preg_match_all( "/\[\[" . $categoryName . ":[^\]]*\]\]/im", $text, $matches );
		foreach( $matches[0] as $cat ) {
			$cat = str_replace( '[[' . $categoryName . ':', '', $cat );
			$cat = trim( str_replace( ']]', '', $cat ) );
			$this->mCategories[] = $cat;
			$text = str_replace( '[[Category:' . $cat . ']]', '', $text );
		}
		// extract interlanguage links
		$matches = array();
		if ( preg_match_all( '/\[\[[a-z][a-z]:.*\]\]/', $text, $matches ) ) {
			foreach ( $matches[0] as $match ) {
				$text = str_replace( $match, '', $text );
				$this->mLangLinks .= "\n" . $match;
			}
		}
		$this->mLangLinks = trim( $this->mLangLinks );

		// get the number of sections
		$sectionCount = self::getSectionCount( $text );

		$found_summary = false;
		for ( $i = 0; $i < $sectionCount; $i++ ) {
			$section = Article::getSection( $text, $i );
			$title = self::getSectionTitle( $section );
			$section = trim( preg_replace( "@^==.*==@", '', $section ) );
			$title = strtolower( $title );
			$title = trim( $title );
			if ( $title == '' && !$found_summary ) {
				$this->section_array['summary'] = $section;
				$this->section_ids['summary'] = $i;
				$found_summary = true;
			} else {
				$orig = $title;
				$counter = 0;
				while ( isset( $section_array[$title] ) ) {
					$title = $orig + $counter;
				}
				$title = trim( $title );
				$this->section_array[$title] = $section;
				$this->section_ids[$title] = $i;
			}
		}

		// set the steps
		// AA $index = strpos($text, "== Steps ==");
		// AA if (!$index) {
		if ( $this->hasSection( 'steps' ) == false ) {
			$this->mIsWikiHow = false;
			return;
		}

		$this->mSummary = $this->getSection( 'summary' );
		$this->mSteps = $this->getSection( wfMsg( 'steps' ) );

		// TODO: get we get tips and warnings from getSection?
		$this->mIsNew = false;
	}

	/**
	 * used by formatWikiText below
	 */
	private static function formatBulletList( $text ) {
		$result = '';
		if ( $text == null || $text == '' ) {
			return $result;
		}
		$lines = explode( "\n", $text );
		if ( !is_array( $lines ) ) {
			return $result;
		}
		foreach( $lines as $line ) {
			if ( strpos( $line, '*' ) === 0 ) {
				$line = substr( $line, 1 );
			}
			$line = trim( $line );
			if ( $line != '' ) {
				$result .= "*$line\n";
			}
		}
		return $result;
	}

	/**
	 * Returns the index of the given section
	 * returns -1 if not known
	 */
	public function getSectionNumber( $section ) {
		$section = strtolower( $section );
		if ( !empty( $this->section_ids[$section] ) ) {
			return $this->section_ids[$section];
		} else {
			return -1;
		}
	}

	/* SET */
	private function setArticle( $article ) {
		$this->mArticle = $article;
	}

	private function setSteps( $steps ) {
		$this->mSteps = $steps;
	}

	private function setTitle( $title ) {
		$this->mTitle = $title;
	}

	private function setSummary( $summary ) {
		$this->mSummary = $summary;
	}

	private function setCategoryString( $categories ) {
		$this->mCategories = explode( ',', $categories );
	}

	public function getLangLinks() {
		return $this->mLangLinks;
	}

	private function setLangLinks( $links ) {
		$this->mLangLinks = $links;
	}

	/* GET */
	public function getSteps( $forEditing = false ) {
		return str_replace( "\n\n", "\n", $this->mSteps );
	}

	public function getTitle() {
		return $this->mTitle;
	}

	public function getSummary() {
		return $this->mSummary;
	}

	/**
	 * This function is used in places where the intro is shown to help
	 * in various backend tools (Intro Image Adder, Video Adder, etc)
	 * This removes all images for these tools.
	 */
	public static function removeWikitext( $text ) {
		global $wgParser, $wgTitle, $wgContLang;

		$fileNamespaceName = $wgContLang->getNsText( NS_FILE );

		// remove all images
		$text = preg_replace( "@\[\[(:?Image|$fileNamespaceName):[^\]]*\]\]@im", '', $text );

		// then turn wikitext into HTML
		$options = new ParserOptions();
		$text = $wgParser->parse( $text, $wgTitle, $options )->getText();

		// need to remove all <pre></pre> tags (not sure why they sometimes get added
		$text = preg_replace( '/\<pre\>/i', '', $text );
		$text = preg_replace( '/\<\/pre\>/i', '', $text );

		return $text;
	}

	// DEPRECATED -- used only in EditPageWrapper.php
	public function getCategoryString() {
		$s = '';
		foreach ( $this->mCategories as $cat ) {
			$s .= $cat . '|';
		}
		return $s;
	}

	// USE OF THIS METHOD IS DEPRECATED
	// it munges the wikitext too much and can't handle alt methods
	// BuildWikiHow.body.php and EditPageWrapper.php are the only files
	// that should use it
	public function formatWikiText() {
		$text = $this->mSummary . "\n";

		// move all categories to the end of the intro
		$text = trim( $text );
		foreach ( $this->mCategories as $cat ) {
			$cat = trim( $cat );
			if ( $cat != '' ) {
				$text .= "\n[[Category:$cat]]";
			}
		}

		$ingredients = $this->getSection( 'ingredients' );
		if ( $ingredients != null && $ingredients != '' ) {
			$text .= "\n== " . wfMsg( 'ingredients' ) . " ==\n" . $ingredients;
		}

		$step = explode( "\n", $this->mSteps );
		$steps = '';
		foreach ( $step as $s ) {
			$s = preg_replace( '/^[0-9]*/', '', $s );
			$index = strpos( $s, '.' );
			if ( $index !== false && $index == 0 ) {
				$s = substr( $s, 1 );
			}
			if ( trim( $s ) == '' ) {
				continue;
			}
			$s = trim( $s );
			if ( strpos( $s, '#' ) === 0 ) {
				$steps .= $s . "\n";
			} else {
				$steps .= "#" . $s . "\n";
			}
		}
		$this->mSteps = $steps;

		$text .= "\n== " . wfMsg( 'steps' ) . " ==\n" . $this->mSteps;

		$tmp = $this->getSection( 'video' );
		if ( $tmp != '' ) {
			$text .= "\n== " . wfMsg( 'video' ) . " ==\n" . trim( $tmp ) . "\n";
		}

		// do the bullet sections
		$bullet_lists = array(
			'tips',
			'warnings',
			'thingsyoullneed',
			'related',
			'sources'
		);
		foreach ( $bullet_lists as $b ) {
			$tmp = self::formatBulletList( $this->getSection( $b ) );
			if ( $tmp != '' ) {
				$text .= "\n== " . wfMsg( $b ) . " ==\n" . $tmp;
			}
		}

		$text .= $this->mLangLinks;

		// add the references div if necessary
		if ( strpos( $text, '<ref>' ) !== false ) {
			$rdiv = '{{reflist}}';
			$headline = '== ' . wfMsg( 'sources' ) . ' ==';
			if ( strpos( $text, $headline ) !== false ) {
				$text = trim( $text ) . "\n$rdiv\n";
				//str_replace($headline . "\n", $headline . "\n" . $rdiv . "\n", $text);
			} else {
				$text .= "\n== " . wfMsg( 'sources' ) . " ==\n" . $rdiv . "\n";
			}
		}

		return $text;
	}

	public function getFullURL() {
		return $this->mTitleObj->getFullURL();
	}

	public function getDBkey() {
		return $this->mTitleObj->getDBkey();
	}

	public function isWikiHow() {
		return $this->mIsWikiHow;
	}

	/**
	 * We might want to update this function later to be more comprehensive
	 * for now, if it has == Steps == in it, it's a WikiHow article
	 */
	public static function articleIsWikiHow( $article ) {
		if ( !$article instanceof Article ) {
			return false;
		}
		if ( !$article->mTitle instanceof Title ) {
			return false;
		}
		if ( $article->getTitle()->getNamespace() != NS_MAIN ) {
			return false;
		}
		$text = $article->getContent( true );
		$count = preg_match( '/^==[ ]*' . wfMsg( 'steps' ) . '[ ]*==/mi', $text );
		return $count > 0;
	}

	/**
	 * Returns true if the guided editor can be used on this article.
	 * Iterates over the article's sections and makes sure it contains
	 * all the normal sections.
	 *
	 * DEPRECATED -- used only in includes/Wiki.php to determine if we
	 * can load the article in the guided editor.
	 */
	public static function useWrapperForEdit( $article ) {
		global $wgWikiHowSections;

		$index = 0;
		$foundSteps = 0;
		$text = $article->getContent( true );

		$mw = MagicWord::get( 'forceadv' );
		if ( $mw->match( $text ) ) {
			return false;
		}
		$count = self::getSectionCount( $text );

		// these are the good titles, if we have a section title
		// with a title in this list, the guided editor can't handle it
		$sections = array();
		foreach( $wgWikiHowSections as $s ) {
			$sections[] = wfMsg( $s );
		}

		while ( $index < $count ) {
			$section = $article->getSection( $text, $index );
			$title = self::getSectionTitle( $section );

			if ( $title == wfMsg( 'steps' ) ) {
				$foundSteps = true;
			} elseif ( $title == '' && $index == 0 ) {
				// summary
			} elseif ( !in_array( $title, $sections ) ) {
				return false;
			}
			if ( !$section ) {
				break;
			}
			$index++;
		}

		if ( $index <= 8 ) {
			return $foundSteps;
		} else {
			return false;
		}
	}

	private static function getSectionCount( $text ) {
		// would this be better? :)
		$matches = array();
		preg_match_all( '/^(=+).+?=+|^<h([1-6]).*?>.*?<\/h[1-6].*?>(?!\S)/mi', $text, $matches );
		return count( $matches[0] ) + 1;
	}

	/***
	 * Given a MediaWiki section, such as
	 *  == Steps ===
	 *  1. This is the first step.
	 *  2. This is the second step.
	 *
	 * This function returns 'Steps'.
	 */
	private static function getSectionTitle( $section ) {
		$title = '';
		$index = strpos( trim( $section ), '==' );
		if ( $index !== false && $index == 0 ) {
			$index2 = strpos( $section, '==', $index + 2 );
			if ( $index2 !== false && $index2 > $index ) {
				$index += 2;
				$title = substr( $section, $index, $index2 - $index );
				$title = trim( $title );
			}
		}
		return $title;
	}

	public function hasSection( $title ) {
		$ret = isset( $this->section_array[strtolower( wfMsg( $title ) )] );
		if ( !$ret ) {
			$ret = isset( $this->section_array[$title]);
		}
		return $ret;
	}

	public function getSection( $title ) {
		$title = strtolower( $title );
		if ( $this->hasSection( $title ) ) {
			$ret = $this->section_array[strtolower( wfMsg( $title ) )];
			$ret = empty( $ret ) ? $this->section_array[$title] : $ret;
			return $ret;
		} else {
			return '';
		}
	}

	private function setSection( $title, $section ) {
		$this->section_array[$title] = $section;
	}

	private function setRelatedString( $related ) {
		$r_array = explode( '|', $related );
		$result = '';
		foreach ( $r_array as $r ) {
			$r = trim( $r );
			if ( $r == '' ) {
				continue;
			}
			$result .= '*  [[' . $r . '|' . wfMsg( 'howto', $r ) . "]]\n";
		}
		$this->setSection( 'related', $result );
	}

	// DEPRECATED -- used only in EditPageWrapper.php and BuildWikiHow.body.php
	public static function newFromRequest( $request ) {
		$whow = new WikiHow();
		$steps = $request->getText( 'steps' );
		$tips = $request->getText( 'tips' );
		$warnings = $request->getText( 'warnings' );
		$summary = $request->getText( 'summary' );

		$category = '';
		$categories = '';
		for ( $i = 0; $i < 2; $i++ ) {
			if ( $request->getVal( 'category' . $i, null ) != null ) {
				if ( $categories != '') {
					$categories .= ', ';
				}
				$categories .= $request->getVal( 'category' . $i );
			} elseif ( $request->getVal( 'topcategory' . $i, null ) != null && $request->getVal( 'TopLevelCategoryOk' ) == 'true' ) {
				if ( $categories != '' ) {
					$categories .= ', ';
				}
				$categories .= $request->getVal( 'topcategory' . $i );
			}
		}

		$hidden_cats = $request->getText( 'categories22' );
		if ( $categories == '' && $hidden_cats != '' ) {
			$categories = $hidden_cats;
		}

		$ingredients = $request->getText( 'ingredients' );

		$whow->setSection( 'ingredients', $ingredients );
		$whow->setSteps( $steps );
		$whow->setSection( 'tips', $tips );
		$whow->setSection( 'warnings', $warnings );
		$whow->setSummary( $summary );
		$whow->setSection( 'thingsyoullneed', $request->getVal( 'thingsyoullneed' ) );
		$whow->setLangLinks( $request->getVal( 'langlinks' ) );

		$related_no_js = $request->getVal( 'related_no_js' );
		$no_js = $request->getVal( 'no_js' );

		if ( $no_js != null && $no_js == true ) {
			$whow->setSection( 'related', $related_no_js );
		} else {
			// user has JavaScript
			$whow->setRelatedString( $request->getVal( 'related_list' ) );
		}

		$whow->setSection( 'sources', $request->getVal( 'sources' ) );
		$whow->setSection( 'video', $request->getVal( 'video' ) );
		$whow->setCategoryString( $categories );
		return $whow;
	}

	/**
	 * Convert wikitext to plain text
	 *
	 * @param    text  The wikitext
	 * @param    options An array of options that you would like to keep in the text
	 *				"category": Keep category tags
	 *				"image": Keep image tags
	 *				"internallinks": Keep internal links the way they are
	 *				"externallinks": Keep external links the way they are
	 *				"headings": Keep the headings tags
	 *				"templates": Keep templates
	 *				"bullets": Keep bullets
	 * @return     text
	 */
	public function textify( $text, $options = array() ) {
		// take out category and image links
		$tags = array();
		if ( !isset( $options['category'] ) ) {
			$tags[] = 'Category';
		}
		if ( !isset( $options['image'] ) ) {
			$tags[] = 'Image';
		}
		$text = preg_replace( "@^#[ ]*@m", '', $text );
		foreach ( $tags as $tag ) {
			$text = preg_replace( "@\[\[{$tag}:[^\]]*\]\]@", '', $text );
		}

		// take out internal links
		if ( !isset( $options['internallinks'] ) ) {
			preg_match_all( "@\[\[[^\]]*\|[^\]]*\]\]@", $text, $matches );
			foreach ( $matches[0] as $m ) {
				$n = preg_replace( "@.*\|@", '', $m );
				$n = preg_replace( "@\]\]@", '', $n );
				$text = str_replace( $m, $n, $text );
			}

			// internal links with no alternate text
			$text = preg_replace( "@\]\]|\[\[@", '', $text );
		}

		// external links
		if ( isset( $options['remove_ext_links'] ) ) {
			// for [http://google.com proper links]
			$text = preg_replace( "@\[[^\]]*\]@", '', $text );
			// for http://www.inlinedlinks.com
			$text = preg_replace( "@http://[^ |\n]*@", '', $text );
		} elseif ( !isset( $options['externallinks'] ) ) {
			// take out internal links
			preg_match_all( "@\[[^\]]*\]@", $text, $matches );
			foreach ( $matches[0] as $m ) {
				$n = preg_replace( "@^[^ ]*@", '', $m );
				$n = preg_replace( "@\]@", '', $n );
				$text = str_replace( $m, $n, $text );
			}
		}

		// headings tags
		if ( !isset( $options['headings'] ) ) {
			$text = preg_replace( "@^[=]+@m", '', $text );
			$text = preg_replace( "@[=]+$@m", '', $text );
		}

		// templates
		if ( !isset( $options['templates'] ) ) {
			$text = preg_replace( "@\{\{[^\}]*\}\}@", '', $text );
		}

		// bullets
		if ( !isset( $options['bullets'] ) ) {
			$text = preg_replace("@^[\*|#]*@m", '', $text );
		}

		// leading space
		$text = preg_replace( "@^[ ]*@m", '', $text );

		// kill HTML
		$text = strip_tags( $text );

		return trim( $text );
	}

	public static function getCurrentParentCategories() {
		global $wgTitle, $wgMemc;

		$cacheKey = wfMemcKey( 'parentcats', $wgTitle->getArticleId() );
		$cats = $wgMemc->get( $cacheKey );
		if ( $cats ) {
			return $cats;
		}

		$cats = $wgTitle->getParentCategories();

		$wgMemc->set( $cacheKey, $cats );
		return $cats;
	}

	public static function getCurrentParentCategoryTree() {
		global $wgTitle, $wgMemc;

		$cacheKey = wfMemcKey( 'parentcattree', $wgTitle->getArticleId() );
		$cats = $wgMemc->get( $cacheKey );
		if ( $cats ) {
			return $cats;
		}

		$cats = $wgTitle->getParentCategoryTree();

		$wgMemc->set( $cacheKey, $cats );
		return $cats;
	}
}
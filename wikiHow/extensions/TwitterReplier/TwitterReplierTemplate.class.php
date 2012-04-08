<?php
/**
 * @file
 * @author Piotr Molski <moli@wikia.com>
 * @author Krzysztof Krzyżaniak <eloy@wikia-inc.com>
 *
 * TwitterReplierTemplate class for easy mixing of HTML/JavaScript/CSS/PHP code
 * for the TwitterReplier extension.
 * Yes, this is a renamed version of the EasyTemplate class with
 * TwitterReplierTemplate's features integrated into it because I was too lazy
 * to rewrite everything using QuickTemplate.
 */

/**
 * ideas taken from Template class by
 * Copyright (c) 2003 Brian E. Lozier (brian@massassi.net)
 *
 * set_vars() method contributed by Ricardo Garcia (Thanks!)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

class TwitterReplierTemplate {

	private static $path = '';

	public $mPath, $mVars;

	/**
	 * public constructor
	 */
	public function __construct( $path = '' ) {
		if ( !empty( $path ) ) {
			$this->mPath = rtrim( $path, '/' );
		} else {
			$this->mPath = self::$path;
		}
		$this->mVars = array();
	}

	/**
	 * Set a bunch of variables at once using an associative array.
	 *
	 * @param $vars Array: array of vars to set
	 * @param $clear Boolean: whether to completely overwrite the existing vars
	 * @return void
	 */
	public function set_vars( $vars, $clear = false ) {
		if( $clear ) {
			$this->mVars = $vars;
		} else {
			$this->mVars = is_array( $vars )
				?  array_merge( $this->mVars, $vars )
				:  array_merge( $this->mVars, array() );
		}
	}

	/**
	 * Open, parse, and return the template file.
	 *
	 * @param $file String: the template file name
	 * @return String
	 */
	public function execute( $file ) {
		wfProfileIn( __METHOD__ );
		if( !strstr( $file, '.tmpl.php' ) ) {
			$file .= '.tmpl.php';
		}

		if ( !empty( $this->mPath ) ) {
			$path = $this->mPath . '/' . $file;
		} else {
			if ( $file{0} != '/' ) {
				throw new Exception( 'Must use EasyTemplate::set_path' );
			} else {
				$path = $file;
			}
		}

		extract( $this->mVars );
		ob_start();
		include( $path );
		$contents = ob_get_clean();

		wfProfileOut( __METHOD__ );
		return $contents;
	}

	/**
	 * Check if template's file exists
	 *
	 * @param $file String: path to file with template
	 * @return Boolean
	 */
	public function template_exists( $file ) {
		if( !strstr( $file, '.tmpl.php' ) ) {
			$file .= '.tmpl.php';
		}
		return file_exists( $this->mPath . '/' . $file );
	}

	public static function set_path( $path ) {
		self::$path = $path;
	}

	/**
	 * utility to create and execute a WH template
	 */
	public static function html( $name, $vars = array() ) {
        $tmpl = new EasyTemplate();
		if ( !empty( $vars ) ) {
			$tmpl->set_vars( $vars );
		}
        return $tmpl->execute( $name );
	}

	/**
	 * Credit: http://www.php.net/time
	 * Calculates the difference between two time stamps
	 *
	 * @param timestamp $time
	 * @param array $opt
	 * @return str
	 */
	public static function formatTime( $time, $opt = array() ) {
		if ( strlen( $time ) > 0 ) {
			// The default values
			$defOptions = array(
				'to' => 0,
				'parts' => 1,
				'precision' => 'second',
				'distance' => true,
				'separator' => ', '
			);
			$opt = array_merge( $defOptions, $opt );
			// Default to current time if no to point is given
			( !$opt['to'] ) && ( $opt['to'] = time() );
			// Init an empty string
			$str = '';
			// To or From computation
			$diff = ( $opt['to'] > $time ) ? $opt['to'] - $time : $time - $opt['to'];
			// An array of label => periods of seconds;
			$periods = array(
				'decade' => 315569260,
				'year' => 31556926,
				'month' => 2629744,
				'week' => 604800,
				'day' => 86400,
				'hour' => 3600,
				'minute' => 60,
				'second' => 1
			);
			// Round to precision
			if ( $opt['precision'] != 'second' ) {
				$diff = round( ( $diff / $periods[$opt['precision']] ) ) * $periods[$opt['precision']];
			}
			// Report the value is 'less than 1 ' precision period away
			( 0 == $diff ) && ( $str = 'less than 1 ' . $opt['precision']);
			// Loop over each period
			foreach ( $periods as $label => $value ) {
				// Stitch together the time difference string
				( ( $x = floor( $diff / $value ) ) && $opt['parts']-- ) && $str .= ( $str ? $opt['separator'] : '' ) . ( $x . ' ' . $label . ( $x > 1 ? 's' : '' ) );
				// Stop processing if no more parts are going to be reported.
				if ( $opt['parts'] == 0 || $label == $opt['precision'] ) {
					break;
				}
				// Get ready for the next pass
				$diff -= $x * $value;
			}

			$opt['distance'] && $str.= ( $str && $opt['to'] > $time ) ? ' ago' : ' away';

			return $str;
		}
	}
}
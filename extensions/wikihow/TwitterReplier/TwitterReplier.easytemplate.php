<?php

//TODO: fix file name
class TwitterReplierTemplate extends EasyTemplate
{

	function __construct( $path = null )
	{
		parent::__construct( $path );
		//self::set_path( dirname( __FILE__ ) );
	}

	public static function linkJs( $fileName )
	{
		$tmpl = new TwitterReplierTemplate( dirname( __FILE__ ) );

		return '<script type="text/javascript" src="/extensions/wikihow/TwitterReplier/' . $fileName . '"></script>' . PHP_EOL;
	}

	public static function linkCss( $fileName, $noCache = false )
	{
		$tmpl = new TwitterReplierTemplate( dirname( __FILE__ ) );
		$timestamp = '';

		if ( $noCache ) {
			$timestamp = '?' . filemtime( dirname( __FILE__ ) . '/' . $fileName );
		}
		return '<style type="text/css" media="all">/*<![CDATA[*/ @import "/extensions/wikihow/TwitterReplier/' . $fileName . $timestamp . '"; /*]]>*/</style>' . PHP_EOL;
	}

	/**
	 * Credit: http://www.php.net/time
	 * Calculates the difference between two time stamps
	 *
	 * @param timestamp $time
	 * @param array $opt
	 * @return str
	 */
	public static function formatTime( $time, $opt = array( ) )
	{
		if ( strlen( $time ) > 0 ) {
			// The default values
			$defOptions = array(
				'to' => 0,
				'parts' => 1,
				'precision' => 'second',
				'distance' => TRUE,
				'separator' => ', '
			);
			$opt = array_merge( $defOptions, $opt );
			// Default to current time if no to point is given
			(!$opt['to']) && ($opt['to'] = time());
			// Init an empty string
			$str = '';
			// To or From computation
			$diff = ($opt['to'] > $time) ? $opt['to'] - $time : $time - $opt['to'];
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
			if ( $opt['precision'] != 'second' )
				$diff = round( ($diff / $periods[$opt['precision']] ) ) * $periods[$opt['precision']];
			// Report the value is 'less than 1 ' precision period away
			(0 == $diff) && ($str = 'less than 1 ' . $opt['precision']);
			// Loop over each period
			foreach ( $periods as $label => $value ) {
				// Stitch together the time difference string
				(($x = floor( $diff / $value )) && $opt['parts']--) && $str.= ( $str ? $opt['separator'] : '') . ($x . ' ' . $label . ($x > 1 ? 's' : ''));
				// Stop processing if no more parts are going to be reported.
				if ( $opt['parts'] == 0 || $label == $opt['precision'] )
					break;
				// Get ready for the next pass
				$diff -= $x * $value;
			}

			$opt['distance'] && $str.= ( $str && $opt['to'] > $time) ? ' ago' : ' away';

			return $str;
		}
	}

}
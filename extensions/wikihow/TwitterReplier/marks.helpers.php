<?php

/**
 * Used for debugging, will display a array in a preformated way, can add optional header
 *
 * @param array $array
 * @param string $header
 */
function displayArray( $array, $header = null, $returnString = false )
{
	$s = '<div style="position:relative;z-index:2;background-color:white;color:black">';
	if( isset( $header ) ) {
		$s .= "<h2>$header</h2>";
	}
	$s .= "<pre>";
	$s .= print_r( $array, true );
	$s .= "</pre>";
	$s .= '</div>' . PHP_EOL;
	if( $returnString ) {
		return $s;
	}
	else {
		echo $s;
	}
}

function el( $value, $header = '' )
{
	$path = dirname( __FILE__ );
	error_log( "\n" . '#### ' . $header . ' ####' . "\n", 3, $path . '/php_error_log' );
	error_log( print_r( $value, true ) . "\n", 3, $path . '/php_error_log' );
}
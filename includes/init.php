<?php
namespace JTM\Crypto;
define( 'CRYP_ROOT', dirname( dirname( __FILE__ ) ) . '/' );


spl_autoload_register( function ( $class_name ) {
	$class_file = strtolower( $class_name ) . '.php';
	$class_file = str_replace( 'jtm\\crypto\\', '', $class_file );	//	Remove JTM\Crypto part of namespace
	
	$filename = CRYP_ROOT . 'includes/' . str_replace( '\\', '/', $class_file );
	
	if( is_readable( $filename ) ) {
		require_once $filename; 
	}
} );

Log::start_log_session();
Log::info( 'Responding to request for ' . ( is_secure() ? "https" : "http" ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ' from ' . $_SERVER['REMOTE_ADDR'] . ' (USER AGENT: "' . $_SERVER['HTTP_USER_AGENT'] . '")' );





/**
 * Adds a trailing slash to a string (e.g. file path) if one does not already exist.
 *
 * @param string $str_to_slash The string to add trailing slash to.
 * @return string The slashed string.
 */
function trailingslashit( string $str_to_slash ) {
	return untrailingslashit( $str_to_slash ) . '/';
}
/**
 * Removes any trailing slashes from the end of the provided string.
 *
 * @param string $str_to_deslash The string to remove slashes from.
 * @return string The deslashed string.
 */
function untrailingslashit( string $str_to_deslash ) {
	return rtrim( $str_to_deslash, '/\\' );
}
/**
 * Determines whether the request is using a secure connection using the HTTP request data. DO NOT
 * DEPEND ON FOR ACCURACY FOR SECURITY PURPOSES.
 *
 * @return boolean TRUE if secure. FALSE if not.
 */
function is_secure() {
	return ( !empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' );
}
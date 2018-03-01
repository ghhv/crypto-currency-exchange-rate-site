<?php
namespace JTM\Crypto;


class Log {
	protected static $our_log_file_name = 'log.txt';
	protected static $our_eol = "\r\n\r\n";
	protected static $our_delimiter = ' ::: ';
	protected static $our_timezone = 'Europe/London';	//	UTC
	protected static $our_timezone_short = 'UTC';

	public static function info( string $msg ) {
		return static::log_entry( 'INFO', $msg );
	}
	public static function error( string $msg ) {
		return static::log_entry( 'ERROR', $msg );
	}
	public static function perf( string $msg ) {
		return static::log_entry( 'PERF', $msg );
	}


	public static function log_entry( string $level_label, string $msg ) {
		// $D = new \DateTime( 'now', new \DateTimeZone( static::$our_timezone ) );
		// $dt = $D->format( 'Y-m-d H:i:s' ) . ' ('. static::$our_timezone_short . ')';

		return static::prepend( /*$dt . static::$our_delimiter . */$level_label . static::$our_delimiter . $msg );
	}

	public static function start_log_session() {
		static::prune_log_file();

		$D = new \DateTime( 'now', new \DateTimeZone( static::$our_timezone ) );
		$dt = $D->format( 'Y-m-d H:i:s' ) . ' ('. static::$our_timezone_short . ')';

		static::prepend( '^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^' . static::$our_eol . static::$our_eol );
		static::prepend( '   ' . $dt . '   ' );
		static::prepend( static::$our_eol . '^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^' );
	}
	
	
	
	
	
	protected static function prepend( string $msg ) {
		touch( static::get_path_to_log_file() );
		$data = file_get_contents( static::get_path_to_log_file() );
		return file_put_contents( static::get_path_to_log_file(), $msg . static::$our_eol . $data );
	}

	protected static function append( string $msg ) {
		$fn = static::get_path_to_log_file();
		$fh = fopen( $fn, 'a' );

		fwrite( $fh, $msg . static::$our_eol );

		fclose( $fh );
	}

	protected static function prune_log_file( int $max_file_size = 100000 ) {
		$fn = static::get_path_to_log_file();
		
		if( file_exists( $fn ) ) {
			$fh = fopen( $fn, 'r+' );
		
			ftruncate( $fh, $max_file_size );

			fclose( $fh );
		}
	}

	protected static function open_log_file() {
		$fn = static::get_path_to_log_file();		
		return fopen( $fn, 'r+' );
	}


	public static function get_path_to_log_file( string $log_dir_relative_to_this_file = '../' ) {
		return trailingslashit( dirname( __FILE__ ) ) . $log_dir_relative_to_this_file . static::$our_log_file_name;
	}
}
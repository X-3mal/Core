<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 08.12.2014
 * Time: 10:23
 */

namespace DB;


class Debugger {

	static private $enabled = false;
	private        $dir;
	private        $queries = array ();
	private        $cached  = array ();

	public function __construct ( $dir = null ) {
		$this->dir = \Config::home() . 'logs/db/';
	}

	static function enabled () {
		return static::$enabled;
	}

	static function enable () {
		static::$enabled = true;
	}

	static function disable () {
		static::$enabled = false;
	}

	private function filename () {
		return isset( $this->cached[ __FUNCTION__ ] ) ? $this->cached[ __FUNCTION__ ] : date( 'Y-m-d' );
	}

	function add_args ( $args, $time ) {
		if ( !self::enabled() ) {
			return;
		}
		$query = array_shift( $args );
		$cnt   = 0;
		foreach ( $args as $arg ) {
			if ( is_array( $arg ) ) {
				$cnt += count( $arg );
			} else {
				$cnt++;
			}
		}
		$query            = preg_replace( '/\d+/', '%d', $query );
		$query            = preg_replace( '/\s+/', ' ', $query );
		$query            = trim( $query );
		$query            = mb_substr( $query, 0, 200 );
		$this->queries[ ] = json_encode( array (
											 'q' => $query,
											 'a' => $cnt,
											 'm' => $time * 1000,
											 't' => time(),
										 )
		);
	}

	function get () {
		return implode( PHP_EOL, $this->queries ) . PHP_EOL;
	}

	function write ( $db_name ) {
		$dir = $this->dir . $db_name . '/';
		if ( !file_exists( $dir ) ) {
			mkdir( $dir, 0777 );
			chmod( $dir, 0777 );
		}
		$f_exists = file_exists( $dir . $this->filename() );
		$fp       = fopen( $dir . $this->filename(), 'a' );
		if ( $this->queries ) {
			fwrite( $fp, $this->get() );
		}
		fclose( $fp );
		if ( !$f_exists ) {
			chmod( $dir . $this->filename(), 0777 );
		}
	}
} 
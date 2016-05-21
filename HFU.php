<?php

class HFU {

	static private $args        = array();
	static private $names       = array();
	static private $names_stack = array();
	static private $path        = '';

	static function args( $key, $url = false ) {
		if( empty ( self::$args[ $key ] ) ) {
			return '';
		}

		if( $url ) {
			return '-' . implode( '-', self::$args[ $key ] );
		} else {
			return self::$args[ $key ];
		}
	}

	static function arg( $key, $index, $default = '' ) {
		return isset ( self::$args[ $key ][ $index ] ) ? self::$args[ $key ][ $index ] : $default;
	}

	static function module_url() {
		if( in_array( self::path(), array( '/error/403', '/error/404' ) ) ) {
			return self::path();
		}

		return preg_replace( '/([\/a-zA-Z0-9_]*)\/.*/', '$1', Input::server( 'REQUEST_URI' ) );
	}

	static function next( $default = '' ) {
		$key = array_shift( self::$names_stack );
		if( $key || empty ( $default ) ) {
			return $key;
		}

		self::add( $default );
		return array_shift( self::$names_stack );
	}

	static function next_no_shift( $default = '' ) {
		$lookup = self::lookup();
		return !empty( $lookup ) ? $lookup : $default;
	}

	static function url( $key ) {
		return $key . '-' . implode( '-', self::$args[ $key ] );
	}

	static function clear() {
		self::$args        = array();
		self::$names       = array();
		self::$names_stack = array();
		self::$path        = "";
	}

	static public function set( $path ) {
		self::clear();
		self::add( $path );
	}

	static function add( $path ) {
		$args = explode( "/", $path );
		foreach( $args as $arg ) {
			if( empty ( $arg ) ) {
				continue;
			}

			self::$path .= "/" . $arg;

			//$data = explode ( "-", $arg );
			$data               = array( $arg );
			$key                = array_shift( $data );
			self::$args[ $key ] = $data;
			array_push( self::$names_stack, $key );
			array_push( self::$names, $key );
		}
	}

	static function path( $value = false, $full = true ) {
		if( $value === false ) {
			return self::$path;
		} elseif( $full ) {
			return self::$path === $value;
		} else {
			return mb_substr( self::$path, 0, mb_strlen( $value ) ) == $value;
		}
	}

	static function fullpath() {
		return 'http://' . ( !empty ( $_SERVER[ 'HTTP_HOST' ] ) ? $_SERVER[ 'HTTP_HOST' ] : 'popunder.ru' ) . self::$path;
	}

	static function route( $length = false ) {
		if( empty ( $length ) ) {
			return implode( '/', self::$names );
		} else {
			return implode( '/', array_slice( self::$names, 0, $length ) );
		}
	}

	static function name( $index, $path = '', $default = '' ) {
		if( !empty ( $path ) && ( substr( self::$path, 1, strlen( $path ) ) !== $path ) ) {
			return $default;
		}
		return isset( self::$names[ $index ] ) ? self::$names[ $index ] : $default;
	}

	static function lookup( $name = false, $true = true, $false = false ) {
		if( $name === false ) {
			return reset( self::$names_stack );
		} elseif( is_array( $name ) ) {
			return in_array( reset( self::$names_stack ), $name ) ? $true : $false;
		} else {
			return reset( self::$names_stack ) == $name ? $true : $false;
		}
	}

	static function check( $url_part ) {
		return strpos( self::$path, $url_part ) !== false;
	}

}

HFU::set( parse_url( Input::server( 'REQUEST_URI' ), PHP_URL_PATH ) );

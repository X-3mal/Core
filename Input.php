<?php

class Input {

	static private $get_html_safe = false;
	static private $post_html_safe = false;

	static function &custom ( &$array, $key, $default = false ) {
		if ( isset ( $array[ $key ] ) ) {
			return $array[ $key ];
		} else {
			return $default;
		}
	}

	static function server ( $key, $default = false ) {
		return isset ( $_SERVER[ $key ] ) ? $_SERVER[ $key ] : $default;
	}

	static function domain () {
		return self::server ( 'HTTP_HOST', '' );
	}

	/**
	 * @param                       $key
	 * @param bool|array|int|string $default
	 * @return bool|array|int|string
	 */
	static function post ( $key, $default = false ) {
		return isset ( $_POST[ $key ] ) ? $_POST[ $key ] : $default;
	}

	/**
	 * @param                       $key
	 * @param bool|array|int|string $default
	 * @return bool|array|int|string
	 */
	static function get ( $key, $default = false ) {
		return isset ( $_GET[ $key ] ) ? $_GET[ $key ] : $default;
	}

	/**
	 * @param                       $key
	 * @param bool|array|int|string $default
	 * @return bool|array|int|string
	 */
	static function post_get ( $key, $default = false ) {
		return self::post ( $key, self::get ( $key, $default ) );
	}

	/**
	 * @param                       $key
	 * @param bool|array|int|string $default
	 * @return bool|array|int|string
	 */
	static function get_post ( $key, $default = false ) {
		return self::get ( $key, self::post ( $key, $default ) );
	}

	static function post_get_set ( $key ) {
		return self::get_set ( $key ) || self::post_set ( $key );
	}

	static function post_set ( $key ) {
		return isset ( $_POST[ $key ] );
	}

	static function get_set ( $key ) {
		return isset ( $_GET[ $key ] );
	}

	static function file ( $name, $key = 'tmp_name' ) {
		return isset ( $_FILES[ $name ][ $key ] ) ? $_FILES[ $name ][ $key ] : '';
	}

	static function param ( $array, $key, $default = '' ) {
		return isset ( $array[ $key ] ) ? $array[ $key ] : $default;
	}

	static function referer ( $default = '' ) {
		return self::server ( 'HTTP_REFERER', $default );
	}

	static function url ( $default = '' ) {
		return self::server ( 'REQUEST_URI', $default );
	}

	static function host ( $default = '' ) {
		return self::server ( 'HTTP_HOST', $default );
	}

	static function first_not_empty () {
		$args = func_get_args ();
		foreach ( $args as $arg ) {
			if ( !empty( $arg ) )
				return $arg;
		}
		return false;
	}

	static function array_except ( $array, $except ) {
		if ( is_scalar ( $except ) ) {
			$except = array( $except );
		}
		foreach ( $except as $key ) {
			if ( isset ( $array[ $key ] ) ) {
				unset ( $array[ $key ] );
			}
		}
		return $array;
	}

	static function iconv ( $object, $from = 'windows-1251//IGNORE' ) {
		if ( is_array ( $object ) ) {
			foreach ( $object as $key => $val ) {
				$object[ $key ] = self::iconv ( $val, $from );
			}
			return $object;
		} else {
			return iconv ( $from, "UTF-8", $object );
		}
	}

	static function htmlspecial ( $object ) {
		if ( is_array ( $object ) ) {
			foreach ( $object as $key => $val ) {
				$object[ $key ] = self::htmlspecial ( $val );
			}
			return $object;
		} else {
			return htmlspecialchars ( $object );
		}
	}

	static function parse_safe_get () {
		if ( self::$get_html_safe ) {
			return;
		}
		$_GET                = self::htmlspecial ( $_GET );
		self::$get_html_safe = true;
	}

	static function parse_safe_post () {
		if ( self::$post_html_safe ) {
			return;
		}
		$_POST                = self::htmlspecial ( $_POST );
		self::$post_html_safe = true;
	}

	static function parse_safe () {
		self::parse_safe_get ();
		self::parse_safe_post ();
	}

	static function etag () {
		return self::server ( 'HTTP_IF_NONE_MATCH' );
	}

	static function argv ( $key, $default = null ) {
		return isset( $argv[ $key ] ) ? $argv[ $key ] : $default;
	}


}
<?php

class Cookie {

	static private function _set ( $name, $value, $time, $path = '/', $domain = null ) {
		if ( is_scalar ( $value ) ) {
			setcookie ( $name, $value, $time, $path, $domain );
		} elseif ( is_array ( $value ) ) {
			foreach ( $value as $k => &$v ) {
				self::_set ( $name . '[' . $k . ']', $v, $time, $path, $domain );
			}
			unset( $v );
		}
	}

	static private function _clear ( $name, $path = '/', $domain = null, &$cookie = null ) {
		if ( is_null ( $cookie ) ) {
			if ( !isset( $_COOKIE[ $name ] ) ) {
				return;
			}
			$cookie = & $_COOKIE[ $name ];
		}

		if ( is_scalar ( $cookie ) ) {
			setcookie ( $name, null, 0, $path, $domain );
		} elseif ( is_array ( $cookie ) ) {
			foreach ( $cookie as $k => &$v ) {
				self::_clear ( $name . '[' . $k . ']', $path, $domain, $v );
			}
			unset( $v );

			setcookie ( $name, null, 0, $path, $domain );
		}
	}

	static function set ( $name, $value, $time = null, $path = '/', $domain = null ) {

		if ( !is_null ( $time ) ) {
			$time = time () + $time;
		}

		if ( is_scalar ( $name ) ) {
			self::_clear ( $name, $path, $domain );
			self::_set ( $name, $value, $time, $path, $domain );

			$_COOKIE[ $name ] = $value;
		} else {
			$key    = '';
			$cookie = & $_COOKIE;
			foreach ( $name as $k ) {
				$key .= $key ? '[' . $k . ']' : $k;
				$cookie = & $_COOKIE[ $k ];
			}

			self::_clear ( $key, $path, $domain );
			self::_set ( $key, $value, $time, $path, $domain );

			$cookie = $value;
		}
	}

	static function set_local ( $name, $value, $time = null ) {
		if ( !is_null ( $time ) ) {
			$time = time () + $time;
		}

		if ( is_scalar ( $name ) ) {
			self::_clear ( $name, null );
			self::_set ( $name, $value, $time, null );

			$_COOKIE[ $name ] = $value;
		} else {
			$key    = '';
			$cookie = & $_COOKIE;
			foreach ( $name as $k ) {
				$key .= $key ? '[' . $k . ']' : $k;
				$cookie = & $_COOKIE[ $k ];
			}

			self::_clear ( $key, null );
			self::_set ( $key, $value, $time, null );

			$cookie = $value;
		}
	}

	static function clear ( $name, $path = '/', $domain = null ) {
		if ( is_scalar ( $name ) ) {
			self::_clear ( $name, $path, $domain );

			unset( $_COOKIE[ $name ] );
		} else {
			$key    = '';
			$cookie = & $_COOKIE;
			$last   = array_pop ( $name );
			foreach ( $name as $k ) {
				$key .= $key ? '[' . $k . ']' : $k;
				$cookie = & $_COOKIE[ $k ];
			}

			self::_clear ( $key, $path, $domain );

			unset( $cookie[ $last ] );
		}
	}

	static function clear_local ( $name ) {
		if ( is_scalar ( $name ) ) {
			self::_set ( $name, null, 1 );
			$_COOKIE[ $name ] = null;
		} else {
			$key    = '';
			$cookie = & $_COOKIE;
			$last   = array_pop ( $name );
			foreach ( $name as $k ) {
				$key .= $key ? '[' . $k . ']' : $k;
				$cookie = & $_COOKIE[ $k ];
			}

			self::_clear ( $key, null );

			unset( $cookie[ $last ] );
		}
	}

	static function get ( $name, $default = null ) {
		if ( isset( $_COOKIE[ $name ] ) ) {
			return $_COOKIE[ $name ];
		} else {
			return $default;
		}
	}

	static function token_set () {
		if ( !self::get ( 'token' ) ) {
			self::set ( 'token', md5 ( mt_rand () ), null, '/', URL::glob ()->domain () );
		}
	}

	static function token_get () {
		return self::get ( 'token' );
	}

	static function token_check ( $value ) {
		return self::get ( 'token' ) && ( $value === self::get ( 'token' ) );
	}

}
<?php

class Output {

	static private $head        = '';
	static private $body        = '';
	static private $title       = '';
	static private $description = '';
	static private $keywords    = '';
	static private $favicon;

	static public function js( $filename, $foreign = false ) {
		if( !$foreign ) {
			$filename = $filename . '.js';
			$path     = Config::home() . Config::public_dir() . $filename;
			$time     = file_exists( $path ) ? @filemtime( Config::home() . Config::public_dir() . $filename ) : 123;
			self::$head .= '<script type="text/javascript" src="/' . Config::public_dir() . $filename . '?t=' . $time . '"></script>';
		} else {
			self::$head .= '<script type="text/javascript" src="' . $filename . '"></script>';
		}
	}


	static public function css( $filename ) {
		$time = @filemtime( Config::home() . $filename );
		self::$head .= '<link rel="stylesheet" type="text/css" href="/' . Config::public_dir() . $filename . '?t=' . $time . '"></link>';
	}

	static public function doctype() {
		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	}


	static public function favicon( $filename = 'favicon.ico' ) {
		if( !isset( self::$favicon ) ) {
			self::$favicon = $filename;
			self::$head .= '<link rel="icon" href="/' . Config::public_dir() . $filename . '" type="image/x-icon"/><link rel="shortcut icon" href="/' . $filename . '" type="image/x-icon"/>';
		}
	}

	static public function title( $name = false ) {
		static::$title;

		if( $name === false ) {
			self::$head .= '<title>' . static::$title . '</title>';
		} else {
			if( empty( static::$title ) ) {
				static::$title = '';
			}
			static::$title .= ( static::$title ? ' &raquo; ' : '' ) . $name;
		}
	}

	static function clear_title() {
		static::$title = '';
	}

	static public function keywords( $name = false ) {


		if( $name === false ) {
			self::$head .= '<meta  name="keywords" content="' . static::$keywords . '"/>';
		} else {
			static::$keywords = $name;
		}
	}

	static public function description( $name = false ) {
		if( $name === false ) {
			self::$head .= '<meta name="description" content="' . static::$description . '"/>';
		} else {
			static::$description = $name;
		}
	}

	static public function error( $msg ) {
		if( !empty ( $msg ) ) {
			return '<div class="error">' . $msg . '</div>';
		}
		return false;
	}

	static public function errors( $errors, $messages ) {
		foreach( $errors as $key => $value ) {
			if( $value ) {
				return '<div class="error">' . ( isset ( $messages[ $key ] ) ? $messages[ $key ] : $key ) . '</div>';
			}
		}
		return '';
	}

	static public function encoding( $value = 'utf-8' ) {
		self::$head .= '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
	}

	static public function base() {
		self::$head .= '<base href="' . HFU::fullpath() . '"></base>';
	}

	static public function text( $string ) {
		return htmlspecialchars( $string );
	}

	static function GetParams( $array, $first = '?', $_key = '', $_val = null ) {
		$f   = false;
		$str = '';
		foreach( $array as $key => $val ) {
			if( $key == $_key ) {
				continue;
			}
			if( !$str ) {
				$str = $first;
			} else {
				$str .= '&';
			}
			$str .= $key . '=' . $val;
		}
		if( $_key && !is_null( $_val ) ) {
			if( !$str ) {
				$str = $first;
			} else {
				$str .= '&';
			}
			$str .= $_key . '=' . $_val;
		}
		return $str;
	}

	static public function pages( $number, $count, $margin ) {
		if( $count < $margin * 2 + 1 ) {
			return range( 1, $count );
		}

		$number = min( $count, max( $number, 1 ) );

		$min = $number - $margin;
		$max = $number + $margin;
		if( $min < 1 ) {
			$max += 1 - $min;
			$min = 1;
		} elseif( $max > $count ) {
			$min -= $max - $count;
			$max = $count;
		}
		if( $min <= 3 ) {
			$min = 1;
		}
		if( $max >= $count - 2 ) {
			$max = $count;
		}
		$result = range( $min, $max );
		if( $min > 3 ) {
			array_unshift( $result, 1, 'prev' );
		}

		if( $max < $count - 2 ) {
			array_push( $result, 'next', $count );
		}

		return $result;
	}

	static public function head() {
		self::encoding();
		self::title();
		self::description();
		self::keywords();
		self::favicon();
		self::base();
		return self::$head;
	}

	static public function body( $content = false ) {
		if( $content === false ) {
			return self::$body;
		} else {
			return self::$body = &$content;
		}
	}

	static public function json( $data, $die = true ) {
		header( 'Content-type: text/plain' );
		print json_encode( $data );
		if( $die ) {
			die ();
		}
	}

	static function header_javascript() {
		header( 'Content-Type: text/javascript' );
	}

	static function header_json() {
		header( 'Content-Type: application/json' );
	}

	static function header_html() {
		header( 'Content-Type: text/html;charset=UTF-8' );
	}

	static function header_no_cache() {
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
	}

	static function header_image( $type = '' ) {
		header( "Content-type: image" . ( $type ? '/' . $type : '' ) );
	}

	static function checked( $checked ) {
		return $checked ? 'checked="checked"' : '';
	}

	static function selected( $checked ) {
		return $checked ? 'selected="selected"' : '';
	}

	static function disabled( $checked ) {
		return $checked ? 'disabled="disabled"' : '';
	}


	static function header_error( $code, $msg = '' ) {
		header( "HTTP/1.0 " . $code . " " . $msg );
	}

	static public function error404_empty() {
		header( "HTTP/1.0 404 Not Found" );
		header( "Status: 404 Not Found" );
		exit;
	}

	static public function error404() {

		while( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		HFU::set( 'error/404' );
		header( "HTTP/1.0 404 Not Found" );
		header( "Status: 404 Not Found" );
		$params = array();
		$script = new Script ( '', $params );

		print $script->_process();
		die ();
	}

	static public function back( $default = '/' ) {
		self::go( Input::referer( $default ) );
	}

	static public function go( $url, $exit = false ) {
		header( 'Location: ' . $url );
		if( $exit ) {
			exit;
		}
	}

}
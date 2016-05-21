<?php

abstract class Script_Abstract {

	private        $_dir          = "";
	private        $_file         = "";
	private        $_params       = "";
	private static $_token        = false;
	private        $_url          = "";
	private        $_url_args     = "";
	private        $latest_module = '';
	private static $_root         = 'scripts';
	private        $title         = '';
	private        $description   = '';
	private        $keywords      = '';
	private        $controller_result;
	private        $errors        = array();
	private        $response;

	static function root( $path ) {
		self::$_root = $path;
	}

	public function title( $title = null ) {

		if( isset( $title ) ) {
			$this->title = $title;
			Output::title( $title );
		}
		return $this->title;
	}

	public function keywords( $keywords = null ) {
		if( isset( $keywords ) ) {
			$this->keywords = $keywords;
			Output::keywords( $keywords );
		}
		return $this->keywords;
	}

	public function description( $description = null ) {
		if( isset( $description ) ) {
			$this->description = $description;
			Output::description( $description );
		}
		return $this->description;
	}

	static function realpath( $path ) {
		if( empty( $path ) ) {
			$path = self::$_root;
		}

		$path = realpath( $path );
		$root = realpath( self::$_root );

		if( mb_substr( $path, 0, mb_strlen( $root ) ) . '/' !== $root . '/' ) {
			Output::error404();
		}

		return self::$_root . mb_ereg_replace( '\\\\', '/', mb_substr( $path, mb_strlen( $root ) ) );
	}

	public function __construct( $filename, &$params, $url = '', $url_args = '' ) {

		$this->initialize( $filename, $params, $url, $url_args );
	}

	public function controller_result( $default = array() ) {
		return isset( $this->controller_result ) ? $this->controller_result : $default;
	}

	public function initialize( $filename, &$params, $url = '', $url_args = '' ) {
		$filename = self::realpath( $filename );

		\Modules::current();
		/**
		 * TODO: Check Access
		 */

		$p = strrpos( $filename, '/' );
		if( $p !== false ) {
			$p++;
			$this->_dir = substr( $filename, 0, $p );
		}
		$this->_file = substr( $filename, $p );
		$this->_dir .= $this->_file . '/';
		if( !empty( $url ) ) {
			$this->_url      = $url;
			$this->_url_args = $url_args;
		}
		$this->_params = $params;
	}

	public function error( $code = null, $message = null, $data = array() ) {
		if( isset( $code ) ) {
			if( isset( $message ) ) {
				$this->errors[ $code ] = $message;
				$this->response        = array( 'error' => true, 'data' => array_merge( $this->errors, $data ) );
			}

			return isset( $this->errors[ $code ] ) ? $this->errors[ $code ] : null;
		}

		return $this->errors;
	}


	public function response( $value = null ) {
		if( isset( $value ) ) {
			$this->response = $value;
		}

		return $this->response;
	}

	public function response_success( $data = null ) {
		return $this->response( array( 'success' => true, 'data' => $data ) );
	}

	public function response_key( $key, $value = null ) {
		if( isset( $value ) ) {
			if( !is_array( $this->response ) ) {
				$this->response = array( $key => $value );
			} else {
				$this->response[] = $value;
			}
		}

		return $this->response[ $key ];
	}

	abstract protected function _run();

	static $ii = 0;

	public function _process() {

		$filename = $this->_dir . $this->_file;


		ob_start();
		if( Input::post( 'action' ) ) {
			if( ( $controllerClassName = Input::post( 'processor' ) ) && class_exists( $controllerClassName ) ) {
				/* @var $controller Controller */
				$controller              = new $controllerClassName( $_POST );
				$this->controller_result = $controller->process();
				if( $controller->request()->ajax() ) {
					Output::header_json();
					print ( $this->controller_result );
					exit;
				}
			}
		}

		if( is_readable( $filename . ".inc" ) ) {
			$this->_run( $filename . ".inc" );
		}
		if( is_readable( $filename . ".php" ) ) {
			$this->_run( $filename . ".php" );
		}

		if( is_readable( $filename . ".tpl" ) ) {
			$this->_run( $filename . ".tpl" );
		}

		return ob_get_clean();
	}


	public function ajax( $name, $dir = null ) {


		if( is_null( $dir ) ) {
			$filename = $this->_dir . 'ajax.' . $name;
		} else {
			$filename = $dir . '/ajax.' . $name;
		}

		ob_start();
		if( is_readable( $filename . ".php" ) ) {
			$this->_run( $filename . ".php" );
		}

		if( is_readable( $filename . ".tpl" ) ) {
			$this->_run( $filename . ".tpl" );
		}

		return ob_get_clean();
	}

	public function image( $name, $dir = null ) {

		if( is_null( $dir ) ) {
			$filename = $this->_dir . 'image.' . $name;
		} else {
			$filename = $dir . '/image.' . $name;
		}

		ob_start();
		if( is_readable( $filename . ".php" ) ) {
			$this->_run( $filename . ".php" );
		}

		return ob_get_clean();
	}

	public function resource( $name, $dir = null ) {

		if( is_null( $dir ) ) {
			$filename = $this->_dir . 'resource.' . $name;
		} else {
			$filename = $dir . '/resource.' . $name;
		}

		ob_start();
		if( is_readable( $filename . ".php" ) ) {
			$this->_run( $filename . ".php" );
		}

		return ob_get_clean();
	}

	public function execute( $name, $dir = null ) {
		if( is_null( $dir ) ) {
			$filename = $this->_dir . $name . '.inc';
		} else {
			$filename = $dir . '/' . $name . '.inc';
		}

		if( is_readable( $filename ) ) {
			return $this->_run( $filename );
		} else {
			return false;
		}
	}

	public function action( $code, $secure = true ) {

		if( $secure && ( URL::glob()->domain() != URL::ref()->domain() ) ) {
			return false;
		}
		try {
			\DBS::main()->transaction();
			$this->execute( $code );
			\DBS::main()->commit();

			return true;
		} catch( \Exception $e ) {
			$this->error( $e->getCode(), $e->getMessage() );
			\DBS::main()->rollback();

			return false;
		}

	}

	public function render( $name, $dir = null ) {
		if( is_null( $dir ) ) {
			$filename = $this->_dir . $name . '.tpl';
		} else {
			$filename = $dir . '/' . $name . '.tpl';
		}
		ob_start();
		if( is_readable( $filename ) ) {

			$this->_run( $filename );
		}
		return ob_get_clean();
	}

	public function script( $name, $params = array() ) {
		$url = $this->url( $name );
		if( empty( $name ) ) {
			$name = mb_substr( $this->_dir, 0, -1 );
		} elseif( mb_substr( $name, 0, 1 ) == '/' ) {
			$name = self::$_root . $name;
		} else {
			$name = $this->_dir . $name;
		}
		$script = new Script( $name, $params, $url );
		return $script->_process();
	}

	public function cscript( $name, $params = array() ) {
		$script = clone $this;
		$script->initialize( $this->_dir . $name, $params );
		return $script->_process();
	}

	public function subscript( $name, $params = array() ) {

		$old_dir    = $this->_dir;
		$old_file   = $this->_file;
		$old_params = &$this->_params;

		$this->initialize( $this->_dir . $name, $params );

		$result = $this->_process();

		$this->_dir    = $old_dir;
		$this->_file   = $old_file;
		$this->_params = $old_params;

		return $result;
	}

	public function LastModule() {
		return $this->latest_module;
	}

	public function nextscript( $name = '', $params = array() ) {
		$old_dir      = $this->_dir;
		$old_file     = $this->_file;
		$old_params   = &$this->_params;
		$old_url      = $this->_url;
		$old_url_args = $this->_url_args;


		$filename = HFU::next( $name );
		if( !$filename )
			return '';
		$this->latest_module = $filename;
		$next                = $filename;

		$this->_url .= $this->_url_args . "/" . $next;
		$this->_url_args = HFU::args( $next, true );

		while( !is_readable( $this->_dir . $filename . "/" . $next . ".php" ) && !is_readable( $this->_dir . $filename . "/" . $next . ".tpl" ) ) {
			$next = HFU::next();
			if( empty( $next ) ) {
				break;
			}
			$filename .= "/" . $next;
			$this->_url .= $this->_url_args . "/" . $next;
			$this->_url_args = HFU::args( $next, true );
		}

		if( !empty( $next ) ) {

			$this->initialize( $this->_dir . $filename, $params );
			$result = $this->_process();
		} else {
			Output::error404();
			$result = false;
		}

		$this->_dir      = $old_dir;
		$this->_file     = $old_file;
		$this->_params   = $old_params;
		$this->_url      = $old_url;
		$this->_url_args = $old_url_args;

		return $result;
	}

	public function dir() {
		return $this->_dir;
	}


	public function url( $path = '', $query = false, $lang_code = null ) {

		$path = $this->path( $path, false, $query );


		return '/' . ( $lang_code ? $lang_code : Text::lang_code() ) .
		       ( $path && $path[ 0 ] == '/' ? '' : '/' ) . $path;
	}

	public function path( $path = '', $args = false, $query = false ) {
		$url = $this->_url;

		if( !empty( $path ) ) {
			if( $path[ 0 ] == '/' ) {
				$url = $path;
			} else {
				$url .= '/' . $path;
			}

		}

		$url_parts  = mb_split( '/', $url );
		$i          = 0;
		$url_cparts = array();
		foreach( $url_parts as $part ) {
			if( $part == '.' ) {
				$url_cparts = array();
			} elseif( $part == '..' ) {
				array_pop( $url_cparts );
			} else {
				array_push( $url_cparts, $part );
			}
		}

		if( is_array( $query ) ) {
			$query = http_build_query( $query );
		}
		$url = implode( '/', $url_cparts );

		if( $args ) {
			$url .= '-' . $args;
		}

		return $url . ( empty( $query ) ? '' : '?' . $query );
	}

	public function &param( $key, $default = false ) {
		if( isset( $this->_params[ $key ] ) ) {
			return $this->_params[ $key ];
		} else {
			return $default;
		}
	}

	public function &params() {
		return $this->_params;
	}

	public function arg( $index, $default = '' ) {
		return HFU::arg( $this->_file, $index, $default );
	}

	public function js( $filename = "" ) {
		if( empty( $filename ) ) {
			$filename = $this->_file;
		}
		Output::js( $this->_dir . $filename );
	}

	public function css( $filename = "" ) {
		if( empty( $filename ) ) {
			$filename = $this->_file;
		}

		Output::css( $this->_dir . $filename );
	}

	public function redirect( $url ) {
		header( 'Location: ' . $url );
		die();
	}

	public function refresh() {
		header( 'Location: ' . $this->_url . '/' );
		die();
	}

	static public function get_token() {
		if( empty( self::$_token ) ) {

			if( !Cookie::get( 'token' ) )
				self::set_token();
			else
				self::$_token = Cookie::get( 'token' );
		}
		return self::$_token;
	}

	static public function set_token() {
		self::$_token = md5( rand() );
	}

}
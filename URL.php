<?php

class URL {

	private static $glob;
	private $parse_url;
	private static $instances = array();
	private $url;

	function __construct ( $url ) {
		$this->url = $url;
	}

	protected function parse ( $key, $default = '' ) {
		isset( $this->parse_url ) ? $this->parse_url : $this->parse_url = parse_url ( $this->url () );
		return isset( $this->parse_url[ $key ] ) ? $this->parse_url[ $key ] : $default;
	}


	/**
	 * @param $url string
	 *
	 * @return static
	 */
	static function instance( $url ) {
		return isset( self::$instances[ $url ] ) ? self::$instances[ $url ] : self::$instances[ $url ] = new static( $url );
	}

	/**
	 * @return URL
	 */
	static function glob () {
		return static::instance( 'http://' . Input::host() . Input::url() );
	}

	/**
	 * @return URL
	 */
	static function ref() {
		return static::instance( \Input::referer() );
	}

	public function domain () {
		return $this->parse ( 'host' );
	}

	public function url () {
		return $this->url;
	}

	public function path () {
		return $this->parse ( __FUNCTION__ );
	}

	public function query () {
		return $this->parse ( __FUNCTION__ );
	}

	public function uri () {
		return $this->path () . ( $this->query () ? '?' . $this->query () : '' );
	}

	public function subdomain () {
		preg_match ( '/^([^\.]+)\..*$/', $this->domain (), $matches );
		if ( count ( $matches ) === 2 ) {
			return $matches[ 1 ];
		} else {
			return '';
		}
	}

}
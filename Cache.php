<?php



class Cache {


	const LOCAL_SERVER = 'localhost';
	const LOCAL_POST   = '11211';

	static private $local;
	/**
	 * @var Memcache
	 */
	private $instance;

	public function  __construct ( $server, $port ) {
		$this->connect ( $server, $port );
	}

	private function connect ( $server, $port ) {
		if ( !isset ( $this->instance ) ) {
			$this->instance = new Memcache();
			$this->instance->pconnect ( $server, $port );
		}
		return $this->instance;
	}


	/**
	 * @return Cache
	 */
	static function local () {
		if ( !isset( self::$local ) ) {
			self::$local = new Cache( self::LOCAL_SERVER, self::LOCAL_POST );
		}
		return self::$local;
	}


	public function get ( $name, $default = false ) {
		$ret = $this->instance->get ( $name );
		if ( $ret === false ) {
			return $default;
		}
		if ( !is_string ( $ret ) ) {
			return $ret;
		}
		return unserialize ( $ret );
	}

	public function set ( $name, $data, $time = 3600 ) {
		return $this->instance->set ( $name, serialize ( $data ), null, time () + $time );
	}

	public function delete ( $name ) {
		return $this->instance->delete ( $name );
	}
}
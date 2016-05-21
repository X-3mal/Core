<?php
/**
 * popunder.su
 * Author: X-3mal
 * Date: 07.10.13
 * Time: 12:04
 */


class DBS {
	/**
	 * @var DBDriver[]
	 */
	private static $drivers = array();
	/**
	 * @var DBDriver\Cached[]
	 */
	private static $drivers_cached = array();


	/**
	 * @return DBDriver
	 */
	public static function main () {
		return self::instance ( __FUNCTION__ );

	}

	/**
	 * @param null|int $time
	 * @return DBDriver\Cached
	 */
	public static function main_cached ( $time = null ) {
		return self::instance_cached ( __FUNCTION__, $time );
	}


	private function instance_cached ( $name, $time = null ) {
		$name = preg_replace ( '/_cached$/', '', $name );
		if ( !isset( self::$drivers_cached[ $name ] ) ) {
			$config                        = \Config::$DB[ $name ];
			self::$drivers_cached[ $name ] = new DBDriver( $config[ 'server' ], $config[ 'username' ], $config[ 'password' ], $config[ 'db' ], $config[ 'encoding' ], $config[ 'slave' ], $config[ 'prefix' ], 0, self::instance ( $name )->link () );
		}
		if ( isset( $time ) ) {
			self::$drivers_cached[ __FUNCTION__ ]->time ( $time );
		}
		return self::$drivers_cached[ $name ];
	}

	private static function instance ( $name ) {
		if ( !isset( self::$drivers[ $name ] ) ) {
			$config                 = \Config::$DB[ $name ];
			self::$drivers[ $name ] = new DBDriver( $config[ 'server' ], $config[ 'username' ], $config[ 'password' ], $config[ 'db' ], $config[ 'encoding' ], $config[ 'slave' ], $config[ 'prefix' ] );
		}
		return self::$drivers[ $name ];
	}


	static function shutdown () {
		foreach ( self::$drivers as $driver ) {
			$driver->shutdown ();
		}
	}
}

register_shutdown_function ( array( 'DBS', 'shutdown' ) );
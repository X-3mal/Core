<?php

/**
 * popunder.su
 * Author: X-3mal
 * Date: 03.04.14
 * Time: 14:22
 */
class Modules {
	private static $modules = array(
		'www'   => array(),
		'admin' => array(),
	);

	/**
	 * @param string $path
	 *
	 * @return \DB\modules
	 */
	static function get( $path ) {

		$path = self::parse_path( $path );

		if( !isset( self::$modules[ $path ] ) ) {
			$data = array( 'dir' => $path );
			if( !file_exists( Config::home() . $path ) ) {
				$module = new \DB\modules( null );
			} else {
				$module = new \DB\modules( $data );
				if( !$module->id() ) {

					$parent = self::parent( $path );
					if( $parent && $parent->id() ) {
						$id   = $parent->id();
						$type = $module::TYPE_CHILD_LAST;
					} else {
						$id   = null;
						$type = null;
					}
					$module = $module->insert( $data, array(), $type, $id );
				}
			}

			self::$modules[ $path ] = $module;
		}
		return self::$modules[ $path ];
	}

	/**
	 * @param $dir
	 *
	 * @return \DB\modules
	 */
	private static function parent( $dir ) {
		$path = preg_replace( '/\\/[^\\/]+\\/?$/', '/', $dir );
		if( $path && $path != $dir ) {
			return self::get( $path );
		} else {
			return null;
		}

	}

	static private function parse_path( $path ) {
		if( empty( $path ) ) {
			return '/';
		}
		if( $path[ mb_strlen( $path ) - 1 ] != '/' ) {
			$path .= '/';
		}
		$path = preg_replace( '#/+#', '/', $path );
		return $path;
	}

	static function current_main_script() {
		switch( URL::glob()->subdomain() ) {
			case 'admin':
				return 'scripts/admin';
			case 'api':
				return 'scripts/api';
			case 'fast':
				return 'scripts/landing';
			default:
				return 'scripts/www';
		}
	}

	static function current() {
		return self::get( URL::glob()->path() );
	}

	static function parse() {
		/**
		 * @param $dir \Files\DirRecursive
		 */
		function recursive_parse( $dir ) {
			$dir_name = str_replace( Config::home() . 'scripts/', '', $dir->dir() );
			Modules::get( $dir_name );
		}


		\DB\modules::normalize( true, false, true );
	}
} 
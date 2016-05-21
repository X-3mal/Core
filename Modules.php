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
	 * @param string $main_script
	 * @return \DB\modules
	 */
	static function get ( $path, $main_script = null ) {
		if ( !isset( $main_script ) ) {
			$main_script = self::current_main_script ();
		}
		$path = self::parse_path ( $path );

		if ( !isset( self::$modules[ $main_script ][ $path ] ) ) {
			$data = array( 'dir' => $path, 'main_script' => $main_script );
			if ( !file_exists ( $main_script . '/' . $path ) ) {
				$module = new \DB\modules( null );
			} else {
				$module = new \DB\modules( $data );
				if ( !$module->id () ) {

					$parent = self::parent ( $path, $main_script );
					if ( $parent && $parent->id () ) {
						$id   = $parent->id ();
						$type = $module::TYPE_CHILD_LAST;
					} else {
						$id   = null;
						$type = null;
					}
					$module = $module->insert ( $data, array(), $type, $id );
				}
			}

			self::$modules[ $main_script ][ $path ] = $module;
		}
		return self::$modules[ $main_script ][ $path ];
	}

	/**
	 * @param $dir
	 * @param $main_script
	 * @return \DB\modules
	 */
	private static function parent ( $dir, $main_script ) {
		$path = preg_replace ( '/\\/[^\\/]+\\/?$/', '/', $dir );
		if ( $path && $path != $dir ) {
			return self::get ( $path, $main_script );
		} else {
			return null;
		}

	}

	static private function parse_path ( $path ) {
		if ( empty( $path ) ) {
			return '/';
		}
		if ( $path[ mb_strlen ( $path ) - 1 ] != '/' ) {
			$path .= '/';
		}
		$path = preg_replace ( '#/+#', '/', $path );
		return $path;
	}

	static function current_main_script () {
		switch ( URL::glob ()->subdomain () ) {
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

	static function current () {
		return self::get ( URL::glob ()->path (), self::current_main_script () );
	}

	static function parse () {
		global $main_script;
		/**
		 * @param $dir \Files\DirRecursive
		 */
		function recursive_parse ( $dir ) {
			global $main_script;
			$dir_name = str_replace ( Config::home () . 'scripts/' . $main_script, '', $dir->dir () );
			Modules::get ( $dir_name, $main_script );
		}

		$main_scripts = array(
			'admin',
			'empty',
			'www',
			'landing/main',
			'landing',
		);
		foreach ( $main_scripts as $main_script ) {
			$file = new \Files\DirRecursive( Config::home () . 'scripts/' . $main_script );
			\Files\DirRecursive::recursive_parse ( $file, 'recursive_parse' );
		}
		\DB\modules::normalize ( true, false, true );
	}
} 
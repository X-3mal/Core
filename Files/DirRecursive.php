<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 27.02.14
 * Time: 21:41
 */
namespace Files;
class DirRecursive {
	private $options;
	private $dir;
	private $scan;
	private $files;
	private $folders;

	function __construct ( $dir, $options = array() ) {
		if ( substr ( $dir, -1 ) != DIRECTORY_SEPARATOR ) {
			$dir .= DIRECTORY_SEPARATOR;
		}
		$this->dir     = $dir;
		$this->options = $options;
	}

	private function options ( $key, $default = null ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	function depth () {
		return $this->options ( __FUNCTION__, 1 );
	}

	private function ignored_masks () {
		return array_merge ( $this->options ( __FUNCTION__, array() ), array( '\\.+' ) );
	}

	private function allowed_masks () {
		return $this->options ( __FUNCTION__, array() );
	}

	private function is_ignored ( $filename, $is_dir ) {
		foreach ( $this->ignored_masks () as $mask ) {
			$mask = '/^' . $mask . '$/';
			if ( preg_match ( $mask, $filename ) ) {
				return true;
			}
		}
		if ( !$is_dir && $this->allowed_masks () ) {
			foreach ( $this->allowed_masks () as $mask ) {
				$mask = '/^' . $mask . '$/';
				if ( preg_match ( $mask, $filename ) ) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	private function scan () {
		return isset( $this->scan ) ? $this->scan : $this->scan = scandir ( $this->dir );
	}

	function files () {
		if ( !isset( $this->files ) ) {
			$this->files = array();
			foreach ( $this->scan () as $filename ) {
				if ( !$this->is_ignored ( $filename, false ) && is_file ( $this->dir . $filename ) && is_readable ( $this->dir . $filename ) ) {
					$this->files[ ] = $filename;
				}
			}
		}
		return $this->files;
	}


	function dir () {
		return $this->dir;
	}

	/**
	 * @return DirRecursive[]
	 */
	function folders () {
		if ( !isset( $this->folders ) ) {
			$this->folders = array();
			foreach ( $this->scan () as $filename ) {
				if ( !$this->is_ignored ( $filename, true ) && is_dir ( $this->dir . $filename ) ) {
					$this->folders[ ] = new DirRecursive( $this->dir . $filename . DIRECTORY_SEPARATOR, array_merge ( $this->options, array( 'depth' => $this->depth () + 1 ) ) );
				}
			}
		}
		return $this->folders;
	}

	/**
	 * @param DirRecursive $dir
	 * @param string $function_name
	 * @throws \Exception
	 * @param Object|null $object
	 */
	static function recursive_parse ( $dir, $function_name, $object = null ) {
		if ( isset( $object ) && is_object ( $object ) && method_exists ( $object, $function_name ) ) {
			$object->$function_name( $dir );
		} elseif ( function_exists ( $function_name ) ) {
			$function_name( $dir );
		} else {
			throw new \Exception( 'Wrong callback' );
		}
		foreach ( $dir->folders () as $folder ) {
			self::recursive_parse ( $folder, $function_name, $object );
		}
	}


} 
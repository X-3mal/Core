<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 16.03.14
 * Time: 21:11
 */

class Module {
	private $path;
	/**
	 * @var \DB\modules
	 */
	private $module;

	function __construct ( $path ) {
		$this->path = $path;
	}

	function module () {
		if ( !isset( $this->module ) ) {
			$data         = array( 'path' => $this->path );
			$this->module = \DB\modules::insert ( $data );
			if ( !$this->module->exists () ) {
				$this->module = $this->module->insert ( $data );
			}
		}
		return $this->module ();
	}

} 
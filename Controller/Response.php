<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 26.02.14
 * Time: 0:01
 */

namespace Controller;


abstract class Response {
	protected $data;

	function __construct ( $data ) {
		$this->data = $data;
	}

	function data ( $key, $default = null ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : $default;
	}

	/**
	 * @return bool
	 */
	function error () {
		return (bool)$this->data ( __FUNCTION__, false );
	}

	function error_code () {
		return (bool)$this->data ( __FUNCTION__, false );
	}

	abstract function get ();

} 
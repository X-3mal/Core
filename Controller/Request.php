<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 25.02.14
 * Time: 23:59
 */

namespace Controller;


class Request {
	private $options;
	private $client;

	/**
	 * @param array $options
	 */
	function __construct ( $options = array () ) {
		$this->options = $options;
	}

	function addOption ( $key, $value, $replace = true ) {
		if ( $replace || ! isset( $this->options[ $key ] ) ) {
			switch ( $key ) {
				case 'id_cl':
					$this->client = null;
					break;
			}
			$this->options[ $key ] = $value;
		}
	}

	function options ( $key, $default = null ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	function action () {
		return $this->options ( __FUNCTION__, 0 );
	}
} 
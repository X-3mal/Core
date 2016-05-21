<?php

/**
 * Created by PhpStorm.
 * User: �������
 * Date: 25.02.14
 * Time: 23:59
 */
class Request {
	private $options;
	private $client;

	/**
	 * @param array $options
	 */
	function __construct ( $options = array() ) {
		$this->options = $options;
	}

	function addOption ( $key, $value, $replace = true ) {
		if ( $replace || !isset( $this->options[ $key ] ) ) {
			switch ( $key ) {
				case 'id_cl':
					$this->client = null;
					break;
			}
			$this->options[ $key ] = $value;
		}
	}

	function options ( $key = null, $default = null ) {
		if ( !isset( $key ) ) {
			return $this->options;
		}
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	function ajax () {
		return $this->options ( __FUNCTION__, 0 );
	}

	function action ( $default ) {
		return $this->options ( __FUNCTION__, $default );
	}
} 
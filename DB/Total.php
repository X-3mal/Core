<?php
/**
 * popunder.su
 * Author: X-3mal
 * Date: 10.10.13
 * Time: 15:19
 */

namespace DB;


class Total {
	private $fields;

	function __construct ( $fields, $init = array () ) {
		$this->fields = array ();
		foreach ( $fields as $field ) {
			$this->fields[ $field ] = 0;
		}
		if ( $init ) {
			$this->add ( $init );
		}
	}

	function add ( $row ) {
		foreach ( $this->fields as $field => &$val ) {
			if ( isset( $row[ $field ] ) && is_scalar ( $row[ $field ] ) ) {
				$val += $row[ $field ];
			}
		}
		unset( $val );
	}

	function get ( $key, $default = 0 ) {
		return isset( $this->fields[ $key ] ) ? $this->fields[ $key ] : $default;
	}
}
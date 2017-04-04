<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 02.03.14
 * Time: 20:48
 */

namespace DB\fieldType;


use DB\fieldType;

class enum extends fieldType {


	function check( $val ) {
		$val = $this->val( $val );
		return $this->check_length( $val ) && $this->check_length_min( $val ) && $this->check_length_max( $val ) && $this->check_regexp( $val ) && $this->check_null( $val ) && $this->check_values( $val );
	}

	function sql( $val ) {
		$val = $this->val( $val );

		return \DBS::main()->escape( $this->can_be_null() ? '#S' : '#s', $val );
	}

	function val( $val ) {
		return strval( $val );
	}
} 
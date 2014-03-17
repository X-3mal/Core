<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 02.03.14
 * Time: 18:01
 */

namespace DB\fieldType;


use DB\fieldType;

class date extends fieldType {

	function check ( $val ) {
		$val = $this->val ( $val );
		return $this->check_max ( $val ) && $this->check_min ( $val ) && $this->check_null ( $val ) && $this->check_unsigned ( $val );
	}

	function sql ( $val ) {
		return \DBS::main ()->escape ( $this->can_be_null () ? '#S' : '#s', $this->val ( $val ) );
	}

	function val ( $val ) {
		return is_null($val) ? null : date ( 'Y-m-d', is_int ( $val ) ? $val : strtotime ( $val ) );
	}

} 
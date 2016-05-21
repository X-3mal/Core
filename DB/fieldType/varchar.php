<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 02.03.14
 * Time: 18:00
 */

namespace DB\fieldType;


use DB\fieldType;

class varchar extends fieldType {
	function check ( $val ) {
		$val = $this->val ( $val );
		return $this->check_length ( $val ) && $this->check_length_min ( $val ) && $this->check_length_max ( $val ) && $this->check_regexp ( $val ) && $this->check_null ( $val );
	}

	function sql ( $val ) {
		return \DBS::main ()->escape ( $this->can_be_null () ? '#S' : '#s', $this->val ( $val ) );
	}

	function val ( $val ) {
		return is_null ( $val ) ? null : strval ( $val );
	}
} 
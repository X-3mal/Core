<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 02.03.14
 * Time: 18:00
 */

namespace DB\fieldType;


use DB\fieldType;

class float extends fieldType {
	function check ( $val ) {
		$val = $this->val ( $val );
		return $this->check_max ( $val ) && $this->check_min ( $val ) && $this->check_null ( $val ) && $this->check_unsigned ( $val );
	}

	function sql ( $val ) {
		return \DBS::main ()->escape ( $this->can_be_null () ? '#F' : '#f', $this->val ( $val ) );
	}

	function val ( $val ) {
		return is_null($val) ? null : floatval ( $val );
	}
} 
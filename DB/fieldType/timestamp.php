<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 02.03.14
 * Time: 18:01
 */

namespace DB\fieldType;


use DB\fieldType;

class timestamp extends fieldType {
	function unsigned () {
		return true;
	}

	function check ( $val ) {
		$val = $this->val ( $val );
		return $this->check_unsigned ( $val );
	}

	function sql ( $val ) {
		return \DBS::main ()->escape ( 'FROM_UNIXTIME( #d )', $this->val ( $val ) );
	}

	function val ( $val ) {
		return is_null ( $val ) ? null : intval ( $val == 'CURRENT_TIMESTAMP' ? \DBS::main ()->timestamp () : $val );
	}
} 
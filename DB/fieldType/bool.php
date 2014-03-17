<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 02.03.14
 * Time: 20:41
 */

namespace DB\fieldType;


use DB\fieldType;

class bool extends fieldType {
	function sql ( $val ) {
		return \DBS::main ()->escape ( $this->can_be_null () ? '#B' : '#b', $this->val ( $val ) );
	}

	function check ( $val ) {
		$val = $this->val ( $val );
		return $this->check_null ( $val );
	}

	function val ( $val ) {
		return is_null ( $val ) ? null : !empty ( $val );
	}
} 
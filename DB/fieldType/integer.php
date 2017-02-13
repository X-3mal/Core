<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 02.03.14
 * Time: 18:00
 */

namespace DB\fieldType;


use DB\fieldType;

class integer extends float {

	function sql ( $val ) {
		return \DBS::main ()->escape ( $this->can_be_null () ? '#D' : '#d', $this->val ( $val ) );
	}

	function val ( $val ) {
		return is_null ( $val ) ? null : is_null ( $val ) ? null : intval ( $val );
	}
} 
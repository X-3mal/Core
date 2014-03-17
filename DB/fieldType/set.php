<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 02.03.14
 * Time: 20:48
 */

namespace DB\fieldType;


use DB\fieldType;

class set extends fieldType {
	function check ( $val ) {
		$val = $this->val ( $val );
		return $this->check_length ( $val ) && $this->check_length_min ( $val ) && $this->check_length_max ( $val ) && $this->check_regexp ( $val ) && $this->check_null ( $val );
	}

	function sql ( $val ) {
		$val = $this->val ( $val );
		if ( !is_null ( $val ) ) {
			if ( is_array ( $val ) ) {
				$val = implode ( ',', $val );
			} else {
				throw new \Exception( 'Wrong value [set]: ' . print_r ( $val, true ) );
			}
		}
		return \DBS::main ()->escape ( $this->can_be_null () ? '#S' : '#s', $val );
	}

	function val ( $val ) {
		if ( !is_null ( $val ) ) {
			if ( is_string ( $val ) ) {
				$val = explode ( ',', $val );
			} elseif ( is_scalar ( $val ) ) {
				$val = array( $val );
			}
		}
		return $val;

	}
} 
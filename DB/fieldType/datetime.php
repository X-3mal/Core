<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 02.03.14
 * Time: 20:39
 */

namespace DB\fieldType;


class datetime extends date {
	function val ( $val ) {
		return is_null ( $val ) ? null : date ( 'Y-m-d H:i:s', is_int ( $val ) ? $val : strtotime ( $val ) );
	}
} 
<?php

/**
 * happy
 * Author: Алексей
 * Date: 03.02.2017
 * Time: 1:20
 */
class Tools {
	static public function generate_str( $min = 10, $max = 15 ) {
		$string  = implode( '', array_merge( range( 'a', 'z' ), range( 'A', 'Z' ), range( '0', '9' ) ) );
		$key     = '';
		$len     = mt_rand( $min, $max );
		$str_len = mb_strlen( $string );
		for( $i = 0; $i < $len; $i++ ) {
			$key .= $string[ mt_rand( 0, $str_len - 1 ) ];
		}

		return $key;
	}
}
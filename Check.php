<?php

class Check {

	const PATTERN_EMAIL      = '([a-z0-9_\.\-]{1,25})@([a-z0-9\.\-]{1,20})\.([a-z]{2,18})';
	const PATTERN_EMAIL_FULL = '^(([a-zA-Zа-яА-Я0-9_\.\-]{1,100})@([a-zA-Zа-яА-Я0-9\.\-]{1,20})\.([a-zA-Zа-яА-Я]{2,18}))?$';

	static private $rights = array ();

	static public function login ( $string ) {
		return mb_ereg_match( "^[\w\d_\-]{3,32}$", $string );
	}
 
	static public function password ( $string ) {
		return mb_ereg_match( "^.{4,}$", $string );
	}

	static public function name ( $string ) {
		return mb_ereg_match( "^\w{0,30}$", $string );
	}

	static public function surname ( $string ) {
		return mb_ereg_match( "^\w{0,100}$", $string );
	}

	static public function email ( $string ) {
		return mb_eregi( self::PATTERN_EMAIL_FULL, $string );
	}

	static public function wmr ( $string ) {
		return mb_eregi( "^(R[0-9]{12})?$", $string );
	}

	static public function wmz ( $string ) {
		return mb_eregi( "^(Z[0-9]{12})?$", $string );
	}

	static function color ( $string ) {
		return preg_match( "/^(transparent)|(#[0-9a-fA-F]{3,6})$/", $string );
	}

	static public function text ( $string, $length ) {
		return !empty ( $string ) && mb_strlen( $string ) <= $length;
	}

	static public function notEmpty ( &$value ) {
		return !empty ( $value );
	}



}
<?php

/**
 * Osbb
 * Author: Алексей
 * Date: 24.02.2016
 * Time: 23:33
 */
class Text {
	const LANG_UA = 1;
	const LANG_EN = 2;
	const LANG_RU = 3;
	protected static $codes   = array(
		self::LANG_UA => 'ua',
		self::LANG_EN => 'en',
		self::LANG_RU => 'ru',
	);
	protected static $lang_id = self::LANG_UA;

	static function lang_ids() {
		return array( self::LANG_UA, self::LANG_EN, self::LANG_RU );
	}

	static function init( $code = null ) {

	}

	static function lang_code() {
		return static::$codes[ static::$lang_id ];
	}


	static function lang_codes() {
		return static::$codes;
	}

	static function lang_id() {
		return static::$lang_id;
	}

	static function id_to_code( $id ) {
		return Input::param( static::$codes, $id, null );
	}

	static function code_to_id( $code ) {
		return array_search( $code, static::$codes );
	}

	static function set_lang_id( $lang_id ) {
		if( in_array( $lang_id, self::lang_ids() ) ) {
			self::$lang_id = $lang_id;
		}
	}


}
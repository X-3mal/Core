<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 26.02.14
 * Time: 20:54
 */

require_once __DIR__ . '/../classes/Config/Global.php';

class Config extends Config_Global {
	static function home () {
		return self::$home;
	}

	static function public_dir () {
		return self::$public_dir;
	}

	static function lang () {
		return Localization::LANG_RU;
	}

	static function errors_dir () {
		return self::home () . self::$errors_dir . '/';
	}
}
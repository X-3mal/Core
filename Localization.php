<?php


class Localization {
	static private $langs = array(
		1 => array(
			'locale' => 'ru_RU',
			'name'   => 'ru',
		),
		2 => array(
			'locale' => 'en_GB',
			'name'   => 'en',
		)
	);
	const LANG_RU = 1;
	const LANG_EN = 2;

	private $id;

	function __construct ( $lang_id ) {
		$this->id = $lang_id;
	}

	function id () {
		return $this->id;
	}

	function locale () {
		return self::langs ( $this->id (), 'locale' );
	}

	private $domain;

	private function domain () {
		if ( !isset( $this->domain ) ) {
			$dir = new \Files\DirRecursive( $this->mo_directory (), array( 'allowed_masks' => array( $this->base_domain () . "_\\d+\\.mo" ) ) );

			foreach ( $dir->files () as $filename ) {
				$this->domain = pathinfo ( $filename, PATHINFO_FILENAME );
				break;
			}
		}
		return $this->domain;
	}

	private function base_domain () {
		return $this->name ();
	}


	function name () {
		return self::langs ( $this->id (), 'name' );
	}

	function init () {

		putenv ( 'LC_ALL=' . $this->locale () );
		putenv ( 'LANG=' . $this->locale () );
		setlocale ( LC_MESSAGES, $this->locale () . '.utf8' );
		setlocale ( LC_NUMERIC, 'en_US.utf8' );
		bindtextdomain ( $this->domain (), self::locale_path () );
		bind_textdomain_codeset ( $this->domain (), 'UTF-8' );
		textdomain ( $this->domain () );
	}

	private static function locale_path () {
		return Config::home () . 'locale';
	}

	static function langs ( $lang_id, $key ) {
		return self::$langs[ $lang_id ][ $key ];
	}

	private static $instances;

	static function glob () {
		return self::instance ( Config::lang () );
	}

	/**
	 * @param $lang
	 * @return Localization
	 */
	static function instance ( $lang ) {
		return isset( self::$instances[ $lang ] ) ? self::$instances[ $lang ] : self::$instances[ $lang ] = new Localization( $lang );
	}

	private function mo_directory () {
		return self::locale_path () . '/' . $this->locale () . '/LC_MESSAGES/';
	}


	function update_mo () {
		$need_update = false;
		$base        = $this->mo_directory () . $this->base_domain () . '.mo';
		if ( !file_exists ( $base ) || !is_readable ( $base ) ) {
			throw new Exception( 'No base file' );
		}
		$base_time = filemtime ( $base );
		$dir       = new \Files\DirRecursive( $this->mo_directory (), array( 'allowed_masks' => array( $this->base_domain () . '_\d+\.mo' ) ) );

		foreach ( $dir->files () as $filename ) {
			$full = $dir->dir () . $filename;
			if ( filemtime ( $full ) < $base_time ) {
				unlink ( $full );
				$need_update = true;
			}
		}

		if ( $need_update || !$dir->files () ) {
			copy ( $base, $this->mo_directory () . $this->base_domain () . '_' . time () . '.mo' );
		}
	}

}
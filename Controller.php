<?php

/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 25.02.14
 * Time: 23:58
 */
class Controller {
	const EXCEPTION_ERROR_JSON = 1;

	/**
	 * @var \Request
	 */
	protected $options;
	protected $format;

	/**
	 * @return \Controller\Format
	 */
	function format () {
		return isset( $this->format ) ? $this->format : new \Controller\Format\JSON();
	}

	/**
	 * @param $options array
	 */
	function __construct ( $options ) {
		$this->options = $options;
	}

	/**
	 * @return \Request
	 */
	function request () {
		return isset( $this->request ) ? $this->request : $this->request = new \Request( $this->options );
	}

	private function error ( $msg, $code = 0 ) {
		return $this->format ()->encode ( array( 'error' => $msg, 'error_code' => $code ) );
	}

	/**
	 * @param string $action
	 * @throws \Exception
	 * @return int|string|bool
	 */
	function action ( $action ) {
		$action = 'action' . $action;
		if ( !method_exists ( $this, $action ) ) {
			return $this->error ( _ ( 'Не верное действие' ) );
		}
		try {
			\DBS::main ()->transaction ();
			$return = $this->$action();
			\DBS::main ()->commit ();
			return $this->format ()->encode ( $return );
		} catch ( \Exception $e ) {
			\DBS::main ()->rollback ();
			$message = $e->getCode () == self::EXCEPTION_ERROR_JSON ? json_decode ( $e->getMessage (), true ) : $e->getMessage ();
			return $this->error ( $message, $e->getCode () );
		}
	}

	function process ( $action = null ) {
		return $this->action ( $this->request ()->action ( $action ) );
	}

	static function html_form_inputs ( $controller, $action ) {
		return '<input type="hidden" name="processor" value="' . htmlspecialchars ( $controller ) . '"/>' .
			   '<input type="hidden" name="action" value="' . htmlspecialchars ( $action ) . '"/>';
	}
} 
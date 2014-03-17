<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 25.02.14
 * Time: 23:58
 */

class Controller {
	/**
	 * @var \Controller\Request
	 */
	private $request;
	protected $format;

	/**
	 * @return \Controller\Format
	 */
	function format () {
		return isset( $this->format ) ? $this->format : new \Controller\Format\JSON();
	}

	/**
	 * @param $request Controller\Request
	 */
	function __construct ( $request ) {
		$this->request = $request;
	}

	function request () {
		return $this->request;
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
			return $this->error ( $e->getMessage (), $e->getCode () );
		}
	}

	function process () {
		return $this->action ( $this->request ()->action () );
	}
} 
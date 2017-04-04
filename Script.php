<?php


class Script {
	private $_dir;
	private $_filename;
	private $_response;
	private $_errors;

	function __construct( $base_dir ) {
		$this->_dir = $base_dir;
	}

	function execute( $name ) {
		$dir = $this->_dir;

		$this->run( $name . '.inc' );

		$this->_dir = $dir;

	}

	function action( $name, $safe=true ) {

		if( URL::glob()->domain() !== URL::ref()->domain() && $safe ) {
			$this->error( '', _( 'Не правильный запрос' ) );
			return false;
		}
		try {
			\DBS::main()->transaction();
			$this->execute( $name );
			\DBS::main()->commit();
			return true;
		} catch( Exception\Script $e ) {
			\DBS::main()->rollback();
			$this->error( $e->getCode(), $e->getMessage() );
			return false;
		} catch( Exception $e ) {
			\DBS::main()->rollback();
			$this->error( $e->getCode(), $e->getMessage() );
			return false;
		}
	}

	function next( $default = '' ) {
		$next = HFU::next( $default );

		$dir = $this->_dir;

		$this->_dir = $this->_dir . '/' . $next;

		$this->run( $next . '.php' );

		$this->_dir = $dir;
	}

	function run( $name ) {
		if( file_exists( $filename = $this->_dir . '/' . $name ) ) {
			include( $filename );
		}
	}

	function error( $key, $msg ) {
		$this->_response[ 'errors' ][ $key ] = $msg;
		$this->_errors[ $key ]               = $msg;
		return false;
	}

	function errors() {
		return $this->_errors;

	}

	function response( $data = null ) {
		if( isset( $data ) ) {
			$this->_response = $data;
		}
		return $this->_response;
	}

	function success_message( $msg, $data = array() ) {
		$this->response( array_merge( $data, array( 'message' => $msg ) ) );
	}

}
<?php

class SMTP {
	private $connection;
	private $login;
	private $host;
	private $password;
	private $port;
	private $timeout;
	private $error_code;
	private $error_str;
	private $errors = array();
	const EOL = "\r\n";

	function __construct ( $host, $login, $password, $port = 25, $timeout = 10 ) {
		$this->host     = $host;
		$this->login    = $login;
		$this->password = $password;
		$this->port     = $port;
		$this->timeout  = $timeout;
		$this->connect ();
		//register_shutdown_function ( array( $this, 'disconnect' ) );
	}

	private function get_data () {
		$data = "";
		while ( $str = trim ( fgets ( $this->connection (), 515 ) ) ) {
			$data .= $str;
			if ( substr ( $str, 3, 1 ) == " " ) {
				break;
			}
		}
		return $data;
	}

	private function send_data ( $command ) {
		fputs ( $this->connection (), $command . self::EOL );
		$data = $this->get_data ();
		$code = substr ( $data, 0, 3 );
		if ( $code && !in_array ( $code, array( 250, 235, 334, 354 ) ) ) {
			$this->errors[ ] = $data;
		}
		return $data;
	}

	private function connection () {
		return isset( $this->connection ) ? $this->connection : $this->connection = fsockopen ( $this->host, $this->port, $this->error_code, $this->error_str, $this->timeout );
	}

	function error_code () {
		return $this->error_code;
	}

	function error_str () {
		return $this->error_str;
	}

	function errors () {
		return $this->errors;
	}

	private function connect () {
		$this->send_data ( 'EHLO ' . $this->host );
		$this->send_data ( 'AUTH LOGIN' );
		$this->send_data ( base64_encode ( $this->login ) );
		$this->send_data ( base64_encode ( $this->password ) );
	}

	private function disconnect () {
		$this->send_data ( 'QUIT' );
	}

	/**
	 * @param $to_email
	 * @param $from_email
	 * @param $text
	 * @param $headers \SMTP\Headers
	 */
	private function _send ( $to_email, $from_email, $text, $headers ) {
		$this->send_data ( 'MAIL FROM:' . $from_email );
		$this->send_data ( 'RCPT TO:' . $to_email );
		$this->send_data ( 'DATA' );
		$this->send_data ( $headers->get () . self::EOL . $text . self::EOL . '.' . self::EOL );
		$this->send_data ( 'RSET' );
	}

	function send ( $email, $title, $body ) {
		$this->errors = array();
		$from         = 'alex@palad.kiev.ua';
		$headers      = new \SMTP\Headers( array(
												'from_email'   => $from,
												'to_email'     => $email,
												'subject'      => $title,
												'content_type' => 'text/html',
										   ) );
		$this->_send ( $email, $from, nl2br ( $body ), $headers );
		return empty( $this->errors );
	}
}
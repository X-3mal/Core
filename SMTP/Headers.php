<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 11.04.14
 * Time: 22:58
 */
namespace SMTP;
class Headers {
	private $options;

	function __construct ( $options ) {
		$this->options = $options;
	}

	private function options ( $key, $default = null ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	private static function parse_str ( $str ) {
		return str_replace ( "+", "_", str_replace ( "%", "=", urlencode ( $str ) ) );
	}

	/**
	 * @param $key
	 * @param $value
	 * @return self
	 */
	private function set ( $key, $value ) {
		$this->options[ $key ] = $value;
		return $this;
	}

	private function time () {
		return $this->options ( __FUNCTION__, strtotime ( $this->options ( 'date' ) ) );
	}

	function date () {

		return date ( 'D, j M Y G:i:s', $this->time () );
	}

	function from_name () {
		return $this->parse_str ( $this->options ( __FUNCTION__, $this->from_email () ) );
	}

	function from_email () {
		return $this->options ( __FUNCTION__ );
	}

	function encoding () {
		return $this->options ( __FUNCTION__, 'utf-8' );
	}

	function reply_to_name () {
		return $this->parse_str ( $this->options ( __FUNCTION__, $this->reply_to_email () ) );
	}

	function reply_to_email () {
		return $this->options ( __FUNCTION__, $this->from_email () );
	}

	function to_name () {
		return $this->parse_str ( $this->options ( __FUNCTION__, $this->to_email () ) );
	}

	function to_email () {
		return $this->options ( __FUNCTION__, '' );
	}

	function subject () {
		return $this->parse_str ( $this->options ( __FUNCTION__ ) );
	}

	function message_id () {
		return rand ( 100000, 9999999 ) . '.' . date ( "YmjHis" );
	}

	function content_type () {
		return $this->options ( __FUNCTION__, 'text/plain' );
	}

	function content_transfer_encoding () {
		return $this->options ( __FUNCTION__, 'text/plain' );
	}

	function get () {
		$header = "Date: " . $this->date () . " +0700\r\n";
		$header .= "From: =?" . $this->encoding () . "?Q?" . $this->from_name () . "?= <" . $this->from_email () . ">\r\n";
		$header .= "X-Mailer: The Bat! (v3.99.3) Professional\r\n";
		$header .= "Reply-To: =?" . $this->encoding () . "?Q?" . $this->reply_to_name () . "?= <" . $this->reply_to_email () . ">\r\n";
		$header .= "X-Priority: 3 (Normal)\r\n";
		$header .= "Message-ID: <" . $this->message_id () . "@mail.ru>\r\n";
		$header .= "To: =?" . $this->encoding () . "?Q?" . $this->to_name () . "?= <" . $this->to_email () . ">\r\n";
		$header .= "Subject: =?" . $this->encoding () . "?Q?" . $this->subject () . "?=\r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: " . $this->content_type () . "; charset=" . $this->encoding () . "\r\n";
		$header .= "Content-Transfer-Encoding: 8bit\r\n";
		return $header;
	}


} 
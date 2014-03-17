<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 02.03.14
 * Time: 18:00
 */

namespace DB;


abstract class fieldType {
	private $options;

	/**
	 * @param $options array(
	 * min
	 * max
	 * length
	 * length_min
	 * length_max
	 * unsigned
	 * regexp
	 * )
	 */
	function __construct ( $options = array() ) {
		$this->options = $options;
	}


	/**
	 * @param $val
	 * @return bool
	 */
	abstract function check ( $val );

	/**
	 * @param $val
	 * @throws \Exception
	 * @return string
	 */
	abstract function sql ( $val );

	/**
	 * @param $val
	 * @return mixed
	 */
	abstract function val ( $val );


	/**
	 * @param $type
	 * @param array $options
	 * @return fieldType
	 * @throws \Exception
	 */
	static function instance ( $type, $options = array() ) {
		$class_name = '\\DB\\fieldType\\' . $type;
		if ( class_exists ( $class_name ) ) {
			return new $class_name( $options );
		} else {
			throw new \Exception( 'Wrong field type: ' . $type . ' ' . $class_name );
		}
	}

	public function type () {
		$class_name = get_class ( $this );
		return join ( '', array_slice ( explode ( '\\', $class_name ), -1 ) );
	}

	protected function options ( $key, $default = null ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	/* ++++++++++++ OPTIONS +++++++++++++++ */

	/**
	 * @return string
	 */
	function name () {
		return strval ( $this->options ( __FUNCTION__, '' ) );
	}

	/**
	 * @return string
	 */
	function error () {
		return strval ( $this->options ( __FUNCTION__, '' ) );
	}

	/**
	 * @return string
	 */
	function def () {
		return strval ( $this->options ( 'default' ) );
	}

	/**
	 * @return string
	 */
	function can_be_null () {
		return (bool)$this->options ( 'null', false );
	}

	/**
	 * @return float
	 */
	function min () {
		return is_null ( $this->options ( __FUNCTION__ ) ) ? null : floatval ( $this->options ( __FUNCTION__ ) );
	}

	/**
	 * @return float
	 */
	function max () {
		return is_null ( $this->options ( __FUNCTION__ ) ) ? null : floatval ( $this->options ( __FUNCTION__ ) );
	}

	/**
	 * @return int
	 */
	function length () {
		return max ( 0, intval ( $this->options ( __FUNCTION__, 0 ) ) );
	}

	/**
	 * @return int
	 */
	function length_min () {
		return max ( 0, intval ( $this->options ( __FUNCTION__, 0 ) ) );
	}

	/**
	 * @return int
	 */
	function length_max () {
		return max ( 0, intval ( $this->options ( __FUNCTION__, 0 ) ) );
	}

	/**
	 * @return bool
	 */
	function unsigned () {
		return (bool)$this->options ( __FUNCTION__, false );
	}

	/**
	 * @return string
	 */
	function regexp () {
		return $this->options ( __FUNCTION__, '' );
	}

	/* --------------- END OPTIONS --------------- */

	/* +++++++++++++++ CHECK FUNCTIONS +++++++++++ */

	protected function check_min ( $val ) {
		return is_null ( $this->min () ) || $val >= $this->min ();
	}

	protected function check_max ( $val ) {
		return is_null ( $this->max () ) || $val <= $this->max ();
	}

	protected function check_null ( $val ) {
		return $this->can_be_null () || !is_null ( $val );
	}

	protected function check_unsigned ( $val ) {
		return !$this->unsigned () || $val >= 0;
	}

	protected function check_length ( $val ) {
		return !$this->length () || mb_strlen ( $val ) <= $this->length ();
	}

	protected function check_length_min ( $val ) {
		return !$this->length_min () || mb_strlen ( $val ) >= $this->length_min ();
	}

	protected function check_length_max ( $val ) {
		return !$this->length_max () || mb_strlen ( $val ) <= $this->length_max ();
	}

	protected function check_regexp ( $val ) {
		return !$this->regexp () || preg_match ( $this->regexp (), $val );
	}


	/* --------------- END CHECK  FUNCTIONS ----- */
} 
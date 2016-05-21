<?php
/**
 * popunder.su
 * Author: X-3mal
 * Date: 30.01.14
 * Time: 18:03
 */

namespace DB;


class table_simple {
	private $row;
	protected static $table;
	protected static $id_field = 'id';
	protected static $fields = array (
		'id' => array (
			'macros'  => '#d',
			'default' => 0
		)
	);

	/**
	 * @return \DBDriver
	 */
	protected static function dbdriver () {
		return \DBS::stat ();
	}

	function __construct ( $data, $row = null ) {
		if ( isset( $row ) ) {
			$this->row = $row;
		} else {
			if ( empty( $data ) ) {
				$data = 0;
			}
			if ( is_scalar ( $data ) ) {
				$filter = self::filter ( array ( static::$id_field => $data ) );
			} else {
				$filter = self::filter ( $data );
			}
			$this->row = static::dbdriver ()->select_row ( ">SELECT * FROM `" . static::$table . "` " . $filter->filter ( 'WHERE' ) );
		}

	}

	public final function id () {
		return $this->row ( static::$id_field );
	}

	final public function update ( $data ) {
		$insert = new Filter();
		$insert->addFilter ( self::field_query ( static::$id_field, $this->id () ) );
		foreach ( $data as $key => $value ) {
			if ( isset( static::$fields[ $key ] ) ) {
				$insert->addInsert ( self::field_query ( $key, $value ) );
				$this->row[ $key ] = $value;
			}
		}
		static::dbdriver ()->query ( "UPDATE `" . static::$table . "`" . $insert->insert ( ' SET' ) . $insert->filter ( ' WHERE' ) );
	}

	static function insert ( $data ) {
		$insert = new Filter();
		$row    = array ();
		foreach ( $data as $key => $value ) {
			if ( isset( static::$fields[ $key ] ) ) {
				$row[ $key ] = $value;
				$insert->addInsert ( self::field_query ( $key, $value ) );
			}
		}
		self::dbdriver ()->query ( "INSERT INTO `" . static::$table . "`" . $insert->insert ( ' SET' ) );
		$row[ static::$id_field ] = self::dbdriver ()->insert_id ();
		return new static( null, $row );
	}

	protected static function filter ( $array ) {
		$filter = new Filter();
		foreach ( $array as $key => $value ) {
			if ( is_object ( $value ) ) {
				//\DBS::main ()->Log ( $array );
			}
			$filter->addFilter ( self::field_query ( $key, $value ) );
		}
		return $filter;
	}

	static protected function field_query ( $field, $value ) {
		return self::dbdriver ()->escape ( '`' . $field . '` = ' . self::field_macros ( $field ), $value );
	}


	protected static function field_macros ( $field, $default = '#s' ) {
		return isset( static::$fields[ $field ][ 'macros' ] ) ? static::$fields[ $field ][ 'macros' ] : $default;
	}


	public function row ( $key, $default = null, $set = null ) {
		if ( ! isset( $default ) && isset( static::$fields[ $key ][ 'default' ] ) ) {
			$default = static::$fields[ $key ][ 'default' ];
		}
		if ( isset( $set ) ) {
			$this->update ( array ( $key => $set ) );
		}
		return isset( $this->row[ $key ] ) ? $this->row[ $key ] : $default;
	}


}
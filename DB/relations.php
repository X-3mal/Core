<?php

namespace DB;

class relations {
	private $id;

	static protected $name;
	static protected $item_id_name = 'item_id';
	static protected $property_id_name = 'property_id';
	private $rows;
	static private $fields_obj = array();

	/**
	 * @return \DBDriver
	 */
	protected static function db_driver () {
		return \DBS::main ();
	}

	static protected $fields = array(
		'item_id'     => array(
			'type'    => 'int',
			'null'    => false,
			'default' => null
		),
		'property_id' => array(
			'type'    => 'int',
			'null'    => false,
			'default' => ''
		),
	);

	final protected function table () {
		return static::$name;
	}

	function __construct ( $item_id = null ) {
		$this->id   = $item_id;
		$this->rows = $this->_select ();
	}

	final public function id () {
		return $this->id;
	}

	final function rows ( $id = null, $key = null ) {
		if ( isset( $id ) ) {
			if ( isset( $key ) ) {
				return isset( $this->rows[ $id ][ $key ] ) ? $this->rows[ $id ][ $key ] : null;
			}
			return isset( $this->rows[ $id ] ) ? $this->rows[ $id ] : null;
		}
		return $this->rows;
	}

	final function property_ids () {
		return array_keys ( $this->rows () );
	}

	final private function _add ( $ids, $options = array() ) {
		$names  = array( static::$item_id_name, static::$property_id_name );
		$values = array();
		foreach ( $ids as $id ) {
			$value = array(
				self::fields_obj_item_id ()->sql ( $this->id () ),
				self::fields_obj_prop_id ()->sql ( $id )
			);
			foreach ( $options as $key => $val ) {
				$field    = self::fields_obj ( $key );
				$value[ ] = $field->sql ( $val );
				if ( array_search ( $key, $names ) !== false ) {
					$names[ ] = $key;
				}
			}
			$values[ ] = implode ( ',', $value );
		}
		if ( $values ) {
			$this->db_driver ()->query ( ">INSERT IGNORE INTO `" . self::table () . "` ( `" . implode ( '`,`', $names ) . "` ) VALUES (" . implode ( '), (', $values ) . ")" );
		}
	}

	private function _select () {
		$select = new Filter();
		$select->addSelect ( '`' . static::$property_id_name . '`' );
		foreach ( static::$fields as $name => $field ) {
			if ( $name != static::$property_id_name ) {
				$select->addSelect ( '`' . $name . '`' );
			}
		}
		return $this->db_driver ()->select_assoc ( "SELECT #q FROM `" . self::table () . "` WHERE `" . static::$item_id_name . "` = " . self::fields_obj_item_id ()->val ( $this->id () ), $select->select ( '' ) );
	}

	private function _clear () {
		$this->db_driver ()->query ( "DELETE FROM `" . self::table () . "` WHERE `#q` = #q", self::fields_obj_item_id ()->name (), self::fields_obj_item_id ()->sql ( $this->id () ) );
	}

	protected function after_add ( $ids, $options = array() ) {

	}

	protected function after_set ( $ids, $options = array() ) {

	}

	function after_clear () {

	}

	function add ( $ids, $options = array() ) {
		$this->_add ( $ids, $options );
		$this->after_add ( $ids, $options );
	}

	function add_rows ( $rows ) {
		foreach ( $rows as $row ) {
			if ( !isset( $row[ static::$property_id_name ] ) ) {
				throw new \Exception( static::$property_id_name . ' not set' );
			}
			$ids = array( $row[ static::$property_id_name ] );
			unset( $row[ static::$property_id_name ] );
			$this->add ( $ids, $row );
		}
	}

	final function set ( $ids, $options = array() ) {
		$this->_clear ();
		$this->_add ( $ids, $options );
		$this->after_set ( $ids, $options );
	}

	final function clear () {
		$this->_clear ();
		$this->after_clear ();
	}

	/**
	 * @param $field
	 * @return fieldType
	 * @throws \Exception
	 */
	private static function fields_obj ( $field ) {
		if ( !isset( static::$fields[ $field ] ) || !isset( static::$fields[ $field ][ 'type' ] ) ) {
			throw new \Exception( 'No field in config: ' . $field );
		}
		$data           = static::$fields[ $field ];
		$data[ 'name' ] = $field;
		return isset( self::$fields_obj[ $field ] ) ? self::$fields_obj[ $field ] : self::$fields_obj[ $field ] = fieldType::instance ( $data[ 'type' ], $data );
	}

	/**
	 * @return fieldType
	 */
	private static function fields_obj_item_id () {
		return self::fields_obj ( static::$item_id_name );
	}

	/**
	 * @return fieldType
	 */
	private static function fields_obj_prop_id () {
		return self::fields_obj ( static::$property_id_name );
	}

}

?>

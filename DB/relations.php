<?php

namespace DB;

class relations {
	private $id;


	static protected $name;
	static protected $item_id_name = 'item_id';
	static protected $property_id_name = 'property_id';

	/**
	 * @return \DBDriver
	 */
	protected static function db_driver () {
		return \DBS::main ();
	}

	static protected $fields = array (
		'item_id'     => array (
			'type'    => 'int',
			'null'    => false,
			'default' => null
		),
		'property_id' => array (
			'type'    => 'int',
			'null'    => false,
			'default' => ''
		),
	);

	function __construct ( $item_id = null ) {
		$this->id = $item_id;
	}

	static public function field ( $key, $property = null ) {
		if ( ! isset ( $property ) ) {
			return static::$fields[ $key ];
		} else {
			return static::$fields[ $key ][ $property ];
		}
	}

	protected $property_ids;
	protected $property_rows;

	public function id () {
		return $this->id;
	}

	public function default_properties () {
		return array ();
	}

	public function properties_rows () {
		if ( ! isset( $this->property_rows ) ) {
			$this->id () ? $this->property_rows = $this->db_driver ()->select_assoc ( "SELECT `" . static::$property_id_name . "` AS `prop_id`, `t`.* FROM `" . static::$name . "` `t` WHERE `" . static::$item_id_name . "` = #q", $this->sql_id_value () ) : $this->property_ids = array ();
		}
		return $this->property_rows;
	}

	public function property_ids () {
		if ( ! isset( $this->property_ids ) ) {
			$this->property_ids = $this->id () ? array_keys ( $this->properties_rows () ) : $this->default_properties ();
		}
		return $this->property_ids;
	}


	/**
	 * @param array $ids
	 */
	protected function after_add ( $ids ) {

	}

	/**
	 * @param array $ids
	 * @param array $properties
	 */
	public function add ( $ids, $properties = array () ) {
		$this->property_ids = null;
		$ids                = array_unique ( $ids );
		$items              = array ();
		foreach ( $ids as $id ) {
			$id = table::field_sql ( $id, self::field ( static::$property_id_name ), true );
			if ( $id !== false ) {
				$items[ ] = $this->sql_id_value () . ", " . $id;
			}
		}
		if ( $items ) {
			$items_sql = '(' . implode ( '),(', $items ) . ')';
			$this->db_driver ()->query ( "REPLACE INTO `" . static::$name . "` ( `" . static::$item_id_name . "` , `" . static::$property_id_name . "`) VALUES  #q ", $items_sql );
			$this->after_add ( $ids );
		}
	}

	/**
	 * @param $ids array
	 */
	public function remove ( $ids ) {
		$this->property_ids = null;
		$ids                = array_unique ( $ids );
		$items              = array ();
		foreach ( $ids as $id ) {
			$items[ ] = $this->sql_property_value ( $id );
		}
		if ( $items ) {
			$this->db_driver ()->query ( "DELETE FROM `" . static::$name . "` WHERE `" . static::$item_id_name . "` = #q AND `" . static::$property_id_name . "` = (#q)", $this->sql_id_value (), implode ( ',', $items ) );
		}
	}

	public function clear () {
		$this->property_ids = null;
		$this->db_driver ()->query ( "DELETE FROM `" . static::$name . "` WHERE `" . static::$item_id_name . "` = #q", $this->sql_id_value () );
	}

	public function set ( $ids ) {
		$this->clear ();
		$this->add ( $ids );
	}

	private function sql_id_value () {
		return table::field_sql ( $this->id (), self::field ( static::$item_id_name ) );
	}

	private function sql_property_value ( $value ) {
		return table::field_sql ( $value, self::field ( static::$property_id_name ) );
	}

	/**
	 * @param int|array $property_id
	 * @param           $data
	 */
	public function update ( $property_id, $data ) {
		if ( is_array ( $property_id ) ) {
			foreach ( $property_id as $id ) {
				$this->update ( $id, $data );
			}
			return;
		}
		$insert = new Filter();
		foreach ( $data as $key => $val ) {
			$insert->addInsert ( '`' . $key . '` = #q', table::field_sql ( $val, self::field ( $key ) ) );
		}
		$this->db_driver ()->query (
			 "UPDATE `" . static::$name . "` #q WHERE `" . static::$item_id_name . "` = #q AND `" . static::$property_id_name . "` = #q",
				 $insert->insert ( ' SET' ),
				 $this->sql_id_value (),
				 table::field_sql ( $property_id, self::field ( static::$property_id_name ) )
		);
	}

}

?>

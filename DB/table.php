<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 03.03.14
 * Time: 21:34
 */

namespace DB;


abstract class table {
	/* +++ MUST BE DECLARED AT CHILDREN +++ */

	static protected $name;

	static protected $fields;


	/* --- MUST BE DECLARED AT CHILDREN --- */

	/* +++ CAN BE DECLARED AT CHILDREN +++ */

	static protected $id_name = 'id';
	/**
	 * @var array[table_name => array[ type:INNER|LEFT, on: array[field1 => field2] ] ]
	 */
	static protected $joins = array();
	protected static $alias;

	static protected function error() {
		return array(
			'_field' => _( 'Поле указано неверно' ) //Ошибка по умолчанию
		);
	}

	static protected function error_text( $key ) {
		return \Input::param( static::error(), $key, \Input::param( static::error(), '_field', _( 'Поле указано неверно' ) ) );
	}

	/* --- CAN BE DECLARED AT CHILDREN --- */

	/**
	 * @var static[]
	 */
	static protected $instances = array();
	static protected $lock      = array();
	/**
	 * @var fieldType[]
	 */
	private static $fields_obj = array();
	private        $id;
	private        $row;
	private        $errors     = array();

	function __construct( $data, $fields = array() ) {
		if( is_null( $data ) && is_array( $fields ) ) {
			$this->row = $fields;
			if( !empty ( $fields ) ) {
				if( !isset ( $fields[ static::$id_name ] ) ) {
					\trigger_error( 'There is no id in data for ' . static::$name, E_USER_WARNING );
					throw new \Exception();
				}

				$this->id = $fields[ static::$id_name ];
			}
		} else {
			if( empty ( $fields ) ) {
				$fields = array_keys( static::$fields );
			}
			if( !is_array( $data ) ) {
				$data = $this->fields_obj( static::$id_name )->val( $data );
			}
			$row = static::select_one( $data, $fields );
			if( !empty ( $row ) ) {
				$this->row = $row->row();
				$this->id  = $row->id();
			} else {
				$this->row = array();
				/*foreach ( static::$fields as $key => $row ) {
					$this->row[ $key ] = $row[ 'default' ];
				}*/
			}
		}
	}

	protected static function db_driver() {
		return \DBS::main();
	}

	final public function id() {
		return $this->id;
	}

	final public function exists() {
		return !empty( $this->row );
	}

	/**
	 * @param $field
	 *
	 * @return fieldType
	 * @throws \Exception
	 */
	private static function fields_obj( $field ) {
		if( !isset( static::$fields[ $field ] ) || !isset( static::$fields[ $field ][ 'type' ] ) ) {
			throw new \Exception( 'No field in config: ' . $field );
		}
		$data           = static::$fields[ $field ];
		$data[ 'name' ] = $field;
		return isset( self::$fields_obj[ $field ] ) ? self::$fields_obj[ $field ] : self::$fields_obj[ $field ] = fieldType::instance( $data[ 'type' ], $data );
	}

	static public function query_field( $name, $joined = false ) {
		if( !isset( static::$fields[ $name ] ) ) {
			\trigger_error( 'Invalid join ' . $name . ' and ', E_USER_WARNING );
			throw new \Exception();
		}
		if( $joined ) {
			if( static::$fields[ $name ][ 'type' ] == 'timestamp' ) {
				return 'UNIX_TIMESTAMP(`' . static::alias() . '`.`' . $name . '`) AS `' . static::alias() . '.' . $name . '`';
			} else {
				return '`' . static::alias() . '`.`' . $name . '` AS `' . static::alias() . '.' . $name . '`';
			}
		} else {
			if( static::$fields[ $name ][ 'type' ] == 'timestamp' ) {
				return 'UNIX_TIMESTAMP(`' . static::alias() . '`.`' . $name . '`) AS `' . $name . '`';
			} else {
				return '`' . static::alias() . '`.`' . $name . '`';
			}
		}
	}

	static public function query_join( $table ) {
		$table_class = 'DB\\' . $table;
		/* @var $table_obj table */
		$table_obj = new $table_class( null, array() );
		if( !isset ( static::$joins[ $table ] ) ) {
			\trigger_error( 'Invalid join ' . static::$name . ' and ' . $table_obj::$name, E_USER_WARNING );
			throw new \Exception();
		}

		$join = &static::$joins[ $table ];
		if( !isset ( $join[ 'sql' ] ) ) {
			$join[ 'sql' ] = ( isset ( $join[ 'type' ] ) ? $join[ 'type' ] : 'INNER' ) . ' JOIN `' . $table_obj::$name . '` AS `' . $table_obj::alias() . '` ON ';
			$on            = array();
			foreach( $join[ 'on' ] as $field1 => $field2 ) {
				$on[] = '`' . $table_obj::alias() . '`.`' . $field1 . '`=`' . static::alias() . '`.`' . $field2 . '`';
			}

			$join[ 'sql' ] .= implode( ' AND ', $on );
		}

		return $join[ 'sql' ];
	}

	static function select( $where = array(), $fields = null, $order = array(), $limit = 0 ) {
		$filter = new Filter();
		$filter->addSelect( '`' . static::alias() . '`.`' . static::$id_name . '`' );

		$sets = array();
		if( empty( $fields ) ) {
			$fields = array_keys( static::$fields );
		}
		foreach( $fields as $field ) {
			if( is_array( $field ) ) {
				$table       = $field[ 0 ];
				$field       = $field[ 1 ];
				$table_class = 'DB\\' . $table;

				if( !class_exists( $table_class ) ) {
					trigger_error( 'No class found ' . $table_class );
					throw new \Exception();
				}

				/* @var $table_obj table */
				$table_obj = new $table_class( null, array() );


				if( $field == '*' ) {
					foreach( $table_obj::$fields as $key => &$value ) {
						if( $table_obj::$fields[ $key ][ 'type' ] === 'set' ) {
							$sets[] = $key;
						}
						$filter->addSelect( $table_obj->query_field( $key, true ) );
					}
					unset ( $value );
				} else {
					if( $table_obj::$fields[ $field ][ 'type' ] === 'set' ) {
						$sets[] = $field;
					}
					$filter->addSelect( $table_obj->query_field( $field, true ) );
				}


				$filter->addJoin( true, $table_obj::alias(), static::query_join( $table ) );
			} else {
				if( $field == '*' ) {
					foreach( static::$fields as $key => &$value ) {
						if( self::fields_obj( $key )->type() === 'set' ) {
							$sets[] = $key;
						}
						$filter->addSelect( static::query_field( $key ) );
					}
					unset ( $value );
				} else {
					if( self::fields_obj( $field )->type() === 'set' ) {
						$sets[] = $field;
					}
					$filter->addSelect( static::query_field( $field ) );
				}
			}
		}

		if( is_array( $where ) ) {
			foreach( $where as $key => $value ) {
				if( is_int( $key ) ) {
					/**
					 * @todo на будущее
					 */
				} else {
					if( !isset ( static::$fields[ $key ] ) ) {
						trigger_error( 'Incorrect field `' . static::alias() . '`.`' . $key . '`', E_USER_ERROR );
						throw new \Exception();
					}
					$filter->addFilter( '`#q`.`#q` = #q', static::alias(), $key, self::fields_obj( $key )->sql( $value ) );
				}
			}
		} else {
			$filter->addFilter( '`#q`.`#q` = #q', static::alias(), static::$id_name, self::fields_obj( static::$id_name )->val( $where ) );
		}
		if( $limit ) {
			$filter->addLimit( 0, $limit );
		}


		$rows    = \DBS::main()->select( 'SELECT #q FROM `' . \DBS::main()->escape( static::$name ) . '` AS `' . static::alias() . '` #q #q #q' . ( !empty ( static::$lock[ get_called_class() ] ) ? ' FOR UPDATE' : '' ), $filter->select( '' ), $filter->join(), $filter->filter( 'WHERE' ), $filter->limit() );
		$results = array();
		if( !empty ( $rows ) ) {
			foreach( $rows as $key => &$row ) {
				foreach( $sets as $key ) {
					if( empty ( $row[ $key ] ) ) {
						$row[ $key ] = array();
					} else {
						$row[ $key ] = mb_split( ',', $row[ $key ] );
					}
				}
				$results[] = new static( null, $row );
			}
			unset ( $row );
		}

		return $results;
	}

	static function select_one( $where = array(), $fields = array(), $order = array() ) {
		$rows = static::select( $where, $fields, $order, 1 );
		return $rows ? $rows[ 0 ] : new static( null );
	}

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @throws \Exception
	 * @return bool
	 */
	final public function in_set( $key, $value ) {
		if( $this->fields_obj( $key )->type() != 'set' ) {
			throw new \Exception( 'Field type is not "SET"' );
		}
		return in_array( $value, $this->row[ $key ], true );
	}

	static public function alias() {
		return isset ( static::$alias ) ? static::$alias : mb_substr( static::$name, 2 );
	}

	private function set_errors( $errors ) {
		$this->errors = $errors;
	}

	/**
	 * @param array $data
	 *
	 * @return static
	 * @throws \Exception
	 */
	static public function insert( $data = array() ) {
		$query = new Filter();
		if( empty( $data ) ) {
			$data = array( static::$id_name => self::fields_obj( static::$id_name )->def() );
		}
		$errors = array();
		foreach( $data as $key => &$val ) {
			$field = self::fields_obj( $key );
			$val   = $field->val( $val );
			if( !$field->check( $val ) ) {
				$errors[ $key ] = $field->error( static::error_text( $key ) );
			}
			$query->addInsert( '`' . $field->name() . '` = #q', $field->sql( $val ) );
		}
		if( $errors ) {

			$object = new static( null, array() );
			/* @var $object table */
			$object->set_errors( $errors );
			return $object;
		}
		unset( $val );
		self::db_driver()->query( "INSERT INTO `" . static::$name . "` SET #q", $query->insert( '' ) );
		if( !isset( $data[ static::$id_name ] ) ) {
			$data[ static::$id_name ] = self::db_driver()->insert_id();
		}
		return new static ( null, $data );
	}

	/**
	 * @param array $data
	 *
	 * @return int|bool
	 * @throws \Exception
	 */
	public function update( $data = array() ) {
		$errors = array();
		$query  = new Filter();
		if( empty( $data ) || !$this->exists() ) {
			return 0;
		}
		foreach( $data as $key => &$val ) {
			$field = self::fields_obj( $key );
			$val   = $field->val( $val );
			if( !$field->check( $val ) ) {
				$errors[ $key ] = $field->error( static::error_text( $key ) );
			}
			$query->addInsert( '`' . $field->name() . '` = #q', $field->sql( $val ) );
		}
		unset( $val );
		if( $errors ) {
			$this->set_errors( $errors );
			return false;
		}
		self::db_driver()->query( "UPDATE `" . static::$name . "` SET #q WHERE `" . static::$id_name . "` = #q", $query->insert( '' ), $this->fields_obj( static::$id_name )->sql( $this->id() ) );

		if( $affected_rows = $this->db_driver()->affected_rows() ) {
			$this->row = array_merge( $this->row, $data );
		}
		return $affected_rows;
	}

	public function errors() {
		return $this->errors;
	}

	public function row( $key = null ) {
		return empty ( $key ) ? $this->row : ( isset ( $this->row[ $key ] ) ? $this->row[ $key ] : $this->fields_obj( $key )->def() );
	}

	/**
	 * @param $row
	 *
	 * @return static
	 */
	static public function item( $row ) {
		return new static ( $row );
	}


	/**
	 * @param null $id
	 *
	 * @return static
	 */
	static function instance( $id = null ) {
		$class = get_called_class();
		return isset( static::$instances[ $class ][ $id ] ) ?
			static::$instances[ $class ][ $id ] :
			static::$instances[ $class ][ $id ] = static::select_one( array( static::$id_name => $id ) );
	}


}
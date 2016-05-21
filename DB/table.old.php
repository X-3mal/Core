<?php

namespace DB;

abstract class _Table {

	static protected $instances = array();
	static protected $name;
	static protected $alias;
	static protected $fields = array();
	static protected $joins = array();
	protected $id = null;
	protected $row;
	static protected $id_name = 'id';
	private $errors_data = array();
	static protected $errors = array(
		'_field' => 'Поле указано неверно'
	);
	static protected $off = array();
	static protected $lock = array();

	function __construct ( $data, $fields = array() ) {
		if ( is_null ( $data ) && is_array ( $fields ) ) {
			$this->row = $fields;
			if ( !empty ( $fields ) ) {
				if ( !isset ( $fields[ static::$id_name ] ) ) {
					\trigger_error ( 'There is no id in data for ' . static::$name, E_USER_WARNING );
					throw new \Exception();
				}

				$this->id = $fields[ static::$id_name ];
			}
		} else {
			if ( empty ( $fields ) ) {
				$fields = array_keys ( static::$fields );
			}
			if ( !is_array ( $data ) )
				$data = intval ( $data );
			$rows = static::_select ( $fields, $data );
			if ( !empty ( $rows ) ) {
				$this->row = $rows[ 0 ];
				$this->id  = $this->row[ static::$id_name ];
			} else {
				$this->row = array();
				foreach ( static::$fields as $key => $row ) {
					$this->row[ $key ] = $row[ 'default' ];
				}
			}
		}
	}

	static private function _select ( $fields, $where = array() ) {
		$query_joins  = array();
		$query_fields = '`' . static::alias () . '`.`' . static::$id_name . '`';

		$sets = array();
		foreach ( $fields as $field ) {
			if ( is_array ( $field ) ) {
				$table       = $field[ 0 ];
				$field       = $field[ 1 ];
				$table_class = 'DB\\' . $table;

				if ( $field == '*' ) {
					foreach ( $table_class::$fields as $key => &$value ) {
						if ( $table_class::$fields[ $key ][ 'type' ] === 'set' ) {
							$sets[ ] = $key;
						}
						$query_fields .= ',' . $table_class::query_field ( $key, true );
					}
					unset ( $value );
				} else {
					if ( $table_class::$fields[ $field ][ 'type' ] === 'set' ) {
						$sets[ ] = $field;
					}
					$query_fields .= ',' . $table_class::query_field ( $field, true );
				}

				if ( !isset ( $query_joins[ $table ] ) ) {
					$query_joins[ $table ] = static::query_join ( $table );
				}
			} else {
				if ( $field == '*' ) {
					foreach ( static::$fields as $key => &$value ) {
						if ( static::$fields[ $key ][ 'type' ] === 'set' ) {
							$sets[ ] = $key;
						}
						$query_fields .= ',' . static::query_field ( $key );
					}
					unset ( $value );
				} else {
					if ( static::$fields[ $field ][ 'type' ] === 'set' ) {
						$sets[ ] = $field;
					}
					$query_fields .= ',' . static::query_field ( $field );
				}
			}
		}

		if ( is_array ( $where ) ) {
			$query_where = '';
			foreach ( $where as $key => $value ) {
				if ( is_int ( $key ) ) {
					/**
					 * @todo на будущее
					 */
				} else {
					if ( !isset ( static::$fields[ $key ] ) ) {
						trigger_error ( 'Incorrect field `' . static::alias () . '`.`' . $key . '`', E_USER_ERROR );
						throw new \Exception();
					}

					$query_where .= ' AND `' . static::alias () . '`.`' . $key . '`=' . self::field_sql ( $value, static::$fields[ $key ] );
				}
			}
			if ( !empty ( $query_where ) ) {
				$query_where = 'WHERE ' . mb_substr ( $query_where, 5 );
			}
		} else {
			$query_where = 'WHERE `' . static::alias () . \DBS::main ()->escape ( '`.`' . static::$id_name . '`= #d', $where );
		}

		$rows = \DBS::main ()->select ( '>SELECT ' . $query_fields . ' FROM `' . \DBS::main ()->escape ( static::$name ) . '` AS `' . static::alias () . '` ' . implode ( $query_joins ) . ' ' . $query_where . ( !empty ( static::$lock[ get_called_class () ] ) ? ' FOR UPDATE' : '' ) );
		if ( !empty ( $rows ) ) {
			foreach ( $rows as &$row ) {
				foreach ( $sets as $key ) {
					if ( empty ( $row[ $key ] ) ) {
						$row[ $key ] = array();
					} else {
						$row[ $key ] = mb_split ( ',', $row[ $key ] );
					}
				}
			}
			unset ( $row );
		}

		return $rows;
	}

	/**
	 * @todo  узнать по поводу варианта, когда инициализируется 2 разных класса с 1м ИД
	 * @param mixed $id
	 * @param mixed $lock
	 * @return \DB\table
	 */
	static public function instance ( $id = null, $lock = null ) {
		if ( !isset ( $id ) || is_array ( $id ) ) {
			return new static ( null, array() );
		}
		$class = get_called_class ();
		if ( !isset ( static::$instances[ $class ][ $id ] ) ) {
			if ( isset ( $lock ) ) {
				$default_lock                       = isset ( static::$lock[ $class ] ) ? static::$lock[ $class ] : false;
				static::$lock[ $class ]             = $lock;
				static::$instances[ $class ][ $id ] = new static ( $id );
				static::$lock[ $class ]             = $default_lock;
			} else {
				static::$instances[ $class ][ $id ] = new static ( $id );
			}
		}

		return static::$instances[ $class ][ $id ];
	}

	static public function test () {
		print_r ( static::$instances );
	}

	/*
	  static public function instances( $ids ) {
	  $list = array();
	  $result = array();
	  foreach ( $ids as $id ) {
	  $id = intval( $id );
	  if ( !isset( static::$instances[$id] ) ) {
	  $list[] = $id;
	  }
	  }
	  }
	 */

	static public function item ( $row ) {
		return new static ( $row );
	}

	static public function alias () {
		return isset ( static::$alias ) ? static::$alias : mb_substr ( static::$name, 2 );
	}

	static public function query_field ( $name, $joined = false ) {
		$temp = new static ( null, array() );
		if ( !isset ( static::$fields[ $name ] ) && !property_exists ( $temp, $name ) ) {
			\trigger_error ( 'Invalid field ' . static::$name . '.' . $name, E_USER_WARNING );
			throw new \Exception();
		}

		if ( $joined ) {
			if ( static::$fields[ $name ][ 'type' ] == 'timestamp' ) {
				return 'UNIX_TIMESTAMP(`' . static::alias () . '`.`' . $name . '`) AS `' . static::alias () . '.' . $name . '`';
			} else {
				return '`' . static::alias () . '`.`' . $name . '` AS `' . static::alias () . '.' . $name . '`';
			}
		} else {
			if ( static::$fields[ $name ][ 'type' ] == 'timestamp' ) {
				return 'UNIX_TIMESTAMP(`' . static::alias () . '`.`' . $name . '`) AS `' . $name . '`';
			} else {
				return '`' . static::alias () . '`.`' . $name . '`';
			}
		}
	}

	static public function query_join ( $table ) {
		$table_class = 'DB\\' . $table;
		if ( !isset ( static::$joins[ $table ] ) ) {
			\trigger_error ( 'Invalid join ' . static::$name . ' and ' . $table_class::$name, E_USER_WARNING );
			throw new \Exception();
		}

		$join = & static::$joins[ $table ];
		if ( !isset ( $join[ 'sql' ] ) ) {
			$join[ 'sql' ] = ( isset ( $join[ 'type' ] ) ? $join[ 'type' ] : 'INNER' ) . ' JOIN `' . $table_class::$name . '` AS `' . $table_class::alias () . '` ON ';
			$on            = array();
			foreach ( $join[ 'on' ] as $field1 => $field2 ) {
				$on[ ] = '`' . $table_class::alias () . '`.`' . $field1 . '`=`' . static::alias () . '`.`' . $field2 . '`';
			}

			$join[ 'sql' ] .= implode ( ' AND ', $on );
		}

		return $join[ 'sql' ];
	}

	public function exists () {
		if ( empty ( $this->row ) ) {
			if ( empty ( $this->errors_data ) ) {
				$this->error ( 'exists', 'Ошибка доступа!' );
			}
			return false;
		} else {
			return true;
		}
	}

	public function clear () {
		$this->row = array();
		$this->id  = false;
	}

	final public function id () {
		return $this->id;
	}

	final public function row ( $key = false ) {
		return empty ( $key ) ? $this->row : ( isset ( $this->row[ $key ] ) ? $this->row[ $key ] : null );
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	final public function in_set ( $key, $value ) {
		return in_array ( $value, $this->row[ $key ], true );
	}

	final public function to_set ( $key, $value ) {
		$result = $this->row[ $key ];
		if ( !in_array ( $value, $result ) )
			$result[ ] = $value;
		return $result;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return array
	 */
	final public function from_set ( $key, $value ) {
		return array_filter (
			$this->row[ $key ], function ( $item ) use ( $value ) {
			return $value !== $item;
		} );
	}

	final public function change_set ( $key, $value, $add = null ) {
		if ( !isset ( $add ) ) {
			$add = !$this->in_set ( $key, $value );
		}

		if ( $add ) {
			return $this->to_set ( $key, $value );
		} else {
			return $this->from_set ( $key, $value );
		}
	}

	final static public function find_in_set ( $field, $value ) {
		return in_array ( $value, $field, true );
	}

	final static public function errors_messages ( $errors ) {
		static::$errors = array_merge ( static::$errors, $errors );
	}


	/**
	 * @param bool|array $values
	 * @return $this|array
	 */
	final public function errors ( $values = false ) {
		if ( $values === false ) {
			return $this->errors_data;
		} else {
			foreach ( $values as $key => $value ) {
				if ( !isset ( $value ) ) {
					if ( isset ( static::$errors[ $key ] ) ) {
						$value = static::$errors[ $key ];
					} elseif ( isset ( static::$fields[ $key ] ) && isset ( static::$fields[ $key ][ 'error' ] ) ) {
						$value = static::$fields[ $key ][ 'error' ];
					} else {
						//\trigger_error( 'Error message is not set for table `' . static::$name . '` key ' . $key, \E_USER_WARNING );
						//throw new \Exception();
						//echo $key;
						$value = static::$errors[ '_field' ];
					}
				}
				$this->errors_data[ $key ] = $value;
			}

			return $this;
		}
	}

	protected function before_delete () {

	}

	public function delete () {
		if ( $this->id () ) {
			$this->before_delete ();
			\DBS::main ()->query ( "DELETE FROM `" . static::$name . "` WHERE `" . static::$id_name . "` = #d", $this->id () );
		}
	}

	final public function error ( $key, $value = false ) {
		if ( $value === false ) {
			$this->errors_data[ $key ] = isset ( static::$errors[ $key ] ) ? static::$errors[ $key ] : static::$fields[ $key ][ 'error' ];
		} else {
			$this->errors_data[ $key ] = $value;
		}

		return $this;
	}

	static public function field ( $key, $property = null ) {
		if ( !isset ( $property ) ) {
			return static::$fields[ $key ];
		} else {
			return static::$fields[ $key ][ $property ];
		}
	}


	static public function field_sql ( $value, $field_info, $check = false ) {
		if ( is_null ( $field_info ) ) {
			return false;
		}

		if ( array_key_exists ( 'on_empty', $field_info ) && empty ( $value ) ) {
			$value = $field_info[ 'on_empty' ];
		}

		if ( !empty ( $field_info[ 'null' ] ) && is_null ( $value ) ) {
			return 'NULL';
		}


		if ( $check ) {
			if ( isset ( $field_info[ 'regexp' ] ) && ( !\mb_ereg_match ( $field_info[ 'regexp' ], $value ) ) ) {
				return false;
			}

			switch ( $field_info[ 'type' ] ) {
				case 'tinyint' :
				case 'int' :
					if ( $value != ( string )intval ( $value ) ) {
						return false;
					}

					if ( !empty ( $field_info[ 'unsigned' ] ) && $value < 0 ) {
						return false;
					}

					if ( isset ( $field_info[ 'min' ] ) && $field_info[ 'min' ] > $value ) {
						return false;
					}

					if ( isset ( $field_info[ 'max' ] ) && $field_info[ 'max' ] < $value ) {
						return false;
					}

					break;
				case 'decimal' :
					if ( !is_numeric ( $value ) ) {
						return false;
					}

					if ( !empty ( $field_info[ 'unsigned' ] ) && $value < 0 ) {
						return false;
					}

					if ( isset ( $field_info[ 'min' ] ) && $field_info[ 'min' ] > $value ) {
						return false;
					}

					if ( isset ( $field_info[ 'max' ] ) && $field_info[ 'max' ] < $value ) {
						return false;
					}

					break;
				case 'varchar' :
				case 'text' :
				case 'blob' :
					$length = mb_strlen ( $value );
					if ( $length > $field_info[ 'length' ] ) {
						return false;
					}
					if ( !empty ( $field_info[ 'length_min' ] ) && $length < $field_info[ 'length_min' ] ) {
						return false;
					}
					break;
				case 'enum' :

					if ( !\in_array ( $value, $field_info[ 'length' ], true ) ) {
						return false;
					}
					break;
				case 'set' :
					if ( \array_diff ( $value, $field_info[ 'length' ] ) ) {
						return false;
					}
					break;
			}
		}

		switch ( $field_info[ 'type' ] ) {
			case 'bool' :
				return empty ( $value ) ? 0 : 1;
			case 'tinyint' :
			case 'int' :
				return intval ( $value );
			case 'float' :
				return number_format ( floatval ( $value ), 12, '.', '' );
			case 'timestamp' :
				if ( $value === 'CURRENT_TIMESTAMP' ) {
					$value = \DBS::main ()->timestamp ();
				}
				return 'FROM_UNIXTIME(' . intval ( $value ) . ')';
			case 'set' :
				return '"' . \DBS::main ()->escape_string ( \implode ( ',', $value ) ) . '"';
			case 'blob':
				return "x'" . bin2hex ( $value ) . "'";
			default :
				return '"' . \DBS::main ()->escape_string ( $value ) . '"';
		}
	}

	static private function field_format ( $value, $field_info ) {
		if ( is_null ( $field_info ) ) {
			\trigger_error ( 'Field info is not set!', E_USER_ERROR );
			return false;
		}

		if ( array_key_exists ( 'on_empty', $field_info ) && empty ( $value ) ) {
			$value = $field_info[ 'on_empty' ];
		}

		switch ( $field_info[ 'type' ] ) {
			case 'bool' :
				return empty ( $value ) ? false : true;
			case 'tinyint' :
			case 'int' :
				return intval ( $value );
			case 'float' :
				return number_format ( floatval ( $value ), 12, '.', '' );
			case 'bool' :
				return empty ( $value ) ? 0 : 1;
			case 'timestamp' :
				if ( $value === 'CURRENT_TIMESTAMP' ) {
					$value = \DBS::main ()->timestamp ();
				}
				return $value;
			case 'set':
				if ( empty( $value ) ) {
					return array();
				}
				return $value;
			default :
				return $value;
		}
	}

	static private function field_default ( $field_info ) {
		$result = $field_info[ 'default' ];
		if ( $field_info[ 'type' ] == 'timestamp' && $result === 'CURRENT_TIMESTAMP' ) {
			$result = \DBS::main ()->timestamp ();
		}

		return $result;
	}

	static public function select ( $where = array(), $fields = array() ) {
		if ( empty ( $fields ) ) {
			$fields = array_keys ( static::$fields );
		}

		$rows   = static::_select ( $fields, $where );
		$result = array();
		foreach ( $rows as $row ) {
			$result[ ] = new static ( null, $row );
		}

		return $result;
	}

	static private function select2 ( $fields = false, $where = array() ) {
		return false;
		if ( empty ( $fields ) ) {
			$fields = array_keys ( self::$fields );
		}

		$query_fields = '`' . static::$id_name . '`';
		foreach ( $fields as $field ) {
			if ( !isset ( static::$fields[ $field ] ) ) {
				throw new \Exception();
			}
			if ( static::$fields[ $field ][ 'type' ] == 'timestamp' ) {
				$query_fields .= ',UNIX_TIMESTAMP(`' . $field . '`) AS `' . $field . '`';
			} else {
				$query_fields .= ',`' . $field . '`';
			}
		}

		$query_where = '';
		foreach ( $where as $expression ) {
			$key = $expression[ 0 ];

			if ( !isset ( static::$fields[ $key ] ) ) {
				throw new \Exception();
			}

			if ( isset ( $expression[ 2 ] ) ) {
				$query_where .= ',`' . $key . '`' . $expression[ 1 ] . self::field_sql ( $expression[ 2 ], static::$fields[ $key ] );
			} else {
				$query_where .= ',`' . $key . '`' . $expression[ 1 ];
			}
		}

		$query = '>' . \DBS::main ()->escape ( 'SELECT ' . $query_fields . ' FROM `' . static::$name . '` WHERE ' ) . mb_substr ( $query_where, 1 );
		$rows  = \DBS::main ()->select_assoc ( $query );
		foreach ( $rows as &$row ) {
			$row = new static ( null, $row );
		}
		unset ( $row );
		return $rows;
	}

	/**
	 * @param array $data
	 * @param array $errors
	 * @return static
	 * @throws \Exception
	 */
	static public function insert ( $data = array(), $errors = array() ) {
		if ( empty ( $errors ) ) {
			$errors = $data;
		} else {
			$errors = array_flip ( $errors );
		}

		$row = array( static::$id_name => null );

		$query_set  = '';
		$field_info = null;
		foreach ( $errors as $key => &$error ) {
			$field_info = & static::$fields[ $key ];
			if ( !array_key_exists ( $key, $data ) ) {
				if ( !array_key_exists ( 'default', $field_info ) ) {
					$error = isset ( $field_info[ 'error' ] ) ? $field_info[ 'error' ] : null;
					continue;
				} else {
					unset ( $errors[ $key ] );
				}
			} else {
				$value = self::field_sql ( $data[ $key ], $field_info, true );
				if ( $value === false ) {
					$error = isset ( $field_info[ 'error' ] ) ? $field_info[ 'error' ] : '';
				} else {
					$query_set .= ',`' . $key . '`= ' . $value;
					$row[ $key ] = self::field_format ( $data[ $key ], $field_info );
					unset ( $errors[ $key ] );
				}
			}
		}
		unset ( $error );
		unset ( $temp );
		unset ( $field_info );

		if ( !empty ( $errors ) ) {
			$result = new static ( null, array() );
			$result->errors ( $errors );
			return $result;
		}

		foreach ( static::$fields as $key => &$field_info ) {
			if ( !\array_key_exists ( $key, $row ) ) {
				if ( !array_key_exists ( 'default', $field_info ) ) {
					trigger_error ( 'Default value for `' . static::$name . '`.`' . $key . '` is not set!', \E_USER_WARNING );
					throw new \Exception();
				}
				$row[ $key ] = self::field_default ( $field_info );
			}
		}
		unset ( $field_info );

		if ( empty ( $query_set ) ) {
			$query_set = 'id=NULL';
		} else {
			$query_set = mb_substr ( $query_set, 1 );
		}
		$query = '>' . \DBS::main ()->escape ( 'INSERT INTO `' . static::$name . '` SET ' ) . $query_set;

		\DBS::main ()->query ( $query );
		$row[ static::$id_name ] = \DBS::main ()->insert_id ();

		$temp = new static ( null, $row );
		if ( !$temp->on_insert () ) {
			return new static ( null, array() );
		} else {
			return $temp;
		}


		return new static ( null, $row );
	}

	static public function insert_ignore ( &$is_ignored, $data = array(), $errors = array() ) {
		if ( empty ( $errors ) ) {
			$errors = $data;
		} else {
			$errors = array_flip ( $errors );
		}

		$row = array( static::$id_name => false );

		$query_set  = '';
		$field_info = null;
		foreach ( $errors as $key => &$error ) {
			$field_info = & static::$fields[ $key ];
			if ( !array_key_exists ( $key, $data ) ) {
				if ( !array_key_exists ( 'default', $field_info ) ) {
					$error = isset ( $field_info[ 'error' ] ) ? $field_info[ 'error' ] : null;
					continue;
				} else {
					unset ( $errors[ $key ] );
				}
			} else {
				$value = self::field_sql ( $data[ $key ], $field_info, true );
				if ( $value === false ) {
					$error = isset ( $field_info[ 'error' ] ) ? $field_info[ 'error' ] : null;
				} else {
					$query_set .= ',`' . $key . '`= ' . $value;
					$row[ $key ] = self::field_format ( $data[ $key ], $field_info );
					unset ( $errors[ $key ] );
				}
			}
		}
		unset ( $error );
		unset ( $field_info );

		if ( !empty ( $errors ) ) {
			$result = new static ( null, array() );
			$result->errors ( $errors );
			return $result;
		}

		foreach ( static::$fields as $key => &$field_info ) {
			if ( !\array_key_exists ( $key, $row ) ) {
				$row[ $key ] = self::field_default ( $field_info );
			}
		}
		unset ( $field_info );

		$query = '>' . \DBS::main ()->escape ( 'INSERT IGNORE INTO `' . static::$name . '` SET ' ) . mb_substr ( $query_set, 1 );
		\DBS::main ()->query ( $query );
		$is_ignored = \DBS::main ()->affected_rows () == 0;

		if ( $is_ignored ) {
			return new static ( null, array() );
		} else {
			$row[ static::$id_name ] = \DBS::main ()->insert_id ();

			$temp = new static ( null, $row );
			if ( !$temp->on_insert () ) {
				return new static ( null, array() );
			} else {
				return $temp;
			}

			return new static ( null, $row );
		}
	}

	/**
	 * @param data_insert Данные для insert
	 * @param data_update Данные для update
	 * @param errors массив допустимых полей
	 */
	static public function insert_or_update ( &$is_update, $data_insert = array(), $data_update = array(), $errors_insert = array(), $errors_update = array() ) {
		if ( empty ( $errors_insert ) ) {
			$errors_insert = $data_insert;
		} else {
			$errors_insert = array_flip ( $errors_insert );
		}

		$row = array( static::$id_name => false );

		$query_insert = '';
		$field_info   = null;
		foreach ( $errors_insert as $key => &$error ) {
			$field_info = & static::$fields[ $key ];
			if ( !array_key_exists ( $key, $data_insert ) ) {
				if ( !array_key_exists ( 'default', $field_info ) ) {
					$error = isset ( $field_info[ 'error' ] ) ? $field_info[ 'error' ] : null;
					continue;
				} else {
					unset ( $errors_insert[ $key ] );
				}
			} else {
				$value = self::field_sql ( $data_insert[ $key ], $field_info, true );
				if ( $value === false ) {
					$error = isset ( $field_info[ 'error' ] ) ? $field_info[ 'error' ] : null;
				} else {
					$query_insert .= ',`' . $key . '`= ' . $value;
					$row[ $key ] = self::field_format ( $data_insert[ $key ], $field_info );
					unset ( $errors_insert[ $key ] );
				}
			}
		}
		unset ( $error );
		unset ( $field_info );

		if ( empty ( $errors_update ) ) {
			$errors_update = $data_update;
		} else {
			$errors_update = array_flip ( $errors_update );
		}

		$query_update = '';
		$field_info   = null;
		foreach ( $errors_update as $key => &$error ) {
			$field_info = & static::$fields[ $key ];
			if ( array_key_exists ( $key, $data_update ) ) {
				$value = self::field_sql ( $data_update[ $key ], $field_info, true );
				if ( $value === false ) {
					$error = isset ( $field_info[ 'error' ] ) ? $field_info[ 'error' ] : null;
				} else {
					$query_update .= ',`' . $key . '`= ' . $value;
					unset ( $errors_update[ $key ] );
				}
			}
		}
		unset ( $error );
		unset ( $field_info );

		$errors = array_merge ( $errors_insert, $errors_update );

		if ( !empty ( $errors ) ) {
			$result = new static ( null, array() );
			$result->errors ( $errors );
			return $result;
		}

		$query = '>' . \DBS::main ()->escape ( 'INSERT INTO `' . static::$name . '` SET ' ) . mb_substr ( $query_insert, 1 ) . ' ON DUPLICATE KEY UPDATE `' . static::$id_name . '`=LAST_INSERT_ID(`' . static::$id_name . '`)' . $query_update;
		\DBS::main ()->query ( $query );

		$row[ static::$id_name ] = \DBS::main ()->insert_id ();

		$affected_rows = \DBS::main ()->affected_rows ();
		if ( $affected_rows == 1 ) {
			$is_update = false;
		} else {
			$is_update = true;
		}

		if ( !$is_update ) {

			foreach ( static::$fields as $key => &$field_info ) {
				if ( !\array_key_exists ( $key, $row ) ) {
					$row[ $key ] = self::field_default ( $field_info );
				}
			}
			unset ( $field_info );

			$temp = new static ( null, $row );
			if ( !$temp->on_insert () ) {
				return new static ( null, array() );
			} else {
				return $temp;
			}
		} else {
			$temp = new static ( $row[ static::$id_name ] );
			if ( !$temp->on_update ( $data_update ) )
				return new static ( null, array() );

			return $temp;
		}
	}

	/**
	 * @param array|string $data
	 * @param array|int|string $errors
	 * @return bool|int
	 */
	public function update ( $data = array(), $errors = array() ) {

		if ( empty ( $data ) ) {
			return true;
		}

		if ( is_string ( $data ) ) {
			$data   = array( $data => $errors );
			$errors = false;
		}

		$data_update = array_intersect_key ( $this->row (), $data );

		if ( empty ( $errors ) ) {
			$errors = $data;
		} else {
			$errors = array_flip ( $errors );
		}

		$row = $this->row;

		$query_set  = '';
		$field_info = null;
		foreach ( $errors as $key => &$error ) {
			$field_info = & static::$fields[ $key ];
			if ( array_key_exists ( $key, $data ) ) {
				$value = self::field_sql ( $data[ $key ], $field_info, true );
				if ( $value === false ) {
					$error = isset ( $field_info[ 'error' ] ) ? $field_info[ 'error' ] : null;
				} else {
					$query_set .= ',`' . $key . '`= ' . $value;
					$row[ $key ] = self::field_format ( $data[ $key ], $field_info );
					unset ( $errors[ $key ] );
				}
			}
		}
		unset ( $error );
		unset ( $field_info );

		if ( !empty ( $errors ) ) {

			$this->errors ( $errors );
			return false;
		}

		$query = \DBS::main ()->escape ( '>UPDATE `' . static::$name . '` SET ' ) . mb_substr ( $query_set, 1 ) . \DBS::main ()->escape ( ' WHERE `' . static::$id_name . '` = #d', $this->id );
		\DBS::main ()->query ( $query );

		$this->row = $row;

		if ( \DBS::main ()->affected_rows () > 0 ) {
			if ( $this->on_update ( $data_update ) ) {
				return 1;
			} else {
				return false;
			}
		} else {
			return 2;
		}
	}


	/**
	 * @param array $data_update - старые данные
	 * @return boolean
	 */
	protected function on_update ( $data_update ) {
		return true;
	}

	protected function on_insert () {
		return true;
	}

	static function getTableName () {
		return static::$name;
	}

	/**
	 * @deprecated
	 * @todo удалить к чертям
	 * @param type $message
	 * @return string
	 */
	static public function tooltip ( $message = false ) {
		if ( empty ( $message ) )
			return "";

		if ( is_array ( $message ) )
			$actual_off = $message;
		elseif ( is_string ( $message ) )
			$actual_off = explode ( ',', $message );
		else
			return '';

		return implode ( '<br/>', array_intersect_key ( static::$off, array_flip ( $actual_off ) ) );
	}

}

?>

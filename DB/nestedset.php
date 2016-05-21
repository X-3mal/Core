<?php

namespace DB;

class nestedset extends table {

	static protected $left_key = 'left_key';
	static protected $right_key = 'right_key';
	static protected $level = 'level';
	static protected $parent_id = 'parent_id';
	static protected $path = 'path';
	static protected $tree = null;
	static protected $order = 'order';

	const TYPE_CHILD_LAST = 0;
	const TYPE_AFTER      = 1;
	const TYPE_BEFORE     = 2;
	const DELETE          = 3;

	/**
	 * @param array $data
	 * @param array $errors
	 * @param int $type
	 * @param int $id
	 * @return \DB\nestedset
	 */
	static public function insert ( $data = array(), $errors = array(), $type = null, $id = null ) {
		/* @var $element nestedset */
		if ( !$result = static::table_locked () ) {
			if ( $result = static::lock_table () ) {

				if ( $data instanceof static ) {
					$temp_item = $data;
					$data      = $temp_item->row ();
					unset( $data[ 'id' ] );
				}
				/* @var $temp nestedset */
				$temp = new static( null, array() );

				switch ( $type ) {
					case self::TYPE_AFTER:

						$check   = $temp->check_element ( $id, $type );
						$element = $check[ 'element' ];

						if ( empty( $check[ 'result' ] ) ) {
							$error_temp = $element;
							break;
						}

						$data[ self::$left_key ]  = (int)$element->row ( self::$right_key ) + 1;
						$data[ self::$right_key ] = (int)$element->row ( self::$right_key ) + 2;
						$data[ self::$level ]     = (int)$element->row ( self::$level );
						$data[ self::$parent_id ] = $element->row ( self::$parent_id );

						$right_key = $element->row ( self::$right_key );
						self::update_keys ( array( 'right_key' => $right_key ), $type );

						unset( $element );
						break;
					case self::TYPE_BEFORE:

						$check   = $temp->check_element ( $id, $type );
						$element = $check[ 'element' ];

						if ( empty( $check[ 'result' ] ) ) {
							$error_temp = $element;
							break;
						}

						$data[ self::$left_key ]  = (int)$element->row ( self::$left_key );
						$data[ self::$right_key ] = (int)$element->row ( self::$right_key );
						$data[ self::$level ]     = (int)$element->row ( self::$level );
						$data[ self::$parent_id ] = $element->row ( self::$parent_id );

						$right_key = $element->row ( self::$right_key ) - 2;

						self::update_keys ( array( 'right_key' => $right_key ), $type );
						unset( $element );

						break;
					case self::TYPE_CHILD_LAST:

						$check   = $temp->check_element ( $id, $type );
						$element = $check[ 'element' ];
						//                        var_dump( $check );

						if ( empty( $check[ 'result' ] ) ) {

							$right_key                  = \DBS::main ()->select_value ( 'SELECT MAX(`' . static::$right_key . '`) FROM ' . static::$name );
							$data[ static::$right_key ] = $right_key + 2;
							$data[ static::$left_key ]  = $right_key + 1;
							$data[ static::$level ]     = 1;

							self::update_keys ( array( 'right_key' => $right_key ), self::TYPE_AFTER );
						} else {

							$data[ self::$left_key ]  = (int)$element->row ( self::$right_key );
							$data[ self::$right_key ] = (int)$element->row ( self::$right_key ) + 1;
							$data[ self::$level ]     = (int)$element->row ( self::$level ) + 1;
							$data[ self::$parent_id ] = $element->id ();

							$right_key = $element->row ( self::$right_key );

							self::update_keys ( array( 'right_key' => $right_key ), $type );
							unset( $element );
						}

						break;
					default:
						unset( $data[ static::$right_key ] );
						unset( $data[ static::$left_key ] );
						unset( $data[ static::$level ] );
						unset( $data[ static::$parent_id ] );
				}

				if ( isset( $error_temp ) ) {
					static::unlock_tables ();
					return $error_temp;
				}


				if ( isset( $temp_item ) && $temp_item->exists () ) {
					if ( $temp_item->_update ( $data, $errors ) )
						$result = $temp_item;
					else {
						return static::instance ();
					}
				} else{
					$result = parent::insert ( $data, $errors );
				}
			}
			static::unlock_tables ();
		}
		return $result;
	}

	/**
	 * @param array $data
	 * @param array $errors
	 * @return \DB\nestedset
	 */
	public function update ( $data = array(), $errors = array() ) {

		unset( $data[ static::$right_key ] );
		unset( $data[ static::$left_key ] );
		unset( $data[ static::$level ] );


		return parent::update ( $data, $errors );
	}

	protected function _update ( $data = array(), $errors = array() ) {
		return parent::update ( $data, $errors );
	}

	public function update_after ( $id, $data = array(), $errors = array() ) {
		/* @var $element nestedset */
		if ( $this->exists () || !static::table_locked () ) {
			if ( static::lock_table () ) {

				$check   = $this->check_element ( $id, self::TYPE_AFTER );
				$element = $check[ 'element' ];

				if ( empty( $check[ 'result' ] ) ) {
					$error_temp = $element;
					return $error_temp;
				}

				$skew_tree  = $this->row ( static::$right_key ) - $this->row ( static::$left_key ) + 1;
				$skew_level = $element->row ( static::$level ) - $this->row ( static::$level );

				if ( $this->row ( static::$right_key ) > $element->row ( static::$right_key ) ) {
					$skew_edit = $element->row ( static::$right_key ) - $this->row ( static::$left_key ) + 1;
					$result    = $this->move_up_sql (
									  array(
										   'left_key'       => $this->row ( static::$left_key ),
										   'right_key'      => $this->row ( static::$right_key ),
										   'skew_edit'      => $skew_edit,
										   'skew_tree'      => $skew_tree,
										   'skew_level'     => $skew_level,
										   'right_key_near' => $element->row ( static::$right_key ),
									  )
					);
				} else {
					$skew_edit = $element->row ( static::$right_key ) - $this->row ( static::$left_key ) + 1 - $skew_tree;
					$result    = $this->move_down_sql (
									  array(
										   'left_key'       => $this->row ( static::$left_key ),
										   'right_key'      => $this->row ( static::$right_key ),
										   'skew_edit'      => $skew_edit,
										   'skew_tree'      => $skew_tree,
										   'skew_level'     => $skew_level,
										   'right_key_near' => $element->row ( static::$right_key ),
									  )
					);
				}

				\DBS::main ()->query ( $result );

				unset( $data[ static::$right_key ] );
				unset( $data[ static::$left_key ] );
				unset( $data[ static::$level ] );

				$data[ static::$parent_id ] = ( $element->row ( static::$parent_id ) !== false ? $element->row ( static::$parent_id ) : null );

				parent::update ( $data, $errors );
			}
			static::unlock_tables ();
		}
		return $this;
	}

	public function update_before ( $id, $data = array(), $errors = array() ) {
		/* @var $element nestedset */
		if ( $this->exists () || !static::table_locked () ) {
			if ( static::lock_table () ) {

				$check   = $this->check_element ( $id, self::TYPE_AFTER );
				$element = $check[ 'element' ];

				if ( empty( $check[ 'result' ] ) ) {
					$error_temp = $element;
					return $error_temp;
				}


				$skew_tree  = $this->row ( static::$right_key ) - $this->row ( static::$left_key ) + 1;
				$skew_level = $element->row ( static::$level ) - $this->row ( static::$level );

				if ( $this->row ( static::$right_key ) > $element->row ( static::$right_key ) ) {

					$skew_edit = $element->row ( static::$left_key ) - $this->row ( static::$left_key );
					$result    = $this->move_up_sql (
									  array(
										   'left_key'       => $this->row ( static::$left_key ),
										   'right_key'      => $this->row ( static::$right_key ),
										   'skew_edit'      => $skew_edit,
										   'skew_tree'      => $skew_tree,
										   'skew_level'     => $skew_level,
										   'right_key_near' => $element->row ( static::$left_key ) - 1,
									  )
					);
				} else {

					$skew_edit = $element->row ( static::$left_key ) - $this->row ( static::$left_key ) - $skew_tree;
					$result    = $this->move_down_sql (
									  array(
										   'left_key'       => $this->row ( static::$left_key ),
										   'right_key'      => $this->row ( static::$right_key ),
										   'skew_edit'      => $skew_edit,
										   'skew_tree'      => $skew_tree,
										   'skew_level'     => $skew_level,
										   'right_key_near' => $element->row ( static::$left_key ) - 1,
									  )
					);
				}
				\DBS::main ()->query ( $result );

				unset( $data[ static::$right_key ] );
				unset( $data[ static::$left_key ] );
				unset( $data[ static::$level ] );
				$data[ 'parent_id' ] = ( $element->row ( 'parent_id' ) !== false ? $element->row ( 'parent_id' ) : null );

				parent::update ( $data, $errors );
			}
			static::unlock_tables ();
		}
		return $this;
	}

	public function update_append ( $id, $data = array(), $errors = array() ) {
		if ( $this->exists () || !static::table_locked () ) {
			/* @var $element nestedset */
			if ( static::lock_table () ) {

				$check   = $this->check_element ( $id, self::TYPE_AFTER );
				$element = $check[ 'element' ];

				if ( empty( $check[ 'result' ] ) ) {
					$error_temp = $element;
					return $error_temp;
				}

				$skew_tree  = $this->row ( static::$right_key ) - $this->row ( static::$left_key ) + 1;
				$skew_level = $element->row ( static::$level ) - $this->row ( static::$level ) + 1;

				if ( $this->row ( static::$right_key ) > $element->row ( static::$right_key ) ) {

					$skew_edit = $element->row ( static::$right_key ) - $this->row ( static::$left_key );
					$result    = $this->move_up_sql (
									  array(
										   'left_key'       => $this->row ( static::$left_key ),
										   'right_key'      => $this->row ( static::$right_key ),
										   'skew_edit'      => $skew_edit,
										   'skew_tree'      => $skew_tree,
										   'skew_level'     => $skew_level,
										   'right_key_near' => $element->row ( static::$right_key ) - 1,
									  )
					);
				} else {

					$skew_edit = $element->row ( static::$right_key ) - $this->row ( static::$left_key ) - $skew_tree;
					$result    = $this->move_down_sql (
									  array(
										   'left_key'       => $this->row ( static::$left_key ),
										   'right_key'      => $this->row ( static::$right_key ),
										   'skew_edit'      => $skew_edit,
										   'skew_tree'      => $skew_tree,
										   'skew_level'     => $skew_level,
										   'right_key_near' => $element->row ( static::$right_key ) - 1,
									  )
					);
				}
				\DBS::main ()->query ( $result );


				unset( $data[ static::$right_key ] );
				unset( $data[ static::$left_key ] );
				unset( $data[ static::$level ] );
				$data[ 'parent_id' ] = $element->id ();


				parent::update ( $data, $errors );
			}
			static::unlock_tables ();
		}
		return $this;
	}

	private function check_element ( $id = null, $type ) {
		/* @var $error_temp nestedset */
		/* @var $element nestedset */
		switch ( $type ) {
			case self::TYPE_AFTER :
				$message = 'AFTER';
				break;
			case self::TYPE_BEFORE:
				$message = 'BEFORE';
				break;
			case self::TYPE_CHILD_LAST :
				$message = 'LAST CHILD';
				break;
			default :

				$error_temp = new static( null, array() );
				$error_temp->error ( 'message', 'type error' );
				return array( 'result' => false, 'element' => $error_temp );
		}

		if ( !isset( $id ) ) {
			$error_temp = new static( null, array() );
			$error_temp->error ( 'message', '{' . $message . '} element was not set ' );
			return array( 'result' => false, 'element' => $error_temp );
		}

		$element = new static( $id );
		if ( !$element->exists () ) {
			$error_temp = new static( null, array() );
			$error_temp->error ( 'message', '{' . $message . '} element with {id} = ' . $id . ' is absent' );
			return array( 'result' => false, 'element' => $error_temp );
		}
		return array( 'result' => true, 'element' => $element );
	}

	/**
	 * удаление значения из дерева
	 * @return \DB\nestedset
	 */
	public function delete () {
		if ( $this->exists () || !static::table_locked () ) {
			if ( static::lock_table () ) {
				$sql = 'DELETE FROM `' . static::$name . '`
                        WHERE `' . static::$left_key . '` >= #d
                        AND `' . static::$right_key . '` <= #d';

				if ( \DBS::main ()->query ( $sql, $this->row ( static::$left_key ), $this->row ( static::$right_key ) ) ) {
					return static::update_keys ( array(
													  static::$right_key => $this->row ( static::$right_key ),
													  static::$left_key  => $this->row ( static::$left_key ) ), static::DELETE );
				}
			}
			static::unlock_tables ();
		}
		return new static( null, array() );
	}

	/*     * ******************* Работа с переносом ключей *********************************** */

	/**
	 * Обновление ключей дерева, в зависимости от действия
	 * При вставке нового элемента
	 * @param  $data
	 * @param  $type константы класса( self::TYPE_CHILD_LAST | self::TYPE_AFTER | self::TYPE_BEFORE | self::DELETE  )
	 * @return boolean
	 */
	private static function update_keys ( $data, $type = self::TYPE_CHILD_LAST ) {
		$right_key = isset( $data[ 'right_key' ] ) ? $data[ 'right_key' ] : null;
		if ( is_null ( $right_key ) )
			return false;

		switch ( $type ) {
			case self::TYPE_CHILD_LAST:
			{
				$sql_result = \DBS::main ()->escape (
								  ' UPDATE ' . static::$name . '
                                                 SET ' . static::$right_key . ' = ' . static::$right_key . ' + 2,
                                                     ' . static::$left_key . ' = IF( ' . static::$left_key . ' > #d,
                                                                                     ' . static::$left_key . ' + 2 ,
                                                                                     ' . static::$left_key . ')
                                                 WHERE ' . static::$right_key . '>= #d'
									  , $right_key
									  , $right_key );
			}
				break;
			case self::TYPE_BEFORE:
			case self::TYPE_AFTER:
			{
				$sql_result = \DBS::main ()->escape (
								  ' UPDATE ' . static::$name . '
                                                 SET ' . static::$right_key . ' = ' . static::$right_key . ' + 2,
                                               ' . static::$left_key . ' = IF( ' . static::$left_key . ' > #d,
                                                                               ' . static::$left_key . ' + 2 ,
                                                                               ' . static::$left_key . ' )
                                                WHERE ' . static::$right_key . ' > #d'
									  , $right_key
									  , $right_key );
			}
				break;
			case self::DELETE:
			{
				$left_key = isset( $data[ 'left_key' ] ) ? $data[ 'left_key' ] : null;
				if ( is_null ( $left_key ) )
					return false;

				$sql_result = \DBS::main ()->escape (
								  ' UPDATE ' . static::$name . '
                                                SET ' . static::$right_key . ' = ' . static::$right_key . ' - ' . ( $right_key - $left_key + 1 ) . ',
                                                    ' . static::$left_key . ' = IF(
                                                                                    ' . static::$left_key . ' > #d,
                                                                                    ' . static::$left_key . ' - ' . ( $right_key - $left_key + 1 ) . ' ,
                                                                                    ' . static::$left_key . ')
                                                WHERE ' . static::$right_key . ' >= #d'
									  , $left_key
									  , $right_key );
			}
				break;
			default:
				$sql_result = '';
				break;
		}
		return \DBS::main ()->query ( $sql_result );
	}

	/**
	 * Возвращаяет SQL для выполнения перемещения элемента в дереве ввекрх.
	 * Данный запрос работает для переноса элемета(ветки) вверх по дереву
	 * @param array $data left_key && right_key && skew_edit && skew_tree && skew_level && right_key_near
	 * @return boolean|string
	 */
	private function move_up_sql ( $data = array() ) {
		if (
			empty( $data ) ||
			!isset( $data[ 'left_key' ] ) ||
			!isset( $data[ 'right_key' ] ) ||
			!isset( $data[ 'skew_edit' ] ) ||
			!isset( $data[ 'skew_tree' ] ) ||
			!isset( $data[ 'skew_level' ] ) ||
			!isset( $data[ 'right_key_near' ] )
		)
			return false;

		$sql = 'UPDATE `' . static::$name . '`
                SET `' . static::$right_key . '` = IF( `' . static::$left_key . '` >= #d,
                           `' . static::$right_key . '` + #d,
                                IF( `' . static::$right_key . '` <  #d,
                                    `' . static::$right_key . '` + #d,
                                    `' . static::$right_key . '`
                                  )
                          ),
        `' . static::$level . '` = IF( `' . static::$left_key . '` >= #d,
                      `' . static::$level . '` + #d,
                      `' . static::$level . '`
                  ),
        `' . static::$left_key . '` = IF( `' . static::$left_key . '` >= #d,
                       `' . static::$left_key . '` + #d,
                       IF(
                           `' . static::$left_key . '` > #d,
                           `' . static::$left_key . '` + #d,
                           `' . static::$left_key . '`
                         )
                     )
        WHERE `' . static::$right_key . '` > #d AND `' . static::$left_key . '` < #d ';

		return \DBS::main ()->escape (
				   $sql, $data[ 'left_key' ], $data[ 'skew_edit' ], $data[ 'left_key' ], $data[ 'skew_tree' ], $data[ 'left_key' ], $data[ 'skew_level' ], $data[ 'left_key' ], $data[ 'skew_edit' ], $data[ 'right_key_near' ], $data[ 'skew_tree' ], $data[ 'right_key_near' ], $data[ 'right_key' ]
		);
	}

	/**
	 * Возвращаяет SQL для выполнения перемещения элемента в дереве вниз.
	 * Данный запрос работает для переноса элемета(ветки) вверх по дереву
	 * @param array $data left_key && right_key && skew_edit && skew_tree && skew_level && right_key_near
	 * @return boolean|string
	 */
	private function move_down_sql ( $data = array() ) {

		if (
			empty( $data ) ||
			!isset( $data[ 'left_key' ] ) ||
			!isset( $data[ 'right_key' ] ) ||
			!isset( $data[ 'skew_edit' ] ) ||
			!isset( $data[ 'skew_tree' ] ) ||
			!isset( $data[ 'skew_level' ] ) ||
			!isset( $data[ 'right_key_near' ] )
		)
			return false;
		$sql = 'UPDATE ' . static::$name . '
          SET ' . static::$left_key . ' = IF(
                            ' . static::$right_key . ' <=  #d,
                            ' . static::$left_key . ' + #d,
                            IF(
                                ' . static::$left_key . ' > #d,
                                ' . static::$left_key . ' - #d,
                                ' . static::$left_key . '
                              )
                           ),
             ' . static::$level . ' = IF(
                         ' . static::$right_key . ' <= #d,
                         ' . static::$level . ' + #d,
                         ' . static::$level . '
                        ),
             ' . static::$right_key . ' = IF(
                             ' . static::$right_key . ' <= #d,
                             ' . static::$right_key . ' + #d,
                             IF(
                                 ' . static::$right_key . ' <=  #d,
                                 ' . static::$right_key . ' - #d,
                                 ' . static::$right_key . '
                                )
                            )
        WHERE ' . static::$right_key . ' > #d AND ' . static::$left_key . ' <= #d';


		return \DBS::main ()->escape (
				   $sql, $data[ 'right_key' ], $data[ 'skew_edit' ], $data[ 'right_key' ], $data[ 'skew_tree' ], $data[ 'right_key' ], $data[ 'skew_level' ], $data[ 'right_key' ], $data[ 'skew_edit' ], $data[ 'right_key_near' ], $data[ 'skew_tree' ], $data[ 'left_key' ], $data[ 'right_key_near' ]
		);
	}

	/*     * ******************* Работа с переносом ключей *********************************** */

	/*     * *********************** Работа с состоянием таблици ***************************************** */

	/**
	 * Проверка на блокировку таблици
	 * @return \DB\nestedset|boolean
	 */
	private static function table_locked () {
		$checkLockedTable = 'SHOW OPEN TABLES WHERE `In_use` >= 1 and `Table` = \'' . static::$name . '\'';
		/* @var $result nestedset */
		if ( \DBS::main ()->select_exists ( $checkLockedTable ) ) {
			$result = new static( null, array() );
			$result->errors ( array( 'locked' => 'Таблица заблокированна' ) );
			return $result;
		}
		return false;
	}

	/**
	 * Разблокировка всех таблиц,
	 * которые были заблокированы
	 * в данной сессии
	 * @return
	 */
	private static function unlock_tables () {
		$sql_unlock_table = 'unlock tables;';
		return \DBS::main ()->query ( $sql_unlock_table );
	}

	/**
	 * Блокировка таблици
	 * @return
	 */
	private static function lock_table () {
		$sql_lock_table = 'LOCK TABLES ' . static::$name . ' WRITE ';
		return \DBS::main ()->query ( $sql_lock_table );
	}

	public static function normalize ( $keys = true, $tree = false, $path = false ) {

		$tmp_name = static::$name . '_tmp' . mt_rand ();
		if ( $keys ) {


			\DBS::main ()->query (
				'
								CREATE TABLE `' . $tmp_name . '` (
                `id` int(10) unsigned NOT NULL,
                `left` tinyint(1) NOT NULL,
                `path` varchar(2000) DEFAULT NULL,
                `key` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id`,`left`),
                KEY `path` (`path`(255))
                ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;
            ' );

			\DBS::main ()->query (
				'
								INSERT INTO `' . $tmp_name . '`
                SELECT `id`, 0, CONCAT( LPAD( `' . static::$order . '`, 10, "0"), LPAD( `id`, 10, "0") ), 0 FROM `' . static::$name . '` WHERE `' . static::$parent_id . '` IS NULL
            ' );

			do {
				\DBS::main ()->query (
					'
										INSERT IGNORE INTO `' . $tmp_name . '`
                    SELECT `c`.`id`, 0, CONCAT(`p`.`path`, LPAD( `c`.`' . static::$order . '`, 10, "0"), LPAD( `c`.`id`, 10, "0") ), 0 FROM `' . static::$name . '` AS `c` INNER JOIN `' . $tmp_name . '` AS `p` ON `p`.`id` = `c`.`' . static::$parent_id . '`
                ' );
			} while ( \DBS::main ()->affected_rows () > 0 );

			\DBS::main ()->query (
				'
								INSERT INTO `' . $tmp_name . '`
                SELECT `id`, 1, CONCAT( `path`, "/"), 0 FROM `' . $tmp_name . '`
            ' );


			\DBS::main ()->query (
				'
								UPDATE `' . $tmp_name . '`
                SET `path` = CONCAT( `path`, ":") WHERE `left` = 0
            ' );

			\DBS::main ()->query ( 'SET @t = 0;' );

			\DBS::main ()->query (
				'
								UPDATE `' . $tmp_name . '`
                SET `key` = (@t := @t + 1)
                ORDER BY `path` ASC;
            ' );

			\DBS::main ()->query (
				'
								UPDATE `' . static::$name . '` AS `t` INNER JOIN `' . $tmp_name . '` AS `n` ON `n`.`id` = `t`.`id`
                SET `t`.`' . static::$level . '` = LENGTH( `n`.`path` ) / 20, `t`.`' . static::$left_key . '` = `n`.`key` WHERE `n`.`left` = 1;
            ' );

			\DBS::main ()->query (
				'
								UPDATE `' . static::$name . '` AS `t` INNER JOIN `' . $tmp_name . '` AS `n` ON `n`.`id` = `t`.`id`
                SET `t`.`' . static::$right_key . '` = `n`.`key` WHERE `n`.`left` = 0;
            ' );

			\DBS::main ()->query ( 'DROP TABLE `' . $tmp_name . '`' );
		}

		if ( $tree && isset( static::$tree ) ) {
			\DBS::main ()->query ( 'TRUNCATE TABLE `' . static::$tree . '`' );

			\DBS::main ()->query (
				'
								INSERT IGNORE INTO `' . static::$tree . '`
                SELECT `i1`.`id`, `i2`.`id`, `i1`.`' . static::$level . '` - `i2`.`' . static::$level . '`
                FROM `' . static::$name . '` AS `i1`
                INNER JOIN `' . static::$name . '` AS `i2` ON `i2`.`' . static::$left_key . '` <= `i1`.`' . static::$left_key . '` AND `i2`.`' . static::$right_key . '` >= `i1`.`' . static::$right_key . '`
            ' );

			\DBS::main ()->query (
				'
								INSERT IGNORE INTO `' . static::$tree . '`
                SELECT `i2`.`id`, `i1`.`id`, `i2`.`' . static::$level . '` - `i1`.`' . static::$level . '`
                FROM `' . static::$name . '` AS `i1`
                INNER JOIN `' . static::$name . '` AS `i2` ON `i2`.`' . static::$left_key . '` < `i1`.`' . static::$left_key . '` AND `i2`.`' . static::$right_key . '` > `i1`.`' . static::$right_key . '`
            ' );
		}

		if ( $path && isset( static::$path ) ) {
			\DBS::main ()->query (
				'
								CREATE TABLE `' . $tmp_name . '` (
                `id` int(10) unsigned NOT NULL,
                `left_key` int(10) unsigned NOT NULL,                
                `right_key` int(10) unsigned NOT NULL,
                `level` int(10) unsigned NOT NULL
                ) ENGINE=InnoDB
            ' );

			\DBS::main ()->query ( 'INSERT INTO `' . $tmp_name . '` SELECT `id`, `' . static::$left_key . '`, `' . static::$right_key . '`, `' . static::$level . '` FROM `' . static::$name . '`' );
			\DBS::main ()->query (
				'
								UPDATE `' . static::$name . '` AS `i1` SET `i1`.`path` = (
                    SELECT GROUP_CONCAT( `id` ORDER BY `level` DESC )
                    FROM `' . $tmp_name . '` AS `i2`
                    WHERE `i2`.`' . static::$left_key . '` < `i1`.`' . static::$left_key . '` AND `i2`.`' . static::$right_key . '` > `i1`.`' . static::$right_key . '`
                )
            ' );

			\DBS::main ()->query ( 'DROP TABLE `' . $tmp_name . '`' );
		}

	}

	static $need_normalize = array();

	static private function need_normalize ( $field ) {
		return isset( self::$need_normalize[ $field ] ) ? self::$need_normalize[ $field ] : self::$need_normalize[ $field ] = \DBS::main ()->select_exists ( "SELECT `" . static::$id_name . "` FROM `" . static::$name . "` WHERE `" . $field . "` IS NULL LIMIT 1" );
	}

	static function need_normalize_keys () {
		return self::need_normalize ( static::$left_key ) || self::need_normalize ( static::$right_key );
	}

	static function need_normalize_tree () {
		return false;
	}

	static function need_normalize_path () {
		return self::need_normalize ( static::$path );
	}

	static function need_normalize_any () {
		return self::need_normalize_keys () || self::need_normalize_path () || self::need_normalize_tree ();
	}

	/*     * *********************** Работа с состоянием таблицы ***************************************** */
}

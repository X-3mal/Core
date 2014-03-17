<?php

class DBDriver {

	protected $server_id;
	protected $link;
	protected $resource;
	protected $error = false;
	protected $host;
	protected $username;
	protected $password;
	protected $database;
	protected $prefix;
	protected $encoding;
	protected $is_slave;
	protected $js_debug = false;
	protected $js_debug_log = array();
	protected $query_count = 0;
	protected $args;
	protected $i;
	protected $time_prepare;
	protected $time_query;
	private $timestamp;


	protected $transaction;

	function __construct ( $host, $username, $password, $database = '', $encoding = 'utf8', $is_slave = false, $prefix = '', $server_id = 0, $link = null ) {
		$this->server_id = $server_id;
		$this->host      = $host;
		$this->username  = $username;
		$this->password  = $password;
		$this->database  = $database;
		$this->is_slave  = $is_slave;

		if ( isset( $link ) ) {
			$this->link = $link;
		} else {
			if ( !$this->link = mysql_pconnect ( $this->host, $this->username, $this->password, true ) ) {
				throw new \Exception();
			}

			if ( !mysql_select_db ( $this->database, $this->link ) ) {
				throw new \Exception();
			}
		}
		$this->encoding ( 'utf8' );


		$this->prefix ( $prefix );
	}

	function js_debug ( $set = null ) {
		if ( isset( $set ) ) {
			$this->js_debug = !empty( $set );
		}
		return $this->js_debug;
	}

	function js_debug_log ( $query = null, $time = null ) {
		if ( !$this->js_debug () ) {
			return $this->js_debug_log;
		}
		if ( isset( $query ) && isset( $time ) ) {
			$this->js_debug_log[ ] = array( 'query' => $query, 'time' => $time );
		}
		return $this->js_debug_log;
	}

	function Log ( $var ) {
		$var = print_r ( $var, true );
		\DBS::main ()->query ( "INSERT INTO  `offline_logs` SET `text` = #s", $var );
	}

	function link () {
		return $this->link;
	}

	function prefix ( $prefix ) {
		$this->prefix = empty( $prefix ) ? '' : $prefix . '_';
		return $this;
	}

	function encoding ( $string ) {
		$this->encoding = $string;
		mysql_query ( 'SET NAMES ' . $string, $this->link );
		return $this;
	}

	function server_id () {
		return $this->server_id;
	}

	function disconnect () {
		if ( $this->link ) {
			mysql_close ( $this->link );
			$this->link = false;
		}
		return $this;
	}

	function timestamp ( $value = null ) {
		if ( isset( $value ) ) {
			$this->timestamp = null;
		}
		if ( !isset( $this->timestamp ) ) {
			$this->timestamp = isset( $value ) ? intval ( $value ) : time ();
			$this->query ( 'SET SESSION timestamp = #d', $this->timestamp );
		}
		return $this->timestamp;
	}


	public function _escape_callback ( $matches ) {
		switch ( $matches[ 0 ] ) {
			case '##':
				return "#";
			case '#_':
				return $this->prefix;
			case '#b':
				return empty( $this->args[ $this->i++ ] ) ? 0 : 1;
			case '#B':
				if ( is_null ( $this->args[ $this->i ] ) ) {
					$this->i++;
					return "NULL";
					break;
				}
				return empty( $this->args[ $this->i++ ] ) ? 0 : 1;
			case '#d':
				return intval ( $this->args[ $this->i++ ] );
				break;
			case '#D':
				if ( is_null ( $this->args[ $this->i ] ) ) {
					$this->i++;
					return "NULL";
				}
				return intval ( $this->args[ $this->i++ ] );
			case '#f':
				return sprintf ( "%.20F", $this->args[ $this->i++ ] );
			case '#F':
				if ( is_null ( $this->args[ $this->i ] ) ) {
					$this->i++;
					return "NULL";
				}
				return sprintf ( "%.20F", $this->args[ $this->i++ ] );
			case '#t':
				return 'FROM_UNIXTIME(' . max ( 0, min ( 2147483647, intval ( $this->args[ $this->i++ ] ) ) ) . ')';
			case '#T':
				if ( is_null ( $this->args[ $this->i ] ) ) {
					$this->i++;
					return "NULL";
				}
				return 'FROM_UNIXTIME(' . max ( 0, min ( 2147483647, intval ( $this->args[ $this->i++ ] ) ) ) . ')';

			case '#s':
				return "'" . mysql_real_escape_string ( $this->args[ $this->i++ ], $this->link ) . "'";
			case '#S':
				if ( is_null ( $this->args[ $this->i ] ) ) {
					$this->i++;
					return "NULL";
				}
				return "'" . mysql_real_escape_string ( $this->args[ $this->i++ ], $this->link ) . "'";
			case '#l':
				return "'" . mysql_real_escape_string ( mb_ereg_replace ( '([%_])', '\\\1', $this->args[ $this->i++ ] ), $this->link ) . "'";
			case '#L':
				if ( is_null ( $this->args[ $this->i ] ) ) {
					$this->i++;
					return "NULL";
				}
				return "'" . mysql_real_escape_string ( mb_ereg_replace ( '([%_])', '\\\1', $this->args[ $this->i++ ] ), $this->link ) . "'";
			case '#a':
				if ( empty( $this->args[ $this->i ] ) || !is_array ( $this->args[ $this->i ] ) ) {
					$this->i++;
					return "(0)";
				}
				$this->args[ $this->i ] = array_map ( 'intval', $this->args[ $this->i ] );
				return "(" . implode ( ",", $this->args[ $this->i++ ] ) . ")";
			case '#m':
				if ( empty( $this->args[ $this->i ] ) || !is_array ( $this->args[ $this->i ] ) ) {
					$this->i++;
					return "('')";
				} else {
					foreach ( $this->args[ $this->i ] as &$item ) {
						$item = mysql_real_escape_string ( $item, $this->link );
					}
					unset( $item );
				}
				return "('" . implode ( "','", $this->args[ $this->i++ ] ) . "')";
			case '#q':
				return $this->args[ $this->i++ ];
			default:
				return $matches[ 0 ];
		}
	}

	public function _escape ( $args ) {
		$query = $args[ 0 ];

		$this->i    = 1;
		$this->args =& $args;
		return preg_replace_callback ( '/#./u', array( $this, '_escape_callback' ), $query );
	}

	protected function _query ( $args ) {
		$time   = microtime ( true );
		$escape = true;
		if ( is_bool ( $args[ 0 ] ) ) {
			$escape = array_shift ( $args );
		}
		if ( is_array ( $args[ 0 ] ) ) {
			$args = $args[ 0 ];
		}

		if ( mb_substr ( $args[ 0 ], 0, 1 ) == '>' ) {
			$escape    = false;
			$args[ 0 ] = mb_substr ( $args[ 0 ], 1 );
		}

		if ( $escape ) {
			$query = $this->_escape ( $args );
		} else {
			$query = $args[ 0 ];
		}
		$this->time_prepare += microtime ( true ) - $time;
		$time = microtime ( true );

		$this->resource = mysql_query ( $query, $this->link );

		$total_time = microtime ( true ) - $time;
		$this->time_query += $total_time;
		$this->js_debug_log ( $query, $total_time );

		$this->query_count++;
		if ( !$this->resource ) {
			$this->error = true;
			trigger_error ( "query: " . $query . PHP_EOL . mysql_error ( $this->link ), E_USER_WARNING );
			throw new Exception( _ ( 'Во время выполнения запроса произошла ошибка. Обратитесь в службу поддержки. Время ошибки: ' . date ( 'Y-m-d- H:i:s' ) ) );
		}
	}

	function escape ( $query ) {
		return $this->_escape ( func_get_args () );
	}

	public function escape_string ( $string ) {
		return mysql_real_escape_string ( $string, $this->link );
	}

	function query ( $query ) {
		$this->_query ( func_get_args () );
		return $this->resource;
	}

	function rows_count () {
		return mysql_num_rows ( $this->resource );
	}

	function found_rows () {
		return $this->select_value ( 'SELECT FOUND_ROWS()' );
	}

	function insert_id () {
		return mysql_insert_id ( $this->link );
	}

	function insert_id_range () {
		$insert_id     = $this->insert_id ();
		$affected_rows = $this->affected_rows ();
		return array( $insert_id, $insert_id + $affected_rows - 1 );
	}

	function insert_id_list () {
		$insert_id     = $this->insert_id ();
		$affected_rows = $this->affected_rows ();
		return range ( $insert_id, $insert_id + $affected_rows - 1 );
	}

	function affected_rows () {
		return mysql_affected_rows ( $this->link );
	}

	function autocommit ( $value ) {
		$this->query ( 'SET AUTOCOMMIT = #b', $value );
	}

	function commit () {
		$this->transaction = false;
		$this->query ( 'COMMIT' );
		$this->error = false;
	}

	function rollback () {
		$this->transaction = false;
		$this->query ( 'ROLLBACK' );
		$this->error = false;
	}

	function transaction () {
		$this->transaction = true;
		$this->query ( 'START TRANSACTION' );
	}

	function error () {
		return $this->error;
	}

	function select ( $query ) {
		$this->_query ( func_get_args () );
		$rows = array();
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			while ( $rows[ ] = mysql_fetch_assoc ( $this->resource ) ) {

			}
		}
		array_pop ( $rows );
		return $rows;
	}

	/**
	 * ������ � ���� ������
	 * @param string $query �������������� ������ � �������
	 * @param string $query,... ��������� ��� ���������� ������� � �������
	 * @return array() ������ �������� ��������� �� �������
	 */
	function select_assoc ( $query ) {
		$this->_query ( func_get_args () );
		$rows = array();
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			while ( $row = mysql_fetch_assoc ( $this->resource ) ) {
				$rows[ reset ( $row ) ] = $row;
			}
		}
		return $rows;
	}


	function select_multilist ( $query ) {
		$this->_query ( func_get_args () );
		$rows = array();
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			while ( $row = mysql_fetch_assoc ( $this->resource ) ) {
				$rows[ reset ( $row ) ][ ] = $row;
			}
		}
		return $rows;
	}

	function select_multilist_assoc ( $query ) {
		$this->_query ( func_get_args () );
		$rows = array();
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			while ( $row = mysql_fetch_assoc ( $this->resource ) ) {
				$rows[ reset ( $row ) ][ next ( $row ) ] = $row;
			}
		}
		return $rows;
	}

	function select_multilist_column ( $query ) {
		$this->_query ( func_get_args () );
		$rows = array();
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			while ( $row = mysql_fetch_row ( $this->resource ) ) {
				$rows[ $row[ 0 ] ][ $row[ 1 ] ] = $row[ 1 ];
			}
		}
		return $rows;
	}

	function select_multilist_column_assoc ( $query ) {
		$this->_query ( func_get_args () );
		$rows = array();
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			while ( $row = mysql_fetch_row ( $this->resource ) ) {
				$rows[ $row[ 0 ] ][ $row[ 1 ] ] = $row[ 2 ];
			}
		}
		return $rows;
	}

	function select_multilist_assoc_column ( $query ) {
		$this->_query ( func_get_args () );
		$rows = array();
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			while ( $row = mysql_fetch_row ( $this->resource ) ) {
				$rows[ $row[ 0 ] ][ $row[ 1 ] ] = $row[ 2 ];
			}
		}
		return $rows;
	}

	function select_row ( $query ) {
		$this->_query ( func_get_args () );
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			return mysql_fetch_assoc ( $this->resource );
		} else {
			return array();
		}
	}

	function select_column ( $query ) {
		$this->_query ( func_get_args () );
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			$result = array();
			while ( $row = mysql_fetch_row ( $this->resource ) ) {
				$result[ $row[ 0 ] ] = $row[ 0 ];
			}
			return $result;
		} else {
			return array();
		}
	}

	function select_array_column ( $query ) {
		$this->_query ( func_get_args () );
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			$result = array();
			while ( $row = mysql_fetch_row ( $this->resource ) ) {
				$result[ ] = $row[ 0 ];
			}
			return $result;
		} else {
			return array();
		}
	}

	function select_column_assoc ( $query ) {
		$this->_query ( func_get_args () );
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			$result = array();
			while ( $row = mysql_fetch_row ( $this->resource ) ) {
				$result[ $row[ 0 ] ] = $row[ 1 ];
			}
			return $result;
		} else {
			return array();
		}
	}

	function select_value ( $query ) {
		$this->_query ( func_get_args () );
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			return mysql_result ( $this->resource, 0, 0 );
		} else {
			return false;
		}
	}

	function select_exists ( $query ) {
		$this->_query ( func_get_args () );
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $page_number
	 * @param $page_capacity
	 * @param $query
	 * @return DB\Pager
	 */
	function select_paged ( $page_number, $page_capacity, $query ) {
		$page_number = intval ( $page_number );
		if ( $page_number < 1 ) {
			$page_number = 1;
		}
		$args      = array_slice ( func_get_args (), 2 );
		$args[ 0 ] = mb_ereg_replace ( '^\s*(>?)SELECT', '\\1SELECT SQL_CALC_FOUND_ROWS', $args[ 0 ] );
		$args[ 0 ] .= sprintf ( ' LIMIT %d, %d', ( $page_number - 1 ) * $page_capacity, $page_capacity );
		$this->_query ( $args );
		$rows = array();
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			while ( $rows[ ] = mysql_fetch_assoc ( $this->resource ) ) {

			}
		}

		array_pop ( $rows );


		$count      = $this->found_rows ();
		$page_count = floor ( ( $count - 1 ) / $page_capacity ) + 1;

		return new DB\Pager( $rows, $count, $page_count, $page_number );
	}

	/**
	 * @param        int > 0 $page_number
	 * @param int $page_capacity
	 * @param string $query
	 * @return \DB\Pager
	 */
	function select_paged_assoc ( $page_number, $page_capacity, $query ) {
		$page_number = intval ( $page_number );
		if ( $page_number < 1 ) {
			$page_number = 1;
		}
		$args      = array_slice ( func_get_args (), 2 );
		$args[ 0 ] = mb_ereg_replace ( '^\s*(>?)SELECT', '\\1SELECT SQL_CALC_FOUND_ROWS', $args[ 0 ] );
		$args[ 0 ] .= sprintf ( ' LIMIT %d, %d', ( $page_number - 1 ) * $page_capacity, $page_capacity );
		$this->_query ( $args );

		$rows = array();
		if ( $this->resource && ( mysql_num_rows ( $this->resource ) > 0 ) ) {
			while ( $row = mysql_fetch_assoc ( $this->resource ) ) {
				$rows[ reset ( $row ) ] = $row;
			}
		}
		$count = $this->found_rows ();
		return new DB\Pager ( $rows, $count, floor ( ( $count - 1 ) / $page_capacity ) + 1, $page_number );
	}

	public function shutdown () {
		if ( $this->transaction ) {
			try {
				$this->rollback ();
			} catch ( \Exception $e ) {

			}
		}
	}

	function IsSlaveOnline () {

		return $this->ReplicationDelay () < 25;
	}

	function is_master () {
		return !$this->is_slave;
	}

	function ReplicationDelay () {
		if ( !$this->is_slave ) {
			return 0;
		}
		$slave_status = Cache::local ()->get ( 'slave_seconds_b_m' );
		if ( $slave_status === false ) {
			$slave_status = $this->select_row ( "SHOW SLAVE STATUS" );
			$seconds      = $slave_status[ 'Seconds_Behind_Master' ];
			if ( !is_null ( $seconds ) ) {
				$seconds = intval ( $seconds );
			}
			Cache::local ()->set ( 'slave_seconds_b_m', $seconds, 1 );
		} else {
			$seconds = $slave_status;
		}
		return $seconds;
	}

}
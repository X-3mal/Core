<?php

class DBDriver {

	protected $server_id;

	/**
	 * @var mysqli_result
	 */
	protected $result;
	protected $error         = false;
	protected $error_str     = '';
	protected $error_query   = '';
	protected $host;
	protected $username;
	protected $password;
	protected $database;
	protected $prefix;
	protected $encoding;
	protected $is_slave;
	protected $js_debug      = false;
	protected $js_debug_log  = array();
	private   $trigger_error = true;
	protected $query_count   = 0;
	protected $args;
	protected $i;
	protected $time_prepare;
	protected $time_query;
	private   $last_query;
	private   $cached        = array();
	/**
	 * @var mysqli mysqli
	 */
	private $mysqli;


	protected $transaction;

	function __construct( $host, $username, $password, $database = '', $is_slave = false, $prefix = '', $server_id = 0, $mysqli = null ) {
		$this->server_id = $server_id;
		$this->host      = $host;
		$this->username  = $username;
		$this->password  = $password;
		$this->database  = $database;
		$this->is_slave  = !empty( $is_slave );

		if( isset( $mysqli ) ) {
			$this->mysqli = $mysqli;
		} else {
			if( self::no_pconnect() ) {
				$this->mysqli = new mysqli( $this->host, $this->username, $this->password, $this->database );
			} else {
				$this->mysqli = new mysqli( 'p:' . $this->host, $this->username, $this->password, $this->database );
			}
			if( $this->mysqli->connect_errno ) {
				throw new \DBException( 'Host: ' . $this->host . ' Not avialable. Error #' . $this->mysqli->connect_errno );
			}
		}
		$this->encoding( 'utf8' );


		$this->prefix( $prefix );
	}

	function trigger_error( $set = null ) {
		return isset( $set ) ? $this->trigger_error = $set : $this->trigger_error;
	}

	function host() {
		return $this->host;
	}

	function username() {
		return $this->username;
	}

	function database() {
		return $this->database;
	}

	function password() {
		return $this->password;
	}

	function js_debug( $set = null ) {
		if( isset( $set ) ) {
			$this->js_debug = !empty( $set );
		}

		return $this->js_debug;
	}

	function js_debug_log( $query = null, $time = null ) {
		if( !$this->js_debug() ) {
			return $this->js_debug_log;
		}

		if( isset( $query ) && isset( $time ) ) {
			//echo '/*' . PHP_EOL . $query . PHP_EOL . PHP_EOL . PHP_EOL . '*/';
			$this->js_debug_log[] = array( 'query' => $query, 'time' => $time );
		}

		return $this->js_debug_log;
	}

	/**
	 * @return \DB\Debugger
	 */
	function debugger() {
		return isset( $this->cached[ __FUNCTION__ ] ) ? $this->cached[ __FUNCTION__ ] : $this->cached[ __FUNCTION__ ] = new \DB\Debugger();
	}

	function Log() {
		$var = print_r( func_get_args(), true );
		\DBS::main()->query( "INSERT INTO  `offline_logs` SET `text` = #s", $var );
	}

	function mysqli() {
		return $this->mysqli;
	}

	function prefix( $prefix ) {
		$this->prefix = empty( $prefix ) ? '' : $prefix . '_';

		return $this;
	}

	function encoding( $string ) {
		$this->encoding = $string;
		$this->query( 'SET NAMES ' . $string );
		return $this;
	}

	function server_id() {
		return $this->server_id;
	}

	function disconnect() {
		if( $this->mysqli ) {
			$this->mysqli->close();
			$this->mysqli = null;
		}
		return $this;
	}


	public function _escape_callback( $matches ) {


		switch( $matches[ 0 ] ) {
			case '##':
				return "#";
			case '#_':
				return $this->prefix;
			case '#:':
				return '_:' . strval( \Text::lang_id() ) . ':';

			case '#b':
				return empty( $this->args[ $this->i++ ] ) ? 0 : 1;
			case '#B':
				if( is_null( $this->args[ $this->i ] ) ) {
					$this->i++;

					return "NULL";
					break;
				}

				return empty( $this->args[ $this->i++ ] ) ? 0 : 1;
			case '#d':
				return intval( $this->args[ $this->i++ ] );
				break;
			case '#D':
				if( is_null( $this->args[ $this->i ] ) ) {
					$this->i++;

					return "NULL";
				}

				return intval( $this->args[ $this->i++ ] );
			case '#f':
				return sprintf( "%.20F", $this->args[ $this->i++ ] );
			case '#F':
				if( is_null( $this->args[ $this->i ] ) ) {
					$this->i++;

					return "NULL";
				}

				return sprintf( "%.20F", $this->args[ $this->i++ ] );
			case '#t':
				return 'FROM_UNIXTIME(' . max( 0, min( 2147483647, intval( $this->args[ $this->i++ ] ) ) ) . ')';
			case '#T':
				if( is_null( $this->args[ $this->i ] ) ) {
					$this->i++;

					return "NULL";
				}

				return 'FROM_UNIXTIME(' . max( 0, min( 2147483647, intval( $this->args[ $this->i++ ] ) ) ) . ')';

			case '#s':
				return "'" . $this->mysqli->real_escape_string( $this->args[ $this->i++ ] ) . "'";
			case '#S':
				if( is_null( $this->args[ $this->i ] ) ) {
					$this->i++;

					return "NULL";
				}

				return "'" . $this->mysqli->real_escape_string( $this->args[ $this->i++ ] ) . "'";
			case '#l':
				return "'" . $this->mysqli->real_escape_string( mb_ereg_replace( '([%_])', '\\\1', $this->args[ $this->i++ ] ) ) . "'";
			case '#L':
				if( is_null( $this->args[ $this->i ] ) ) {
					$this->i++;

					return "NULL";
				}

				return "'" . $this->mysqli->real_escape_string( mb_ereg_replace( '([%_])', '\\\1', $this->args[ $this->i++ ] ) ) . "'";
			case '#a':
				if( empty( $this->args[ $this->i ] ) || !is_array( $this->args[ $this->i ] ) ) {
					$this->i++;

					return "(0)";
				}
				$this->args[ $this->i ] = array_map( 'intval', $this->args[ $this->i ] );

				return "(" . implode( ",", $this->args[ $this->i++ ] ) . ")";
			case '#m':
				if( empty( $this->args[ $this->i ] ) || !is_array( $this->args[ $this->i ] ) ) {
					$this->i++;

					return "('')";
				} else {
					foreach( $this->args[ $this->i ] as &$item ) {
						$item = $this->mysqli->real_escape_string( $item );
					}
					unset( $item );
				}

				return "('" . implode( "','", $this->args[ $this->i++ ] ) . "')";
			case '#q':
				return $this->args[ $this->i++ ];
			default:
				return $matches[ 0 ];
		}
	}

	public function _escape( $args ) {
		$query = $args[ 0 ];

		$this->i    = 1;
		$this->args =& $args;

		return preg_replace_callback( '/#./u', array( $this, '_escape_callback' ), $query );
	}

	public function insert_values( $query, $values ) {
		$ret = array();
		foreach( $values as $value ) {
			$args = array( $query );
			foreach( $value as $val ) {
				$args[] = $val;
			}
			$ret[] = '(' . $this->_escape( $args ) . ')';
		}

		return implode( ', ', $ret );
	}

	public function timestamp() {
		return isset( $this->cached[ __FUNCTION__ ] ) ? $this->cached[ __FUNCTION__ ] : $this->cached[ __FUNCTION__ ] = time();
	}

	protected function _query( $args ) {
		$time   = microtime( true );
		$escape = true;
		if( is_bool( $args[ 0 ] ) ) {
			$escape = array_shift( $args );
		}
		if( is_array( $args[ 0 ] ) ) {
			$args = $args[ 0 ];
		}

		if( mb_substr( $args[ 0 ], 0, 1 ) == '>' ) {
			$escape    = false;
			$args[ 0 ] = mb_substr( $args[ 0 ], 1 );
		}

		if( $escape ) {
			$query = $this->_escape( $args );
		} else {
			$query = $args[ 0 ];
		}
		$this->time_prepare += microtime( true ) - $time;
		$time             = microtime( true );
		$this->last_query = $query;

		$this->result = $this->mysqli->query( $query );

		$total_time = microtime( true ) - $time;

		$this->debugger()->add_args( $args, $total_time );
		$this->time_query += $total_time;
		$this->js_debug_log( $query, $total_time );

		$this->query_count++;
		if( !$this->result ) {
			$this->error = true;
			$error       = $this->mysqli->error;
			if( $error == 'Deadlock found when trying to get lock; try restarting transaction' || $error == 'Lock wait timeout exceeded; try restarting transaction' ) {
				$this->result = $this->mysqli->query( $query );
				if( !$this->result ) {
					$this->error       = true;
					$error             = $this->mysqli->error;
					$this->error_str   = $error;
					$this->error_query = $error;

					if( $this->trigger_error() ) {
						trigger_error( "query: " . $query . PHP_EOL . $error, E_USER_WARNING );
					}
					throw new DBException( "query: " . $query . PHP_EOL . $error );
				}
			} else {
				if( $this->trigger_error() ) {
					trigger_error( "query: " . $query . PHP_EOL . $error, E_USER_WARNING );
				}
				throw new DBException( "query: " . $query . PHP_EOL . $error );
			}

		}
	}

	function last_query() {
		return $this->last_query;
	}

	function error_str() {
		return $this->error_str;
	}

	function error_query() {
		return $this->error_query;
	}

	function escape( $query ) {
		return $this->_escape( func_get_args() );
	}

	public function escape_string( $string ) {
		return $this->mysqli->real_escape_string( $string );
	}

	function query( $query ) {
		$this->_query( func_get_args() );

		return $this->result;
	}

	function rows_count() {
		return $this->result ? $this->result->num_rows : 0;
	}

	function found_rows() {
		return $this->select_value( 'SELECT FOUND_ROWS()' );
	}

	function insert_id() {
		return $this->mysqli->insert_id;
	}

	function insert_id_range() {
		$insert_id     = $this->insert_id();
		$affected_rows = $this->affected_rows();

		return array( $insert_id, $insert_id + $affected_rows - 1 );
	}

	function insert_id_list() {
		$insert_id     = $this->insert_id();
		$affected_rows = $this->affected_rows();

		return range( $insert_id, $insert_id + $affected_rows - 1 );
	}

	function affected_rows() {
		return $this->mysqli->affected_rows;
	}

	function autocommit( $value ) {
		$this->query( 'SET AUTOCOMMIT = #b', $value );
	}

	function commit() {
		$this->transaction = false;
		$this->query( 'COMMIT' );
		$this->error = false;
	}

	function rollback() {
		$this->transaction = false;
		$this->query( 'ROLLBACK' );
		$this->error = false;
	}

	function transaction() {
		$this->transaction = true;
		$this->query( 'START TRANSACTION' );
	}

	function error() {
		return $this->error;
	}

	function select( $query ) {
		$this->_query( func_get_args() );
		$rows = array();

		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $rows[] = $this->result->fetch_assoc() ) {

			}
		}
		array_pop( $rows );

		return $rows;
	}

	/**
	 * Запрос в базу данных
	 *
	 * @param string $query сформированный запрос к серверу
	 * @param string $query,... параметры для проведения запроса к серверу
	 *
	 * @return array() массив значений полученых по запросу
	 */
	function select_assoc( $query ) {
		$this->_query( func_get_args() );
		$rows = array();
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $row = $this->result->fetch_assoc() ) {
				$rows[ reset( $row ) ] = $row;
			}
		}

		return $rows;
	}


	function select_multilist( $query ) {
		$this->_query( func_get_args() );
		$rows = array();
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $row = $this->result->fetch_assoc() ) {
				$rows[ reset( $row ) ][] = $row;
			}
		}

		return $rows;
	}

	function select_multilist_assoc( $query ) {
		$this->_query( func_get_args() );
		$rows = array();
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $row = $this->result->fetch_assoc() ) {
				$rows[ reset( $row ) ][ next( $row ) ] = $row;
			}
		}

		return $rows;
	}

	function select_multilist_column( $query ) {
		$this->_query( func_get_args() );
		$rows = array();
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $row = $this->result->fetch_row() ) {
				$rows[ $row[ 0 ] ][ $row[ 1 ] ] = $row[ 1 ];
			}
		}

		return $rows;
	}

	function select_multilist_column_assoc( $query ) {
		$this->_query( func_get_args() );
		$rows = array();
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $row = $this->result->fetch_row() ) {
				$rows[ $row[ 0 ] ][ $row[ 1 ] ] = $row[ 2 ];
			}
		}

		return $rows;
	}

	function select_multilist_assoc_column( $query ) {
		$this->_query( func_get_args() );
		$rows = array();

		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $row = $this->result->fetch_row() ) {
				$rows[ $row[ 0 ] ][ $row[ 1 ] ] = $row[ 2 ];
			}
		}

		return $rows;
	}

	function select_row( $query ) {
		$this->_query( func_get_args() );
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			return $this->result->fetch_assoc();
		} else {
			return array();
		}
	}

	function select_column( $query ) {
		$this->_query( func_get_args() );
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			$result = array();
			while( $row = $this->result->fetch_row() ) {
				$result[ $row[ 0 ] ] = $row[ 0 ];
			}

			return $result;
		} else {
			return array();
		}
	}

	function select_array_column( $query ) {
		$this->_query( func_get_args() );
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			$result = array();
			while( $row = $this->result->fetch_row() ) {
				$result[] = $row[ 0 ];
			}

			return $result;
		} else {
			return array();
		}
	}

	function select_column_assoc( $query ) {
		$this->_query( func_get_args() );
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			$result = array();
			while( $row = $this->result->fetch_row() ) {
				$result[ $row[ 0 ] ] = $row[ 1 ];
			}

			return $result;
		} else {
			return array();
		}
	}

	function select_value( $query ) {
		$this->_query( func_get_args() );
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			$row = $this->result->fetch_row();
			return $row[ 0 ];
		} else {
			return false;
		}
	}

	function select_exists( $query ) {
		$this->_query( func_get_args() );
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $page_number
	 * @param $page_capacity
	 * @param $query
	 *
	 * @return \DB\Pager
	 */
	function select_paged( $page_number, $page_capacity, $query ) {
		$page_number = intval( $page_number );
		if( $page_number < 1 ) {
			$page_number = 1;
		}
		$args      = array_slice( func_get_args(), 2 );
		$args[ 0 ] = mb_ereg_replace( '^\s*(>?)SELECT', '\\1SELECT SQL_CALC_FOUND_ROWS', $args[ 0 ] );
		$args[ 0 ] .= sprintf( ' LIMIT %d, %d', ( $page_number - 1 ) * $page_capacity, $page_capacity );
		$this->_query( $args );
		$rows = array();
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $rows[] = $this->result->fetch_assoc() ) {

			}
		}

		array_pop( $rows );


		$count      = $this->found_rows();
		$page_count = floor( ( $count - 1 ) / $page_capacity ) + 1;

		return new \DB\Pager( $rows, $count, $page_count, $page_number, $page_capacity );
	}

	/**
	 * @param $page_number
	 * @param $page_capacity
	 * @param $query
	 *
	 * @return \DB\Pager
	 */
	function select_paged_multilist( $page_number, $page_capacity, $query ) {
		$page_number = intval( $page_number );
		if( $page_number < 1 ) {
			$page_number = 1;
		}
		$args      = array_slice( func_get_args(), 2 );
		$args[ 0 ] = mb_ereg_replace( '^\s*(>?)SELECT', '\\1SELECT SQL_CALC_FOUND_ROWS', $args[ 0 ] );
		$args[ 0 ] .= sprintf( ' LIMIT %d, %d', ( $page_number - 1 ) * $page_capacity, $page_capacity );
		$this->_query( $args );
		$rows = array();
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $row = $this->result->fetch_assoc() ) {
				$rows[ reset( $row ) ][] = $row;
			}
		}


		$count      = $this->found_rows();
		$page_count = floor( ( $count - 1 ) / $page_capacity ) + 1;

		return new \DB\Pager( $rows, $count, $page_count, $page_number, $page_capacity );
	}

	/**
	 * @param $page_number
	 * @param $page_capacity
	 * @param $page_count
	 * @param $query
	 *
	 * @return \DB\Pager
	 */
	function select_paged_no_calc( $page_number, $page_capacity, $page_count, $query ) {
		$page_number = intval( $page_number );
		if( $page_number < 1 ) {
			$page_number = 1;
		}
		$args = array_slice( func_get_args(), 3 );
		$args[ 0 ] .= sprintf( ' LIMIT %d, %d', ( $page_number - 1 ) * $page_capacity, $page_capacity );
		$this->_query( $args );
		$rows = array();
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $rows[] = $this->result->fetch_assoc() ) {

			}
		}

		array_pop( $rows );


		$count = $page_count * $page_capacity;

		return new \DB\Pager( $rows, $count, $page_count, $page_number, $page_capacity );
	}

	/**
	 * @param        int > 0 $page_number
	 * @param int    $page_capacity
	 * @param string $query
	 *
	 * @return \DB\Pager
	 */
	function select_paged_assoc( $page_number, $page_capacity, $query ) {
		$page_number = intval( $page_number );
		if( $page_number < 1 ) {
			$page_number = 1;
		}
		$args      = array_slice( func_get_args(), 2 );
		$args[ 0 ] = mb_ereg_replace( '^\s*(>?)SELECT', '\\1SELECT SQL_CALC_FOUND_ROWS', $args[ 0 ] );
		$args[ 0 ] .= sprintf( ' LIMIT %d, %d', ( $page_number - 1 ) * $page_capacity, $page_capacity );
		$this->_query( $args );

		$rows = array();
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $row = $this->result->fetch_assoc() ) {
				$rows[ reset( $row ) ] = $row;
			}
		}
		$count = $this->found_rows();

		return new \DB\Pager ( $rows, $count, floor( ( $count - 1 ) / $page_capacity ) + 1, $page_number, $page_capacity );
	}

	/**
	 * @param        int > 0 $page_number
	 * @param int    $page_capacity
	 * @param string $query
	 *
	 * @return \DB\Pager
	 */
	function select_paged_assoc_no_calc( $page_number, $page_capacity, $page_count, $query ) {
		$page_number = intval( $page_number );
		if( $page_number < 1 ) {
			$page_number = 1;
		}
		$args = array_slice( func_get_args(), 3 );
		$args[ 0 ] .= sprintf( ' LIMIT %d, %d', ( $page_number - 1 ) * $page_capacity, $page_capacity );
		$this->_query( $args );

		$rows = array();
		if( $this->result && ( $this->result->num_rows > 0 ) ) {
			while( $row = $this->result->fetch_assoc() ) {
				$rows[ reset( $row ) ] = $row;
			}
		}

		//$count = $this->found_rows ();
		return new \DB\Pager ( $rows, $page_count * $page_capacity, $page_count, $page_number, $page_capacity );
	}

	public function shutdown() {
		if( $this->transaction ) {
			try {
				$this->rollback();
			} catch( \DBException $e ) {

			}
		}
	}


	function IsSlaveOnline() {
		if( Server::IsLocal() || $this->is_master() ) {
			return true;
		}
		$online = $this->ReplicationDelay() < 15;
		self::slave_offline( $this->host, !$online );

		return $online;
	}

	function is_master() {
		return !$this->is_slave;
	}

	private static $no_pconnect = false;

	static function no_pconnect( $set = null ) {
		return isset( $set ) ? ( self::$no_pconnect = $set ) : self::$no_pconnect;
	}

	static function slave_offline( $host, $set = null ) {
		$key = 'slave_offline_' . $host;
		if( isset( $set ) ) {
			\Cache::local()->set( $key, $set, 1 );
		}

		return \Cache::local()->get( $key, false );
	}

	function ReplicationDelay() {
		if( !$this->is_slave ) {
			return 0;
		}
		$key          = 'slave_seconds_b_m' . $this->host;
		$slave_status = Cache::local()->get( $key );
		if( $slave_status === false ) {
			$slave_status = $this->select_row( "SHOW SLAVE STATUS" );
			$seconds      = $slave_status[ 'Seconds_Behind_Master' ];
			$slaveAlive   = $slave_status[ 'Slave_IO_Running' ] == 'Yes';
			if( !is_null( $seconds ) && $slaveAlive ) {
				$seconds = intval( $seconds );
			} else {
				$seconds = 3600;
			}
			Cache::local()->set( $key, $seconds, 1 );
		} else {
			$seconds = $slave_status;
		}

		return $seconds;
	}

}

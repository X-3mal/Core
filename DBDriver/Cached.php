<?php
/**
 * popunder.su
 * Author: X-3mal
 * Date: 13.02.14
 * Time: 15:05
 */

namespace DBDriver;

class Cached extends \DBDriver {
	const DEFAULT_TIME = 1;
	protected $time = 1;

	public function _escape_callback ( $matches ) {
		if ( is_array ( $this->args[ $this->i ] ) ) {
			sort ( $this->args[ $this->i ] );
		}
		return parent::_escape_callback ( $matches );
	}


	/**
	 * @param null|int $set
	 * @return Cached
	 */
	function time ( $set = 1 ) {
		$set        = intval ( $set );
		$set        = max ( 0, $set );
		$this->time = $set;
		return $this;
	}

	private function select_cache_name ( $func, $query ) {
		return $func . ':' . md5 ( $query );
	}

	/**
	 * @param                   $func
	 * @param                   $args
	 * @param array|string|bool $default
	 * @return array|mixed|string
	 */
	private function get_cached ( $func, $args, $default = array () ) {
		$query = $this->_escape ( $args );
		$key   = $this->select_cache_name ( $func, $query );
		if ( \Cache::local ()->get ( $key ) === false ) {
			\Cache::local ()->set ( $key, call_user_func ( array ( $this, 'parent::' . $func ), false, array ( $query ) ), $this->time );
		}
		return \Cache::local ()->get ( $key, $default );
	}

	function select_assoc ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), array () );
	}

	function select_multilist ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), array () );
	}

	function select_multilist_assoc ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), array () );
	}

	function select_multilist_column ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), array () );
	}

	function select_multilist_column_assoc ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), array () );
	}

	function select_multilist_assoc_column ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), array () );
	}

	function select_row ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), array () );
	}

	function select_column ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), array () );
	}

	function select_array_column ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), array () );
	}

	function select_column_assoc ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), array () );
	}

	function select_value ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), '' );
	}

	function select_exists ( $query ) {
		return $this->get_cached ( __FUNCTION__, func_get_args (), false );
	}

}
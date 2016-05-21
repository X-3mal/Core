<?php
/**
 * popunder.su
 * Author: X-3mal
 * Date: 05.09.13
 * Time: 17:30
 */

namespace DB;


class Filter {
	private $filter = array();
	private $join   = array();
	private $having = array();
	private $select = array();
	private $insert = array();
	private $limit  = array();


	/**
	 * @return $this
	 */
	public function addFilter() {
		$this->filter[] = \DBS::main()->_escape( func_get_args() );
		return $this;
	}

	/**
	 * @param int $start
	 * @param     $limit
	 *
	 * @return $this
	 */
	public function addLimit( $start, $limit ) {
		$this->limit = array( 'start' => intval( $start ), 'limit' => intval( $limit ) );
		return $this;
	}

	/**
	 * @return $this
	 */
	public function addInsert() {
		$this->insert[] = \DBS::main()->_escape( func_get_args() );
		return $this;
	}

	/**
	 * @return $this
	 */
	public function addSelect() {
		$this->select[] = \DBS::main()->_escape( func_get_args() );
		return $this;
	}

	/**
	 * @return $this
	 */
	public function addHaving() {
		$this->having[] = \DBS::main()->_escape( func_get_args() );
		return $this;
	}

	/**
	 * query OR
	 * 0 - true, 1 - alias
	 *
	 * @return $this
	 */
	public function addJoin() {
		$args = func_get_args();
		if( $args[ 0 ] === true ) {
			array_shift( $args );
			$table = array_shift( $args );
			if( !isset( $this->join[ $table ] ) ) {
				$this->join[ $table ] = \DBS::main()->_escape( $args );
			}
		} else {
			$this->join[] = \DBS::main()->_escape( $args );
		}

		return $this;
	}

	public function getSelect() {
		return $this->select;
	}

	public function getLimit() {
		return $this->limit;
	}

	public function getJoin() {
		return $this->join;
	}

	public function getHaving() {
		return $this->having;
	}

	public function getFilter() {
		return $this->filter;
	}

	public function getInsert() {
		return $this->insert;
	}

	public function filter( $first = 'AND' ) {
		return $this->filter ? ( $first . ' ' . implode( ' AND ', $this->filter ) ) : '';
	}

	public function insert( $first = ',' ) {
		return $this->insert ? ( $first . ' ' . implode( ' , ', $this->insert ) ) : '';
	}

	public function join() {
		return $this->join ? implode( ' ', $this->join ) : '';
	}

	public function select( $first = ',' ) {
		return $this->select ? ( $first . implode( ', ', $this->select ) ) : '';
	}

	public function having( $first = 'AND' ) {
		return $this->having ? $first . ' ' . implode( ' AND ', $this->having ) : '';
	}

	public function limit() {
		return $this->limit ? 'LIMIT ' . $this->limit[ 'start' ] . ', ' . $this->limit[ 'limit' ] : '';
	}

	/**
	 * @param Filter $filter
	 *
	 * @return $this;
	 */
	public function merge( $filter ) {
		$this->filter = array_merge( $this->filter, $filter->getFilter() );
		$this->select = array_merge( $this->select, $filter->getSelect() );
		$this->join   = array_merge( $this->join, $filter->getJoin() );
		$this->having = array_merge( $this->having, $filter->getHaving() );
		$this->insert = array_merge( $this->insert, $filter->getInsert() );
		$this->limit  = $this->limit ? $this->limit : $filter->getLimit();
		return $this;
	}
}
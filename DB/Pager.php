<?php

namespace DB;

class Pager {

	public $rows;
	public $count;
	public $page_count;
	public $page_number;

	public function __construct ( &$rows, $count, $page_count, $page_number ) {
		$this->rows        = $rows;
		$this->count       = $count;
		$this->page_count  = $page_count;
		$this->page_number = $page_number;
	}

}


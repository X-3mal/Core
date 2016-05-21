<?php


/**
 * @property \DB\modules[] top_nav
 */
class Script extends Script_Abstract {

	public $i = 0;

	public function _run () {
		$this->i++;
		return include ( func_get_arg ( 0 ) );
	}

}
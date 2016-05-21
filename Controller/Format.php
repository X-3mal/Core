<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 26.02.14
 * Time: 0:09
 */

namespace Controller;


abstract class Format {
	abstract function encode($data);
	abstract function decode($data);
} 
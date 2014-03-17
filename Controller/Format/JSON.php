<?php


namespace Controller\Format;


use Controller\Format;

class JSON extends Format {
	function encode ( $data ) {
		return json_encode ( $data );
	}

	function decode ( $data ) {
		return json_decode ( $data, true );
	}
} 
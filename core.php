<?php
include __DIR__ . '/Config.php';

include __DIR__ . '/includes/headers.php';

if ( !empty ( Config::$root ) ) {
	chdir ( __DIR__ . '/../' . Config::$root );
} else {
	chdir ( __DIR__ . '/..' );
}

error_reporting ( E_ALL | E_STRICT );

if ( Config::$debug ) {
	ini_set ( 'display_errors', 1 );
} else {
	ini_set ( 'display_errors', 0 );
	include __DIR__ . '/includes/error_handler.php';
}

include __DIR__ . '/includes/autoload.php';

mb_internal_encoding ( Config::$encoding );

<?php
spl_autoload_register ( function ( $name ) {
	$name = str_replace ( '\\', DIRECTORY_SEPARATOR, $name );
	$name = str_replace ( '_', DIRECTORY_SEPARATOR, $name );
	foreach ( Config::$auto_load_dirs as $dir ) {
		if ( is_readable ( $dir . "/" . $name . ".php" ) ) {
			include ( $dir . "/" . $name . ".php" );
			return;
		}
	}
} );
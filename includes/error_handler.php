<?php
set_error_handler ( function ( $err_no, $err_str, $err_file, $err_line ) {
		$s = date ( 'Y-m-d H:i:s', time () ) . PHP_EOL;
		$s .= "Site: " . ( isset ( $_SERVER[ 'SERVER_NAME' ] ) ? $_SERVER[ 'SERVER_NAME' ] : 'unknown' ) . PHP_EOL;
		$s .= ( !empty ( $_SERVER[ 'REQUEST_URI' ] ) ? $_SERVER[ 'REQUEST_URI' ] : '-' ) . PHP_EOL;
		$s .= ( !empty ( $_SERVER[ 'HTTP_REFERER' ] ) ? $_SERVER[ 'HTTP_REFERER' ] : '-' ) . PHP_EOL;
		$s .= "Time: " . date ( "Y-m-d H:i:s" ) . PHP_EOL;
		$s .= "Error #: " . $err_no . PHP_EOL;
		$s .= "Message: " . $err_str . PHP_EOL;
		$s .= "File: " . $err_file . PHP_EOL;
		$s .= "Line: " . $err_line . PHP_EOL;
		$s .= "User IP: " . ( isset ( $_SERVER[ 'REMOTE_ADDR' ] ) ? $_SERVER[ 'REMOTE_ADDR' ] : '?' ) . PHP_EOL;
		$s .= "\nDebug backtrace:\n";
		$backtrace_array = debug_backtrace ();
		$backtrace       = "";
		foreach ( $backtrace_array as $key => $record ) {
			if ( $key == 0 ) {
				continue;
			}
			$backtrace .= "#" . $key . ": " . $record[ 'function' ] . "(";
			if ( isset ( $record[ 'args' ] ) && is_array ( $record[ 'args' ] ) ) {
				$args = array();
				foreach ( $record[ 'args' ] as &$arg ) {
					if ( is_object ( $arg ) && !method_exists ( $arg, '__toString' ) ) {
						$args[ ] = 'Object';
					} elseif ( is_array ( $arg ) ) {
						$args[ ] = 'Array';
					} else {
						$args[ ] = $arg;
					}
				}
				unset ( $arg );
				$backtrace .= implode ( ",", $args );
			}
			$backtrace .= ") called at [" . ( isset ( $record[ 'file' ] ) ? $record[ 'file' ] : '?' ) . ":" . ( isset ( $record[ 'line' ] ) ? $record[ 'line' ] : '?' ) . "]\n";
		}

		$s .= $backtrace;
		$s .= PHP_EOL . '------------------------------------------' . PHP_EOL . PHP_EOL;

		$log = @fopen ( Config::errors_dir () . date ( 'Y-m-d', time () ), 'a' );

		@fwrite ( $log, $s );
		@fclose ( $log );

		return false;
	}, E_ALL | E_STRICT );
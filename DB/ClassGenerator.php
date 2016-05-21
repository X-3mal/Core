<?php
/**
 * Created by PhpStorm.
 * User: Алексей
 * Date: 08.06.14
 * Time: 21:58
 */

namespace DB;


class ClassGenerator {

	private $namespace;
	private $className;
	private $create_table;
	private $data;
	private $table;
	private $fields_data;

	function __construct ( $fields, $className, $table, $namespace = 'DB' ) {
		$this->fields_data = $fields;
		$this->className   = $className;
		$this->namespace   = $namespace;
		$this->table       = $table;
	}

	private function table_name () {

	}


	private function fields () {
		if ( !isset( $this->fields ) ) {
			$this->fields = array();
			foreach ( $this->fields_data AS $field ) {

				/*if ( $field[ 'Key' ] == 'PRI' ) {
					continue;
				}*/

				$name = $field[ 'Field' ];
				$type = $field[ 'Type' ];

				$options = array(
					'null' => $field[ 'Null' ] == 'YES'
				);
				if ( $field[ 'Default' ] ) {
					$options[ 'default' ] = $field[ 'Default' ];
				}


				preg_match ( '/^(\w+)(\(([\w\d,\s]+)\))?$/', $type, $type_data );

				$type_str = strtoupper ( $type_data[ 1 ] );
				if ( !empty( $type_data[ 3 ] ) ) {
					$type_len = $type_data[ 3 ];
				} else {
					$type_len = '';
				}

				switch ( $type_str ) {
					case 'TINYINT':
					case 'SMALLINT':
					case 'MEDIUMINT':
					case 'INT':
					case 'BIGINT':
						$type = 'int';
						break;
					case 'DATE':
						$type = 'date';
						break;
					case 'DATETIME':
						$type = 'datetime';
						break;
					case 'TIMESTAMP':
						$type = 'timestamp';
						break;
					case 'DECIMAL':
					case 'FLOAT':
					case 'DOUBLE':
						$type = 'float';
						break;

					case 'SET':
						$type                = 'set';
						$options[ 'values' ] = array( explode ( ',', $type_len ) );
						break;
					case 'CHAR':
					case 'VARCHAR':
					case 'TEXT':
					default:

						$type = 'varchar';

						if ( $type_len = intval ( $type_len ) ) {
							$options[ 'length_max' ] = $type_len;
						}
						break;
				}
				$this->fields[ ] = array_merge ( array( 'name' => $name, 'type' => $type, ), $options );

			}
		}
		return $this->fields;
	}


	private function table () {
		return $this->table;
	}

	private function class_name () {
		return $this->className;
	}

	private function get_class () {
		$class = '<?php' . PHP_EOL;
		$class .= 'namespace ' . $this->namespace . ';' . PHP_EOL . PHP_EOL .
				  'class ' . $this->class_name () . ' extends \DB\table {' . PHP_EOL .
				  "\t" . 'protected static $name = "' . $this->table () . '";' . PHP_EOL .
				  "\t" . 'protected static $fields = array(' . PHP_EOL;
		foreach ( $this->fields () as $field ) {
			$class .= "\t\t" . '"' . $field[ 'name' ] . '"' . "\t=>\t" . 'array (' . PHP_EOL;
			foreach ( $field as $key => $val ) {
				$class .= "\t\t\t" . '"' . $key . '" => ' . json_encode ( $val ) . ',' . PHP_EOL;
			}
			$class .= "\t\t" . '),' . PHP_EOL;
		}
		$class .= "\t" . ');' . PHP_EOL;
		foreach ( $this->fields () as $field ) {
			if ( $field[ 'name' ] != 'id' ) {
				$class .= "\t" . 'function ' . $field[ 'name' ] . ' (){' . PHP_EOL .
						  "\t\t" . 'return $this->row( __FUNCTION__ );' . PHP_EOL .
						  "\t" . '}' . PHP_EOL . PHP_EOL;
			}
		}
		$class .= '}' . PHP_EOL;


		return $class;
	}

	private function dir () {

		$dirs = explode ( '\\', $this->namespace );
		$dir  = \Config::home () . 'classes/';
		foreach ( $dirs as $_dir ) {
			$dir .= $_dir . '/';
			if ( !file_exists ( $dir ) && !is_dir ( $dir ) ) {
				mkdir ( $dir, 0777 );
			}
		}
		return $dir;
	}

	private function filename () {
		return $this->dir () . $this->class_name () . '.php';
	}

	function create_file () {

		if ( file_exists ( $this->filename () ) ) {
			return false;
		}

		$fp = fopen ( $this->filename (), 'a+' );
		fwrite ( $fp, $this->get_class () );
		fclose ( $fp );
		return true;
	}

	static function parse ( $table, $className, $namespace ) {
		$fields  = \DBS::main ()->select ( "SHOW COLUMNS FROM `" . $table . "`" );
		$creator = new self( $fields, $className, $table, $namespace );
		return $creator->create_file ();
	}

} 
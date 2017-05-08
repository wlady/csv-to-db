<?php
/**
 * Created by PhpStorm.
 * User: wlady2001
 * Date: 05.05.17
 * Time: 12:28
 */

namespace CSV2DB\Models;

class Table {
	const TABLE_NAME = 'csv_to_db';

	/**
	 * Create DB table from saved configuration
	 * @return string/bool On error returns error message
	 */
	public static function create_table( $fields ) {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '`' );
		try {
			$schema = self::create_schema( $fields );
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
		$wpdb->query( $schema );

		return $wpdb->last_error !== '' ? $wpdb->last_error : true;
	}

	/**
	 * @param $fields
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function create_schema( $fields ) {
		global $wpdb;

		$columns = array();
		$indexes = array();
		foreach ( $fields as $field ) {
			$type = $field['type'];
			$null = $field['null'] == 0 ? 'NOT NULL' : 'NULL';
			$ai   = $field['ai'] == 1 ? 'AUTO_INCREMENT' : '';
			if ( ! in_array( $type, array( 'TEXT', 'BLOB' ) ) ) {
				$type = "{$field['type']}({$field['size']}) {$null} {$ai}";
			}
			$columns[] = "`{$field['name']}` {$type}";
			if ( ! empty( $field['index'] ) ) {
				if ( $field['index'] == 'PRIMARY' ) {
					$current_index = 'PRIMARY KEY ';
				} elseif ( $field['index'] == 'UNIQUE' ) {
					$current_index = "UNIQUE KEY `{$field['name']}`";
				} else {
					$current_index = "KEY `{$field['name']}`";
				}
				$indexes[] = $current_index . "(`{$field['name']}`)";
			}
		}
		if ( count( $indexes ) ) {
			$columns = array_merge( $columns, $indexes );
		}
		if ( ! count( $columns ) ) {
			throw new \Exception( \__( 'Column configuration is empty', 'csv-to-db' ) );
		}

		return 'CREATE TABLE IF NOT EXISTS `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '` (' . implode( ',', $columns ) . ')';
	}

	/**
	 * @param $file_name
	 *
	 * @return mix On error returns error message
	 */
	public static function import_file( $file_name, $options ) {
		global $wpdb;

		$use_local     = $options['use-local'] == 1 ? 'LOCAL' : '';
		$fields_params = array();
		$lines_params  = array();
		if ( ! empty( $options['fields-terminated'] ) ) {
			$fields_params[] = 'TERMINATED BY \'' . $options['fields-terminated'] . '\'';
		}
		if ( ! empty( $options['fields-enclosed'] ) ) {
			$symbol = $options['fields-enclosed'];
			if ( in_array( $symbol, array( '"', "'" ) ) ) {
				$fields_params[] = 'ENCLOSED BY \'\\' . $symbol . '\'';
			}
		}
		if ( ! empty( $options['fields-escaped'] ) ) {
			$fields_params[] = 'ESCAPED BY \'' . $options['fields-escaped'] . '\'';
		}
		if ( ! empty( $options['lines-starting'] ) ) {
			$lines_params[] = 'STARTING BY \'' . $options['lines-starting'] . '\'';
		}
		if ( ! empty( $options['lines-terminated'] ) ) {
			$lines_params[] = 'TERMINATED BY \'' . $options['lines-terminated'] . '\'';
		}
		$query = 'LOAD DATA ' . $use_local . ' INFILE \'' . $file_name . '\' INTO TABLE `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '`';
		if ( count( $fields_params ) ) {
			$query .= ' FIELDS ' . implode( ' ', $fields_params );
		}
		if ( count( $lines_params ) ) {
			$query .= ' LINES ' . implode( ' ', $lines_params );
		}
		if ( intval( $_POST['skip-rows'] ) > 0 ) {
			$query .= ' IGNORE ' . intval( $_POST['skip-rows'] ) . ' LINES';
		}
		$wpdb->query( $query );

		return $wpdb->last_error !== '' ? $wpdb->last_error : true;
	}

	/**
	 * @param $columns
	 * @param $fields
	 * @param int $start
	 * @param int $limit
	 * @param string $order
	 *
	 * @return array
	 */
	public static function get_items( $columns, $fields, $start = 0, $limit = 10, $order = 'asc' ) {
		global $wpdb;

		$res   = $wpdb->get_results( 'SELECT SQL_CALC_FOUND_ROWS `' . implode( '`,`', $fields ) . '` FROM `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '` LIMIT ' . "{$start}, {$limit}" );
		$total = $wpdb->get_var( 'SELECT FOUND_ROWS() AS total' );
		$rows  = self::convert_fields( $columns, $res );

		return array( $total, $rows );
	}

	/**
	 * @param $columns
	 * @param $records
	 *
	 * @return array
	 */
	public static function convert_fields( $columns, $records ) {
		$rows = array_map( function ( $item ) use ( $columns ) {
			$row = (array) $item;
			foreach ( $row as $field => $value ) {
				$column = array_filter( $columns, function ( $col ) use ( $field ) {
					return $col['name'] == $field ? $col : null;
				} );
				$col    = array_pop( $column );
				switch ( $col['type'] ) {
					case 'INT':
						$item->{$field} = (int) $value;
						break;
					case 'FLOAT':
						$item->{$field} = (float) $value;
						break;
					case 'DOUBLE':
					case 'DECIMAL':
						$item->{$field} = (double) $value;
						break;
				}
				if ( $col['index'] == 'PRIMARY' ) {
					$item->id = (int) $value;
				}
			}

			return $item;
		}, $records );

		return $rows;
	}

	/**
	 * Called on uninstall
	 */
	public static function drop_tables() {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '`' );
	}
}
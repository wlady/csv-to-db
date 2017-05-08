<?php
/**
 * Created by PhpStorm.
 * User: wlady2001
 * Date: 05.05.17
 * Time: 12:15
 */

namespace CSV2DB\Controllers;

use CSV2DB\Engine\Options;
use CSV2DB\Models;

class Admin extends Options {
	protected $upload_max_filesize = 0;
	// used in Bootstrap Table
	protected $data_id_field = '';
	// Every POST action has related method
	private $actions = array(
		'save_fields', // save_fields_action etc
		'export_schema',
		'create_table',
		'clear_fields',
		'export_fields',
		'import_fields',
	);
	// Every hook has related method
	private $hooks = array(
		'admin_init', // admin_init_hook etc
		'admin_menu',
		'wp_ajax_import_csv',
		'wp_ajax_analyze_csv',
		'wp_ajax_get_items',
	);
	// Styles to enqueue (related to plugin directory)
	private $styles = array(
		'/assets/bootstrap/css/bootstrap.min.css',
		'/assets/bootstrap-table/bootstrap-table.css',
		'/assets/style.css',
	);
	// Scripts to enqueue (related to plugin directory)
	private $scripts = array(
		'/assets/bootstrap/js/bootstrap.min.js',
		'/assets/bootstrap-table/bootstrap-table.js',
		'/assets/bootstrap-table/extensions/export/bootstrap-table-export.min.js',
		'/assets/tableExport.min.js',
		'/assets/utilities.js',
	);

	public function __construct( $config ) {
		parent::__construct( $config );
		$this->upload_max_filesize = Models\File::convert_bytes( ini_get( 'upload_max_filesize' ) );
	}

	public function init() {
		// initialize hooks
		foreach ( $this->hooks as $hook ) {
			$method = $hook . '_hook';
			if ( method_exists( $this, $method ) ) {
				\add_action( $hook, array( $this, $method ) );
			}
		}
		// enqueue styles
		foreach ( $this->styles as $style ) {
			\wp_enqueue_style( md5( $style ), \plugins_url( $style, $this->config['plugin_basename'] ) );
		}
		// enqueue scripts
		foreach ( $this->scripts as $script ) {
			\wp_enqueue_script( md5( $script ), \plugins_url( $script, $this->config['plugin_basename'] ) );
		}
	}

	/**
	 * @param $action
	 *
	 * @throws \Exception
	 */
	public function dispatch( $action ) {
		// route POST requests
		if ( in_array( $action, $this->actions ) ) {
			$method = $action . '_action';
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			} else {
				throw new \Exception( \__( 'Method ' . $method . ' was not found', 'csv-to-db' ) );
			}
		}
	}

	/**
	 * Whitelist the csv-to-db options
	 *
	 * @Hook admin_init
	 * @return none
	 */
	public function admin_init_hook() {
		\register_setting( 'csv-to-db', 'csv-to-db', array( $this, 'update' ) );
	}

	/**
	 * Add the options page
	 *
	 * @Hook admin_menu
	 * @return none
	 */
	public function admin_menu_hook() {
		if ( \current_user_can( 'manage_options' ) ) {
			\add_menu_page( \__( 'CSV to DB', 'csv-to-db' ), \__( 'CSV to DB', 'csv-to-db' ), 'manage_options', 'wp-csv-to-db', array(
				$this,
				'items_page_action'
			), 'dashicons-book-alt' );
			\add_submenu_page( 'wp-csv-to-db', \__( 'Import', 'csv-to-db' ), \__( 'Import', 'csv-to-db' ), 'manage_options', 'wp-csv-to-db-import', array(
				$this,
				'import_page_action'
			) );
			\add_submenu_page( 'wp-csv-to-db', \__( 'Fields', 'csv-to-db' ), \__( 'Fields', 'csv-to-db' ), 'manage_options', 'wp-csv-to-db-fields', array(
				$this,
				'fields_page_action'
			) );
			\add_submenu_page( 'wp-csv-to-db', \__( 'Settings', 'csv-to-db' ), \__( 'Settings', 'csv-to-db' ), 'manage_options', 'wp-csv-to-db-settings', array(
				$this,
				'options_page_action'
			) );
		}
	}

	/**
	 * Import CSV file by AJAX
	 *
	 * @Hook wp_ajax_import_csv
	 */
	public function wp_ajax_import_csv_hook() {
		try {
			$tmp_file_name = Models\File::upload_file();
			if ( $tmp_file_name ) {
				if ( isset( $_POST['re-create'] ) ) {
					$res = Models\Table::create_table( $this->options['fields'] );
					if ( is_string( $res ) ) {
						throw new \Exception( htmlspecialchars( $res, ENT_QUOTES ) );
					}
				}
				$res = Models\Table::import_file( $tmp_file_name, $this->options );
				if ( is_string( $res ) ) {
					throw new \Exception( htmlspecialchars( $res, ENT_QUOTES ) );
				} else {
					$results = array(
						'success' => true,
						'message' => \__( 'Success!', 'csv-to-db' ),
					);
				}
			}
		} catch ( \Exception $e ) {
			$results = array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
		Models\File::unlink( $tmp_file_name );
		$this->load_view( 'json', $results );
	}

	/**
	 * Analyze CSV file by AJAX
	 *
	 * @Hook wp_ajax_analyze_csv
	 */
	public function wp_ajax_analyze_csv_hook() {
		try {
			$tmp_file_name = Models\File::upload_file();
			if ( $tmp_file_name ) {
				$fp = fopen( $tmp_file_name, 'r' );
				if ( ! $fp ) {
					throw new \Exception( \__( 'Cannot read from CSV', 'csv-to-db' ) );
				}
				$fields = fgetcsv( $fp, 0, $this->get_option( 'fields-terminated' ), $this->get_option( 'fields-enclosed' ), $this->get_option( 'fields-escaped' ) );
				if ( ! $fields || ! count( $fields ) ) {
					throw new \Exception( \__( 'Cannot detect fields', 'csv-to-db' ) );
				} else {
					// save fields
					$fields_data = array();
					foreach ( $fields as $field ) {
						$fields_data[] = $this->generate_empty_field( $field );
					}
					$this->options['fields'] = $fields_data;
					\update_option( 'csv-to-db', $this->options );
					$results = array(
						'success' => true,
						'data'    => $fields,
						'message' => \__( 'Success! Reloading...', 'csv-to-db' ),
					);
				}
			}
		} catch ( \Exception $e ) {
			$results = array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
		Models\File::unlink( $tmp_file_name );
		$this->load_view( 'json', $results );
	}

	/**
	 * Get items by AJAX
	 *
	 * @Hook wp_ajax_get_items
	 */
	public function wp_ajax_get_items_hook() {
		$results = array(
			'total' => 0,
			'rows'  => array(),
		);
		$columns = $this->collect_columns_to_show( $skip_auto_generated = true );
		if ( count( $columns ) ) {
			$start = (int) filter_var( $_POST['offset'], FILTER_SANITIZE_NUMBER_INT );
			$limit = (int) filter_var( $_POST['limit'], FILTER_SANITIZE_NUMBER_INT );
			if ( ! $limit ) {
				$limit = 10;
			}
			$order  = filter_var( $_POST['order'], FILTER_SANITIZE_STRING );
			$fields = array_column( $columns, 'name' );
			list( $total, $rows ) = Models\Table::get_items( $columns, $fields, $start, $limit, $order );
			$results = array(
				'total' => (int) $total,
				'rows'  => (array) $rows,
			);
		}
		$this->load_view( 'json', $results );
	}

	/**
	 * @param bool $skip_auto_generated
	 *
	 * @return array
	 */
	public function collect_columns_to_show( $skip_auto_generated = false ) {
		$columns = array();
		$checked = false;
		foreach ( $this->options['fields'] as $field ) {
			if ( isset( $field['show'] ) && ! empty( $field['title'] ) ) {
				$columns[] = $field;
				if ( isset( $field['check'] ) ) {
					$this->data_id_field = $field['name'];
					$checked             = true;
				}
			}
		}
		usort( $columns, function ( $a, $b ) {
			return ( isset( $a['index'] ) && $a['index'] == 'PRIMARY' ) ? 0 : 1;
		} );
		if ( ! $skip_auto_generated && ! $checked ) {
			array_unshift( $columns, array(
				'name'  => '__auto_generated_check_column__',
				'check' => true,
			) );
			$this->data_id_field = '__auto_generated_check_column__';
		}

		return $columns;
	}

	/**
	 * Show the options page via admin menu
	 *
	 * @Slug wp-csv-to-db-settings
	 */
	public function options_page_action() {
		return $this->load_view( 'options' );
	}

	/**
	 * Show the import page via admin menu
	 *
	 * @Slug wp-csv-to-db-import
	 */
	public function import_page_action() {
		if ( ! count( $this->options['fields'] ) ) {
			$this->message = \__( 'Fields undefined! Click <a href="admin.php?page=wp-csv-to-db-fields">Fields</a> to prepare fields.', 'csv-to-db' );

			return $this->load_view( 'error' );
		} else {
			return $this->load_view( 'import' );
		}
	}

	/**
	 * Show the fields page via admin menu
	 *
	 * @Slug wp-csv-to-db-fields
	 */
	public function fields_page_action() {
		return $this->load_view( 'fields' );
	}

	/**
	 * Show the items page via admin menu
	 *
	 * @Slug wp-csv-to-db
	 */
	public function items_page_action() {
		$columns = $this->collect_columns_to_show();
		if ( ! count( $columns ) ) {
			$this->message = \__( 'Columns undefined! Click <a href="admin.php?page=wp-csv-to-db-fields">Fields</a> to prepare columns.', 'csv-to-db' );

			return $this->load_view( 'error' );
		} else {
			return $this->load_view( 'items', array( 'columns' => $columns ) );
		}
	}

	/**
	 * @Action create_table
	 */
	public function create_table_action() {
		$this->save_fields_action();

		Models\Table::create_table( $this->options['fields'] );
	}

	/**
	 * @Action save_fields
	 */
	public function save_fields_action() {
		$this->options['fields'] = $_POST['csv-to-db']['fields'];
		\update_option( 'csv-to-db', $this->options );
	}

	/**
	 * @Action import_fields
	 */
	public function import_fields_action() {
		try {
			$tmp_file_name = Models\File::upload_file();
			if ( $tmp_file_name ) {
				$content = unserialize( file_get_contents( $tmp_file_name ) );
				if ( $content ) {
					$this->options['fields'] = $content;
					\update_option( 'csv-to-db', $this->options );
					$results = array(
						'success' => true,
						'message' => \__( 'Success!', 'csv-to-db' ),
					);
				}
			}
		} catch ( \Exception $e ) {
			$results = array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
		Models\File::unlink( $tmp_file_name );
		$this->load_view( 'json', $results );
	}

	/**
	 * @Action clear_fields
	 */
	public function clear_fields_action() {
		$this->options['fields'] = array();
		\update_option( 'csv-to-db', $this->options );
	}

	/**
	 * @Action export_fields
	 */
	public function export_fields_action() {
		$this->save_fields_action();
		$content = serialize( $this->options['fields'] );
		$this->load_view( 'attachment', array( 'content' => $content, 'filename' => 'csv-to-db-fields.txt' ) );
	}

	/**
	 * @Action export_schema
	 */
	public function export_schema_action() {
		$this->save_fields_action();
		$createTable = Models\Table::create_schema( $this->options['fields'] );
		$content     = <<<EOC
# Schema File v.1.0.0
# Do not edit!!!
{$createTable};

EOC;
		$this->load_view( 'attachment', array( 'content' => $content, 'filename' => 'csv-to-db-schema.sql' ) );
	}

}

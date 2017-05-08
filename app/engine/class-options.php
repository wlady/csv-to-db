<?php
/**
 * Created by PhpStorm.
 * User: wlady2001
 * Date: 05.05.17
 * Time: 17:18
 */

namespace CSV2DB\Engine;

class Options extends Base {
	const OPTIONS_NAME = 'csv-to-db';

	protected $config = null;

	protected $options = null;

	public function __construct( $config ) {
		$this->config = $config;
		if ( ! ( $this->options = \get_option( self::OPTIONS_NAME ) ) ) {
			$this->options = $this->defaults();
			\add_option( 'csv-to-db', $this->options );
		}
	}

	/**
	 * Return the default options
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'use-local'         => 1,
			'fields-terminated' => ',',
			'fields-enclosed'   => '"',
			'fields-escaped'    => '\\\\',
			'lines-starting'    => '',
			'lines-terminated'  => '\\n',
			'fields'            => array(),
		);
	}

	/**
	 * Return the empty field
	 *
	 * @return array
	 */
	protected function generate_empty_field( $field_name ) {
		return array(
			'name'  => $field_name,
			'type'  => 'VARCHAR',
			'size'  => 255,
			'null'  => 0,
			'ai'    => 0,
			'index' => '',
			'title' => '',
			'show'  => 0,
			'align' => '',
			'check' => 0,
		);
	}

	/**
	 * Called on uninstall
	 */
	public static function purge_options() {
		\delete_option( self::OPTIONS_NAME );
		\delete_site_option( self::OPTIONS_NAME );
	}

	/**
	 * Get specific option from the options table
	 *
	 * @param string $option Name of option to be used as array key for retrieving the specific value
	 *
	 * @return mixed
	 */
	public function get_option( $option, $options = null ) {
		if ( is_null( $options ) ) {
			$options = $this->options;
		}
		if ( isset ( $options[ $option ] ) ) {
			return $options[ $option ];
		} else {
			return false;
		}
	}

	/**
	 * Update the options in the options table from the POST
	 *
	 * @param mixed $options
	 *
	 * @return none
	 */
	public function update( $options ) {
		if ( isset( $_POST['csv-to-db-defaults'] ) ) {
			$this->options = $this->defaults();
		} else {
			if ( count( $this->options['fields'] ) ) {
				$options['fields'] = $this->options['fields'];
			} else {
				$options['fields'] = array();
			}
			$this->options = $options;
		}

		return $this->options;
	}
}

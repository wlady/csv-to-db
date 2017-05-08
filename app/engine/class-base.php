<?php

namespace CSV2DB\Engine;

use CSV2DB\Controllers;

class Base {
	public $controller = null;
	protected $config = null;

	public function __construct( $config ) {
		$this->config = $config;
		\load_textdomain( 'csv-to-db', $this->config['plugin_dir'] . '/lang/csv-to-db-' . \get_locale() . '.mo' );
	}

	public function init() {
		if ( \is_admin() ) {
			$this->controller = new Controllers\Admin( $this->config );
		} else {
			$this->controller = new Controllers\Front( $this->config );
		}
		$this->controller->init();
		if ( isset( $_POST['action'] ) ) {
			$this->controller->dispatch( $_POST['action'] );
		}
	}

	public function load_view( $view, $data = null ) {
		ob_start();
		include_once( $this->config['plugin_dir'] . '/app/views/' . $view . '-template.php' );
		$content = ob_get_contents();
		ob_end_flush();

		return $content;
	}

}


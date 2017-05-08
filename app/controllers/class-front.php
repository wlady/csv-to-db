<?php

namespace CSV2DB\Controllers;

use CSV2DB\Engine\Options;

class Front extends Options {
	public function __construct( $config ) {
		parent::__construct( $config );

		\register_activation_hook( $this->plugin_file, array( $this, 'init' ) );

	}

	public function dispatch( $action ) {
	}

	public function init() {
	}
}

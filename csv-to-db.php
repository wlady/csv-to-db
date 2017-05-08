<?php
/**
 * Plugin Name: CSV to DB
 * Description: Demo plugin is built with micro-MVC engine.
 * Version: 1.0.0
 * Author: Vladimir Zabara <wlady2001@gmail.com>
 */

namespace CSV2DB;

if ( ! defined( 'WPINC' ) ) {
	die;
}

include __DIR__ . '/app/autoload.php';

$config = array(
	'plugin_file'     => __FILE__,
	'plugin_basename' => \plugin_basename( __FILE__ ),
	'plugin_slug'     => basename( __DIR__ ),
	'plugin_dir'      => __DIR__,
);

$plugin = new Engine\Base( $config );
$plugin->init();

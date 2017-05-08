<?php

namespace CSV2DB;

use CSV2DB\Engine\Options;
use CSV2DB\Models\Table;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

include __DIR__ . '/app/autoload.php';

Options::purge_options();
Table::drop_tables();

<?php
/**
 * Plugin Name: CSV to DB
 * Description: This plugin allows to import/export lists in various formats
 * Version: 1.0.0
 * Author: Vladimir Zabara <wlady2001@gmail.com>
 */

include('csv-to-db.class.php');
if ( is_admin () ) {
	include('csv-to-db-admin.class.php');
    $plugin = new CSV2DBAdmin();
} else {
	new CSV2DB();
}

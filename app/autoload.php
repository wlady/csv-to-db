<?php

spl_autoload_register('csv_to_db_autoload');

function csv_to_db_autoload($class_name)
{
    if ( false === strpos( $class_name, 'CSV2DB' ) ) {
        return;
    }

    $file_parts = explode( '\\', $class_name );
    foreach ($file_parts as $index=>$part) {
        $current = strtolower($part);
        $current = str_ireplace( '_', '-', $current );
        if (!$index) {
            $namespace = 'app';
        } else if ($index<count($file_parts)-1) {
            $namespace .= DIRECTORY_SEPARATOR . $current;
        } else {
            $file_name = "$current.php";
        }
    }
    $filepath  = dirname(__DIR__) . DIRECTORY_SEPARATOR . $namespace . DIRECTORY_SEPARATOR . $file_name;

    if ( file_exists( $filepath ) ) {
        include_once( $filepath );
    } else {
        wp_die(
            esc_html( "The file attempting to be loaded at $filepath does not exist." )
        );
    }

}

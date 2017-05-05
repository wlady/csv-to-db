<?php

spl_autoload_register('csv_to_db_autoload');

function csv_to_db_autoload($class_name)
{
    if ( false === strpos( $class_name, 'CSV2DB' ) ) {
        return;
    }

    // Split the class name into an array to read the namespace and class.
    $file_parts = explode( '\\', $class_name );
    // Do a reverse loop through $file_parts to build the path to the file.
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
    // Now build a path to the file using mapping to the file location.
    $filepath  = dirname(__DIR__) . DIRECTORY_SEPARATOR . $namespace . DIRECTORY_SEPARATOR;
    $filepath .= $file_name;

    // If the file exists in the specified path, then include it.
    if ( file_exists( $filepath ) ) {
        include_once( $filepath );
    } else {
        wp_die(
            esc_html( "The file attempting to be loaded at $filepath does not exist." )
        );
    }

}
<?php

spl_autoload_register( 'csv_to_db_autoload' );

function csv_to_db_autoload( $class_name ) {
	if ( false === strpos( $class_name, 'CSV2DB' ) ) {
		return;
	}

	$file_parts = explode( '\\', $class_name );
	foreach ( $file_parts as $index => $part ) {
		$current = strtolower( $part );
		$current = str_ireplace( '_', '-', $current );
		if ( ! $index ) {
			$namespace = 'app';
		} elseif ( $index < count( $file_parts ) - 1 ) {
			$namespace .= DIRECTORY_SEPARATOR . $current;
		} else {
			$file_name = "class-{$current}.php";
		}
	}
	$file_path = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . $namespace . DIRECTORY_SEPARATOR . $file_name;

	if ( file_exists( $file_path ) ) {
		include_once( $file_path );
	} else {
		wp_die(
			esc_html( "The file attempting to be loaded at {$file_path} does not exist." )
		);
	}

}

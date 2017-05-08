<?php
/**
 * Created by PhpStorm.
 * User: wlady2001
 * Date: 05.05.17
 * Time: 12:20
 */

namespace CSV2DB\Models;

class File {
	/**
	 * Upload file by AJAX
	 * @return string/void If not requested by AJAX
	 * @throws \Exception
	 */
	public static function upload_file() {
		if ( isset( $_FILES["file"] ) && $_FILES["file"]["error"] == UPLOAD_ERR_OK ) {

			$upload_directory = \wp_upload_dir();

			if ( $_FILES["file"]["size"] > self::convert_bytes( ini_get( 'upload_max_filesize' ) ) ) {
				throw new \Exception( \__( 'File size is too big!', 'csv-to-db' ) );
			}

			switch ( strtolower( $_FILES['file']['type'] ) ) {
				//allowed file types
				case 'text/csv':
				case 'text/plain':
					break;
				default:
					throw new \Exception( \__( 'Unsupported File!', 'csv-to-db' ) );
			}

			$file_name     = strtolower( $_FILES['file']['name'] );
			$file_ext      = substr( $file_name, strrpos( $file_name, '.' ) ); //get file extention
			$random_number = rand( 0, 9999999999 ); //Random number to be added to name.
			$new_file_name = $random_number . $file_ext; //new file name
			$tmp_file_name = $upload_directory['basedir'] . '/' . $new_file_name;

			if ( move_uploaded_file( $_FILES['file']['tmp_name'], $tmp_file_name ) ) {
				return $tmp_file_name;
			} else {
				throw new \Exception( \__( 'Error uploading File!', 'csv-to-db' ) );
			}
		} else {
			throw new \Exception( __( 'Something wrong with upload! Is "upload_max_filesize" set correctly?', 'csv-to-db' ) );
		}
	}

	/**
	 * Translate human readable values (128M => 134217728)
	 */
	public static function convert_bytes( $value ) {
		if ( is_numeric( $value ) ) {
			return $value;
		} else {
			$value_length = strlen( $value );
			$quantity     = substr( $value, 0, $value_length - 1 );
			$unit         = strtolower( substr( $value, $value_length - 1 ) );
			switch ( $unit ) {
				case 'k':
					$quantity *= 1024;
					break;
				case 'm':
					$quantity *= 1048576;
					break;
				case 'g':
					$quantity *= 1073741824;
					break;
			}

			return $quantity;
		}
	}

	public static function unlink( $file ) {
		// remove temp file
		@unlink( $file );
	}

}
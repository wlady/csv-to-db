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
    public static function uploadFile()
    {
        if (isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {

            $uploadDirectory = \wp_upload_dir();

            //Is file size is less than allowed size.
            if ($_FILES["file"]["size"] > self::convertBytes(ini_get('upload_max_filesize'))) {
                throw new \Exception(\__('File size is too big!', 'csv-to-db'));
            }

            //allowed file type Server side check
            switch (strtolower($_FILES['file']['type'])) {
                //allowed file types
                case 'text/csv':
                case 'text/plain':
                    break;
                default:
                    throw new \Exception(\__('Unsupported File!', 'csv-to-db'));
            }

            $fileName = strtolower($_FILES['file']['name']);
            $fileExt = substr($fileName, strrpos($fileName, '.')); //get file extention
            $randomNumber = rand(0, 9999999999); //Random number to be added to name.
            $newFileName = $randomNumber . $fileExt; //new file name
            $tmpFileName = $uploadDirectory['basedir'] . '/' . $newFileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $tmpFileName)) {
                return $tmpFileName;
            } else {
                throw new \Exception(\__('Error uploading File!', 'csv-to-db'));
            }
        } else {
            throw new \Exception(__('Something wrong with upload! Is "upload_max_filesize" set correctly?', 'csv-to-db'));
        }
    }

    public static function unlink($file)
    {
        // remove temp file
        @unlink($file);
    }

    /**
     * Convert human readable values (128M => 134217728)
     */
    public static function convertBytes($value)
    {
        if (is_numeric($value)) {
            return $value;
        } else {
            $value_length = strlen($value);
            $qty = substr($value, 0, $value_length - 1);
            $unit = strtolower(substr($value, $value_length - 1));
            switch ($unit) {
                case 'k':
                    $qty *= 1024;
                    break;
                case 'm':
                    $qty *= 1048576;
                    break;
                case 'g':
                    $qty *= 1073741824;
                    break;
            }
            return $qty;
        }
    }

}
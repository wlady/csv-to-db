<?php

/**
 * CSV2DBAdmin class for admin actions
 *
 */
class CSV2DBAdmin extends CSV2DB
{
    private $dataIdField = '';

    /**
     * Setup backend functionality in WordPress
     *
     * @since 3.0.0.0
     */
    public function __construct()
    {
        parent::__construct();

        register_activation_hook($this->plugin_file, array($this, 'init'));

        // routing actions
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'save_fields':
                    $this->saveFields();
                    break;
                case 'export_schema':
                    $this->exportSchema();
                    break;
                case 'create_table':
                    $this->createTable();
                    break;
                case 'clear_fields':
                    $this->clearFields();
                    break;
                case 'export_fields':
                    $this->exportFields();
                    break;
                case 'import_fields':
                    $this->importFields();
                    break;
            }
        }
    }

    /**
     * Whitelist the csv-to-db options
     *
     * @since 3.0.0.1
     * @return none
     */
    function registerSettings()
    {
        register_setting('csv-to-db', 'csv-to-db', array($this, 'update'));
    }

    public function init()
    {
        parent::init();
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_menu', array($this, 'addPage'), 11, 0);
        add_action('wp_ajax_import_csv', array($this, 'importCsv'));
        add_action('wp_ajax_analyze_csv', array($this, 'analyzeCsv'));
        add_action('wp_ajax_get_items', array($this, 'getItems'));

        wp_enqueue_style('poi_mapper_bootstrap_css', plugins_url('/bootstrap/css/bootstrap.min.css', __FILE__));
        wp_enqueue_style('poi_mapper_bootstrap_table_css', plugins_url('/bootstrap-table/bootstrap-table.css', __FILE__));
        wp_enqueue_script('poi_mapper_bootstrap_js', plugins_url('/bootstrap/js/bootstrap.min.js', __FILE__));
        wp_enqueue_script('poi_mapper_bootstrap_table_js', plugins_url('/bootstrap-table/bootstrap-table.js', __FILE__));
        wp_enqueue_script('poi_mapper_bootstrap_table_export_js', plugins_url('/bootstrap-table/extensions/export/bootstrap-table-export.min.js', __FILE__));
        wp_enqueue_script('poi_mapper_bootstrap_table_export_addon_js', plugins_url('/bootstrap-table/tableExport.min.js', __FILE__));
    }

    /**
     * Update/validate the options in the options table from the POST
     *
     * @since 3.0.0.1
     * @param mixed $options
     * @return none
     */
    public function update($options)
    {
        if (!empty($_POST['csv-to-db-defaults'])) {
            $this->options = $this->defaults();
        } else {
            foreach ($this->defaults() as $key => $value) {
                if (!isset ($options[$key])) {
                    $options[$key] = $value;
                }
            }
            if (count($this->options['fields'])) {
                $options['fields'] = $this->options['fields'];
            }
            $this->options = $options;
        }
        return $this->options;
    }

    /**
     * Add the options page
     *
     * @return none
     * @since 2.0.3
     */
    public function addPage()
    {
        if (current_user_can('manage_options')) {
            add_menu_page(__('CSV to DB', 'csv-to-db'), __('CSV to DB', 'csv-to-db'), 'manage_options', 'wp-csv-to-db', array($this, 'itemsPage'), 'dashicons-book-alt');
            add_submenu_page('wp-csv-to-db', __('Import', 'csv-to-db'), __('Import', 'csv-to-db'), 'manage_options', 'wp-csv-to-db-import', array($this, 'importPage'));
            add_submenu_page('wp-csv-to-db', __('Fields', 'csv-to-db'), __('Fields', 'csv-to-db'), 'manage_options', 'wp-csv-to-db-fields', array($this, 'fieldsPage'));
            add_submenu_page('wp-csv-to-db', __('Settings', 'csv-to-db'), __('Settings', 'csv-to-db'), 'manage_options', 'wp-csv-to-db-settings', array($this, 'adminPage'));
        }
    }

    /**
     * Output the options page
     *
     * @return none
     */
    public function adminPage()
    {
        include(dirname(__FILE__) . '/options-page.php');
    }

    /**
     * Output the import page
     *
     * @return none
     */
    public function importPage()
    {
        if (!count($this->options['fields'])) {
            _e(sprintf('<div id="message" class="updated error"><p>Fields undefined! Click <a href="%s">Fields</a> to prepare the fields.</p></div>', 'admin.php?page=wp-csv-to-db-fields'));
        } else {
            $maxFileSize = $this->convertBytes(ini_get('upload_max_filesize'));
            include('import-page.php');
        }
    }

    public function fieldsPage()
    {
        $message = $this->message;
        $maxFileSize = $this->convertBytes(ini_get('upload_max_filesize'));
        include('fields-page.php');
    }

    public function itemsPage()
    {
        $columns = $this->collectColumnsToShow();
        $idField = $this->dataIdField;
        if (!count($columns)) {
            _e(sprintf('<div id="message" class="updated error"><p>Columns undefined! Click <a href="%s">Fields</a> to prepare the fields.</p></div>', 'admin.php?page=wp-csv-to-db-fields'));
        } else {
            include('items-page.php');
        }
    }

    public function importFields()
    {
        try {
            $tmpFileName = $this->uploadFile();
            if ($tmpFileName) {
                $content = unserialize(file_get_contents($tmpFileName));
                if ($content) {
                    $this->options['fields'] = $content;
                    update_option('csv-to-db', $this->options);
                    echo json_encode(
                        array(
                            'success' => true,
                            'message' => __('Success!', 'csv-to-db'),
                        )
                    );
                }
            }
        } catch (Exception $e) {
            echo json_encode(
                array(
                    'success' => false,
                    'message' => $e->getMessage(),
                )
            );
        }
        // remove temp file
        @unlink($tmpFileName);
        wp_die();
    }

    /**
     * Upload file by AJAX
     * @return void If not requested by AJAX
     * @throws Exception
     */
    protected function uploadFile()
    {
        if (isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {

            $uploadDirectory = wp_upload_dir();

            //Is file size is less than allowed size.
            if ($_FILES["file"]["size"] > $this->convertBytes(ini_get('upload_max_filesize'))) {
                throw new Exception(__('File size is too big!', 'csv-to-db'));
            }

            //allowed file type Server side check
            switch (strtolower($_FILES['file']['type'])) {
                //allowed file types
                case 'text/csv':
                case 'text/plain':
                    break;
                default:
                    throw new Exception(__('Unsupported File!', 'csv-to-db'));
            }

            $fileName = strtolower($_FILES['file']['name']);
            $fileExt = substr($fileName, strrpos($fileName, '.')); //get file extention
            $randomNumber = rand(0, 9999999999); //Random number to be added to name.
            $newFileName = $randomNumber . $fileExt; //new file name
            $tmpFileName = $uploadDirectory['basedir'] . '/' . $newFileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $tmpFileName)) {
                return $tmpFileName;
            } else {
                throw new Exception(__('Error uploading File!', 'csv-to-db'));
            }
        } else {
            throw new Exception(__('Something wrong with upload! Is "upload_max_filesize" set correctly?', 'csv-to-db'));
        }
    }

    /**
     * Import CSV file by AJAX
     */
    public function importCsv()
    {
        header('Content-Type: application/json');
        try {
            $tmpFileName = $this->uploadFile();
            if ($tmpFileName) {
                if (isset($_POST['re-create'])) {
                    $res = $this->createTable();
                    if (is_string($res)) {
                        throw new Exception(htmlspecialchars($res, ENT_QUOTES));
                    }
                }
                $res = $this->importFile($tmpFileName);
                if (is_string($res)) {
                    throw new Exception(htmlspecialchars($res, ENT_QUOTES));
                } else {
                    echo json_encode(
                        array(
                            'success' => true,
                            'message' => __('Success!', 'csv-to-db'),
                        )
                    );
                }
            }
        } catch (Exception $e) {
            echo json_encode(
                array(
                    'success' => false,
                    'message' => $e->getMessage(),
                )
            );
        }
        // remove temp file
        @unlink($tmpFileName);
        wp_die();
    }

    /**
     * Analyze CSV file by AJAX
     */
    public function analyzeCsv()
    {
        header('Content-Type: application/json');
        try {
            $tmpFileName = $this->uploadFile();
            if ($tmpFileName) {
                $fp = fopen($tmpFileName, 'r');
                if (!$fp) {
                    throw new Exception(__('Cannot read from CSV', 'csv-to-db'));
                }
                $fields = fgetcsv($fp, 0, $this->get_option('fields-terminated'), $this->get_option('fields-enclosed'), $this->get_option('fields-escaped'));
                if (!count($fields)) {
                    throw new Exception(__('Cannot detect fields', 'csv-to-db'));
                } else {
                    // save fields
                    $fieldsData = array();
                    foreach ($fields as $field) {
                        $fieldsData[] = array(
                            'name'  => $field,
                            'type'  => 'VARCHAR',
                            'size'  => '255',
                            'null'  => 0,
                            'ai'    => 0,
                            'index' => '',
                            'title' => '',
                            'show'  => 0,
                            'align' => '',
                            'check' => 0,
                        );
                    }
                    $this->options['fields'] = $fieldsData;
                    update_option('csv-to-db', $this->options);
                    echo json_encode(
                        array(
                            'success' => true,
                            'data'    => $fields,
                            'message' => __('Success! Reloading...', 'csv-to-db'),
                        )
                    );
                }
            }
        } catch (Exception $e) {
            echo json_encode(
                array(
                    'success' => false,
                    'message' => $e->getMessage(),
                )
            );
        }
        // remove temp file
        @unlink($tmpFileName);
        wp_die();
    }

    public function getItems()
    {
        global $wpdb;
        $columns = $this->collectColumnsToShow($skipAutogenerated = true);
        if (count($columns)) {
            $start = (int)filter_var($_POST['offset'], FILTER_SANITIZE_NUMBER_INT);
            $limit = (int)filter_var($_POST['limit'], FILTER_SANITIZE_NUMBER_INT);
            if (!$limit) {
                $limit = 10;
            }
            $order = filter_var($_POST['order'], FILTER_SANITIZE_STRING);
            $fields = array_column($columns, 'name');
            $res = $wpdb->get_results('SELECT SQL_CALC_FOUND_ROWS `' . implode('`,`', $fields) . '` FROM `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '` LIMIT ' . "{$start}, {$limit}");
            $total = $wpdb->get_var('SELECT FOUND_ROWS() AS total');
            $rows = $this->convertFields($columns, $res);
            header('Content-Type: application/json');
            echo json_encode(
                array(
                    'total' => (int)$total,
                    'rows'  => (array)$rows,
                )
            );
        }
        wp_die();
    }

    /**
     * Save fields settings
     */
    public function saveFields()
    {
        $this->options['fields'] = $_POST['csv-to-db']['fields'];
        update_option('csv-to-db', $this->options);
    }

    /**
     * Clear fields settings
     */
    public function clearFields()
    {
        $this->options['fields'] = array();
        update_option('csv-to-db', $this->options);
    }

    /**
     * Export fields to file
     */
    public function exportFields()
    {
        $this->saveFields();
        $content = serialize($this->options['fields']);

        header('Content-Type: text/plain; charset=' . get_option('blog_charset'), true);
        header('Content-Disposition: attachment; filename="csv-to-db-fields.txt"');
        header('Content-Length:' . strlen($content));
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        echo $content;
        exit();
    }

    /**
     * Export schema file
     */
    public function exportSchema()
    {
        $this->saveFields();
        $createTable = $this->createSchema();
        $content = <<<EOC
# Schema File v.1.0.0
# Do not edit!!!
{$createTable};

EOC;

        header('Content-Type: text/plain; charset=' . get_option('blog_charset'), true);
        header('Content-Disposition: attachment; filename="csv-to-db-schema.sql"');
        header('Content-Length:' . strlen($content));
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        echo $content;
        exit();
    }

    /**
     * Convert human readable values (128M => 134217728)
     */
    private function convertBytes($value)
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

    /**
     * Create DB table from saved fields settings
     * @return mix On error returns error message
     */
    protected function createTable()
    {
        global $wpdb;

        $this->saveFields();
        $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '`');
        $schema = $this->createSchema();
        $wpdb->query($schema);

        return $wpdb->last_error !== '' ? $wpdb->last_error : true;
    }

    protected function createSchema()
    {
        global $wpdb;

        $columns = array();
        $indexes = array();
        foreach ($this->options['fields'] as $field) {
            $type = $field['type'];
            $null = $field['null'] == 0 ? 'NOT NULL' : 'NULL';
            $ai = $field['ai'] == 1 ? 'AUTO_INCREMENT' : '';
            if (!in_array($type, array('TEXT', 'BLOB'))) {
                $type = "{$field['type']}({$field['size']}) {$null} {$ai}";
            }
            $columns[] = "`{$field['name']}` {$type}";
            if (!empty($field['index'])) {
                if ($field['index'] == 'PRIMARY') {
                    $curIndex = 'PRIMARY KEY ';
                } else if ($field['index'] == 'UNIQUE') {
                    $curIndex = "UNIQUE KEY `{$field['name']}`";
                } else {
                    $curIndex = "KEY `{$field['name']}`";
                }
                $indexes[] = $curIndex . "(`{$field['name']}`)";
            }
        }
        if (count($indexes)) {
            $columns = array_merge($columns, $indexes);
        }

        return 'CREATE TABLE IF NOT EXISTS `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '` (' . implode(',', $columns) . ')';
    }

    /**
     * @param $fileName
     * @return mix On error returns error message
     */
    protected function importFile($fileName)
    {
        global $wpdb;

        $use_local = $this->get_option('use-local') == 1 ? 'LOCAL' : '';
        $fields_params = array();
        $lines_params = array();
        if (!empty($this->get_option('fields-terminated'))) {
            $fields_params[] = 'TERMINATED BY \'' . $this->get_option('fields-terminated') . '\'';
        }
        if (!empty($this->get_option('fields-enclosed'))) {
            $symbol = $this->get_option('fields-enclosed');
            if (in_array($symbol, array('"', "'"))) {
                $fields_params[] = 'ENCLOSED BY \'\\' . $this->get_option('fields-enclosed') . '\'';
            }
        }
        if (!empty($this->get_option('fields-escaped'))) {
            $fields_params[] = 'ESCAPED BY \'' . $this->get_option('fields-escaped') . '\'';
        }
        if (!empty($this->get_option('lines-starting'))) {
            $lines_params[] = 'STARTING BY \'' . $this->get_option('lines-starting') . '\'';
        }
        if (!empty($this->get_option('lines-terminated'))) {
            $lines_params[] = 'TERMINATED BY \'' . $this->get_option('lines-terminated') . '\'';
        }
        $query = 'LOAD DATA ' . $use_local . ' INFILE \'' . $fileName . '\' INTO TABLE `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '`';
        if (count($fields_params)) {
            $query .= ' FIELDS ' . implode(' ', $fields_params);
        }
        if (count($lines_params)) {
            $query .= ' LINES ' . implode(' ', $lines_params);
        }
        if (intval($_POST['skip-rows']) > 0) {
            $query .= ' IGNORE ' . intval($_POST['skip-rows']) . ' LINES';
        }
        $wpdb->query($query);

        return $wpdb->last_error !== '' ? $wpdb->last_error : true;
    }

    public function collectColumnsToShow($skipAutogenerated = false)
    {
        $columns = array();
        $checked = false;
        foreach ($this->options['fields'] as $field) {
            if (isset($field['show']) && !empty($field['title'])) {
                $columns[] = $field;
                if (isset($field['check'])) {
                    $this->dataIdField = $field['name'];
                    $checked = true;
                }
            }
        }
        usort($columns, function ($a, $b) {
            return (isset($a['index']) && $a['index'] == 'PRIMARY') ? 0 : 1;
        });
        if (!$skipAutogenerated && !$checked) {
            array_unshift($columns, array(
                'name'  => '_autogenerated_check_column',
                'check' => true,
            ));
            $this->dataIdField = '_autogenerated_check_column';
        }

        return $columns;
    }

    public function convertFields($columns, $records)
    {
        $rows = array_map(function ($item) use ($columns) {
            $row = (array)$item;
            foreach ($row as $field => $value) {
                $column = array_filter($columns, function ($col) use ($field) {
                    return $col['name'] == $field ? $col : null;
                });
                $col = array_pop($column);
                switch ($col['type']) {
                    case 'INT':
                        $item->{$field} = (int)$value;
                        break;
                    case 'FLOAT':
                        $item->{$field} = (float)$value;
                        break;
                    case 'DOUBLE':
                    case 'DECIMAL':
                        $item->{$field} = (double)$value;
                        break;
                }
                if ($col['index'] == 'PRIMARY') {
                    $item->id = (int)$value;
                }
            }
            return $item;
        }, $records);

        return $rows;
    }
}

function _var_dump($var)
{
    ob_start();
    print_r($var);
    $v = ob_get_contents();
    ob_end_clean();
    return $v . PHP_EOL;
}

function flog($var)
{
    file_put_contents('/tmp/log.txt', '+---+ ' . date('H:i:s d-m-Y') . ' +-----+' . PHP_EOL . _var_dump($var) . PHP_EOL, FILE_APPEND);
}


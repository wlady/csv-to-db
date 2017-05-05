<?php
/**
 * Created by PhpStorm.
 * User: wlady2001
 * Date: 05.05.17
 * Time: 12:15
 */

namespace CSV2DB\Controllers;

use CSV2DB\Engine\Options;
use CSV2DB\Models;

class Admin extends Options
{
    // Every POST action has related method
    private $actions = array(
        'save_fields', // saveFieldsAction etc
        'export_schema',
        'create_table',
        'clear_fields',
        'export_fields',
        'import_fields',
    );

    // Every hook has related method
    private $hooks = array(
        'admin_init', // adminInitHook etc
        'admin_menu',
        'wp_ajax_import_csv',
        'wp_ajax_analyze_csv',
        'wp_ajax_get_items',
    );

    // Styles to enqueue (related to plugin directory)
    private $styles = array(
        '/assets/bootstrap/css/bootstrap.min.css',
        '/assets/bootstrap-table/bootstrap-table.css',
        '/assets/style.css',
    );

    // Scripts to enqueue (related to plugin directory)
    private $scripts = array(
        '/assets/bootstrap/js/bootstrap.min.js',
        '/assets/bootstrap-table/bootstrap-table.js',
        '/assets/bootstrap-table/extensions/export/bootstrap-table-export.min.js',
        '/assets/tableExport.min.js',
        '/assets/utilities.js',
    );

    protected $upload_max_filesize = 0;
    protected $data_id_field = '';

    /**
     * Setup backend functionality in WordPress
     *
     * @since 3.0.0.0
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->upload_max_filesize = Models\File::convertBytes(ini_get('upload_max_filesize'));
    }

    public function init()
    {
        // init hooks
        foreach ($this->hooks as $hook) {
            $parts = explode('_', $hook);
            $parts[] = 'hook';
            $method = str_replace(' ', '', lcfirst(ucwords(implode(' ', $parts))));
            if (method_exists($this, $method)) {
                \add_action($hook, array($this, $method));
            }
        }
        // enqueue styles
        foreach ($this->styles as $style) {
            \wp_enqueue_style(md5($style), \plugins_url($style, $this->config['plugin_basename']));
        }
        // enqueue scripts
        foreach ($this->scripts as $script) {
            \wp_enqueue_script(md5($script), \plugins_url($script, $this->config['plugin_basename']));
        }
    }

    /**
     * @param $action
     * @throws \Exception
     */
    public function dispatch($action)
    {
        // route POST requests
        if (in_array($action, $this->actions)) {
            $parts = explode('_', $action);
            $parts[] = 'action';
            $method = str_replace(' ', '', lcfirst(ucwords(implode(' ', $parts))));
            if (method_exists($this, $method)) {
                $this->$method();
            } else {
                throw new \Exception(\__('Method ' . $method . ' was not found', 'csv-to-db'));
            }
        }
    }

    /**
     * Whitelist the csv-to-db options
     *
     * @Hook admin_init
     * @since 3.0.0.1
     * @return none
     */
    public function adminInitHook()
    {
        \register_setting('csv-to-db', 'csv-to-db', array($this, 'update'));
    }

    /**
     * Add the options page
     *
     * @Hook admin_menu
     * @since 2.0.3
     * @return none
     */
    public function adminMenuHook()
    {
        if (\current_user_can('manage_options')) {
            \add_menu_page(\__('CSV to DB', 'csv-to-db'), \__('CSV to DB', 'csv-to-db'), 'manage_options', 'wp-csv-to-db', array($this, 'itemsPageAction'), 'dashicons-book-alt');
            \add_submenu_page('wp-csv-to-db', \__('Import', 'csv-to-db'), \__('Import', 'csv-to-db'), 'manage_options', 'wp-csv-to-db-import', array($this, 'importPageAction'));
            \add_submenu_page('wp-csv-to-db', \__('Fields', 'csv-to-db'), \__('Fields', 'csv-to-db'), 'manage_options', 'wp-csv-to-db-fields', array($this, 'fieldsPageAction'));
            \add_submenu_page('wp-csv-to-db', \__('Settings', 'csv-to-db'), \__('Settings', 'csv-to-db'), 'manage_options', 'wp-csv-to-db-settings', array($this, 'optionsPageAction'));
        }
    }

    /**
     * Import CSV file by AJAX
     *
     * @Hook wp_ajax_import_csv
     */
    public function wpAjaxImportCsvHook()
    {
        try {
            $tmpFileName = Models\File::uploadFile();
            if ($tmpFileName) {
                if (isset($_POST['re-create'])) {
                    $res = Models\Table::createTable($this->options['fields']);
                    if (is_string($res)) {
                        throw new \Exception(htmlspecialchars($res, ENT_QUOTES));
                    }
                }
                $res = Models\Table::importFile($tmpFileName, $this->options);
                if (is_string($res)) {
                    throw new \Exception(htmlspecialchars($res, ENT_QUOTES));
                } else {
                    $results = array(
                        'success' => true,
                        'message' => \__('Success!', 'csv-to-db'),
                    );
                }
            }
        } catch (\Exception $e) {
            $results = array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
        Models\File::unlink($tmpFileName);
        $this->load_view('json', $results);
    }

    /**
     * Analyze CSV file by AJAX
     *
     * @Hook wp_ajax_analyze_csv
     */
    public function wpAjaxAnalyzeCsvHook()
    {
        try {
            $tmpFileName = Models\File::uploadFile();
            if ($tmpFileName) {
                $fp = fopen($tmpFileName, 'r');
                if (!$fp) {
                    throw new \Exception(\__('Cannot read from CSV', 'csv-to-db'));
                }
                $fields = fgetcsv($fp, 0, $this->get_option('fields-terminated'), $this->get_option('fields-enclosed'), $this->get_option('fields-escaped'));
                if (!$fields || !count($fields)) {
                    throw new \Exception(\__('Cannot detect fields', 'csv-to-db'));
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
                    \update_option('csv-to-db', $this->options);
                    $results = array(
                        'success' => true,
                        'data'    => $fields,
                        'message' => \__('Success! Reloading...', 'csv-to-db'),
                    );
                }
            }
        } catch (\Exception $e) {
            $results = array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
        Models\File::unlink($tmpFileName);
        $this->load_view('json', $results);
    }

    /**
     * Get items by AJAX
     *
     * @Hook wp_ajax_get_items
     */
    public function wpAjaxGetItemsHook()
    {
        $results = array(
            'total' => 0,
            'rows'  => array(),
        );
        $columns = $this->collectColumnsToShow($skipAutogenerated = true);
        if (count($columns)) {
            $start = (int)filter_var($_POST['offset'], FILTER_SANITIZE_NUMBER_INT);
            $limit = (int)filter_var($_POST['limit'], FILTER_SANITIZE_NUMBER_INT);
            if (!$limit) {
                $limit = 10;
            }
            $order = filter_var($_POST['order'], FILTER_SANITIZE_STRING);
            $fields = array_column($columns, 'name');
            list($total, $rows) = Models\Table::getItems($columns, $fields, $start, $limit, $order);
            $results = array(
                'total' => (int)$total,
                'rows'  => (array)$rows,
            );
        }
        $this->load_view('json', $results);
    }

    /**
     * Show the options page via admin menu
     *
     * @Slug wp-csv-to-db-settings
     */
    public function optionsPageAction()
    {
        return $this->load_view('options');
    }

    /**
     * Show the import page via admin menu
     *
     * @Slug wp-csv-to-db-import
     */
    public function importPageAction()
    {
        if (!count($this->options['fields'])) {
            $this->message = \__('Fields undefined! Click <a href="admin.php?page=wp-csv-to-db-fields">Fields</a> to prepare the fields.', 'csv-to-db');
            return $this->load_view('error');
        } else {
            return $this->load_view('import');
        }
    }

    /**
     * Show the fields page via admin menu
     *
     * @Slug wp-csv-to-db-fields
     */
    public function fieldsPageAction()
    {
        return $this->load_view('fields');
    }

    /**
     * Show the items page via admin menu
     *
     * @Slug wp-csv-to-db
     */
    public function itemsPageAction()
    {
        $columns = $this->collectColumnsToShow();
        if (!count($columns)) {
            $this->message = \__('Columns undefined! Click <a href="admin.php?page=wp-csv-to-db-fields">Fields</a> to prepare the fields.', 'csv-to-db');
            return $this->load_view('error');
        } else {
            return $this->load_view('items', array('columns' => $columns));
        }
    }

    /**
     * @Action create_table
     */
    public function createTableAction()
    {
        $this->saveFieldsAction();

        Models\Table::createTable($this->options['fields']);
    }

    /**
     * @Action import_fields
     */
    public function importFieldsAction()
    {
        try {
            $tmpFileName = Models\File::uploadFile();
            if ($tmpFileName) {
                $content = unserialize(file_get_contents($tmpFileName));
                if ($content) {
                    $this->options['fields'] = $content;
                    \update_option('csv-to-db', $this->options);
                    $results = array(
                        'success' => true,
                        'message' => \__('Success!', 'csv-to-db'),
                    );
                }
            }
        } catch (\Exception $e) {
            $results = array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
        Models\File::unlink($tmpFileName);
        $this->load_view('json', $results);
    }

    /**
     * @Action save_fields
     */
    public function saveFieldsAction()
    {
        $this->options['fields'] = $_POST['csv-to-db']['fields'];
        \update_option('csv-to-db', $this->options);
    }

    /**
     * @Action clear_fields
     */
    public function clearFieldsAction()
    {
        $this->options['fields'] = array();
        \update_option('csv-to-db', $this->options);
    }

    /**
     * @Action export_fields
     */
    public function exportFieldsAction()
    {
        $this->saveFieldsAction();
        $content = serialize($this->options['fields']);
        $this->load_view('attachment', array('content' => $content, 'filename' => 'csv-to-db-fields.txt'));
    }

    /**
     * @Action export_schema
     */
    public function exportSchemaAction()
    {
        $this->saveFieldsAction();
        $createTable = Models\Table::createSchema($this->options['fields']);
        $content = <<<EOC
# Schema File v.1.0.0
# Do not edit!!!
{$createTable};

EOC;
        $this->load_view('attachment', array('content' => $content, 'filename' => 'csv-to-db-schema.sql'));
    }

    /**
     * @param bool $skipAutogenerated
     * @return array
     */
    public function collectColumnsToShow($skipAutogenerated = false)
    {
        $columns = array();
        $checked = false;
        foreach ($this->options['fields'] as $field) {
            if (isset($field['show']) && !empty($field['title'])) {
                $columns[] = $field;
                if (isset($field['check'])) {
                    $this->data_id_field = $field['name'];
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

}

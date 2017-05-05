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
        foreach($this->hooks as $hook) {
            $parts = explode('_', $hook);
            $parts[] = 'hook';
            $method = str_replace(' ', '', lcfirst(ucwords(implode(' ', $parts))));
            if (method_exists($this, $method)) {
                \add_action($hook, array($this, $method));
            }
        }

        foreach ($this->styles as $style) {
            \wp_enqueue_style(md5($style), \plugins_url($style, $this->config['plugin_basename']));
        }

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
     * @return none
     * @since 2.0.3
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
     */
    public function wpAjaxImportCsvHook()
    {
        header('Content-Type: application/json');
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
                    echo json_encode(
                        array(
                            'success' => true,
                            'message' => \__('Success!', 'csv-to-db'),
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            echo json_encode(
                array(
                    'success' => false,
                    'message' => $e->getMessage(),
                )
            );
        }
        Models\File::unlink($tmpFileName);
        \wp_die();
    }

    /**
     * Analyze CSV file by AJAX
     */
    public function wpAjaxAnalyzeCsvHook()
    {
        header('Content-Type: application/json');
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
                    echo json_encode(
                        array(
                            'success' => true,
                            'data'    => $fields,
                            'message' => \__('Success! Reloading...', 'csv-to-db'),
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            echo json_encode(
                array(
                    'success' => false,
                    'message' => $e->getMessage(),
                )
            );
        }
        Models\File::unlink($tmpFileName);
        \wp_die();
    }

    /**
     * Get items by AJAX
     */
    public function wpAjaxGetItemsHook()
    {
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
            header('Content-Type: application/json');
            echo json_encode(
                array(
                    'total' => (int)$total,
                    'rows'  => (array)$rows,
                )
            );
        }
        \wp_die();
    }

    /**
     * Output the options page
     *
     * @return none
     */
    public function optionsPageAction()
    {
        return $this->load_view('options');
    }

    /**
     * Output the import page
     */
    public function importPageAction()
    {
        if (!count($this->options['fields'])) {
            $this->message = __('Fields undefined! Click <a href="admin.php?page=wp-csv-to-db-fields">Fields</a> to prepare the fields.', 'csv-to-db');
            return $this->load_view('error');
        } else {
            return $this->load_view('import');
        }
    }

    /**
     * Output the fields page
     */
    public function fieldsPageAction()
    {
        return $this->load_view('fields');
    }

    /**
     * Output the items page
     */
    public function itemsPageAction()
    {
        $columns = $this->collectColumnsToShow();
        if (!count($columns)) {
            $this->message = __('Columns undefined! Click <a href="admin.php?page=wp-csv-to-db-fields">Fields</a> to prepare the fields.', 'csv-to-db');
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
                    echo json_encode(
                        array(
                            'success' => true,
                            'message' => \__('Success!', 'csv-to-db'),
                        )
                    );
                }
            }
        } catch (\Exception $e) {
            echo json_encode(
                array(
                    'success' => false,
                    'message' => $e->getMessage(),
                )
            );
        }
        Models\File::unlink($tmpFileName);
        \wp_die();
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

        header('Content-Type: text/plain; charset=' . \get_option('blog_charset'), true);
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

        header('Content-Type: text/plain; charset=' . \get_option('blog_charset'), true);
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

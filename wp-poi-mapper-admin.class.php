<?php

/**
 * POIMapperAdmin class for admin actions
 *
 */
class POIMapperAdmin extends POIMapper
{

    /**
     * Full file system path to the main plugin file
     *
     * @since 3.0.0.0
     * @var string
     */
    protected $plugin_file;

    /**
     * Path to the main plugin file relative to WP_CONTENT_DIR/plugins
     *
     * @since 3.0.0.0
     * @var string
     */
    protected $plugin_basename;

    /**
     * Name of options page hook
     *
     * @since 3.0.0.1
     * @var string
     */
    protected $options_page_hookname;

    /**
     * Plugin slug to detect available updates
     * @var string
     */
    protected $plugin_slug;

    protected $options;

    /**
     * Setup backend functionality in WordPress
     *
     * @since 3.0.0.0
     */
    public function __construct()
    {
        parent::__construct();
        $this->plugin_file = dirname(__FILE__) . '/wp-poi-mapper.php';
        $this->plugin_basename = plugin_basename($this->plugin_file);
        $this->plugin_slug = basename(dirname(__FILE__));
        // Activation hook
        register_activation_hook($this->plugin_file, array($this, 'init'));
        // Whitelist options
        add_action('admin_init', array($this, 'register_settings'));
        // Activate the options page
        add_action('admin_menu', array($this, 'add_page'));
        // import CSV
        add_action('wp_ajax_import_csv', array($this, 'import_csv'));
    }

    /**
     * Whitelist the poi-mapper options
     *
     * @since 3.0.0.1
     * @return none
     */
    function register_settings()
    {
        register_setting('poi-mapper', 'poi-mapper', array($this, 'update'));
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
        if (!empty($_POST['wp-poi-mapper-defaults'])) {
            $this->options = $this->defaults();
        } else {
            foreach ($this->defaults() as $key => $value) {
                if ((!isset ($options[$key]) || empty ($options[$key]))) {
                    $options[$key] = $value;
                }
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
    public function add_page()
    {
        if (current_user_can('manage_options')) {
            add_menu_page(__('POI Mapper', 'poi-mapper'), __('POI Mapper', 'poi-mapper'), 'manage_options', 'wp-poi-mapper', array($this, 'get_list'), 'dashicons-location');
            add_submenu_page('wp-poi-mapper', __('Import', 'poi-mapper'), __('Import', 'poi-mapper'), 'manage_options', 'wp-poi-mapper-import', array($this, 'import_page'));
            add_submenu_page('wp-poi-mapper', __('Settings', 'poi-mapper'), __('Settings', 'poi-mapper'), 'manage_options', 'wp-poi-mapper-settings', array($this, 'admin_page'));

            $this->options_page_hookname = add_options_page(__('POI Mapper', 'poi-mapper'), __('POI Mapper', 'poi-mapper'), 'manage_options', 'poi-mapper-settings', array($this, 'admin_page'));
        }
    }

    /**
     * Output the options page
     *
     * @return none
     * @since 2.0.3
     */
    public function admin_page()
    {
        if (!@include(dirname(__FILE__) . '/options-page.php')) {
            _e(sprintf('<div id="message" class="updated fade"><p>The options page for the <strong>POI Mapper</strong> cannot be displayed.  The file <strong>%s</strong> is missing.  Please reinstall the plugin.</p></div>', dirname(__FILE__) . '/options-page.php'));
        }
    }

    public function import_page()
    {
        $maxFileSize = $this->convertBytes(ini_get('upload_max_filesize'));
        if (!@include(dirname(__FILE__) . '/import-page.php')) {
            _e(sprintf('<div id="message" class="updated fade"><p>The import page for the <strong>POI Mapper</strong> cannot be displayed.  The file <strong>%s</strong> is missing.  Please reinstall the plugin.</p></div>', dirname(__FILE__) . '/import-page.php'));
        }
    }

    public function get_list()
    {
        _e('Lists will be placed here', 'poi-mapper');
    }

    public function import_csv()
    {
        if (isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {

            $UploadDirectory = wp_upload_dir();

            //check if this is an ajax request
            if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                wp_die();
            }

            header('Content-Type: application/json');
            //Is file size is less than allowed size.
            if ($_FILES["file"]["size"] > $this->convertBytes(ini_get('upload_max_filesize'))) {
                echo json_encode(
                    array(
                        'success' => false,
                        'message' => __('File size is too big!', 'poi-mapper'),
                    )
                );
                wp_die();
            }

            //allowed file type Server side check
            switch (strtolower($_FILES['file']['type'])) {
                //allowed file types
                case 'text/csv':
                case 'application/csv':
                    break;
                default:
                    echo json_encode(
                        array(
                            'success' => false,
                            'message' => __('Unsupported File!', 'poi-mapper'),
                        )
                    );
                    wp_die();
            }

            $File_Name = strtolower($_FILES['file']['name']);
            $File_Ext = substr($File_Name, strrpos($File_Name, '.')); //get file extention
            $Random_Number = rand(0, 9999999999); //Random number to be added to name.
            $NewFileName = $Random_Number . $File_Ext; //new file name

            if (move_uploaded_file($_FILES['file']['tmp_name'], $UploadDirectory . $NewFileName)) {
                // TODO: import CSV

                echo json_encode(
                    array(
                        'success' => true,
                        'message' => __('Success! File Uploaded', 'poi-mapper'),
                    )
                );
            } else {
                echo json_encode(
                    array(
                        'success' => false,
                        'message' => __('Error uploading File!', 'poi-mapper'),
                    )
                );
            }

        } else {
            echo json_encode(
                array(
                    'success' => false,
                    'message' => __('Something wrong with upload! Is "upload_max_filesize" set correctly?', 'poi-mapper'),
                )
            );
        }
        wp_die();
    }

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
}

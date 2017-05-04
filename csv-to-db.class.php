<?php

if (!class_exists('CSV2DB')) {

    class CSV2DB
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
         * Plugin slug to detect available updates
         * @var string
         */
        protected $plugin_slug;

        const TABLE_NAME = 'csv_to_db';

        protected $options = null;

        public function __construct()
        {
            $this->plugin_file = __DIR__ . '/csv-to-db.php';
            $this->plugin_basename = plugin_basename($this->plugin_file);
            $this->plugin_slug = basename(__DIR__);
            $this->init();
            // set text domain
            load_textdomain('csv-to-db', __DIR__ . '/lang/csv-to-db-' . get_locale() . '.mo');
        }

        /**
         * Initialize the default options during plugin activation
         *
         * @return none
         * @since 2.0.3
         */
        public function init()
        {
            if (!($this->options = get_option('csv-to-db'))) {
                $this->options = $this->defaults();
                add_option('csv-to-db', $this->options);
            }
        }

        /**
         * Return the default options
         *
         * @return array
         * @since 2.0.3
         */
        protected function defaults()
        {
            return array(
                'use-local'         => 1,
                'fields-terminated' => ',',
                'fields-enclosed'   => '"',
                'fields-escaped'    => '\\\\',
                'lines-starting'    => '',
                'lines-terminated'  => '\\n',
                'fields'            => array(),
            );
        }

        /**
         * Get specific option from the options table
         *
         * @param string $option Name of option to be used as array key for retrieving the specific value
         * @return mixed
         * @since 2.0.3
         */
        public function get_option($option, $options = null)
        {
            if (is_null($options)) {
                $options = $this->options;
            }
            if (isset ($options[$option])) {
                return $options[$option];
            } else {
                return false;
            }
        }
    }
}

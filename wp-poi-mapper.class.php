<?php

if (!class_exists('POIMapper')) {

    class POIMapper
    {

        protected $options = null;

        public function __construct()
        {
            $this->init();
            // set text domain
            load_textdomain('poi-mapper', __DIR__ . '/lang/poi-mapper-' . get_locale() . '.mo');
        }

        /**
         * Initialize the default options during plugin activation
         *
         * @return none
         * @since 2.0.3
         */
        public function init()
        {
            if (!($this->options = get_option('poi-mapper'))) {
                $this->options = $this->defaults();
                add_option('poi-mapper', $this->options);
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
                'gmap-key'          => '',
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

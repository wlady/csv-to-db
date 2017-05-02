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
                'use-local'         => 0,
                'skip-rows'         => 0,
                'fields-terminated' => ',',
                'fields-enclosed'   => '"',
                'fields-escaped'    => '\\',
                'lines-starting'    => '',
                'lines-terminated'  => '\\r\\n',
                'gmap-key'          => '',
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

//        public function getCDNs()
//        {
//            $cdns = $this->get_option('wp-poi-mapper-hosts');
//            if (is_array($cdns)) {
//                $empties = array();
//                array_walk($cdns, function (&$item, $key) use (&$empties) {
//                    if ($item) {
//                        $item = trim(strtolower($item));
//                        if (substr($item, 0, 4) !== 'http') {
//                            $item = 'http://' . $item;
//                        }
//                        if (substr($item, -1) !== '/') {
//                            $item .= '/';
//                        }
//                    } else {
//                        // mark empty item
//                        $empties[$key] = '';
//                    }
//                });
//                if (count($empties)) {
//                    // remove empty items
//                    $cdns = array_diff_key($cdns, $empties);
//                }
//                return count($cdns) ? $cdns : false;
//            }
//            return false;
//        }

    }
}

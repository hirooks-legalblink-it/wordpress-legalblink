<?php

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

if ( ! class_exists( 'WPLB_Shortcode_Manager' ) ) {
    final class WPLB_Shortcode_Manager
    {
        private static $instance;

        /**
         * Get singleton instance
         */
        public static function get_instance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor
         */
        private function __construct()
        {
            $this->init_shortcodes();
        }

        /**
         * Initialize all shortcodes
         */
        private function init_shortcodes()
        {
            // Initialize all shortcode classes
            $shortcode_classes = array(
                'WPLB_Cookie_Policy_Shortcode',
                'WPLB_Privacy_Policy_Shortcode',
                'WPLB_Terms_Of_Service_Shortcode',
            );

            foreach ($shortcode_classes as $class_name) {
                if (class_exists($class_name)) {
                    new $class_name();
                }
            }
        }
    }
}

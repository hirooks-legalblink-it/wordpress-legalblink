<?php

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

if ( ! class_exists( 'WPLB_Frontend_Manager' ) ) {
    final class WPLB_Frontend_Manager
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
         * Initialize frontend features
         */
        private function __construct()
        {
            add_action('wp_footer', [$this, 'render_cookie_banner']);
        }

        /**
         * Render cookie consent banner
         */
        public function render_cookie_banner()
        {
            if (!$this->should_show_banner()) {
                return;
            }

            $cache_duration = WPLB_Option_Helper::getOption('cache_duration', 30);
            $cache_duration_days = $cache_duration * 3600 * 24;

            $banner_snippet = WPLB_Transient_Helper::get('cookie_banner_snippet');
            if ($banner_snippet === false) {
                $banner_snippet = $this->fetch_banner_snippet();
                if (!empty($banner_snippet)) {
                    WPLB_Transient_Helper::set('cookie_banner_snippet', $banner_snippet, $cache_duration_days);
                }
            }

            $allow_list = [
                'script' => [
                    'type' => [],
                    'src' => [],
                    'id' => [],
                    'async' => [],
                    'defer' => [],
                ],
            ];

            if (!empty($banner_snippet)) {
                echo wp_kses($banner_snippet, $allow_list);
            }
        }

        public function fetch_banner_snippet()
        {
            $jwt_token = WPLB_Option_Helper::getOption('jwt_token');

            if (empty($jwt_token)) {
                return '';
            }

            $url = WPLB_Base_API_Controller::get_api_base_url() . '/cookie-solution/embed?language=it';
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $jwt_token,
                ),
                'timeout' => 30
            ));

            if (is_wp_error($response)) {
                WPLB_Logger::error('Error', WPLB_Logger::CATEGORY_GENERAL, 'fetch_banner_snippet');
                return '';
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $banner_data = json_decode($body, true);

            WPLB_Logger::debug('Banner fetch response: ' . wp_json_encode($banner_data), WPLB_Logger::CATEGORY_GENERAL, 'fetch_banner_snippet');

            if ($code !== 200 || !isset($banner_data['html'])) {
                WPLB_Logger::warning('Error', WPLB_Logger::CATEGORY_GENERAL, 'fetch_banner_snippet');
                return '';
            }

            WPLB_Logger::info('Banner snippet fetched successfully', WPLB_Logger::CATEGORY_GENERAL, 'fetch_banner_snippet');

            return $banner_data['html'];
        }

        /**
         * Check if cookie banner should be shown
         */
        private function should_show_banner()
        {
            return (bool)WPLB_Option_Helper::getOption('cookie_banner_enabled',false);
        }
    }
}

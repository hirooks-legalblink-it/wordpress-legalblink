<?php

if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('WPLB_External_API_Controller')) {
    /**
     * External API Controller
     * Handles communication with external services (languages, branding, upsell)
     */
    class WPLB_External_API_Controller extends WPLB_Base_API_Controller
    {
        /**
         * Register external API routes
         */
        public function register_routes()
        {
            // Get languages
            register_rest_route(self::get_api_namespace(), '/languages', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_languages'),
                'permission_callback' => array($this, 'check_admin_permissions_with_nonce')
            ));

            // Get branding
            register_rest_route(self::get_api_namespace(), '/branding', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_branding'),
                'permission_callback' => array($this, 'check_admin_permissions_with_nonce')
            ));
        }

        /**
         * Get available languages from API
         */
        public function get_languages()
        {
            try {
                // Check cache first
                $cached_languages = WPLB_Transient_Helper::get('languages');
                if ($cached_languages !== false) {
                    return $this->create_api_response(true, $cached_languages);
                }
                $jwt_token = WPLB_Option_Helper::getOption('jwt_token');

                if (empty($jwt_token)) {
                    return $this->create_error_response(
                        /* translators: Error message when authentication credentials are missing */
                        __('Credenziali mancanti.', 'legalblink-policy'),
                        /* translators: English error message for missing credentials */
                        __('Missing credentials', 'legalblink-policy')
                    );
                }

                $url = self::get_api_base_url() . '/languages';
                $response = wp_remote_get($url, array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $jwt_token,
                    ),
                    'timeout' => 30
                ));

                if (is_wp_error($response)) {
                    return $this->create_error_response(
                        /* translators: Error message prefix for languages request errors, followed by the actual error */
                        __('Errore nella richiesta lingue: ', 'legalblink-policy') . $response->get_error_message(),
                        /* translators: English error message for languages request failure */
                        __('Languages request failed', 'legalblink-policy')
                    );
                }

                $code = wp_remote_retrieve_response_code($response);
                if ($code !== 200) {
                    return $this->create_error_response(
                        /* translators: %d is the HTTP response code for languages API error */
                        sprintf(__('Errore API lingue: %d', 'legalblink-policy'), $code),
                        /* translators: English error message for languages API error */
                        __('Languages API error', 'legalblink-policy')
                    );
                }

                $body = wp_remote_retrieve_body($response);
                $languages_data = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->create_error_response(
                        /* translators: Error message when languages response parsing fails */
                        __('Errore nel parsing della risposta lingue', 'legalblink-policy'),
                        /* translators: English error message for languages parse error */
                        __('Languages parse error', 'legalblink-policy')
                    );
                }

                // Cache for 1 hour
                WPLB_Transient_Helper::set('languages', $languages_data, 3600);

                // Log the operation
                WPLB_Logger::info('Languages fetched and cached successfully', WPLB_Logger::CATEGORY_API, 'get_languages');

                return $this->create_api_response(true, $languages_data);

            } catch (Exception $e) {
                WPLB_Logger::error('Languages retrieval failed: ' . $e->getMessage(), WPLB_Logger::CATEGORY_API, 'get_languages');
                return $this->create_error_response(
                    /* translators: Error message for unexpected languages retrieval errors */
                    __('Errore imprevisto nel recupero lingue', 'legalblink-policy'),
                    /* translators: English error message for languages exception */
                    __('Languages exception', 'legalblink-policy')
                );
            }
        }

        /**
         * Get branding data from API
         */
        public function get_branding()
        {
            return $this->create_api_response(true, []);
        }
    }
}

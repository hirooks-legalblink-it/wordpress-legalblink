<?php

if (!defined('ABSPATH')) {
    die;
}

if (!class_exists('WPLB_Auth_API_Controller')) {
    /**
     * Authentication API Controller
     * Handles user authentication and authorization
     */
    class WPLB_Auth_API_Controller extends WPLB_Base_API_Controller
    {
        /**
         * Register authentication routes
         */
        public function register_routes()
        {
            // Verify user authentication
            register_rest_route(self::get_api_namespace(), '/auth/verify', array(
                'methods' => 'GET',
                'callback' => array($this, 'is_logged_in'),
                'permission_callback' => array($this, 'check_admin_permissions_with_nonce')
            ));

            // Login user
            register_rest_route(self::get_api_namespace(), '/auth/login', array(
                'methods' => 'POST',
                'callback' => array($this, 'login'),
                'permission_callback' => array($this, 'check_admin_permissions_with_nonce'),
                'args' => array(
                    'external_id' => array(
                        'required' => true,
                        'type' => 'string'
                    ),
                )
            ));

            // Logout user
            register_rest_route(self::get_api_namespace(), '/auth/logout', array(
                'methods' => 'POST',
                'callback' => array($this, 'logout'),
                'permission_callback' => array($this, 'check_admin_permissions_with_nonce')
            ));
        }

        /**
         * Verify user authentication
         */
        public function is_logged_in()
        {
            try {
                $external_id = WPLB_Option_Helper::getOption('external_id');
                $jwt_token = WPLB_Option_Helper::getOption('jwt_token');

                if (empty($external_id) || empty($jwt_token)) {
                    return $this->create_error_response(
                        /* translators: Error message when authentication credentials are missing */
                        __('Credenziali mancanti.', 'legalblink-policy'),
                        /* translators: English error message for missing credentials */
                        __('Missing credentials', 'legalblink-policy')
                    );
                }

                $url = self::get_api_base_url() . '/users/me';
                $response = wp_remote_get($url, array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $jwt_token,
                    ),
                    'timeout' => 30
                ));

                if (is_wp_error($response)) {
                    // Log request error
                    WPLB_Logger::error('Authentication request error', WPLB_Logger::CATEGORY_AUTH, 'verify_user_authentication');
                    return $this->create_error_response(
                        /* translators: %s is the error message from the authentication request */
                        __('Errore nella richiesta di autenticazione: ', 'legalblink-policy') . $response->get_error_message(),
                        /* translators: English error message for authentication request failure */
                        __('Authentication request failed', 'legalblink-policy')
                    );
                }

                $code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                $auth_data = json_decode($body, true);

                if ($code !== 200 || !isset($auth_data['id'])) {
                    // Log failed verification
                    WPLB_Logger::warning('User authentication verification failed', WPLB_Logger::CATEGORY_AUTH, 'verify_user_authentication');
                    return $this->create_error_response(
                        /* translators: %d is the HTTP response code */
                        sprintf(__('Autenticazione fallita: %d', 'legalblink-policy'), $code),
                        /* translators: English error message for authentication failure */
                        __('Authentication failed', 'legalblink-policy')
                    );
                }

                // Log successful verification
                WPLB_Logger::info('User authentication verified successfully', WPLB_Logger::CATEGORY_AUTH, 'verify_user_authentication');

                return $this->create_api_response(true, $auth_data);
            } catch (Exception $e) {
                // Log exception
                WPLB_Logger::error('Authentication verification exception', WPLB_Logger::CATEGORY_AUTH, 'verify_user_authentication');
                return $this->create_error_response(
                    /* translators: Error message for unexpected authentication verification error */
                    __('Errore imprevisto nella verifica autenticazione', 'legalblink-policy'),
                    /* translators: English error message for authentication exception */
                    __('Authentication exception', 'legalblink-policy')
                );
            }
        }

        /**
         * Authenticate user with external service
         */
        public function login($request)
        {
            $external_id = sanitize_text_field($request->get_param('external_id'));
            $domain = wp_parse_url( home_url(), PHP_URL_HOST );

            try {
                $url = self::get_api_base_url() . '/auth';
                $params = array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . self::get_api_bearer_token(),
                    ),
                    'body' => json_encode(array(
                        'accessToken' => $external_id,
                        'domain' => $domain
                    )),
                    'timeout' => 30
                );

                // WPLB_Logger::info('Authentication request params: ' . wp_json_encode($params), WPLB_Logger::CATEGORY_AUTH, 'authenticate_user');

                $response = wp_remote_post($url, $params);

                if (is_wp_error($response)) {
                    // Log request error
                    WPLB_Logger::error('Authentication request error', WPLB_Logger::CATEGORY_AUTH, 'authenticate_user');
                    return $this->create_error_response(
                        /* translators: Error message prefix for authentication request errors, followed by the actual error */
                        __('Errore nella richiesta di autenticazione: ', 'legalblink-policy') . $response->get_error_message(),
                        /* translators: English error message for authentication request failure */
                        __('Authentication request failed', 'legalblink-policy')
                    );
                }

                $code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                $auth_data = json_decode($body, true);

                // WPLB_Logger::debug('Authentication response: ' . wp_json_encode($auth_data), WPLB_Logger::CATEGORY_AUTH, 'authenticate_user');
                WPLB_Logger::debug('Response code: ' . $code, WPLB_Logger::CATEGORY_AUTH, 'authenticate_user');

                if (isset($auth_data['token'], $auth_data['user']) && $code === 201) {
                    // Store credentials
                    WPLB_Option_Helper::setOption('external_id', $external_id);
                    WPLB_Option_Helper::setOption('jwt_token', $auth_data['token']);
                    WPLB_Option_Helper::setOption('auth_data', $auth_data);

                    // Log successful authentication
                    WPLB_Logger::info('User authenticated successfully', WPLB_Logger::CATEGORY_AUTH, 'authenticate_user');

                    return $this->create_api_response(true, $auth_data, null,
                        /* translators: Success message when user authentication is completed */
                        __('Autenticazione completata con successo.', 'legalblink-policy'));
                }

                return $this->create_error_response(
                    /* translators: Fallback error message when authentication fails */
                    $auth_data['message'] ?? __('Autenticazione fallita.', 'legalblink-policy'),
                    /* translators: English error message for authentication failure */
                    __('Authentication failed', 'legalblink-policy')
                );
            } catch (Exception $e) {
                return $this->create_error_response(
                    /* translators: Error message for unexpected authentication errors */
                    __('Errore imprevisto durante l\'autenticazione', 'legalblink-policy'),
                    /* translators: English error message for authentication exception */
                    __('Authentication exception', 'legalblink-policy')
                );
            }
        }

        /**
         * Logout user
         */
        public function logout(): \WP_REST_Response
        {
            // Clear stored credentials
            WPLB_Option_Helper::deleteOption('external_id');
            WPLB_Option_Helper::deleteOption('jwt_token');
            WPLB_Option_Helper::deleteOption('auth_data');

            // Clear configs
            WPLB_Option_Helper::deleteOption('cache_duration');
            WPLB_Option_Helper::deleteOption('cookie_banner_html_code');
            WPLB_Option_Helper::deleteOption('cookie_banner_enabled');

            // Clear all transients
            WPLB_Transient_Helper::clearAll();

            // Log successful logout
            WPLB_Logger::info('User logged out', WPLB_Logger::CATEGORY_AUTH, 'logout_user');
            return $this->create_api_response(true, array(), null,
                /* translators: Success message when user logout is completed */
                __('Logout completato con successo.', 'legalblink-policy'));
        }
    }
}

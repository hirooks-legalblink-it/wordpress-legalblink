<?php
/**
 * LegalBlink - Configuration
 *
 * Copy this file to config.php and fill in your actual values.
 * The config.php file will not be tracked by git.
 */

if (!defined('ABSPATH')) {
    die;
}

return array(
    /**
     * API Configuration
     */
    'api' => array(
        /**
         * API namespace for REST endpoints
         * Default: 'wplb/v1'
         */
        'namespace' => 'wplb/v1',

        /**
         * Base URL for LegalBlink API calls
         */
        'base_url' => 'https://app.legalblink.it/api/integrations/wordpress',

        /**
         * LegalBlink API Bearer Token
         */
        'bearer_token' => 'your-api-token-here',

        /**
         * Rate limiting for API calls (calls per minute per user)
         * Default: 60
         */
        'rate_limit' => 60,

        /**
         * Cache time for API responses (in seconds)
         * Default: 3600 (1 hour)
         */
        'cache_time' => 3600,
    ),
);


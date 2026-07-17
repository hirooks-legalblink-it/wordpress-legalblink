<?php
/**
 * Plugin Name: LegalBlink Policy
 * Plugin URI: https://wordpress.org/plugins/legalblink-policy/
 * Description: Integrate LegalBlink services in your WordPress site. Generate GDPR-compliant legal documents including Privacy Policy, Cookie Policy, and Terms & Conditions with professional legal support.
 * Version: 2.0.7
 * Author: LegalBlink
 * Author URI: https://legalblink.it/
 * Text Domain: legalblink-policy
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Network: true
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

if (!defined('ABSPATH')) {
    die;
}

// Define plugin directory path
if (!defined('WPLB_PLUGIN_DIR')) {
    define('WPLB_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WPLB_PLUGIN_VERSION')) {
    define('WPLB_PLUGIN_VERSION', '2.0.7');
}

if (!defined('WPLB_SHORTCODE_COOKIE_POLICY')) {
    define('WPLB_SHORTCODE_COOKIE_POLICY', 'WPLB_COOKIE_POLICY');
}
if (!defined('WPLB_SHORTCODE_PRIVACY_POLICY')) {
    define('WPLB_SHORTCODE_PRIVACY_POLICY', 'WPLB_PRIVACY_POLICY');
}
if (!defined('WPLB_SHORTCODE_CGV_POLICY')) {
    define('WPLB_SHORTCODE_CGV_POLICY', 'WPLB_CGV_POLICY');
}
if (!defined('WPLB_SHORTCODE_TERMS_OF_SERVICE_LEGACY')) {
    define('WPLB_SHORTCODE_TERMS_OF_SERVICE_LEGACY', 'WPLB_TERMS_OF_SERVICE');
}

if (!function_exists('wplb_get_policy_shortcode_tag')) {
    /**
     * Get the canonical shortcode tag for a policy type.
     *
     * @param string $policy_type
     * @return string|false
     */
    function wplb_get_policy_shortcode_tag($policy_type)
    {
        $shortcode_tags = array(
            'cookie_policy' => WPLB_SHORTCODE_COOKIE_POLICY,
            'privacy_policy' => WPLB_SHORTCODE_PRIVACY_POLICY,
            'terms_of_service' => WPLB_SHORTCODE_CGV_POLICY,
        );

        return $shortcode_tags[$policy_type] ?? false;
    }
}

if (!function_exists('wplb_get_policy_shortcode_alias_tags')) {
    /**
     * Get legacy shortcode aliases for a policy type.
     *
     * @param string $policy_type
     * @return array
     */
    function wplb_get_policy_shortcode_alias_tags($policy_type)
    {
        $shortcode_aliases = array(
            'terms_of_service' => array(WPLB_SHORTCODE_TERMS_OF_SERVICE_LEGACY),
        );

        return $shortcode_aliases[$policy_type] ?? array();
    }
}

if (!function_exists('wplb_get_policy_shortcode')) {
    /**
     * Get the shortcode string for a policy type.
     *
     * @param string $policy_type
     * @return string|false
     */
    function wplb_get_policy_shortcode($policy_type)
    {
        $shortcode_tag = wplb_get_policy_shortcode_tag($policy_type);

        if (!$shortcode_tag) {
            return false;
        }

        return '[' . $shortcode_tag . ']';
    }
}

if (!function_exists('wplb_get_admin_shortcodes')) {
    /**
     * Get the canonical shortcodes exposed in the admin UI.
     *
     * @return array
     */
    function wplb_get_admin_shortcodes()
    {
        return array(
            'cookie_policy' => wplb_get_policy_shortcode('cookie_policy'),
            'privacy_policy' => wplb_get_policy_shortcode('privacy_policy'),
            'terms_of_service' => wplb_get_policy_shortcode('terms_of_service'),
        );
    }
}

if (!function_exists('wplb_is_elementor_page')) {
    /**
     * Check whether a WordPress page is managed by Elementor.
     *
     * @param int $page_id
     * @return bool
     */
    function wplb_is_elementor_page($page_id)
    {
        if (!$page_id) {
            return false;
        }

        $edit_mode = get_post_meta($page_id, '_elementor_edit_mode', true);
        $elementor_data = get_post_meta($page_id, '_elementor_data', true);

        return !empty($edit_mode) || !empty($elementor_data);
    }
}

require_once __DIR__ . '/vendor/autoload.php';

function wplb_init()
{
    try {
        // Initialize logger
        WPLB_Logger::info('Plugin initialization started', WPLB_Logger::CATEGORY_GENERAL, 'wplb_init');

        // Clear cache if plugin version changed (covers admin, FTP, WP-CLI, auto-updates)
        $stored_version = get_option('wplb_plugin_version', '');
        if ($stored_version !== WPLB_PLUGIN_VERSION) {
            WPLB_Transient_Helper::clearAll();
            update_option('wplb_plugin_version', WPLB_PLUGIN_VERSION, false);
            WPLB_Logger::info('Plugin updated to ' . WPLB_PLUGIN_VERSION . ', cache cleared', WPLB_Logger::CATEGORY_GENERAL, 'wplb_init');
        }

        // Inizializza i componenti principali
        WPLB_Main_API_Controller::get_instance();
        WPLB_Frontend_Manager::get_instance();
        WPLB_Shortcode_Manager::get_instance();

        WPLB_Logger::info('Plugin initialization completed successfully', WPLB_Logger::CATEGORY_GENERAL, 'wplb_init');
    } catch (Exception $e) {
        WPLB_Logger::critical('Plugin initialization failed: ' . $e->getMessage(), WPLB_Logger::CATEGORY_GENERAL, 'wplb_init');
    }
}

// Inizializza il plugin dopo che WordPress ha caricato tutti i plugin
add_action('plugins_loaded', 'wplb_init');

function wplb_add_type_attribute( array $attr )
{
    $scripts_type_module = ['wplb_admin-ui-main-script-js'];

    if (in_array($attr['id'], $scripts_type_module, true)) {
        $attr['type'] = 'module';
    }

    return $attr;
}

add_filter('wp_script_attributes', 'wplb_add_type_attribute', 10, 3);

function wplb_enqueue_admin_assets()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Pass the absolute REST URL so the admin SPA still works on sites with
    // "Plain" permalinks. On those installs `rest_url('wplb/v1')` returns
    // `https://example.com/?rest_route=/wplb/v1` and `PHP_URL_PATH` drops
    // the query string entirely, collapsing `root` to `/` — every admin
    // call then resolves against the site origin (e.g. `/cache/settings`)
    // and 404s. Same-origin so no CORS implication.
    $full_url = rest_url(WPLB_Base_API_Controller::get_api_namespace());

    $api_config = [
        'baseUrl' => '',
        'root' => esc_url_raw( $full_url ),
        'nonce' => wp_create_nonce('wp_rest'),
        'editPagesUrl' => admin_url('edit.php?post_type=page'),
        'shortcodes' => wplb_get_admin_shortcodes(),
    ];
    $admin_ui_url = plugin_dir_url(__FILE__) . 'assets/admin-ui/';
    $version = '1.0.0';

    wp_enqueue_style(
        'wplb_admin-ui-main-style',
        $admin_ui_url . 'style.css',
        [],
        $version
    );

    wp_enqueue_script(
        'wplb_admin-ui-main-script',
        $admin_ui_url . 'index.js',
        [],
        $version,
        [
            'in_footer' => false,
        ]
    );

    wp_add_inline_script(
        'wplb_admin-ui-main-script',
        'var wplb = ' . wp_json_encode($api_config) . ';',
        'before'
    );

    // Render the admin page
    wplb_render_admin_page();
}

function wplb_render_admin_page()
{
    ?>
    <div id="wplb_app"></div>
    <?php
}

// Menu admin
add_action('admin_menu', function () {
    $menu_title = 'LegalBlink';
    add_menu_page(
        $menu_title,
        $menu_title,
        'manage_options',
        'wplb_admin',
        'wplb_enqueue_admin_assets',
        'dashicons-shield-alt',
        85
    );
});

// Network admin menu (multisite)
add_action('network_admin_menu', function () {
    $menu_title = 'LegalBlink';
    add_menu_page(
        $menu_title,
        $menu_title,
        'manage_network_options',
        'wplb_network_admin',
        'wplb_enqueue_admin_assets',
        'dashicons-shield-alt',
        85
    );
});

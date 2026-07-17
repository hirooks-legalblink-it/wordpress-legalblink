<?php
/**
 * WP 7.0 Compatibility Test — REST API Check
 * Usage: wp eval-file /tmp/test-api.php
 */

if (!defined('ABSPATH')) {
    echo "Must be run via WP-CLI: wp eval-file /tmp/test-api.php\n";
    exit(1);
}

$errors = [];

function test_endpoint($method, $route, $expected_code = 200, $auth = true) {
    global $errors;
    $url = rest_url('wplb/v1' . $route);
    
    $args = ['method' => $method];
    if ($auth) {
        $args['headers'] = [
            'X-WP-Nonce' => wp_create_nonce('wp_rest'),
        ];
    }
    
    $response = wp_remote_request($url, $args);
    
    if (is_wp_error($response)) {
        $errors[] = "{$method} {$route}: WP_Error - " . $response->get_error_message();
        echo "  FAIL {$method} {$route}: " . $response->get_error_message() . "\n";
        return false;
    }
    
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== $expected_code) {
        $errors[] = "{$method} {$route}: expected {$expected_code}, got {$code}";
        echo "  FAIL {$method} {$route}: expected {$expected_code}, got {$code}\n";
        return false;
    }
    
    echo "  OK   {$method} {$route}: {$code}\n";
    return true;
}

echo "=== LegalBlink WP 7.0 REST API Test ===\n";
echo "WordPress " . get_bloginfo('version') . " | PHP " . phpversion() . "\n\n";

echo "--- Plugin Status ---\n";
$plugin = 'legalblink-policy/legalblink-policy.php';
$active = is_plugin_active($plugin);
echo "  Plugin active: " . ($active ? 'YES' : 'NO') . "\n";
if (!$active) {
    $errors[] = "Plugin not active";
}

echo "\n--- Authenticated Endpoints ---\n";
test_endpoint('GET', '/auth/verify');
test_endpoint('POST', '/auth/login');
test_endpoint('GET', '/banner');
test_endpoint('POST', '/banner');
test_endpoint('GET', '/documents');
test_endpoint('POST', '/documents/update-page');
test_endpoint('GET', '/pages');
test_endpoint('GET', '/cache/settings');
test_endpoint('POST', '/cache/settings');
test_endpoint('POST', '/cache/clear');
test_endpoint('GET', '/languages');
test_endpoint('GET', '/branding');

echo "\n--- Unauthenticated Endpoints (should 401) ---\n";
test_endpoint('GET', '/auth/verify', 401, false);
test_endpoint('POST', '/auth/login', 401, false);
test_endpoint('GET', '/banner', 401, false);
test_endpoint('GET', '/documents', 401, false);
test_endpoint('GET', '/pages', 401, false);

echo "\n=== Summary ===\n";
if (empty($errors)) {
    echo "ALL TESTS PASSED ✓\n";
} else {
    echo "FAILURES: " . count($errors) . "\n";
    foreach ($errors as $e) {
        echo "  - {$e}\n";
    }
}

echo "\n=== Debug Log ===\n";
$log_path = WP_CONTENT_DIR . '/debug.log';
if (file_exists($log_path)) {
    $log = file_get_contents($log_path);
    $lines = array_filter(explode("\n", $log));
    echo "  debug.log entries: " . count($lines) . "\n";
    foreach ($lines as $line) {
        echo "  | {$line}\n";
    }
} else {
    echo "  No debug.log found\n";
}

exit(empty($errors) ? 0 : 1);

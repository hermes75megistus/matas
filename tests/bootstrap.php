<?php
/**
 * PHPUnit bootstrap file for MATAS
 */

// Composer autoloader
if (file_exists(dirname(dirname(__FILE__)) . '/vendor/autoload.php')) {
    require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
}

// WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// WordPress core test functions
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    // Define constants
    define('MATAS_VERSION', '1.1.0');
    define('MATAS_DB_VERSION', '1.1.0');
    define('MATAS_PLUGIN_FILE', dirname(dirname(__FILE__)) . '/matas.php');
    define('MATAS_PLUGIN_DIR', dirname(dirname(__FILE__)) . '/');
    define('MATAS_PLUGIN_URL', 'http://example.org/wp-content/plugins/matas/');
    define('MATAS_PLUGIN_BASENAME', plugin_basename(MATAS_PLUGIN_FILE));
    
    // Mock WordPress functions for testing
    if (!function_exists('wp_cache_get')) {
        function wp_cache_get($key, $group = '') {
            return false;
        }
    }
    
    if (!function_exists('wp_cache_set')) {
        function wp_cache_set($key, $data, $group = '', $expire = 0) {
            return true;
        }
    }
    
    if (!function_exists('wp_cache_delete')) {
        function wp_cache_delete($key, $group = '') {
            return true;
        }
    }
    
    if (!function_exists('current_time')) {
        function current_time($type, $gmt = 0) {
            return date('Y-m-d H:i:s');
        }
    }
    
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($str) {
            return strip_tags(trim($str));
        }
    }
    
    if (!function_exists('sanitize_email')) {
        function sanitize_email($email) {
            return filter_var($email, FILTER_SANITIZE_EMAIL);
        }
    }
    
    if (!function_exists('wp_create_nonce')) {
        function wp_create_nonce($action = -1) {
            return 'test_nonce_' . md5($action);
        }
    }
    
    if (!function_exists('check_ajax_referer')) {
        function check_ajax_referer($action = -1, $query_arg = false, $die = true) {
            return true;
        }
    }
    
    if (!function_exists('current_user_can')) {
        function current_user_can($capability) {
            return true;
        }
    }
    
    if (!function_exists('get_current_user_id')) {
        function get_current_user_id() {
            return 1;
        }
    }
    
    if (!function_exists('wp_send_json_success')) {
        function wp_send_json_success($data = null) {
            wp_send_json(array('success' => true, 'data' => $data));
        }
    }
    
    if (!function_exists('wp_send_json_error')) {
        function wp_send_json_error($data = null) {
            wp_send_json(array('success' => false, 'data' => $data));
        }
    }
    
    if (!function_exists('wp_send_json')) {
        function wp_send_json($response) {
            echo json_encode($response);
            exit;
        }
    }
    
    if (!function_exists('__')) {
        function __($text, $domain = 'default') {
            return $text;
        }
    }
    
    // Load plugin classes
    require_once dirname(dirname(__FILE__)) . '/includes/class-matas-calculator.php';
    require_once dirname(dirname(__FILE__)) . '/includes/class-matas-loader.php';
    require_once dirname(dirname(__FILE__)) . '/includes/class-matas-shortcode.php';
    require_once dirname(dirname(__FILE__)) . '/includes/class-matas.php';
    require_once dirname(dirname(__FILE__)) . '/admin/admin.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Test helper classes
require_once dirname(__FILE__) . '/helpers/class-matas-test-helper.php';
require_once dirname(__FILE__) . '/helpers/class-mock-wpdb.php';

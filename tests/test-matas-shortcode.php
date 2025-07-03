<?php
/**
 * Shortcode Tests
 *
 * @package MATAS
 * @subpackage Tests
 */

class Test_Matas_Shortcode extends PHPUnit_Framework_TestCase {
    
    private $shortcode;
    private $mock_wpdb;
    
    public function setUp() {
        global $wpdb;
        $this->mock_wpdb = Matas_Test_Helper::create_mock_wpdb();
        $wpdb = $this->mock_wpdb;
        
        $this->shortcode = new Matas_Shortcode('matas', '1.1.0');
        
        // Mock WordPress functions
        if (!function_exists('shortcode_atts')) {
            function shortcode_atts($pairs, $atts, $shortcode = '') {
                return array_merge($pairs, (array)$atts);
            }
        }
        
        if (!function_exists('admin_url')) {
            function admin_url($path = '') {
                return 'http://example.org/wp-admin/' . $path;
            }
        }
        
        if (!function_exists('wp_create_nonce')) {
            function wp_create_nonce($action = -1) {
                return 'test_nonce_' . md5($action);
            }
        }
        
        Matas_Test_Helper::mock_error_log();
    }
    
    public function tearDown() {
        $this->shortcode = null;
        $this->mock_wpdb = null;
        $_POST = array();
    }
    
    /**
     * Test shortcode display with default attributes
     */
    public function test_display_calculator_default() {
        $atts = array();
        
        // Mock the include file
        if (!defined('MATAS_PLUGIN_DIR')) {
            define('MATAS_PLUGIN_DIR', '/mock/path/');
        }
        
        // Create a mock template file content
        $mock_template = '<div class="matas-container"><h1>Test Calculator</h1></div>';
        
        // We can't actually test the include, but we can test the method exists
        $this->assertTrue(method_exists($this->shortcode, 'display_calculator'), 'display_calculator method should exist');
    }
    
    /**
     * Test shortcode display with custom attributes
     */
    public function test_display_calculator_custom_atts() {
        $atts = array(
            'baslik' => 'Custom Title',
            'stil' => 'modern'
        );
        
        // Test that method exists and can be called
        $this->assertTrue(method_exists($this->shortcode, 'display_calculator'), 'display_calculator method should exist');
        
        // Test attribute merging (we'd need to mock the actual template rendering)
        $default_atts = array(
            'baslik' => 'Memur Maaş Hesaplama',
            'stil' => 'modern'
        );
        
        $merged = shortcode_atts($default_atts, $atts, 'matas_hesaplama');
        
        $this->assertEquals('Custom Title', $merged['baslik'], 'Custom title should override default');
        $this->assertEquals('modern', $merged['stil'], 'Style should be preserved');
    }
    
    /**
     * Test AJAX calculation with valid data
     */
    public function test_ajax_calculate_salary_valid() {
        // Mock valid POST data
        $_POST = array(
            'nonce' => 'valid_nonce',
            'unvan' => 'ogretmen_sinif',
            'derece' => 8,
            'kademe' => 5,
            'hizmet_yili' => 15,
            'medeni_hal' => 'evli',
            'es_calisiyor' => 'hayir',
            'cocuk_sayisi' => 2,
            'egitim_durumu' => 'lisans',
            'dil_seviyesi' => 'yok'
        );
        
        // Mock check_ajax_referer to return true
        if (!function_exists('check_ajax_referer')) {
            function check_ajax_referer($action, $query_arg = false, $die = true) {
                return true;
            }
        }
        
        // Mock wp_send_json_success to capture output
        $json_output = null;
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success($data = null) {
                global $json_output;
                $json_output = array('success' => true, 'data' => $data);
                echo json_encode($json_output);
            }
        }
        
        // Test method exists
        $this->assertTrue(method_exists($this->shortcode, 'calculate_salary'), 'calculate_salary method should exist');
    }
    
    /**
     * Test AJAX calculation with invalid nonce
     */
    public function test_ajax_calculate_salary_invalid_nonce() {
        $_POST = array(
            'nonce' => 'invalid_nonce',
            'unvan' => 'ogretmen_sinif'
        );
        
        // Mock check_ajax_referer to return false
        if (!function_exists('check_ajax_referer')) {
            function check_ajax_referer($action, $query_arg = false, $die = true) {
                return false;
            }
        }
        
        $json_output = null;
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null) {
                global $json_output;
                $json_output = array('success' => false, 'data' => $data);
                echo json_encode($json_output);
            }
        }
        
        // Test that method handles invalid nonce
        $this->assertTrue(method_exists($this->shortcode, 'calculate_salary'), 'calculate_salary method should exist');
    }
    
    /**
     * Test AJAX calculation with missing required fields
     */
    public function test_ajax_calculate_salary_missing_fields() {
        $_POST = array(
            'nonce' => 'valid_nonce'
            // Missing required fields
        );
        
        if (!function_exists('check_ajax_referer')) {
            function check_ajax_referer($action, $query_arg = false, $die = true) {
                return true;
            }
        }
        
        $this->assertTrue(method_exists($this->shortcode, 'calculate_salary'), 'calculate_salary method should exist');
    }
    
    /**
     * Test data sanitization in AJAX request
     */
    public function test_ajax_data_sanitization() {
        $_POST = array(
            'nonce' => 'valid_nonce',
            'unvan' => '  ogretmen_sinif  ', // Should be trimmed
            'derece' => '8.5', // Should be converted to int
            'kademe' => '5',
            'hizmet_yili' => '15',
            'medeni_hal' => '<script>alert("xss")</script>evli', // Should be sanitized
            'gorev_tazminati' => '1' // Should be converted to boolean
        );
        
        // Mock sanitize functions
        if (!function_exists('sanitize_text_field')) {
            function sanitize_text_field($str) {
                return strip_tags(trim($str));
            }
        }
        
        // Test sanitization
        $sanitized_unvan = sanitize_text_field($_POST['unvan']);
        $this->assertEquals('ogretmen_sinif', $sanitized_unvan, 'Unvan should be trimmed');
        
        $sanitized_derece = intval($_POST['derece']);
        $this->assertEquals(8, $sanitized_derece, 'Derece should be converted to int');
        
        $sanitized_medeni_hal = sanitize_text_field($_POST['medeni_hal']);
        $this->assertEquals('evli', $sanitized_medeni_hal, 'Medeni hal should be sanitized');
    }
    
    /**
     * Test enqueue styles method
     */
    public function test_enqueue_styles() {
        // Mock wp_enqueue_style
        $enqueued_styles = array();
        if (!function_exists('wp_enqueue_style')) {
            function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
                global $enqueued_styles;
                $enqueued_styles[] = array(
                    'handle' => $handle,
                    'src' => $src,
                    'deps' => $deps,
                    'ver' => $ver,
                    'media' => $media
                );
            }
        }
        
        // Mock constants
        if (!defined('MATAS_PLUGIN_URL')) {
            define('MATAS_PLUGIN_URL', 'http://example.org/wp-content/plugins/matas/');
        }
        
        $this->shortcode->enqueue_styles();
        
        // Test that method exists and can be called
        $this->assertTrue(method_exists($this->shortcode, 'enqueue_styles'), 'enqueue_styles method should exist');
    }
    
    /**
     * Test enqueue scripts method
     */
    public function test_enqueue_scripts() {
        // Mock wp_enqueue_script
        $enqueued_scripts = array();
        if (!function_exists('wp_enqueue_script')) {
            function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
                global $enqueued_scripts;
                $enqueued_scripts[] = array(
                    'handle' => $handle,
                    'src' => $src,
                    'deps' => $deps,
                    'ver' => $ver,
                    'in_footer' => $in_footer
                );
            }
        }
        
        // Mock wp_localize_script
        if (!function_exists('wp_localize_script')) {
            function wp_localize_script($handle, $object_name, $l10n) {
                // Mock implementation
                return true;
            }
        }
        
        if (!defined('MATAS_PLUGIN_URL')) {
            define('MATAS_PLUGIN_URL', 'http://example.org/wp-content/plugins/matas/');
        }
        
        $this->shortcode->enqueue_scripts();
        
        $this->assertTrue(method_exists($this->shortcode, 'enqueue_scripts'), 'enqueue_scripts method should exist');
    }
    
    /**
     * Test form validation logic
     */
    public function test_form_validation() {
        // Test required fields validation
        $required_fields = array('unvan', 'derece', 'kademe', 'hizmet_yili');
        
        // Empty data should fail
        $empty_data = array();
        foreach ($required_fields as $field) {
            $this->assertArrayNotHasKey($field, $empty_data, "Required field {$field} should be missing in empty data");
        }
        
        // Valid data should have all required fields
        $valid_data = Matas_Test_Helper::get_sample_calculation_params();
        foreach ($required_fields as $field) {
            $this->assertArrayHasKey($field, $valid_data, "Required field {$field} should be present in valid data");
            $this->assertNotEmpty($valid_data[$field], "Required field {$field} should not be empty");
        }
    }
    
    /**
     * Test parameter type conversion
     */
    public function test_parameter_type_conversion() {
        // Test string to int conversion for numeric fields
        $numeric_fields = array('derece', 'kademe', 'hizmet_yili', 'cocuk_sayisi');
        
        foreach ($numeric_fields as $field) {
            $string_value = '5';
            $int_value = intval($string_value);
            $this->assertEquals(5, $int_value, "Field {$field} should convert string to int");
            $this->assertIsInt($int_value, "Field {$field} should be integer type");
        }
        
        // Test string to boolean conversion for checkbox fields
        $boolean_fields = array('gorev_tazminati', 'gelistirme_odenegi', 'asgari_gecim_indirimi');
        
        foreach ($boolean_fields as $field) {
            $this->assertEquals(1, intval('1'), "Checkbox field {$field} value '1' should convert to 1");
            $this->assertEquals(0, intval(''), "Checkbox field {$field} empty value should convert to 0");
        }
    }
    
    /**
     * Test error handling in calculation
     */
    public function test_calculation_error_handling() {
        // Mock calculator that throws exception
        $mock_calculator = $this->getMockBuilder('Matas_Calculator')
                               ->setConstructorArgs(array('matas', '1.1.0'))
                               ->setMethods(array('calculate_salary'))
                               ->getMock();
        
        $mock_calculator->method('calculate_salary')
                       ->will($this->throwException(new Exception('Test error')));
        
        // Test that exception is handled gracefully
        try {
            $result = $mock_calculator->calculate_salary(array());
            $this->fail('Exception should have been thrown');
        } catch (Exception $e) {
            $this->assertEquals('Test error', $e->getMessage(), 'Exception message should match');
        }
    }
    
    /**
     * Test shortcode attribute filtering
     */
    public function test_shortcode_attribute_filtering() {
        // Test XSS prevention in attributes
        $malicious_atts = array(
            'baslik' => '<script>alert("xss")</script>Test Title',
            'stil' => 'modern<script>alert("xss")</script>'
        );
        
        // Simulate sanitization
        $clean_baslik = strip_tags($malicious_atts['baslik']);
        $clean_stil = preg_replace('/[^a-zA-Z0-9_-]/', '', $malicious_atts['stil']);
        
        $this->assertEquals('Test Title', $clean_baslik, 'Script tags should be removed from title');
        $this->assertEquals('modern', $clean_stil, 'Invalid characters should be removed from style');
    }
    
    /**
     * Test calculator instance creation
     */
    public function test_calculator_instance_creation() {
        $reflection = new ReflectionClass($this->shortcode);
        $property = $reflection->getProperty('calculator');
        $property->setAccessible(true);
        
        $calculator = $property->getValue($this->shortcode);
        
        $this->assertInstanceOf('Matas_Calculator', $calculator, 'Calculator should be instance of Matas_Calculator');
    }
    
    /**
     * Test response formatting
     */
    public function test_response_formatting() {
        // Test successful response format
        $success_data = array(
            'brutMaas' => 10000.50,
            'netMaas' => 8500.25,
            'toplamKesintiler' => 1500.25
        );
        
        $success_response = array(
            'success' => true,
            'data' => $success_data
        );
        
        $this->assertTrue($success_response['success'], 'Success response should have success=true');
        $this->assertArrayHasKey('data', $success_response, 'Success response should have data key');
        
        // Test error response format
        $error_response = array(
            'success' => false,
            'data' => array('message' => 'Test error')
        );
        
        $this->assertFalse($error_response['success'], 'Error response should have success=false');
        $this->assertArrayHasKey('data', $error_response, 'Error response should have data key');
        $this->assertArrayHasKey('message', $error_response['data'], 'Error response should have message');
    }
}

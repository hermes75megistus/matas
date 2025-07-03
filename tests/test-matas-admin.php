<?php
/**
 * Admin Tests
 *
 * @package MATAS
 * @subpackage Tests
 */

class Test_Matas_Admin extends PHPUnit_Framework_TestCase {
    
    private $admin;
    private $mock_wpdb;
    
    public function setUp() {
        global $wpdb;
        $this->mock_wpdb = Matas_Test_Helper::create_mock_wpdb();
        $wpdb = $this->mock_wpdb;
        
        // Mock $_POST global
        $_POST = array();
        
        // Mock current user capabilities
        if (!function_exists('current_user_can')) {
            function current_user_can($capability) {
                return true; // Always return true for tests
            }
        }
        
        $this->admin = new Matas_Admin('matas', '1.1.0');
        
        Matas_Test_Helper::mock_error_log();
    }
    
    public function tearDown() {
        $this->admin = null;
        $this->mock_wpdb = null;
        $_POST = array();
    }
    
    /**
     * Test katsayılar validation with valid data
     */
    public function test_validate_katsayilar_data_valid() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_katsayilar_data');
        $method->setAccessible(true);
        
        $valid_data = array(
            'donem' => '2025 Ocak-Haziran',
            'aylik_katsayi' => 0.354507,
            'taban_katsayi' => 7.715,
            'yan_odeme_katsayi' => 0.0354507
        );
        
        $result = $method->invoke($this->admin, $valid_data);
        
        $this->assertTrue($result['valid'], 'Valid data should pass validation');
    }
    
    /**
     * Test katsayılar validation with empty donem
     */
    public function test_validate_katsayilar_data_empty_donem() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_katsayilar_data');
        $method->setAccessible(true);
        
        $invalid_data = array(
            'donem' => '',
            'aylik_katsayi' => 0.354507,
            'taban_katsayi' => 7.715,
            'yan_odeme_katsayi' => 0.0354507
        );
        
        $result = $method->invoke($this->admin, $invalid_data);
        
        $this->assertFalse($result['valid'], 'Empty donem should fail validation');
        $this->assertContains('Dönem adı', $result['message']);
    }
    
    /**
     * Test katsayılar validation with zero values
     */
    public function test_validate_katsayilar_data_zero_values() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_katsayilar_data');
        $method->setAccessible(true);
        
        $invalid_data = array(
            'donem' => '2025 Test',
            'aylik_katsayi' => 0,
            'taban_katsayi' => 7.715,
            'yan_odeme_katsayi' => 0.0354507
        );
        
        $result = $method->invoke($this->admin, $invalid_data);
        
        $this->assertFalse($result['valid'], 'Zero values should fail validation');
        $this->assertContains('0\'dan büyük', $result['message']);
    }
    
    /**
     * Test katsayılar validation with excessive values
     */
    public function test_validate_katsayilar_data_excessive_values() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_katsayilar_data');
        $method->setAccessible(true);
        
        $invalid_data = array(
            'donem' => '2025 Test',
            'aylik_katsayi' => 15, // Too high
            'taban_katsayi' => 7.715,
            'yan_odeme_katsayi' => 0.0354507
        );
        
        $result = $method->invoke($this->admin, $invalid_data);
        
        $this->assertFalse($result['valid'], 'Excessive values should fail validation');
        $this->assertContains('makul aralıkta', $result['message']);
    }
    
    /**
     * Test unvan validation with valid data
     */
    public function test_validate_unvan_data_valid() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_unvan_data');
        $method->setAccessible(true);
        
        $valid_data = array(
            'id' => 0,
            'unvan_kodu' => 'test_unvan',
            'unvan_adi' => 'Test Ünvan',
            'ekgosterge' => 2200,
            'ozel_hizmet' => 80,
            'yan_odeme' => 800,
            'is_guclugu' => 300,
            'makam_tazminat' => 0,
            'egitim_tazminat' => 0.20
        );
        
        $result = $method->invoke($this->admin, $valid_data);
        
        $this->assertTrue($result['valid'], 'Valid unvan data should pass validation');
    }
    
    /**
     * Test unvan validation with empty required fields
     */
    public function test_validate_unvan_data_empty_required() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_unvan_data');
        $method->setAccessible(true);
        
        $invalid_data = array(
            'id' => 0,
            'unvan_kodu' => '',
            'unvan_adi' => 'Test Ünvan',
            'ekgosterge' => 2200,
            'ozel_hizmet' => 80,
            'yan_odeme' => 800,
            'is_guclugu' => 300,
            'makam_tazminat' => 0,
            'egitim_tazminat' => 0.20
        );
        
        $result = $method->invoke($this->admin, $invalid_data);
        
        $this->assertFalse($result['valid'], 'Empty required fields should fail validation');
        $this->assertContains('zorunludur', $result['message']);
    }
    
    /**
     * Test unvan validation with invalid characters in code
     */
    public function test_validate_unvan_data_invalid_code() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_unvan_data');
        $method->setAccessible(true);
        
        $invalid_data = array(
            'id' => 0,
            'unvan_kodu' => 'test ünvan!@#', // Invalid characters
            'unvan_adi' => 'Test Ünvan',
            'ekgosterge' => 2200,
            'ozel_hizmet' => 80,
            'yan_odeme' => 800,
            'is_guclugu' => 300,
            'makam_tazminat' => 0,
            'egitim_tazminat' => 0.20
        );
        
        $result = $method->invoke($this->admin, $invalid_data);
        
        $this->assertFalse($result['valid'], 'Invalid characters should fail validation');
        $this->assertContains('sadece harf', $result['message']);
    }
    
    /**
     * Test gösterge validation with valid data
     */
    public function test_validate_gosterge_data_valid() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_gosterge_data');
        $method->setAccessible(true);
        
        $valid_data = array(
            'id' => 0,
            'derece' => 8,
            'kademe' => 5,
            'gosterge_puani' => 720
        );
        
        $result = $method->invoke($this->admin, $valid_data);
        
        $this->assertTrue($result['valid'], 'Valid gösterge data should pass validation');
    }
    
    /**
     * Test gösterge validation with invalid derece
     */
    public function test_validate_gosterge_data_invalid_derece() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_gosterge_data');
        $method->setAccessible(true);
        
        $invalid_data = array(
            'id' => 0,
            'derece' => 20, // Invalid derece
            'kademe' => 5,
            'gosterge_puani' => 720
        );
        
        $result = $method->invoke($this->admin, $invalid_data);
        
        $this->assertFalse($result['valid'], 'Invalid derece should fail validation');
        $this->assertContains('1-15', $result['message']);
    }
    
    /**
     * Test gösterge validation with invalid kademe
     */
    public function test_validate_gosterge_data_invalid_kademe() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_gosterge_data');
        $method->setAccessible(true);
        
        $invalid_data = array(
            'id' => 0,
            'derece' => 8,
            'kademe' => 15, // Invalid kademe
            'gosterge_puani' => 720
        );
        
        $result = $method->invoke($this->admin, $invalid_data);
        
        $this->assertFalse($result['valid'], 'Invalid kademe should fail validation');
        $this->assertContains('1-9', $result['message']);
    }
    
    /**
     * Test gösterge validation with invalid puanı
     */
    public function test_validate_gosterge_data_invalid_puan() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('validate_gosterge_data');
        $method->setAccessible(true);
        
        $invalid_data = array(
            'id' => 0,
            'derece' => 8,
            'kademe' => 5,
            'gosterge_puani' => 0 // Invalid puan
        );
        
        $result = $method->invoke($this->admin, $invalid_data);
        
        $this->assertFalse($result['valid'], 'Invalid gösterge puanı should fail validation');
        $this->assertContains('1-5000', $result['message']);
    }
    
    /**
     * Test input sanitization
     */
    public function test_sanitize_input() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('sanitize_input');
        $method->setAccessible(true);
        
        // Test text sanitization
        $result = $method->invoke($this->admin, '  Test Text  ', 'text');
        $this->assertEquals('Test Text', $result, 'Text should be trimmed');
        
        // Test integer sanitization
        $result = $method->invoke($this->admin, '123.45', 'int');
        $this->assertEquals(123, $result, 'Should convert to integer');
        
        // Test float sanitization
        $result = $method->invoke($this->admin, '123.45', 'float');
        $this->assertEquals(123.45, $result, 'Should convert to float');
        
        // Test email sanitization
        $result = $method->invoke($this->admin, ' test@example.com ', 'email');
        $this->assertEquals('test@example.com', $result, 'Email should be sanitized');
        
        // Test array sanitization
        $input_array = array('  item1  ', '  item2  ');
        $result = $method->invoke($this->admin, $input_array, 'array');
        $this->assertEquals(array('item1', 'item2'), $result, 'Array items should be sanitized');
    }
    
    /**
     * Test rate limiting check
     */
    public function test_check_rate_limit() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('check_rate_limit');
        $method->setAccessible(true);
        
        // First call should pass
        try {
            $method->invoke($this->admin, 'test');
            $this->assertTrue(true, 'First rate limit check should pass');
        } catch (Exception $e) {
            $this->fail('First rate limit check should not throw exception');
        }
    }
    
    /**
     * Test security verification
     */
    public function test_verify_security() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('verify_security');
        $method->setAccessible(true);
        
        // Mock successful nonce verification
        $_POST['nonce'] = 'valid_nonce';
        
        // Mock wp_verify_nonce function
        if (!function_exists('wp_verify_nonce')) {
            function wp_verify_nonce($nonce, $action) {
                return true;
            }
        }
        
        $result = $method->invoke($this->admin);
        $this->assertTrue($result, 'Security verification should pass with valid nonce');
    }
    
    /**
     * Test cache clearing
     */
    public function test_clear_related_cache() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('clear_related_cache');
        $method->setAccessible(true);
        
        // Should not throw exception
        try {
            $method->invoke($this->admin, 'katsayilar');
            $method->invoke($this->admin, 'unvanlar');
            $method->invoke($this->admin, 'gostergeler');
            $this->assertTrue(true, 'Cache clearing should not throw exception');
        } catch (Exception $e) {
            $this->fail('Cache clearing should not throw exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Test admin action logging
     */
    public function test_log_admin_action() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('log_admin_action');
        $method->setAccessible(true);
        
        // Should not throw exception
        try {
            $method->invoke($this->admin, 'test_action', array('test' => 'data'));
            $this->assertTrue(true, 'Admin action logging should not throw exception');
        } catch (Exception $e) {
            $this->fail('Admin action logging should not throw exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Test get user IP
     */
    public function test_get_user_ip() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('get_user_ip');
        $method->setAccessible(true);
        
        // Mock server variables
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        
        $result = $method->invoke($this->admin);
        
        $this->assertNotEmpty($result, 'Should return an IP address');
        $this->assertTrue(filter_var($result, FILTER_VALIDATE_IP) !== false, 'Should return valid IP');
    }
    
    /**
     * Test unvan usage check
     */
    public function test_check_unvan_usage() {
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('check_unvan_usage');
        $method->setAccessible(true);
        
        // Test with existing unvan
        $result = $method->invoke($this->admin, 1);
        
        $this->assertArrayHasKey('can_delete', $result, 'Should return can_delete key');
        $this->assertArrayHasKey('message', $result, 'Should return message key');
        $this->assertIsBool($result['can_delete'], 'can_delete should be boolean');
    }
}

<?php
/**
 * Calculator Tests
 *
 * @package MATAS
 * @subpackage Tests
 */

class Test_Matas_Calculator extends PHPUnit_Framework_TestCase {
    
    private $calculator;
    private $mock_wpdb;
    
    public function setUp() {
        // Mock global wpdb
        global $wpdb;
        $this->mock_wpdb = Matas_Test_Helper::create_mock_wpdb();
        $wpdb = $this->mock_wpdb;
        
        // Create calculator instance
        $this->calculator = new Matas_Calculator('matas', '1.1.0');
        
        // Mock helper functions
        Matas_Test_Helper::mock_error_log();
    }
    
    public function tearDown() {
        // Clean up
        $this->calculator = null;
        $this->mock_wpdb = null;
    }
    
    /**
     * Test successful salary calculation
     */
    public function test_calculate_salary_success() {
        $params = Matas_Test_Helper::get_sample_calculation_params();
        
        $result = $this->calculator->calculate_salary($params);
        
        $this->assertTrue($result['success'], 'Calculation should be successful');
        Matas_Test_Helper::assertValidCalculationResult($result);
        
        // Check that we have reasonable values
        $this->assertGreaterThan(0, $result['brutMaas'], 'Brüt maaş 0\'dan büyük olmalı');
        $this->assertGreaterThan(0, $result['netMaas'], 'Net maaş 0\'dan büyük olmalı');
        $this->assertLessThan($result['brutMaas'], $result['netMaas'], 'Net maaş brüt maaştan küçük olmalı');
    }
    
    /**
     * Test calculation with invalid parameters
     */
    public function test_calculate_salary_invalid_params() {
        $params = Matas_Test_Helper::get_invalid_calculation_params();
        
        $result = $this->calculator->calculate_salary($params);
        
        $this->assertFalse($result['success'], 'Invalid parameters should return false');
        $this->assertArrayHasKey('message', $result, 'Error message should be present');
    }
    
    /**
     * Test calculation with missing required fields
     */
    public function test_calculate_salary_missing_fields() {
        $params = array(); // Empty parameters
        
        $result = $this->calculator->calculate_salary($params);
        
        $this->assertFalse($result['success']);
        $this->assertContains('gereklidir', $result['message']); // Turkish error message
    }
    
    /**
     * Test calculation with invalid derece
     */
    public function test_calculate_salary_invalid_derece() {
        $params = Matas_Test_Helper::get_sample_calculation_params();
        $params['derece'] = 20; // Invalid derece (should be 1-15)
        
        $result = $this->calculator->calculate_salary($params);
        
        $this->assertFalse($result['success']);
        $this->assertContains('1-15', $result['message']);
    }
    
    /**
     * Test calculation with invalid kademe
     */
    public function test_calculate_salary_invalid_kademe() {
        $params = Matas_Test_Helper::get_sample_calculation_params();
        $params['kademe'] = 15; // Invalid kademe (should be 1-9)
        
        $result = $this->calculator->calculate_salary($params);
        
        $this->assertFalse($result['success']);
        $this->assertContains('1-9', $result['message']);
    }
    
    /**
     * Test calculation with excessive service years
     */
    public function test_calculate_salary_excessive_service_years() {
        $params = Matas_Test_Helper::get_sample_calculation_params();
        $params['hizmet_yili'] = 60; // Invalid service years (should be <= 50)
        
        $result = $this->calculator->calculate_salary($params);
        
        $this->assertFalse($result['success']);
        $this->assertContains('50', $result['message']);
    }
    
    /**
     * Test taban aylığı calculation
     */
    public function test_taban_ayligi_calculation() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_taban_ayligi');
        $method->setAccessible(true);
        
        $gosterge_puani = 720;
        $aylik_katsayi = 0.354507;
        
        $result = $method->invoke($this->calculator, $gosterge_puani, $aylik_katsayi);
        
        $expected = $gosterge_puani * $aylik_katsayi;
        $this->assertEquals($expected, $result, 'Taban aylığı hesaplaması yanlış', 0.01);
    }
    
    /**
     * Test ek gösterge calculation
     */
    public function test_ek_gosterge_calculation() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_ek_gosterge_tutari');
        $method->setAccessible(true);
        
        $ek_gosterge = 2200;
        $aylik_katsayi = 0.354507;
        
        $result = $method->invoke($this->calculator, $ek_gosterge, $aylik_katsayi);
        
        $expected = $ek_gosterge * $aylik_katsayi;
        $this->assertEquals($expected, $result, 'Ek gösterge hesaplaması yanlış', 0.01);
    }
    
    /**
     * Test kıdem aylığı calculation with normal service years
     */
    public function test_kidem_ayligi_normal() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_kidem_ayligi');
        $method->setAccessible(true);
        
        $hizmet_yili = 15;
        $aylik_katsayi = 0.354507;
        
        $result = $method->invoke($this->calculator, $hizmet_yili, $aylik_katsayi);
        
        $expected = $hizmet_yili * 25 * $aylik_katsayi;
        $this->assertEquals($expected, $result, 'Kıdem aylığı hesaplaması yanlış', 0.01);
    }
    
    /**
     * Test kıdem aylığı calculation with excessive service years (should cap at 25)
     */
    public function test_kidem_ayligi_capped() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_kidem_ayligi');
        $method->setAccessible(true);
        
        $hizmet_yili = 30; // Should be capped at 25
        $aylik_katsayi = 0.354507;
        
        $result = $method->invoke($this->calculator, $hizmet_yili, $aylik_katsayi);
        
        $expected = 25 * 25 * $aylik_katsayi; // Should use 25, not 30
        $this->assertEquals($expected, $result, 'Kıdem aylığı 25 yılda sınırlanmalı', 0.01);
    }
    
    /**
     * Test yan ödeme calculation
     */
    public function test_yan_odeme_calculation() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_yan_odeme');
        $method->setAccessible(true);
        
        $yan_odeme_puani = 800;
        $yan_odeme_katsayi = 0.0354507;
        
        $result = $method->invoke($this->calculator, $yan_odeme_puani, $yan_odeme_katsayi);
        
        $expected = $yan_odeme_puani * $yan_odeme_katsayi;
        $this->assertEquals($expected, $result, 'Yan ödeme hesaplaması yanlış', 0.01);
    }
    
    /**
     * Test dil tazminatı calculation
     */
    public function test_dil_tazminati_calculation() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_dil_tazminati');
        $method->setAccessible(true);
        
        $aylik_katsayi = 0.354507;
        
        // Test A seviyesi kullanıyor
        $result = $method->invoke($this->calculator, 'a', 'evet', $aylik_katsayi);
        $expected = 1500 * $aylik_katsayi; // A seviyesi gösterge puanı
        $this->assertEquals($expected, $result, 'A seviyesi dil tazminatı yanlış', 0.01);
        
        // Test B seviyesi kullanmıyor
        $result = $method->invoke($this->calculator, 'b', 'hayir', $aylik_katsayi);
        $expected = 300 * $aylik_katsayi; // B seviyesi kullanmıyor gösterge puanı
        $this->assertEquals($expected, $result, 'B seviyesi kullanmıyor dil tazminatı yanlış', 0.01);
        
        // Test dil yok
        $result = $method->invoke($this->calculator, 'yok', 'evet', $aylik_katsayi);
        $this->assertEquals(0, $result, 'Dil yok ise 0 olmalı');
    }
    
    /**
     * Test aile yardımı calculation
     */
    public function test_aile_yardimi_calculation() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_aile_yardimi');
        $method->setAccessible(true);
        
        // Test evli eş çalışmıyor
        $result = $method->invoke($this->calculator, 'evli', 'hayir');
        $this->assertGreaterThan(0, $result, 'Evli eş çalışmıyor ise aile yardımı olmalı');
        
        // Test evli eş çalışıyor
        $result = $method->invoke($this->calculator, 'evli', 'evet');
        $this->assertEquals(0, $result, 'Evli eş çalışıyor ise aile yardımı olmamalı');
        
        // Test bekar
        $result = $method->invoke($this->calculator, 'bekar', 'hayir');
        $this->assertEquals(0, $result, 'Bekar ise aile yardımı olmamalı');
    }
    
    /**
     * Test çocuk yardımı calculation
     */
    public function test_cocuk_yardimi_calculation() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_cocuk_yardimi');
        $method->setAccessible(true);
        
        // Test çocuk yok
        $result = $method->invoke($this->calculator, 0, 0, 0, 0);
        $this->assertEquals(0, $result, 'Çocuk yok ise yardım olmamalı');
        
        // Test normal çocuk
        $result = $method->invoke($this->calculator, 2, 0, 0, 0);
        $this->assertGreaterThan(0, $result, '2 normal çocuk için yardım olmalı');
        
        // Test 0-6 yaş çocuk (daha yüksek yardım)
        $normal_result = $method->invoke($this->calculator, 1, 0, 0, 0);
        $baby_result = $method->invoke($this->calculator, 1, 1, 0, 0);
        $this->assertGreaterThan($normal_result, $baby_result, '0-6 yaş çocuk yardımı daha yüksek olmalı');
    }
    
    /**
     * Test emekli keseneği calculation
     */
    public function test_emekli_kesenegi_calculation() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_emekli_kesenegi');
        $method->setAccessible(true);
        
        $taban_ayligi = 1000;
        $ek_gosterge = 500;
        $kidem_ayligi = 200;
        
        $result = $method->invoke($this->calculator, $taban_ayligi, $ek_gosterge, $kidem_ayligi);
        
        $expected = ($taban_ayligi + $ek_gosterge + $kidem_ayligi) * 0.16;
        $this->assertEquals($expected, $result, 'Emekli keseneği %16 olmalı', 0.01);
    }
    
    /**
     * Test GSS calculation
     */
    public function test_gss_calculation() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_gss_primi');
        $method->setAccessible(true);
        
        $taban_ayligi = 1000;
        $ek_gosterge = 500;
        $kidem_ayligi = 200;
        
        $result = $method->invoke($this->calculator, $taban_ayligi, $ek_gosterge, $kidem_ayligi);
        
        $expected = ($taban_ayligi + $ek_gosterge + $kidem_ayligi) * 0.05;
        $this->assertEquals($expected, $result, 'GSS primi %5 olmalı', 0.01);
    }
    
    /**
     * Test damga vergisi calculation
     */
    public function test_damga_vergisi_calculation() {
        $reflection = new ReflectionClass($this->calculator);
        $method = $reflection->getMethod('calculate_damga_vergisi');
        $method->setAccessible(true);
        
        $brut_maas = 10000;
        
        $result = $method->invoke($this->calculator, $brut_maas);
        
        $expected = $brut_maas * 0.00759;
        $this->assertEquals($expected, $result, 'Damga vergisi oranı yanlış', 0.01);
    }
    
    /**
     * Test calculation with edge cases
     */
    public function test_edge_cases() {
        // Test minimum values
        $params = array(
            'unvan' => 'memur_genel',
            'derece' => 15,
            'kademe' => 1,
            'hizmet_yili' => 0,
            'medeni_hal' => 'bekar',
            'es_calisiyor' => 'evet',
            'cocuk_sayisi' => 0,
            'egitim_durumu' => 'lisans',
            'dil_seviyesi' => 'yok'
        );
        
        $result = $this->calculator->calculate_salary($params);
        
        $this->assertTrue($result['success'], 'Minimum değerlerle hesaplama başarılı olmalı');
        $this->assertGreaterThan(0, $result['brutMaas'], 'Minimum brüt maaş 0\'dan büyük olmalı');
    }
    
    /**
     * Test cache functionality
     */
    public function test_cache_clear() {
        // Test cache clear method exists and callable
        $this->assertTrue(method_exists($this->calculator, 'clear_cache'), 'clear_cache metodu mevcut olmalı');
        
        // Should not throw exception
        $this->calculator->clear_cache();
        $this->assertTrue(true, 'Cache temizleme başarılı');
    }
}

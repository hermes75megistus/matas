<?php
/**
 * Test Helper Class for MATAS
 *
 * @package MATAS
 * @subpackage Tests
 */

class Matas_Test_Helper {
    
    /**
     * Sample calculation parameters for testing
     */
    public static function get_sample_calculation_params() {
        return array(
            'unvan' => 'ogretmen_sinif',
            'derece' => 8,
            'kademe' => 5,
            'hizmet_yili' => 15,
            'medeni_hal' => 'evli',
            'es_calisiyor' => 'hayir',
            'cocuk_sayisi' => 2,
            'cocuk_06' => 1,
            'engelli_cocuk' => 0,
            'ogrenim_cocuk' => 0,
            'egitim_durumu' => 'lisans',
            'dil_seviyesi' => 'b',
            'dil_kullanimi' => 'evet',
            'gorev_tazminati' => 1,
            'gelistirme_odenegi' => 0,
            'asgari_gecim_indirimi' => 1,
            'kira_yardimi' => 0,
            'sendika_uyesi' => 1
        );
    }
    
    /**
     * Sample invalid calculation parameters
     */
    public static function get_invalid_calculation_params() {
        return array(
            'unvan' => '',
            'derece' => 0,
            'kademe' => 0,
            'hizmet_yili' => -1,
            'medeni_hal' => '',
            'es_calisiyor' => ''
        );
    }
    
    /**
     * Sample katsayılar data
     */
    public static function get_sample_katsayilar() {
        return array(
            'donem' => '2025 Ocak-Haziran',
            'aylik_katsayi' => 0.354507,
            'taban_katsayi' => 7.715,
            'yan_odeme_katsayi' => 0.0354507,
            'aktif' => 1
        );
    }
    
    /**
     * Sample unvan data
     */
    public static function get_sample_unvan() {
        return array(
            'unvan_kodu' => 'ogretmen_sinif',
            'unvan_adi' => 'Sınıf Öğretmeni',
            'ekgosterge' => 2200,
            'ozel_hizmet' => 80,
            'yan_odeme' => 800,
            'is_guclugu' => 300,
            'makam_tazminat' => 0,
            'egitim_tazminat' => 0.20
        );
    }
    
    /**
     * Sample gosterge data
     */
    public static function get_sample_gosterge() {
        return array(
            'derece' => 8,
            'kademe' => 5,
            'gosterge_puani' => 720
        );
    }
    
    /**
     * Sample sosyal yardım data
     */
    public static function get_sample_sosyal_yardimlar() {
        return array(
            array('tip' => 'aile_yardimi', 'tutar' => 1200),
            array('tip' => 'cocuk_normal', 'tutar' => 150),
            array('tip' => 'cocuk_0_6', 'tutar' => 300),
            array('tip' => 'cocuk_engelli', 'tutar' => 600),
            array('tip' => 'kira_yardimi', 'tutar' => 2000),
            array('tip' => 'sendika_yardimi', 'tutar' => 500)
        );
    }
    
    /**
     * Sample vergi dilimleri
     */
    public static function get_sample_vergi_dilimleri() {
        return array(
            array('dilim' => 1, 'alt_limit' => 0, 'ust_limit' => 70000, 'oran' => 15),
            array('dilim' => 2, 'alt_limit' => 70000, 'ust_limit' => 150000, 'oran' => 20),
            array('dilim' => 3, 'alt_limit' => 150000, 'ust_limit' => 550000, 'oran' => 27),
            array('dilim' => 4, 'alt_limit' => 550000, 'ust_limit' => 1900000, 'oran' => 35),
            array('dilim' => 5, 'alt_limit' => 1900000, 'ust_limit' => 0, 'oran' => 40)
        );
    }
    
    /**
     * Create a mock wpdb object for testing
     */
    public static function create_mock_wpdb() {
        return new Mock_Wpdb();
    }
    
    /**
     * Assert array contains expected keys
     */
    public static function assertArrayHasKeys($expected_keys, $array, $message = '') {
        foreach ($expected_keys as $key) {
            if (!array_key_exists($key, $array)) {
                throw new PHPUnit_Framework_AssertionFailedError(
                    $message ?: "Array does not contain expected key: {$key}"
                );
            }
        }
    }
    
    /**
     * Assert calculation result is valid
     */
    public static function assertValidCalculationResult($result) {
        $required_keys = array(
            'success', 'brutMaas', 'netMaas', 'toplamKesintiler',
            'tabanAyligi', 'ekGostergeTutari', 'kidemAyligi'
        );
        
        self::assertArrayHasKeys($required_keys, $result);
        
        // Check numeric values
        $numeric_keys = array('brutMaas', 'netMaas', 'toplamKesintiler');
        foreach ($numeric_keys as $key) {
            if (!is_numeric($result[$key])) {
                throw new PHPUnit_Framework_AssertionFailedError(
                    "Expected {$key} to be numeric, got: " . gettype($result[$key])
                );
            }
        }
        
        // Net maaş brüt maaştan küçük olmalı
        if ($result['netMaas'] > $result['brutMaas']) {
            throw new PHPUnit_Framework_AssertionFailedError(
                'Net maaş brüt maaştan büyük olamaz'
            );
        }
    }
    
    /**
     * Generate test data for bulk operations
     */
    public static function generate_test_data($count = 10) {
        $data = array();
        for ($i = 0; $i < $count; $i++) {
            $data[] = array(
                'id' => $i + 1,
                'name' => 'Test Item ' . ($i + 1),
                'value' => rand(100, 1000),
                'created_at' => date('Y-m-d H:i:s')
            );
        }
        return $data;
    }
    
    /**
     * Mock error log function
     */
    public static function mock_error_log() {
        if (!function_exists('error_log')) {
            function error_log($message, $message_type = 0, $destination = null, $extra_headers = null) {
                // Silent mock for testing
                return true;
            }
        }
    }
}

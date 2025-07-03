<?php
/**
 * Maaş hesaplama sınıfı
 * 
 * @package MATAS
 * @since 1.0.0
 */

class Matas_Calculator {
    /**
     * Eklenti ismi
     *
     * @var string
     */
    private $plugin_name;
    
    /**
     * Eklenti versiyonu
     *
     * @var string
     */
    private $version;
    
    /**
     * Cache süresi (saniye)
     *
     * @var int
     */
    private $cache_duration = 3600; // 1 saat
    
    /**
     * Hata logları
     *
     * @var array
     */
    private $errors = array();

    /**
     * Sınıfı başlat
     *
     * @param string $plugin_name Eklenti ismi
     * @param string $version Eklenti versiyonu
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Veritabanından hesaplama için gerekli verileri yükler (cache'li)
     */
    private function load_data() {
        try {
            // Cache anahtarları
            $cache_keys = array(
                'katsayilar' => 'matas_katsayilar_active',
                'unvanlar' => 'matas_unvanlar_all',
                'gostergeler' => 'matas_gostergeler_all',
                'dil_gostergeleri' => 'matas_dil_gostergeleri_all',
                'vergiler' => 'matas_vergiler_' . date('Y'),
                'sosyal_yardimlar' => 'matas_sosyal_yardimlar_' . date('Y')
            );

            // Cache'den veri yükleme
            foreach ($cache_keys as $property => $cache_key) {
                $cached_data = wp_cache_get($cache_key, 'matas');
                
                if (false === $cached_data) {
                    $cached_data = $this->{"fetch_" . $property}();
                    wp_cache_set($cache_key, $cached_data, 'matas', $this->cache_duration);
                }
                
                $this->$property = $cached_data;
            }

            // Fallback kontrolleri
            $this->validate_loaded_data();
            
        } catch (Exception $e) {
            $this->log_error('Data loading failed: ' . $e->getMessage());
            throw new Exception(__('Veriler yüklenirken bir hata oluştu.', 'matas'));
        }
    }

    /**
     * Katsayıları veritabanından çek
     */
    private function fetch_katsayilar() {
        global $wpdb;
        
        $katsayilar = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}matas_katsayilar WHERE aktif = 1 ORDER BY id DESC LIMIT 1",
            ARRAY_A
        );
        
        if (!$katsayilar) {
            return array(
                'donem' => date('Y') . ' Ocak-Haziran',
                'aylik_katsayi' => 0.354507,
                'taban_katsayi' => 7.715,
                'yan_odeme_katsayi' => 0.0354507
            );
        }
        
        return $katsayilar;
    }

    /**
     * Ünvanları veritabanından çek
     */
    private function fetch_unvanlar() {
        global $wpdb;
        
        $unvanlar = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}matas_unvan_bilgileri ORDER BY unvan_adi ASC",
            ARRAY_A
        );
        
        return $unvanlar ?: array();
    }

    /**
     * Gösterge puanlarını veritabanından çek
     */
    private function fetch_gostergeler() {
        global $wpdb;
        
        $gostergeler = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}matas_gosterge_puanlari ORDER BY derece ASC, kademe ASC",
            ARRAY_A
        );
        
        return $gostergeler ?: array();
    }

    /**
     * Dil göstergelerini veritabanından çek
     */
    private function fetch_dil_gostergeleri() {
        global $wpdb;
        
        $dil_gostergeleri = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}matas_dil_gostergeleri",
            ARRAY_A
        );
        
        return $dil_gostergeleri ?: array();
    }

    /**
     * Vergi dilimlerini veritabanından çek
     */
    private function fetch_vergiler() {
        global $wpdb;
        $current_year = date('Y');
        
        $vergiler = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}matas_vergiler WHERE yil = %d ORDER BY dilim ASC",
                $current_year
            ),
            ARRAY_A
        );
        
        if (empty($vergiler)) {
            return array(
                array('yil' => $current_year, 'dilim' => 1, 'alt_limit' => 0, 'ust_limit' => 70000, 'oran' => 15),
                array('yil' => $current_year, 'dilim' => 2, 'alt_limit' => 70000, 'ust_limit' => 150000, 'oran' => 20),
                array('yil' => $current_year, 'dilim' => 3, 'alt_limit' => 150000, 'ust_limit' => 550000, 'oran' => 27),
                array('yil' => $current_year, 'dilim' => 4, 'alt_limit' => 550000, 'ust_limit' => 1900000, 'oran' => 35),
                array('yil' => $current_year, 'dilim' => 5, 'alt_limit' => 1900000, 'ust_limit' => 0, 'oran' => 40),
            );
        }
        
        return $vergiler;
    }

    /**
     * Sosyal yardımları veritabanından çek
     */
    private function fetch_sosyal_yardimlar() {
        global $wpdb;
        $current_year = date('Y');
        
        $sosyal_yardimlar = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}matas_sosyal_yardimlar WHERE yil = %d",
                $current_year
            ),
            ARRAY_A
        );
        
        if (empty($sosyal_yardimlar)) {
            return array(
                array('yil' => $current_year, 'tip' => 'aile_yardimi', 'adi' => 'Aile Yardımı', 'tutar' => 1200),
                array('yil' => $current_year, 'tip' => 'cocuk_normal', 'adi' => 'Çocuk Yardımı', 'tutar' => 150),
                array('yil' => $current_year, 'tip' => 'cocuk_0_6', 'adi' => '0-6 Yaş Çocuk Yardımı', 'tutar' => 300),
                array('yil' => $current_year, 'tip' => 'cocuk_engelli', 'adi' => 'Engelli Çocuk Yardımı', 'tutar' => 600),
                array('yil' => $current_year, 'tip' => 'cocuk_ogrenim', 'adi' => 'Öğrenim Çocuk Yardımı', 'tutar' => 250),
                array('yil' => $current_year, 'tip' => 'kira_yardimi', 'adi' => 'Kira Yardımı', 'tutar' => 2000),
                array('yil' => $current_year, 'tip' => 'sendika_yardimi', 'adi' => 'Sendika Yardımı', 'tutar' => 500),
            );
        }
        
        return $sosyal_yardimlar;
    }

    /**
     * Yüklenen verileri doğrula
     */
    private function validate_loaded_data() {
        $required_fields = array('katsayilar', 'unvanlar', 'gostergeler', 'vergiler', 'sosyal_yardimlar');
        
        foreach ($required_fields as $field) {
            if (!isset($this->$field) || empty($this->$field)) {
                throw new Exception("Required data missing: {$field}");
            }
        }
    }

    /**
     * Rate limiting kontrolü
     */
    private function check_rate_limit() {
        $user_ip = $this->get_user_ip();
        $cache_key = 'matas_rate_limit_' . md5($user_ip);
        $attempts = wp_cache_get($cache_key, 'matas');
        
        if (!$attempts) {
            $attempts = 0;
        }
        
        $attempts++;
        
        if ($attempts > 20) { // 20 istek limiti
            $this->log_error('Rate limit exceeded for IP: ' . $user_ip);
            throw new Exception(__('Çok fazla istek gönderildi. Lütfen bekleyiniz.', 'matas'));
        }
        
        wp_cache_set($cache_key, $attempts, 'matas', 300); // 5 dakika
    }

    /**
     * Kullanıcı IP adresini güvenli şekilde al
     */
    private function get_user_ip() {
        $ip_fields = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_fields as $field) {
            if (!empty($_SERVER[$field])) {
                $ip = trim(explode(',', $_SERVER[$field])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Maaş hesaplama işlemini gerçekleştirir
     *
     * @param array $params Hesaplama parametreleri
     * @return array Hesaplama sonuçları
     */
    public function calculate_salary($params) {
        try {
            // Rate limiting kontrolü
            $this->check_rate_limit();
            
            // Parametre doğrulama
            $validation_result = $this->validate_params($params);
            if (!$validation_result['valid']) {
                throw new Exception($validation_result['message']);
            }

            // Verileri yükle
            $this->load_data();

            // Ünvan bilgilerini bul
            $unvan = $this->find_unvan($params['unvan']);
            if (!$unvan) {
                throw new Exception(__('Ünvan bulunamadı! Lütfen geçerli bir ünvan seçiniz.', 'matas'));
            }

            // Gösterge puanını bul
            $gosterge_puani = $this->find_gosterge_puani($params['derece'], $params['kademe']);
            if (!$gosterge_puani) {
                throw new Exception(__('Gösterge puanı bulunamadı! Lütfen geçerli bir derece ve kademe seçiniz.', 'matas'));
            }

            // Hesaplamaları yap
            $calculation_result = $this->perform_calculations($params, $unvan, $gosterge_puani);
            
            // Sonucu logla (debug için)
            $this->log_calculation($params, $calculation_result);
            
            return $calculation_result;
            
        } catch (Exception $e) {
            $this->log_error('Calculation failed: ' . $e->getMessage(), $params);
            
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'CALCULATION_ERROR'
            );
        }
    }

    /**
     * Parametreleri doğrula
     */
    private function validate_params($params) {
        $required_fields = array('unvan', 'derece', 'kademe', 'hizmet_yili');
        
        foreach ($required_fields as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                return array(
                    'valid' => false,
                    'message' => sprintf(__('%s alanı gereklidir.', 'matas'), $field)
                );
            }
        }

        // Sayısal değer kontrolü
        $numeric_fields = array('derece', 'kademe', 'hizmet_yili');
        foreach ($numeric_fields as $field) {
            if (!is_numeric($params[$field]) || $params[$field] < 0) {
                return array(
                    'valid' => false,
                    'message' => sprintf(__('%s geçerli bir sayı olmalıdır.', 'matas'), $field)
                );
            }
        }

        // Aralık kontrolü
        if ($params['derece'] < 1 || $params['derece'] > 15) {
            return array('valid' => false, 'message' => __('Derece 1-15 arasında olmalıdır.', 'matas'));
        }
        
        if ($params['kademe'] < 1 || $params['kademe'] > 9) {
            return array('valid' => false, 'message' => __('Kademe 1-9 arasında olmalıdır.', 'matas'));
        }
        
        if ($params['hizmet_yili'] > 50) {
            return array('valid' => false, 'message' => __('Hizmet yılı 50\'den fazla olamaz.', 'matas'));
        }

        return array('valid' => true);
    }

    /**
     * Ünvan bul
     */
    private function find_unvan($unvan_kodu) {
        foreach ($this->unvanlar as $unvan) {
            if ($unvan['unvan_kodu'] === $unvan_kodu) {
                return $unvan;
            }
        }
        return null;
    }

    /**
     * Gösterge puanını bul
     */
    private function find_gosterge_puani($derece, $kademe) {
        foreach ($this->gostergeler as $gosterge) {
            if ((int)$gosterge['derece'] === (int)$derece && (int)$gosterge['kademe'] === (int)$kademe) {
                return (int)$gosterge['gosterge_puani'];
            }
        }

        // Fallback - sabit değerler tablosu
        $fallback_gostergeler = $this->get_fallback_gostergeler();
        $key = $derece . '-' . $kademe;
        
        return isset($fallback_gostergeler[$key]) ? $fallback_gostergeler[$key] : null;
    }

    /**
     * Fallback gösterge puanları
     */
    private function get_fallback_gostergeler() {
        return array(
            '1-1' => 1320, '1-2' => 1380, '1-3' => 1440, '1-4' => 1500, '1-5' => 1560, 
            '1-6' => 1620, '1-7' => 1680, '1-8' => 1740,
            '2-1' => 1155, '2-2' => 1210, '2-3' => 1265, '2-4' => 1320, '2-5' => 1380, 
            '2-6' => 1440, '2-7' => 1500, '2-8' => 1560,
            '3-1' => 1020, '3-2' => 1065, '3-3' => 1110, '3-4' => 1155, '3-5' => 1210, 
            '3-6' => 1265, '3-7' => 1320, '3-8' => 1380, '3-9' => 1440,
            '4-1' => 915, '4-2' => 950, '4-3' => 985, '4-4' => 1020, '4-5' => 1065, 
            '4-6' => 1110, '4-7' => 1155, '4-8' => 1210, '4-9' => 1265,
            '5-1' => 835, '5-2' => 870, '5-3' => 905, '5-4' => 915, '5-5' => 950, 
            '5-6' => 985, '5-7' => 1020, '5-8' => 1065, '5-9' => 1110
        );
    }

    /**
     * Tüm hesaplamaları gerçekleştir
     */
    private function perform_calculations($params, $unvan, $gosterge_puani) {
        // Katsayıları al
        $aylik_katsayi = (float)$this->katsayilar['aylik_katsayi'];
        $taban_katsayi = (float)$this->katsayilar['taban_katsayi'];
        $yan_odeme_katsayi = (float)$this->katsayilar['yan_odeme_katsayi'];
        
        // Parametreleri hazırla
        $hizmet_yili = intval($params['hizmet_yili']);
        $medeni_hal = $params['medeni_hal'] ?? 'bekar';
        $es_calisiyor = $params['es_calisiyor'] ?? 'evet';
        $cocuk_sayisi = intval($params['cocuk_sayisi'] ?? 0);
        $cocuk_06 = intval($params['cocuk_06'] ?? 0);
        $engelli_cocuk = intval($params['engelli_cocuk'] ?? 0);
        $ogrenim_cocuk = intval($params['ogrenim_cocuk'] ?? 0);
        $egitim_durumu = $params['egitim_durumu'] ?? 'lisans';
        $dil_seviyesi = $params['dil_seviyesi'] ?? 'yok';
        $dil_kullanimi = $params['dil_kullanimi'] ?? 'hayir';
        
        // Boolean değerler
        $gorev_tazminati = !empty($params['gorev_tazminati']);
        $gelistirme_odenegi = !empty($params['gelistirme_odenegi']);
        $asgari_gecim_indirimi = !empty($params['asgari_gecim_indirimi']);
        $kira_yardimi = !empty($params['kira_yardimi']);
        $sendika_uyesi = !empty($params['sendika_uyesi']);

        // Temel maaş bileşenleri
        $taban_ayligi = $this->calculate_taban_ayligi($gosterge_puani, $aylik_katsayi);
        $ek_gosterge_tutari = $this->calculate_ek_gosterge_tutari($unvan['ekgosterge'], $aylik_katsayi);
        $kidem_ayligi = $this->calculate_kidem_ayligi($hizmet_yili, $aylik_katsayi);
        $yan_odeme = $this->calculate_yan_odeme($unvan['yan_odeme'], $yan_odeme_katsayi);
        $ozel_hizmet_tazminati = $this->calculate_ozel_hizmet_tazminati($gosterge_puani, $unvan['ekgosterge'], $aylik_katsayi, $unvan['ozel_hizmet']);
        $is_gucluguzammi = $this->calculate_is_gucluguzammi($unvan['is_guclugu'], $yan_odeme_katsayi);

        // Ek ödemeler
        $dil_tazminati = $this->calculate_dil_tazminati($dil_seviyesi, $dil_kullanimi, $aylik_katsayi);
        $ek_odeme = $this->calculate_ek_odeme($taban_ayligi, $ek_gosterge_tutari);
        $egitim_tazminati = $this->calculate_egitim_tazminati($gorev_tazminati, $taban_ayligi, $ek_gosterge_tutari, $unvan['egitim_tazminat']);
        $gelistirme_odenegi_tutari = $this->calculate_gelistirme_odenegi($gelistirme_odenegi, $taban_ayligi, $ek_gosterge_tutari);
        $makam_tazminati = $this->calculate_makam_tazminati($unvan['makam_tazminat'], $aylik_katsayi);
        $lisansustu_tazminat = $this->calculate_lisansustu_tazminat($egitim_durumu, $taban_ayligi);

        // Sosyal yardımlar
        $aile_yardimi = $this->calculate_aile_yardimi($medeni_hal, $es_calisiyor);
        $cocuk_yardimi = $this->calculate_cocuk_yardimi($cocuk_sayisi, $cocuk_06, $engelli_cocuk, $ogrenim_cocuk);
        $kira_yardimi_tutari = $this->calculate_kira_yardimi($kira_yardimi);
        $sendika_yardimi = $this->calculate_sendika_yardimi($sendika_uyesi);

        // Brüt maaş
        $brut_maas = $taban_ayligi + $ek_gosterge_tutari + $kidem_ayligi + $yan_odeme + 
                     $ozel_hizmet_tazminati + $is_gucluguzammi + $dil_tazminati + 
                     $ek_odeme + $egitim_tazminati + $gelistirme_odenegi_tutari + 
                     $makam_tazminati + $lisansustu_tazminat + $aile_yardimi + 
                     $cocuk_yardimi + $kira_yardimi_tutari + $sendika_yardimi;

        // Kesintiler
        $emekli_kesenegi = $this->calculate_emekli_kesenegi($taban_ayligi, $ek_gosterge_tutari, $kidem_ayligi);
        $gss_primi = $this->calculate_gss_primi($taban_ayligi, $ek_gosterge_tutari, $kidem_ayligi);
        
        $gelir_vergisi_matrahi = $brut_maas - $emekli_kesenegi - $gss_primi;
        $agi_tutari = $this->calculate_agi($asgari_gecim_indirimi, $medeni_hal, $es_calisiyor, $cocuk_sayisi);
        $gelir_vergisi = $this->calculate_gelir_vergisi($gelir_vergisi_matrahi, $agi_tutari);
        $damga_vergisi = $this->calculate_damga_vergisi($brut_maas);
        $sendika_kesintisi = $this->calculate_sendika_kesintisi($sendika_uyesi, $taban_ayligi);
        
        $toplam_kesintiler = $emekli_kesenegi + $gss_primi + $gelir_vergisi + $damga_vergisi + $sendika_kesintisi;
        $net_maas = $brut_maas - $toplam_kesintiler;

        return array(
            'success' => true,
            'donem' => $this->katsayilar['donem'],
            'unvanAdi' => $unvan['unvan_adi'],
            'gostergePuani' => $gosterge_puani,
            'aylikKatsayi' => $aylik_katsayi,
            'tabanKatsayi' => $taban_katsayi,
            'yanOdemeKatsayi' => $yan_odeme_katsayi,
            'tabanAyligi' => round($taban_ayligi, 2),
            'ekGostergeTutari' => round($ek_gosterge_tutari, 2),
            'kidemAyligi' => round($kidem_ayligi, 2),
            'yanOdeme' => round($yan_odeme, 2),
            'ozelHizmetTazminati' => round($ozel_hizmet_tazminati, 2),
            'isGucluguzammi' => round($is_gucluguzammi, 2),
            'dilTazminati' => round($dil_tazminati, 2),
            'ekOdeme' => round($ek_odeme, 2),
            'egitimTazminati' => round($egitim_tazminati, 2),
            'gelistirmeOdenegiTutari' => round($gelistirme_odenegi_tutari, 2),
            'makamTazminati' => round($makam_tazminati, 2),
            'lisansustuTazminat' => round($lisansustu_tazminat, 2),
            'aileYardimi' => round($aile_yardimi, 2),
            'cocukYardimi' => round($cocuk_yardimi, 2),
            'kiraYardimiTutari' => round($kira_yardimi_tutari, 2),
            'sendikaYardimi' => round($sendika_yardimi, 2),
            'emekliKesenegi' => round($emekli_kesenegi, 2),
            'gssPrimi' => round($gss_primi, 2),
            'gelirVergisi' => round($gelir_vergisi, 2),
            'damgaVergisi' => round($damga_vergisi, 2),
            'sendikaKesintisi' => round($sendika_kesintisi, 2),
            'kefalet' => 0,
            'brutMaas' => round($brut_maas, 2),
            'toplamKesintiler' => round($toplam_kesintiler, 2),
            'netMaas' => round($net_maas, 2)
        );
    }

    /**
     * Hesaplama logla
     */
    private function log_calculation($params, $result) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MATAS Calculation - User: ' . $this->get_user_ip() . ' - Net: ' . $result['netMaas']);
        }
    }

    /**
     * Hata logla
     */
    private function log_error($message, $context = array()) {
        $this->errors[] = array(
            'message' => $message,
            'context' => $context,
            'timestamp' => current_time('mysql'),
            'ip' => $this->get_user_ip()
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MATAS Error: ' . $message . ' - Context: ' . json_encode($context));
        }
    }

    /**
     * Cache temizle
     */
    public function clear_cache() {
        $cache_keys = array(
            'matas_katsayilar_active',
            'matas_unvanlar_all',
            'matas_gostergeler_all',
            'matas_dil_gostergeleri_all',
            'matas_vergiler_' . date('Y'),
            'matas_sosyal_yardimlar_' . date('Y')
        );
        
        foreach ($cache_keys as $cache_key) {
            wp_cache_delete($cache_key, 'matas');
        }
    }

    // ... Mevcut hesaplama metodları devam ediyor (calculate_taban_ayligi, vb.)
    // Bunlar aynı kalacak, sadece error handling eklenecek

    /**
     * Taban aylığı hesaplar
     */
    private function calculate_taban_ayligi($gosterge_puani, $aylik_katsayi) {
        if ($gosterge_puani <= 0 || $aylik_katsayi <= 0) {
            throw new Exception('Invalid parameters for taban ayligi calculation');
        }
        return $gosterge_puani * $aylik_katsayi;
    }

    /**
     * Ek gösterge tutarını hesaplar
     */
    private function calculate_ek_gosterge_tutari($ek_gosterge, $aylik_katsayi) {
        return max(0, $ek_gosterge * $aylik_katsayi);
    }

    /**
     * Kıdem aylığı hesaplar
     */
    private function calculate_kidem_ayligi($hizmet_yili, $aylik_katsayi) {
        return min($hizmet_yili, 25) * 25 * $aylik_katsayi;
    }

    /**
     * Yan ödeme hesaplar
     */
    private function calculate_yan_odeme($yan_odeme_puani, $yan_odeme_katsayi) {
        return max(0, $yan_odeme_puani * $yan_odeme_katsayi);
    }

    /**
     * Özel hizmet tazminatı hesaplar
     */
    private function calculate_ozel_hizmet_tazminati($gosterge_puani, $ek_gosterge, $aylik_katsayi, $ozel_hizmet_yuzdesi) {
        return ($gosterge_puani + $ek_gosterge) * $aylik_katsayi * $ozel_hizmet_yuzdesi / 100;
    }

    /**
     * İş güçlüğü zammı hesaplar
     */
    private function calculate_is_gucluguzammi($is_guclugu, $yan_odeme_katsayi) {
        return max(0, $is_guclugu * $yan_odeme_katsayi);
    }

    /**
     * Dil tazminatı hesaplar
     */
    private function calculate_dil_tazminati($dil_seviyesi, $dil_kullanimi, $aylik_katsayi) {
        if ($dil_seviyesi === 'yok') return 0;
        
        // Dil göstergelerinden bul
        foreach ($this->dil_gostergeleri as $dg) {
            if ($dg['seviye_kodu'] === $dil_seviyesi && $dg['kullanim'] === $dil_kullanimi) {
                return $dg['gosterge'] * $aylik_katsayi;
            }
        }
        
        // Fallback değerler
        $dil_gosterge_map = array(
            'a_evet' => 1500, 'b_evet' => 600, 'c_evet' => 300,
            'a_hayir' => 750, 'b_hayir' => 300, 'c_hayir' => 150
        );
        
        $key = $dil_seviyesi . '_' . $dil_kullanimi;
        $dil_gosterge = isset($dil_gosterge_map[$key]) ? $dil_gosterge_map[$key] : 0;
        
        return $dil_gosterge * $aylik_katsayi;
    }

    /**
     * Ek ödeme (666 KHK) hesaplar
     */
    private function calculate_ek_odeme($taban_ayligi, $ek_gosterge_tutari) {
        return ($taban_ayligi + $ek_gosterge_tutari) * 0.20;
    }

    /**
     * Eğitim-Öğretim tazminatı hesaplar
     */
    private function calculate_egitim_tazminati($gorev_tazminati, $taban_ayligi, $ek_gosterge_tutari, $egitim_tazminat_orani) {
        if (!$gorev_tazminati) return 0;
        return ($taban_ayligi + $ek_gosterge_tutari) * $egitim_tazminat_orani;
    }

    /**
     * Geliştirme ödeneği hesaplar
     */
    private function calculate_gelistirme_odenegi($gelistirme_odenegi, $taban_ayligi, $ek_gosterge_tutari) {
        if (!$gelistirme_odenegi) return 0;
        return ($taban_ayligi + $ek_gosterge_tutari) * 0.10;
    }

    /**
     * Makam tazminatı hesaplar
     */
    private function calculate_makam_tazminati($makam_tazminat_puani, $aylik_katsayi) {
        if ($makam_tazminat_puani <= 0) return 0;
        return $makam_tazminat_puani * $aylik_katsayi;
    }

    /**
     * Lisansüstü tazminatı hesaplar
     */
    private function calculate_lisansustu_tazminat($egitim_durumu, $taban_ayligi) {
        $rates = array('yuksek_lisans' => 0.05, 'doktora' => 0.15);
        return isset($rates[$egitim_durumu]) ? $taban_ayligi * $rates[$egitim_durumu] : 0;
    }

    /**
     * Aile yardımı hesaplar
     */
    private function calculate_aile_yardimi($medeni_hal, $es_calisiyor) {
        if ($medeni_hal === 'evli' && $es_calisiyor === 'hayir') {
            return $this->get_sosyal_yardim_tutari('aile_yardimi', 1200);
        }
        return 0;
    }

    /**
     * Çocuk yardımı hesaplar
     */
    private function calculate_cocuk_yardimi($cocuk_sayisi, $cocuk_06, $engelli_cocuk, $ogrenim_cocuk) {
        if ($cocuk_sayisi <= 0) return 0;
        
        $normal_cocuk = max(0, $cocuk_sayisi - $cocuk_06 - $engelli_cocuk - $ogrenim_cocuk);
        
        $tutarlar = array(
            'normal' => $this->get_sosyal_yardim_tutari('cocuk_normal', 150),
            '06' => $this->get_sosyal_yardim_tutari('cocuk_0_6', 300),
            'engelli' => $this->get_sosyal_yardim_tutari('cocuk_engelli', 600),
            'ogrenim' => $this->get_sosyal_yardim_tutari('cocuk_ogrenim', 250)
        );
        
        return $normal_cocuk * $tutarlar['normal'] + 
               $cocuk_06 * $tutarlar['06'] + 
               $engelli_cocuk * $tutarlar['engelli'] + 
               $ogrenim_cocuk * $tutarlar['ogrenim'];
    }

    /**
     * Kira yardımı hesaplar
     */
    private function calculate_kira_yardimi($kira_yardimi) {
        return $kira_yardimi ? $this->get_sosyal_yardim_tutari('kira_yardimi', 2000) : 0;
    }

    /**
     * Sendika yardımı hesaplar
     */
    private function calculate_sendika_yardimi($sendika_uyesi) {
        return $sendika_uyesi ? $this->get_sosyal_yardim_tutari('sendika_yardimi', 500) : 0;
    }

    /**
     * Sosyal yardım tutarını bul
     */
    private function get_sosyal_yardim_tutari($tip, $default_value) {
        foreach ($this->sosyal_yardimlar as $sy) {
            if ($sy['tip'] === $tip) {
                return (float)$sy['tutar'];
            }
        }
        return $default_value;
    }

    /**
     * Emekli keseneği hesaplar
     */
    private function calculate_emekli_kesenegi($taban_ayligi, $ek_gosterge_tutari, $kidem_ayligi) {
        $emekli_matrahi = $taban_ayligi + $ek_gosterge_tutari + $kidem_ayligi;
        return $emekli_matrahi * 0.16;
    }

    /**
     * Genel sağlık sigortası hesaplar
     */
    private function calculate_gss_primi($taban_ayligi, $ek_gosterge_tutari, $kidem_ayligi) {
        $emekli_matrahi = $taban_ayligi + $ek_gosterge_tutari + $kidem_ayligi;
        return $emekli_matrahi * 0.05;
    }

    /**
     * Asgari Geçim İndirimi hesaplar
     */
    private function calculate_agi($asgari_gecim_indirimi, $medeni_hal, $es_calisiyor, $cocuk_sayisi) {
        if (!$asgari_gecim_indirimi) return 0;
        
        $agi_orani = 0.50; // Bekar
        
        if ($medeni_hal === 'evli' && $es_calisiyor === 'hayir') {
            $agi_orani += 0.10;
        }
        
        $cocuk_oranlari = array(1 => 0.075, 2 => 0.075, 3 => 0.10, 4 => 0.05);
        for ($i = 1; $i <= min($cocuk_sayisi, 4); $i++) {
            $agi_orani += $cocuk_oranlari[$i];
        }
        
        $asgari_ucret = apply_filters('matas_asgari_ucret', 17002.0);
        return $asgari_ucret * $agi_orani * 0.15;
    }

    /**
     * Gelir vergisi hesaplar
     */
    private function calculate_gelir_vergisi($matrah, $agi_tutari) {
        $vergi = 0;
        $kalan_matrah = $matrah;
        
        usort($this->vergiler, function($a, $b) {
            return (int)$a['dilim'] - (int)$b['dilim'];
        });
        
        foreach ($this->vergiler as $index => $dilim) {
            $alt_limit = (float)$dilim['alt_limit'];
            $ust_limit = (float)$dilim['ust_limit'];
            $oran = (float)$dilim['oran'] / 100;
            
            if ($index === count($this->vergiler) - 1 || $ust_limit === 0) {
                $vergi += $kalan_matrah * $oran;
                break;
            }
            
            $hesaplanacak_matrah = min($kalan_matrah, $ust_limit - $alt_limit);
            
            if ($hesaplanacak_matrah <= 0) break;
            
            $vergi += $hesaplanacak_matrah * $oran;
            $kalan_matrah -= $hesaplanacak_matrah;
            
            if ($kalan_matrah <= 0) break;
        }
        
        return max(0, $vergi - $agi_tutari);
    }

    /**
     * Damga vergisi hesaplar
     */
    private function calculate_damga_vergisi($brut_maas) {
        return $brut_maas * 0.00759;
    }

    /**
     * Sendika kesintisi hesaplar
     */
    private function calculate_sendika_kesintisi($sendika_uyesi, $taban_ayligi) {
        return $sendika_uyesi ? $taban_ayligi * 0.01 : 0;
    }
}

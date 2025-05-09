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
     * Katsayılar
     *
     * @var array
     */
    private $katsayilar;
    
    /**
     * Unvanlar
     *
     * @var array
     */
    private $unvanlar;
    
    /**
     * Göstergeler
     *
     * @var array
     */
    private $gostergeler;
    
    /**
     * Dil göstergeleri
     *
     * @var array
     */
    private $dil_gostergeleri;
    
    /**
     * Vergiler
     *
     * @var array
     */
    private $vergiler;
    
    /**
     * Sosyal yardımlar
     *
     * @var array
     */
    private $sosyal_yardimlar;

    /**
     * Sınıfı başlat
     *
     * @param string $plugin_name Eklenti ismi
     * @param string $version Eklenti versiyonu
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->load_data();
    }

    /**
     * Veritabanından hesaplama için gerekli verileri yükler
     */
    private function load_data() {
        global $wpdb;

        // Aktif katsayıları yükle
        $this->katsayilar = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}matas_katsayilar WHERE aktif = 1 ORDER BY id DESC LIMIT 1",
            ARRAY_A
        );
        
        // Aktif katsayı yoksa varsayılan değerleri kullan
        if (!$this->katsayilar) {
            $this->katsayilar = array(
                'donem' => date('Y') . ' Ocak-Haziran',
                'aylik_katsayi' => 0.354507,
                'taban_katsayi' => 7.715,
                'yan_odeme_katsayi' => 0.0354507
            );
        }

        // Unvanları yükle
        $this->unvanlar = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}matas_unvan_bilgileri",
            ARRAY_A
        );

        // Gösterge puanlarını yükle
        $this->gostergeler = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}matas_gosterge_puanlari",
            ARRAY_A
        );

        // Dil göstergelerini yükle
        $this->dil_gostergeleri = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}matas_dil_gostergeleri",
            ARRAY_A
        );

        // Vergi dilimlerini yükle
        $current_year = date('Y');
        $this->vergiler = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}matas_vergiler WHERE yil = %d ORDER BY dilim ASC",
                $current_year
            ),
            ARRAY_A
        );
        
// Vergi dilimleri yoksa varsayılan değerleri kullan
        if (empty($this->vergiler)) {
            $this->vergiler = array(
                array('yil' => $current_year, 'dilim' => 1, 'alt_limit' => 0, 'ust_limit' => 70000, 'oran' => 15),
                array('yil' => $current_year, 'dilim' => 2, 'alt_limit' => 70000, 'ust_limit' => 150000, 'oran' => 20),
                array('yil' => $current_year, 'dilim' => 3, 'alt_limit' => 150000, 'ust_limit' => 550000, 'oran' => 27),
                array('yil' => $current_year, 'dilim' => 4, 'alt_limit' => 550000, 'ust_limit' => 1900000, 'oran' => 35),
                array('yil' => $current_year, 'dilim' => 5, 'alt_limit' => 1900000, 'ust_limit' => 0, 'oran' => 40),
            );
        }

        // Sosyal yardımları yükle
        $this->sosyal_yardimlar = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}matas_sosyal_yardimlar WHERE yil = %d",
                $current_year
            ),
            ARRAY_A
        );
        
        // Sosyal yardımlar yoksa varsayılan değerleri kullan
        if (empty($this->sosyal_yardimlar)) {
            $this->sosyal_yardimlar = array(
                array('yil' => $current_year, 'tip' => 'aile_yardimi', 'adi' => 'Aile Yardımı', 'tutar' => 1200),
                array('yil' => $current_year, 'tip' => 'cocuk_normal', 'adi' => 'Çocuk Yardımı', 'tutar' => 150),
                array('yil' => $current_year, 'tip' => 'cocuk_0_6', 'adi' => '0-6 Yaş Çocuk Yardımı', 'tutar' => 300),
                array('yil' => $current_year, 'tip' => 'cocuk_engelli', 'adi' => 'Engelli Çocuk Yardımı', 'tutar' => 600),
                array('yil' => $current_year, 'tip' => 'cocuk_ogrenim', 'adi' => 'Öğrenim Çocuk Yardımı', 'tutar' => 250),
                array('yil' => $current_year, 'tip' => 'kira_yardimi', 'adi' => 'Kira Yardımı', 'tutar' => 2000),
                array('yil' => $current_year, 'tip' => 'sendika_yardimi', 'adi' => 'Sendika Yardımı', 'tutar' => 500),
            );
        }
    }

    /**
     * Maaş hesaplama işlemini gerçekleştirir
     *
     * @param array $params Hesaplama parametreleri
     * @return array Hesaplama sonuçları
     */
    public function calculate_salary($params) {
        // Parametre doğrulama
        if (!isset($params['unvan']) || !isset($params['derece']) || !isset($params['kademe']) || !isset($params['hizmet_yili'])) {
            return array(
                'success' => false,
                'message' => __('Eksik parametre! Lütfen tüm zorunlu alanları doldurunuz.', 'matas'),
            );
        }

        // Unvan bilgilerini bul
        $unvan = null;
        foreach ($this->unvanlar as $u) {
            if ($u['unvan_kodu'] === $params['unvan']) {
                $unvan = $u;
                break;
            }
        }

        if (!$unvan) {
            return array(
                'success' => false,
                'message' => __('Ünvan bulunamadı! Lütfen geçerli bir ünvan seçiniz.', 'matas'),
            );
        }

        // Gösterge puanını bul
        $gosterge_puani = 0;
        foreach ($this->gostergeler as $g) {
            if ((int)$g['derece'] === (int)$params['derece'] && (int)$g['kademe'] === (int)$params['kademe']) {
                $gosterge_puani = (int)$g['gosterge_puani'];
                break;
            }
        }

        if (!$gosterge_puani) {
            // Gösterge puanı bulunamadıysa constants.js'deki tabloya bakalım
            $gosterge_puani_key = $params['derece'] . '-' . $params['kademe'];
            $default_gosterge_puanlari = apply_filters('matas_default_gosterge_puanlari', array(
                // 1. Derece
                '1-1' => 1320, '1-2' => 1380, '1-3' => 1440, '1-4' => 1500, '1-5' => 1560, 
                '1-6' => 1620, '1-7' => 1680, '1-8' => 1740,
                
                // 2. Derece
                '2-1' => 1155, '2-2' => 1210, '2-3' => 1265, '2-4' => 1320, '2-5' => 1380, 
                '2-6' => 1440, '2-7' => 1500, '2-8' => 1560,
                
                // 3. Derece
                '3-1' => 1020, '3-2' => 1065, '3-3' => 1110, '3-4' => 1155, '3-5' => 1210, 
                '3-6' => 1265, '3-7' => 1320, '3-8' => 1380, '3-9' => 1440,
                
                // 4. Derece
                '4-1' => 915, '4-2' => 950, '4-3' => 985, '4-4' => 1020, '4-5' => 1065, 
                '4-6' => 1110, '4-7' => 1155, '4-8' => 1210, '4-9' => 1265,
                
                // 5. Derece
                '5-1' => 835, '5-2' => 870, '5-3' => 905, '5-4' => 915, '5-5' => 950, 
                '5-6' => 985, '5-7' => 1020, '5-8' => 1065, '5-9' => 1110,
                
                // Diğer dereceler için...
                // 6. Derece
                '6-1' => 760, '6-2' => 785, '6-3' => 810, '6-4' => 835, '6-5' => 870, 
                '6-6' => 905, '6-7' => 915, '6-8' => 950, '6-9' => 985,
                
                // 7. Derece
                '7-1' => 705, '7-2' => 720, '7-3' => 740, '7-4' => 760, '7-5' => 785, 
                '7-6' => 810, '7-7' => 835, '7-8' => 870, '7-9' => 905,
                
                // 8. Derece
                '8-1' => 660, '8-2' => 675, '8-3' => 690, '8-4' => 705, '8-5' => 720, 
                '8-6' => 740, '8-7' => 760, '8-8' => 785, '8-9' => 810,
                
                // 9. Derece
                '9-1' => 620, '9-2' => 630, '9-3' => 645, '9-4' => 660, '9-5' => 675, 
                '9-6' => 690, '9-7' => 705, '9-8' => 720, '9-9' => 740,
                
                // 10. Derece
                '10-1' => 590, '10-2' => 600, '10-3' => 610, '10-4' => 620, '10-5' => 630, 
                '10-6' => 645, '10-7' => 660, '10-8' => 675, '10-9' => 690,
                
                // 11. Derece
                '11-1' => 560, '11-2' => 570, '11-3' => 580, '11-4' => 590, '11-5' => 600, 
                '11-6' => 610, '11-7' => 620, '11-8' => 630, '11-9' => 645,
                
                // 12. Derece
                '12-1' => 545, '12-2' => 550, '12-3' => 555, '12-4' => 560, '12-5' => 570, 
                '12-6' => 580, '12-7' => 590, '12-8' => 600, '12-9' => 610,
                
                // 13. Derece
                '13-1' => 530, '13-2' => 535, '13-3' => 540, '13-4' => 545, '13-5' => 550, 
                '13-6' => 555, '13-7' => 560, '13-8' => 570, '13-9' => 580,
                
                // 14. Derece
                '14-1' => 515, '14-2' => 520, '14-3' => 525, '14-4' => 530, '14-5' => 535, 
                '14-6' => 540, '14-7' => 545, '14-8' => 550, '14-9' => 555,
                
                // 15. Derece
                '15-1' => 500, '15-2' => 505, '15-3' => 510, '15-4' => 515, '15-5' => 520, 
                '15-6' => 525, '15-7' => 530, '15-8' => 535, '15-9' => 540
            ));
            
            if (isset($default_gosterge_puanlari[$gosterge_puani_key])) {
                $gosterge_puani = $default_gosterge_puanlari[$gosterge_puani_key];
            } else {
                return array(
                    'success' => false,
                    'message' => __('Gösterge puanı bulunamadı! Lütfen geçerli bir derece ve kademe seçiniz.', 'matas'),
                );
            }
        }

        // Katsayıları al
        $aylik_katsayi = (float)$this->katsayilar['aylik_katsayi'];
        $taban_katsayi = (float)$this->katsayilar['taban_katsayi'];
        $yan_odeme_katsayi = (float)$this->katsayilar['yan_odeme_katsayi'];
        $donem = sanitize_text_field($this->katsayilar['donem']);

        // Diğer parametreleri hazırla
        $hizmet_yili = intval($params['hizmet_yili']);
        $medeni_hal = isset($params['medeni_hal']) ? sanitize_text_field($params['medeni_hal']) : 'bekar';
        $es_calisiyor = isset($params['es_calisiyor']) ? sanitize_text_field($params['es_calisiyor']) : 'evet';
        $cocuk_sayisi = isset($params['cocuk_sayisi']) ? intval($params['cocuk_sayisi']) : 0;
        $cocuk_06 = isset($params['cocuk_06']) ? intval($params['cocuk_06']) : 0;
        $engelli_cocuk = isset($params['engelli_cocuk']) ? intval($params['engelli_cocuk']) : 0;
        $ogrenim_cocuk = isset($params['ogrenim_cocuk']) ? intval($params['ogrenim_cocuk']) : 0;
        $egitim_durumu = isset($params['egitim_durumu']) ? sanitize_text_field($params['egitim_durumu']) : 'lisans';
        $dil_seviyesi = isset($params['dil_seviyesi']) ? sanitize_text_field($params['dil_seviyesi']) : 'yok';
        $dil_kullanimi = isset($params['dil_kullanimi']) ? sanitize_text_field($params['dil_kullanimi']) : 'hayir';
        $gorev_tazminati = isset($params['gorev_tazminati']) && (bool)$params['gorev_tazminati'];
        $gelistirme_odenegi = isset($params['gelistirme_odenegi']) && (bool)$params['gelistirme_odenegi'];
        $asgari_gecim_indirimi = isset($params['asgari_gecim_indirimi']) && (bool)$params['asgari_gecim_indirimi'];
        $kira_yardimi = isset($params['kira_yardimi']) && (bool)$params['kira_yardimi'];
        $sendika_uyesi = isset($params['sendika_uyesi']) && (bool)$params['sendika_uyesi'];

        // Temel maaş bileşenlerini hesapla
        $taban_ayligi = $this->calculate_taban_ayligi($gosterge_puani, $aylik_katsayi);
        $ek_gosterge_tutari = $this->calculate_ek_gosterge_tutari($unvan['ekgosterge'], $aylik_katsayi);
        $kidem_ayligi = $this->calculate_kidem_ayligi($hizmet_yili, $aylik_katsayi);
        $yan_odeme = $this->calculate_yan_odeme($unvan['yan_odeme'], $yan_odeme_katsayi);
        $ozel_hizmet_tazminati = $this->calculate_ozel_hizmet_tazminati($gosterge_puani, $unvan['ekgosterge'], $aylik_katsayi, $unvan['ozel_hizmet']);
        $is_gucluguzammi = $this->calculate_is_gucluguzammi($unvan['is_guclugu'], $yan_odeme_katsayi);

        // Dil tazminatını hesapla
        $dil_tazminati = $this->calculate_dil_tazminati($dil_seviyesi, $dil_kullanimi, $aylik_katsayi);

        // Ek ödeme hesapla (666 KHK)
        $ek_odeme = $this->calculate_ek_odeme($taban_ayligi, $ek_gosterge_tutari);

        // Eğitim tazminatı hesapla
        $egitim_tazminati = $this->calculate_egitim_tazminati($gorev_tazminati, $taban_ayligi, $ek_gosterge_tutari, $unvan['egitim_tazminat']);

        // Geliştirme ödeneği hesapla
        $gelistirme_odenegi_tutari = $this->calculate_gelistirme_odenegi($gelistirme_odenegi, $taban_ayligi, $ek_gosterge_tutari);

        // Makam tazminatı hesapla
        $makam_tazminati = $this->calculate_makam_tazminati($unvan['makam_tazminat'], $aylik_katsayi);

        // Lisansüstü eğitim tazminatı hesapla
        $lisansustu_tazminat = $this->calculate_lisansustu_tazminat($egitim_durumu, $taban_ayligi);

        // Sosyal yardımları hesapla
        $aile_yardimi = $this->calculate_aile_yardimi($medeni_hal, $es_calisiyor);
        $cocuk_yardimi = $this->calculate_cocuk_yardimi($cocuk_sayisi, $cocuk_06, $engelli_cocuk, $ogrenim_cocuk);
        $kira_yardimi_tutari = $this->calculate_kira_yardimi($kira_yardimi);
        $sendika_yardimi = $this->calculate_sendika_yardimi($sendika_uyesi);

        // Brüt maaşı hesapla
        $brut_maas = $taban_ayligi + $ek_gosterge_tutari + $kidem_ayligi + $yan_odeme + 
                     $ozel_hizmet_tazminati + $is_gucluguzammi + $dil_tazminati + 
                     $ek_odeme + $egitim_tazminati + $gelistirme_odenegi_tutari + 
                     $makam_tazminati + $lisansustu_tazminat + $aile_yardimi + 
                     $cocuk_yardimi + $kira_yardimi_tutari + $sendika_yardimi;

        // Kesintileri hesapla
        $emekli_kesenegi = $this->calculate_emekli_kesenegi($taban_ayligi, $ek_gosterge_tutari, $kidem_ayligi);
        $gss_primi = $this->calculate_gss_primi($taban_ayligi, $ek_gosterge_tutari, $kidem_ayligi);
        
        // Gelir vergisi matrahını hesapla
        $gelir_vergisi_matrahi = $brut_maas - $emekli_kesenegi - $gss_primi;
        
        // Asgari geçim indirimi hesapla
        $agi_tutari = $this->calculate_agi($asgari_gecim_indirimi, $medeni_hal, $es_calisiyor, $cocuk_sayisi);
        
        // Gelir vergisini hesapla
        $gelir_vergisi = $this->calculate_gelir_vergisi($gelir_vergisi_matrahi, $agi_tutari);
        
        // Damga vergisi hesapla
        $damga_vergisi = $this->calculate_damga_vergisi($brut_maas);
        
        // Sendika kesintisi
        $sendika_kesintisi = $this->calculate_sendika_kesintisi($sendika_uyesi, $taban_ayligi);
        
        // Toplam kesintileri hesapla
        $toplam_kesintiler = $emekli_kesenegi + $gss_primi + $gelir_vergisi + $damga_vergisi + $sendika_kesintisi;
        
        // Net maaşı hesapla
        $net_maas = $brut_maas - $toplam_kesintiler;
        
        // Sonuçları döndür
        return array(
            'success' => true,
            'donem' => $donem,
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
            'kefalet' => 0, // Örnek değer
            'brutMaas' => round($brut_maas, 2),
            'toplamKesintiler' => round($toplam_kesintiler, 2),
            'netMaas' => round($net_maas, 2)
        );
    }

    /**
     * Taban aylığı hesaplar
     *
     * @param int $gosterge_puani Gösterge puanı
     * @param float $aylik_katsayi Aylık katsayı
     * @return float Taban aylığı
     */
    private function calculate_taban_ayligi($gosterge_puani, $aylik_katsayi) {
        return $gosterge_puani * $aylik_katsayi;
    }

    /**
     * Ek gösterge tutarını hesaplar
     *
     * @param int $ek_gosterge Ek gösterge puanı
     * @param float $aylik_katsayi Aylık katsayı
     * @return float Ek gösterge tutarı
     */
    private function calculate_ek_gosterge_tutari($ek_gosterge, $aylik_katsayi) {
        return $ek_gosterge * $aylik_katsayi;
    }

    /**
     * Kıdem aylığı hesaplar
     *
     * @param int $hizmet_yili Hizmet yılı
     * @param float $aylik_katsayi Aylık katsayı
     * @return float Kıdem aylığı
     */
    private function calculate_kidem_ayligi($hizmet_yili, $aylik_katsayi) {
        return min($hizmet_yili, 25) * 25 * $aylik_katsayi;
    }

    /**
     * Yan ödeme hesaplar
     *
     * @param int $yan_odeme_puani Yan ödeme puanı
     * @param float $yan_odeme_katsayi Yan ödeme katsayısı
     * @return float Yan ödeme tutarı
     */
    private function calculate_yan_odeme($yan_odeme_puani, $yan_odeme_katsayi) {
        return $yan_odeme_puani * $yan_odeme_katsayi;
    }

    /**
     * Özel hizmet tazminatı hesaplar
     *
     * @param int $gosterge_puani Gösterge puanı
     * @param int $ek_gosterge Ek gösterge puanı
     * @param float $aylik_katsayi Aylık katsayı
     * @param int $ozel_hizmet_yuzdesi Özel hizmet yüzdesi
     * @return float Özel hizmet tazminatı
     */
    private function calculate_ozel_hizmet_tazminati($gosterge_puani, $ek_gosterge, $aylik_katsayi, $ozel_hizmet_yuzdesi) {
        return ($gosterge_puani + $ek_gosterge) * $aylik_katsayi * $ozel_hizmet_yuzdesi / 100;
    }

    /**
     * İş güçlüğü zammı hesaplar
     *
     * @param int $is_guclugu İş güçlüğü puanı
     * @param float $yan_odeme_katsayi Yan ödeme katsayısı
     * @return float İş güçlüğü zammı
     */
    private function calculate_is_gucluguzammi($is_guclugu, $yan_odeme_katsayi) {
        return $is_guclugu * $yan_odeme_katsayi;
    }

    /**
     * Dil tazminatı hesaplar
     *
     * @param string $dil_seviyesi Dil seviyesi (a, b, c)
     * @param string $dil_kullanimi Dil kullanım durumu (evet, hayir)
     * @param float $aylik_katsayi Aylık katsayı
     * @return float Dil tazminatı
     */
    private function calculate_dil_tazminati($dil_seviyesi, $dil_kullanimi, $aylik_katsayi) {
        if ($dil_seviyesi === 'yok') return 0;
        
        // Önce dil göstergelerinden kontrol et
        foreach ($this->dil_gostergeleri as $dg) {
            if ($dg['seviye_kodu'] === $dil_seviyesi && $dg['kullanim'] === $dil_kullanimi) {
                return $dg['gosterge'] * $aylik_katsayi;
            }
        }
        
        // Dil göstergesi bulunamadıysa varsayılanları kullan
        $dil_gosterge = 0;
        
        if ($dil_seviyesi === 'a' && $dil_kullanimi === 'evet') {
            $dil_gosterge = 1500;
        } else if ($dil_seviyesi === 'b' && $dil_kullanimi === 'evet') {
            $dil_gosterge = 600;
        } else if ($dil_seviyesi === 'c' && $dil_kullanimi === 'evet') {
            $dil_gosterge = 300;
        } else if ($dil_seviyesi === 'a' && $dil_kullanimi === 'hayir') {
            $dil_gosterge = 750;
        } else if ($dil_seviyesi === 'b' && $dil_kullanimi === 'hayir') {
            $dil_gosterge = 300;
        } else if ($dil_seviyesi === 'c' && $dil_kullanimi === 'hayir') {
            $dil_gosterge = 150;
        }
        
        return $dil_gosterge * $aylik_katsayi;
    }

    /**
     * Ek ödeme (666 KHK) hesaplar
     *
     * @param float $taban_ayligi Taban aylığı
     * @param float $ek_gosterge_tutari Ek gösterge tutarı
     * @return float Ek ödeme tutarı
     */
    private function calculate_ek_odeme($taban_ayligi, $ek_gosterge_tutari) {
        return ($taban_ayligi + $ek_gosterge_tutari) * 0.20;
    }

    /**
     * Eğitim-Öğretim tazminatı hesaplar
     *
     * @param bool $gorev_tazminati Eğitim-öğretim tazminatı alıp almadığı
     * @param float $taban_ayligi Taban aylığı
     * @param float $ek_gosterge_tutari Ek gösterge tutarı
     * @param float $egitim_tazminat_orani Eğitim tazminatı oranı
     * @return float Eğitim-öğretim tazminatı
     */
    private function calculate_egitim_tazminati($gorev_tazminati, $taban_ayligi, $ek_gosterge_tutari, $egitim_tazminat_orani) {
        if (!$gorev_tazminati) return 0;
        return ($taban_ayligi + $ek_gosterge_tutari) * $egitim_tazminat_orani;
    }

    /**
     * Geliştirme ödeneği hesaplar
     *
     * @param bool $gelistirme_odenegi Geliştirme ödeneği alıp almadığı
     * @param float $taban_ayligi Taban aylığı
     * @param float $ek_gosterge_tutari Ek gösterge tutarı
     * @return float Geliştirme ödeneği tutarı
     */
    private function calculate_gelistirme_odenegi($gelistirme_odenegi, $taban_ayligi, $ek_gosterge_tutari) {
        if (!$gelistirme_odenegi) return 0;
        // Örnek oran - gerçek projede bir tabloda saklanabilir
        $gelistirme_orani = 0.10;
        return ($taban_ayligi + $ek_gosterge_tutari) * $gelistirme_orani;

    /**
     * Makam tazminatı hesaplar
     *
     * @param int $makam_tazminat_puani Makam tazminatı puanı
     * @param float $aylik_katsayi Aylık katsayı
     * @return float Makam tazminatı
     */
    private function calculate_makam_tazminati($makam_tazminat_puani, $aylik_katsayi) {
        if ($makam_tazminat_puani <= 0) return 0;
        return $makam_tazminat_puani * $aylik_katsayi;
    }

    /**
     * Lisansüstü tazminatı hesaplar
     *
     * @param string $egitim_durumu Eğitim durumu (lisans, yuksek_lisans, doktora)
     * @param float $taban_ayligi Taban aylığı
     * @return float Lisansüstü eğitim tazminatı
     */
    private function calculate_lisansustu_tazminat($egitim_durumu, $taban_ayligi) {
        if ($egitim_durumu === 'yuksek_lisans') {
            return $taban_ayligi * 0.05;
        } else if ($egitim_durumu === 'doktora') {
            return $taban_ayligi * 0.15;
        }
        return 0;
    }

    /**
     * Aile yardımı hesaplar
     *
     * @param string $medeni_hal Medeni hal (evli, bekar)
     * @param string $es_calisiyor Eşin çalışma durumu (evet, hayir)
     * @return float Aile yardımı
     */
    private function calculate_aile_yardimi($medeni_hal, $es_calisiyor) {
        if ($medeni_hal === 'evli' && $es_calisiyor === 'hayir') {
            // Sosyal yardımlar tablosundan bul
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'aile_yardimi') {
                    return (float)$sy['tutar'];
                }
            }
            
            // Bulunamazsa varsayılan değer
            return 1200.0;
        }
        return 0;
    }

    /**
     * Çocuk yardımı hesaplar
     *
     * @param int $cocuk_sayisi Toplam çocuk sayısı
     * @param int $cocuk_06 0-6 yaş arası çocuk sayısı
     * @param int $engelli_cocuk Engelli çocuk sayısı
     * @param int $ogrenim_cocuk Öğrenim gören çocuk sayısı
     * @return float Çocuk yardımı
     */
    private function calculate_cocuk_yardimi($cocuk_sayisi, $cocuk_06, $engelli_cocuk, $ogrenim_cocuk) {
        if ($cocuk_sayisi <= 0) {
            return 0;
        }
        
        // Normal çocuk sayısını hesapla
        $normal_cocuk = max(0, $cocuk_sayisi - $cocuk_06 - $engelli_cocuk - $ogrenim_cocuk);
        
        // Varsayılan değerler
        $normal_yardim = 150.0;
        $yardim_06 = 300.0;
        $yardim_engelli = 600.0;
        $yardim_ogrenim = 250.0;
        
        // Sosyal yardımlar tablosundan bul
        foreach ($this->sosyal_yardimlar as $sy) {
            if ($sy['tip'] === 'cocuk_normal') {
                $normal_yardim = (float)$sy['tutar'];
            } else if ($sy['tip'] === 'cocuk_0_6') {
                $yardim_06 = (float)$sy['tutar'];
            } else if ($sy['tip'] === 'cocuk_engelli') {
                $yardim_engelli = (float)$sy['tutar'];
            } else if ($sy['tip'] === 'cocuk_ogrenim') {
                $yardim_ogrenim = (float)$sy['tutar'];
            }
        }
        
        // Toplamı hesapla
        return $normal_cocuk * $normal_yardim + 
               $cocuk_06 * $yardim_06 + 
               $engelli_cocuk * $yardim_engelli + 
               $ogrenim_cocuk * $yardim_ogrenim;
    }

    /**
     * Kira yardımı hesaplar
     *
     * @param bool $kira_yardimi Kira yardımı alıp almadığı
     * @return float Kira yardımı tutarı
     */
    private function calculate_kira_yardimi($kira_yardimi) {
        if (!$kira_yardimi) return 0;
        
        // Sosyal yardımlar tablosundan bul
        foreach ($this->sosyal_yardimlar as $sy) {
            if ($sy['tip'] === 'kira_yardimi') {
                return (float)$sy['tutar'];
            }
        }
        
        // Bulunamazsa varsayılan değer
        return 2000.0;
    }

    /**
     * Sendika yardımı hesaplar
     *
     * @param bool $sendika_uyesi Sendika üyesi olup olmadığı
     * @return float Sendika yardımı tutarı
     */
    private function calculate_sendika_yardimi($sendika_uyesi) {
        if (!$sendika_uyesi) return 0;
        
        // Sosyal yardımlar tablosundan bul
        foreach ($this->sosyal_yardimlar as $sy) {
            if ($sy['tip'] === 'sendika_yardimi') {
                return (float)$sy['tutar'];
            }
        }
        
        // Bulunamazsa varsayılan değer
        return 500.0;
    }

    /**
     * Emekli keseneği hesaplar
     *
     * @param float $taban_ayligi Taban aylığı
     * @param float $ek_gosterge_tutari Ek gösterge tutarı
     * @param float $kidem_ayligi Kıdem aylığı
     * @return float Emekli keseneği
     */
    private function calculate_emekli_kesenegi($taban_ayligi, $ek_gosterge_tutari, $kidem_ayligi) {
        $emekli_matrahi = $taban_ayligi + $ek_gosterge_tutari + $kidem_ayligi;
        return $emekli_matrahi * 0.16;
    }

    /**
     * Genel sağlık sigortası hesaplar
     *
     * @param float $taban_ayligi Taban aylığı
     * @param float $ek_gosterge_tutari Ek gösterge tutarı
     * @param float $kidem_ayligi Kıdem aylığı
     * @return float Genel sağlık sigortası
     */
    private function calculate_gss_primi($taban_ayligi, $ek_gosterge_tutari, $kidem_ayligi) {
        $emekli_matrahi = $taban_ayligi + $ek_gosterge_tutari + $kidem_ayligi;
        return $emekli_matrahi * 0.05;
    }
    
    /**
     * Asgari Geçim İndirimi (AGİ) hesaplar
     * 
     * @param bool $asgari_gecim_indirimi AGİ alıp almadığı
     * @param string $medeni_hal Medeni hal (evli, bekar)
     * @param string $es_calisiyor Eşin çalışma durumu (evet, hayir)
     * @param int $cocuk_sayisi Toplam çocuk sayısı
     * @return float AGİ tutarı
     */
    private function calculate_agi($asgari_gecim_indirimi, $medeni_hal, $es_calisiyor, $cocuk_sayisi) {
        if (!$asgari_gecim_indirimi) return 0;
        
        // Temel oran
        $agi_orani = 0.50; // Bekar
        
        // Eş için
        if ($medeni_hal === 'evli' && $es_calisiyor === 'hayir') {
            $agi_orani += 0.10;
        }
        
        // Çocuklar için
        if ($cocuk_sayisi >= 1) $agi_orani += 0.075;
        if ($cocuk_sayisi >= 2) $agi_orani += 0.075;
        if ($cocuk_sayisi >= 3) $agi_orani += 0.10;
        if ($cocuk_sayisi >= 4) $agi_orani += 0.05;
        
        // 2025 için tahmini asgari ücret
        $asgari_ucret = apply_filters('matas_asgari_ucret', 17002.0);
        
        return $asgari_ucret * $agi_orani * 0.15;
    }

    /**
     * Gelir vergisi hesaplar
     *
     * @param float $matrah Gelir vergisi matrahı
     * @param float $agi_tutari AGİ tutarı
     * @return float Gelir vergisi
     */
    private function calculate_gelir_vergisi($matrah, $agi_tutari) {
        $vergi = 0;
        $kalan_matrah = $matrah;
        
        // Vergi dilimlerini küçükten büyüğe sırala
        usort($this->vergiler, function($a, $b) {
            return (int)$a['dilim'] - (int)$b['dilim'];
        });
        
        foreach ($this->vergiler as $index => $dilim) {
            $alt_limit = (float)$dilim['alt_limit'];
            $ust_limit = (float)$dilim['ust_limit'];
            $oran = (float)$dilim['oran'] / 100;
            
            // Son dilim veya sonsuz limit
            if ($index === count($this->vergiler) - 1 || $ust_limit === 0) {
                $vergi += $kalan_matrah * $oran;
                break;
            }
            
            // Matraha göre vergi hesapla
            $hesaplanacak_matrah = min($kalan_matrah, $ust_limit - $alt_limit);
            
            if ($hesaplanacak_matrah <= 0) {
                break;
            }
            
            $vergi += $hesaplanacak_matrah * $oran;
            $kalan_matrah -= $hesaplanacak_matrah;
            
            if ($kalan_matrah <= 0) {
                break;
            }
        }
        
        // AGİ'yi gelir vergisinden düş, negatif olmasını engelle
        return max(0, $vergi - $agi_tutari);
    }

    /**
     * Damga vergisi hesaplar
     *
     * @param float $brut_maas Brüt maaş
     * @return float Damga vergisi
     */
    private function calculate_damga_vergisi($brut_maas) {
        return $brut_maas * 0.00759;
    }

    /**
     * Sendika kesintisi hesaplar
     *
     * @param bool $sendika_uyesi Sendika üyesi olup olmadığı
     * @param float $taban_ayligi Taban aylığı
     * @return float Sendika kesintisi
     */
    private function calculate_sendika_kesintisi($sendika_uyesi, $taban_ayligi) {
        return $sendika_uyesi ? $taban_ayligi * 0.01 : 0;
    }
}

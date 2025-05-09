<?php
class Matas_Calculator {
    private $plugin_name;
    private $version;
    private $katsayilar;
    private $unvanlar;
    private $gostergeler;
    private $dil_gostergeleri;
    private $vergiler;
    private $sosyal_yardimlar;

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
        // Parametreleri kontrol et
        if (!isset($params['unvan']) || !isset($params['derece']) || !isset($params['kademe']) || !isset($params['hizmet_yili'])) {
            return array(
                'success' => false,
                'message' => 'Eksik parametre!',
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
                'message' => 'Unvan bulunamadı!',
            );
        }

        // Gösterge puanını bul
        $gosterge_puani = 0;
        foreach ($this->gostergeler as $g) {
            if ($g['derece'] == $params['derece'] && $g['kademe'] == $params['kademe']) {
                $gosterge_puani = $g['gosterge_puani'];
                break;
            }
        }

        if (!$gosterge_puani) {
            // Gösterge puanı bulunamadıysa constants.js'deki tabloya bakalım
            $gosterge_puani_key = $params['derece'] . '-' . $params['kademe'];
            $default_gosterge_puanlari = array(
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
            );
            
            if (isset($default_gosterge_puanlari[$gosterge_puani_key])) {
                $gosterge_puani = $default_gosterge_puanlari[$gosterge_puani_key];
            } else {
                return array(
                    'success' => false,
                    'message' => 'Gösterge puanı bulunamadı!',
                );
            }
        }

        // Katsayıları al
        $aylik_katsayi = $this->katsayilar['aylik_katsayi'];
        $taban_katsayi = $this->katsayilar['taban_katsayi'];
        $yan_odeme_katsayi = $this->katsayilar['yan_odeme_katsayi'];
        $donem = $this->katsayilar['donem'];

        // Diğer parametreleri hazırla
        $hizmet_yili = intval($params['hizmet_yili']);
        $medeni_hal = isset($params['medeni_hal']) ? $params['medeni_hal'] : 'bekar';
        $es_calisiyor = isset($params['es_calisiyor']) ? $params['es_calisiyor'] : 'evet';
        $cocuk_sayisi = isset($params['cocuk_sayisi']) ? intval($params['cocuk_sayisi']) : 0;
        $cocuk_06 = isset($params['cocuk_06']) ? intval($params['cocuk_06']) : 0;
        $engelli_cocuk = isset($params['engelli_cocuk']) ? intval($params['engelli_cocuk']) : 0;
        $ogrenim_cocuk = isset($params['ogrenim_cocuk']) ? intval($params['ogrenim_cocuk']) : 0;
        $egitim_durumu = isset($params['egitim_durumu']) ? $params['egitim_durumu'] : 'lisans';
        $dil_seviyesi = isset($params['dil_seviyesi']) ? $params['dil_seviyesi'] : 'yok';
        $dil_kullanimi = isset($params['dil_kullanimi']) ? $params['dil_kullanimi'] : 'hayir';
        $gorev_tazminati = isset($params['gorev_tazminati']) && $params['gorev_tazminati'] == 1;
        $gelistirme_odenegi = isset($params['gelistirme_odenegi']) && $params['gelistirme_odenegi'] == 1;
        $asgari_gecim_indirimi = isset($params['asgari_gecim_indirimi']) && $params['asgari_gecim_indirimi'] == 1;
        $kira_yardimi = isset($params['kira_yardimi']) && $params['kira_yardimi'] == 1;
        $sendika_uyesi = isset($params['sendika_uyesi']) && $params['sendika_uyesi'] == 1;

        // Temel maaş bileşenlerini hesapla
        $taban_ayligi = $gosterge_puani * $aylik_katsayi;
        $ek_gosterge_tutari = $unvan['ekgosterge'] * $aylik_katsayi;
        $kidem_ayligi = min($hizmet_yili, 25) * 25 * $aylik_katsayi;
        $yan_odeme = $unvan['yan_odeme'] * $yan_odeme_katsayi;
        $ozel_hizmet_tazminati = ($gosterge_puani + $unvan['ekgosterge']) * $aylik_katsayi * $unvan['ozel_hizmet'] / 100;
        $is_gucluguzammi = $unvan['is_guclugu'] * $yan_odeme_katsayi;

        // Dil tazminatını hesapla
        $dil_tazminati = 0;
        if ($dil_seviyesi !== 'yok') {
            // Dil göstergesini bul
            foreach ($this->dil_gostergeleri as $dg) {
                if ($dg['seviye_kodu'] === $dil_seviyesi && $dg['kullanim'] === $dil_kullanimi) {
                    $dil_tazminati = $dg['gosterge'] * $aylik_katsayi;
                    break;
                }
            }
            
            // Dil göstergesi bulunamadıysa varsayılan değerleri kullan
            if ($dil_tazminati === 0) {
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
                
                $dil_tazminati = $dil_gosterge * $aylik_katsayi;
            }
        }

        // Ek ödeme hesapla (666 KHK)
        $ek_odeme = ($taban_ayligi + $ek_gosterge_tutari) * 0.20;

        // Eğitim tazminatı hesapla
        $egitim_tazminati = 0;
        if ($gorev_tazminati) {
            $egitim_tazminati = ($taban_ayligi + $ek_gosterge_tutari) * $unvan['egitim_tazminat'];
        }

        // Geliştirme ödeneği hesapla
        $gelistirme_odenegi_tutari = 0;
        if ($gelistirme_odenegi) {
            // Örnek oran - gerçek projede bir tabloda saklanabilir
            $gelistirme_orani = 0.10;
            $gelistirme_odenegi_tutari = ($taban_ayligi + $ek_gosterge_tutari) * $gelistirme_orani;
        }

        // Makam tazminatı hesapla
        $makam_tazminati = 0;
        if ($unvan['makam_tazminat'] > 0) {
            $makam_tazminati = $unvan['makam_tazminat'] * $aylik_katsayi;
        }

        // Lisansüstü eğitim tazminatı hesapla
        $lisansustu_tazminat = 0;
        if ($egitim_durumu === 'yuksek_lisans') {
            $lisansustu_tazminat = $taban_ayligi * 0.05;
        } elseif ($egitim_durumu === 'doktora') {
            $lisansustu_tazminat = $taban_ayligi * 0.15;
        }

        // Sosyal yardımları hesapla
        $aile_yardimi = 0;
        $cocuk_yardimi = 0;
        $kira_yardimi_tutari = 0;
        $sendika_yardimi = 0;

        // Aile yardımı
        if ($medeni_hal === 'evli' && $es_calisiyor === 'hayir') {
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'aile_yardimi') {
                    $aile_yardimi = $sy['tutar'];
                    break;
                }
            }
            
            // Aile yardımı yoksa varsayılan değeri kullan
            if ($aile_yardimi === 0) {
                $aile_yardimi = 1200;
            }
        }

        // Çocuk yardımı
        if ($cocuk_sayisi > 0) {
            $normal_cocuk = max(0, $cocuk_sayisi - $cocuk_06 - $engelli_cocuk - $ogrenim_cocuk);
            
            // Varsayılan yardım tutarları
            $normal_yardim = 150;
            $yardim_06 = 300;
            $yardim_engelli = 600;
            $yardim_ogrenim = 250;
            
            // Normal çocuk yardımı
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'cocuk_normal') {
                    $normal_yardim = $sy['tutar'];
                    break;
                }
            }
            
            // 0-6 yaş çocuk yardımı
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'cocuk_0_6') {
                    $yardim_06 = $sy['tutar'];
                    break;
                }
            }
            
            // Engelli çocuk yardımı
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'cocuk_engelli') {
                    $yardim_engelli = $sy['tutar'];
                    break;
                }
            }
            
            // Öğrenim çocuk yardımı
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'cocuk_ogrenim') {
                    $yardim_ogrenim = $sy['tutar'];
                    break;
                }
            }
            
            // Yardımları topla
            $cocuk_yardimi = $normal_cocuk * $normal_yardim +
                             $cocuk_06 * $yardim_06 +
                             $engelli_cocuk * $yardim_engelli +
                             $ogrenim_cocuk * $yardim_ogrenim;
        }

        // Kira yardımı
        if ($kira_yardimi) {
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'kira_yardimi') {
                    $kira_yardimi_tutari = $sy['tutar'];
                    break;
                }
            }
            
            // Kira yardımı yoksa varsayılan değeri kullan
            if ($kira_yardimi_tutari === 0) {
                $kira_yardimi_tutari = 2000;
            }
        }

        // Sendika yardımı
        if ($sendika_uyesi) {
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'sendika_yardimi') {
                    $sendika_yardimi = $sy['tutar'];
                    break;
                }
            }
            
            // Sendika yardımı yoksa varsayılan değeri kullan
            if ($sendika_yardimi === 0) {
                $sendika_yardimi = 500;
            }
        }

        // Brüt maaşı hesapla
        $brut_maas = $taban_ayligi + $ek_gosterge_tutari + $kidem_ayligi + $yan_odeme + 
                     $ozel_hizmet_tazminati + $is_gucluguzammi + $dil_tazminati + 
                     $ek_odeme + $egitim_tazminati + $gelistirme_odenegi_tutari + 
                     $makam_tazminati + $lisansustu_tazminat + $aile_yardimi + 
                     $cocuk_yardimi + $kira_yardimi_tutari + $sendika_yardimi;

        // Kesintileri hesapla
        $emekli_kesenegi = ($taban_ayligi + $ek_gosterge_tutari + $kidem_ayligi) * 0.16;
        $gss_primi = ($taban_ayligi + $ek_gosterge_tutari + $kidem_ayligi) * 0.05;
        
        // Gelir vergisi matrahını hesapla
        $gelir_vergisi_matrahi = $brut_maas - $emekli_kesenegi - $gss_primi;
        
        // Asgari geçim indirimi hesapla
        $agi_tutari = 0;
        if ($asgari_gecim_indirimi) {
            // Basitleştirilmiş AGİ hesaplaması
            $agi_orani = 0.50; // Bekar çalışan için temel oran
            
            if ($medeni_hal === 'evli' && $es_calisiyor === 'hayir') {
                $agi_orani += 0.10;
            }
            
            // Çocuklar için AGİ
            if ($cocuk_sayisi > 0) {
                if ($cocuk_sayisi >= 1) $agi_orani += 0.075;
                if ($cocuk_sayisi >= 2) $agi_orani += 0.075;
                if ($cocuk_sayisi >= 3) $agi_orani += 0.10;
                if ($cocuk_sayisi >= 4) $agi_orani += 0.05;
            }
            
            // AGİ tutarını hesapla
            $asgari_ucret = 17002; // 2025 tahmini asgari ücret
            $agi_tutari = $asgari_ucret * $agi_orani * 0.15;
        }
        
        // Gelir vergisini hesapla (düzeltilmiş)
        $gelir_vergisi = 0;
        $kalan_matrah = $gelir_vergisi_matrahi;
        $kumulatif_vergi = 0;
        
        // Vergi dilimlerini küçükten büyüğe sırala
        usort($this->vergiler, function($a, $b) {
            return intval($a['dilim']) - intval($b['dilim']);
        });
        
        foreach ($this->vergiler as $index => $dilim) {
            $alt_limit = floatval($dilim['alt_limit']);
            $ust_limit = floatval($dilim['ust_limit']);
            $oran = floatval($dilim['oran']) / 100;
// Son dilim veya sonsuz limit
            if ($index === count($this->vergiler) - 1 || $ust_limit === 0) {
                $gelir_vergisi += $kalan_matrah * $oran;
                break;
            }
            
            // Matraha göre vergi hesapla
            $hesaplanacak_matrah = min($kalan_matrah, $ust_limit - $alt_limit);
            
            if ($hesaplanacak_matrah <= 0) {
                break;
            }
            
            $gelir_vergisi += $hesaplanacak_matrah * $oran;
            $kalan_matrah -= $hesaplanacak_matrah;
            
            if ($kalan_matrah <= 0) {
                break;
            }
        }
        
        // AGİ'yi gelir vergisinden düş
        $gelir_vergisi = max(0, $gelir_vergisi - $agi_tutari);
        
        // Damga vergisi hesapla
        $damga_vergisi = $brut_maas * 0.00759;
        
        // Sendika kesintisi
        $sendika_kesintisi = 0;
        if ($sendika_uyesi) {
            $sendika_kesintisi = $taban_ayligi * 0.01;
        }
        
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
}

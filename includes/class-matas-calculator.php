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
        $this->vergiler = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}matas_vergiler WHERE yil = " . date('Y'),
            ARRAY_A
        );

        // Sosyal yardımları yükle
        $this->sosyal_yardimlar = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}matas_sosyal_yardimlar WHERE yil = " . date('Y'),
            ARRAY_A
        );
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
            return array(
                'success' => false,
                'message' => 'Gösterge puanı bulunamadı!',
            );
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
        }

        // Çocuk yardımı
        if ($cocuk_sayisi > 0) {
            $normal_cocuk = $cocuk_sayisi - $cocuk_06 - $engelli_cocuk - $ogrenim_cocuk;
            
            // Normal çocuk yardımı
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'cocuk_normal') {
                    $cocuk_yardimi += $normal_cocuk * $sy['tutar'];
                    break;
                }
            }
            
            // 0-6 yaş çocuk yardımı
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'cocuk_0_6') {
                    $cocuk_yardimi += $cocuk_06 * $sy['tutar'];
                    break;
                }
            }
            
            // Engelli çocuk yardımı
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'cocuk_engelli') {
                    $cocuk_yardimi += $engelli_cocuk * $sy['tutar'];
                    break;
                }
            }
            
            // Öğrenim çocuk yardımı
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'cocuk_ogrenim') {
                    $cocuk_yardimi += $ogrenim_cocuk * $sy['tutar'];
                    break;
                }
            }
        }

        // Kira yardımı
        if ($kira_yardimi) {
            foreach ($this->sosyal_yardimlar as $sy) {
                if ($sy['tip'] === 'kira_yardimi') {
                    $kira_yardimi_tutari = $sy['tutar'];
                    break;
                }
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
            // Basitleştirilmiş AGİ hesaplaması - gerçek projede daha detaylı hesaplama yapılabilir
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
        
        // Gelir vergisini hesapla
        $gelir_vergisi = 0;
        $vergi_dilimleri = $this->vergiler;
        usort($vergi_dilimleri, function($a, $b) {
            return $a['dilim'] - $b['dilim'];
        });

        $kalan_matrah = $gelir_vergisi_matrahi;
        
        foreach ($vergi_dilimleri as $index => $dilim) {
            $alt_limit = floatval($dilim['alt_limit']);
            $ust_limit = floatval($dilim['ust_limit']);
            $oran = floatval($dilim['oran']) / 100;
            
            if ($index === 0 && $kalan_matrah <= $ust_limit) {
                // İlk dilim ve matrah bu dilimde bitiyor
                $gelir_vergisi = $kalan_matrah * $oran;
                break;
            } elseif ($index === 0) {
                // İlk dilim ama matrah devam ediyor
                $gelir_vergisi += $ust_limit * $oran;
                $kalan_matrah -= $ust_limit;
            } elseif ($index === count($vergi_dilimleri) - 1 || $ust_limit === 0) {
                // Son dilim veya sınırsız dilim
                $gelir_vergisi += $kalan_matrah * $oran;
                break;
            } elseif ($kalan_matrah <= ($ust_limit - $alt_limit)) {
                // Ara dilim ve matrah bu dilimde bitiyor
                $gelir_vergisi += $kalan_matrah * $oran;
                break;
            } else {
                // Ara dilim ve matrah devam ediyor
                $dilim_farki = $ust_limit - $alt_limit;
                $gelir_vergisi += $dilim_farki * $oran;
                $kalan_matrah -= $dilim_farki;
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

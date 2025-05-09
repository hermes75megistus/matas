<?php
class Matas_Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Veritabanı tabloları oluşturma SQL sorgularını çalıştır
        $sql = array();

        $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}matas_katsayilar` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `donem` varchar(50) NOT NULL,
            `aylik_katsayi` decimal(10,6) NOT NULL,
            `taban_katsayi` decimal(10,6) NOT NULL,
            `yan_odeme_katsayi` decimal(10,6) NOT NULL,
            `aktif` tinyint(1) DEFAULT 1,
            `olusturma_tarihi` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}matas_unvan_bilgileri` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `unvan_kodu` varchar(30) NOT NULL,
            `unvan_adi` varchar(100) NOT NULL,
            `ekgosterge` int(11) NOT NULL,
            `ozel_hizmet` int(11) NOT NULL,
            `yan_odeme` int(11) NOT NULL,
            `is_guclugu` int(11) NOT NULL,
            `makam_tazminat` int(11) NOT NULL,
            `egitim_tazminat` decimal(10,2) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unvan_kodu` (`unvan_kodu`)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}matas_gosterge_puanlari` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `derece` int(11) NOT NULL,
            `kademe` int(11) NOT NULL,
            `gosterge_puani` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `derece_kademe` (`derece`, `kademe`)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}matas_dil_gostergeleri` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `seviye_kodu` varchar(30) NOT NULL,
            `seviye_adi` varchar(50) NOT NULL,
            `gosterge` int(11) NOT NULL,
            `kullanim` varchar(30) NOT NULL,
            PRIMARY KEY (`id`)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}matas_vergiler` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `yil` int(11) NOT NULL,
            `dilim` int(11) NOT NULL,
            `alt_limit` decimal(12,2) NOT NULL,
            `ust_limit` decimal(12,2) NOT NULL,
            `oran` decimal(5,2) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `yil_dilim` (`yil`, `dilim`)
        ) $charset_collate;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}matas_sosyal_yardimlar` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `yil` int(11) NOT NULL,
            `tip` varchar(30) NOT NULL,
            `adi` varchar(100) NOT NULL,
            `tutar` decimal(10,2) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `yil_tip` (`yil`, `tip`)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }
        
        // Varsayılan verileri ekle
        self::insert_default_data();
    }
    
    private static function insert_default_data() {
        global $wpdb;
        $current_year = date('Y');
        
        // Katsayılar tablosuna varsayılan veri ekle
        $wpdb->insert(
            $wpdb->prefix . 'matas_katsayilar',
            array(
                'donem' => $current_year . ' Ocak-Haziran',
                'aylik_katsayi' => 0.354507,
                'taban_katsayi' => 7.715,
                'yan_odeme_katsayi' => 0.0354507,
                'aktif' => 1
            )
        );
        
        // Gösterge puanları için varsayılan verileri ekle
        $gosterge_puanlari = array(
            // 1. Derece
            array('derece' => 1, 'kademe' => 1, 'gosterge_puani' => 1320),
            array('derece' => 1, 'kademe' => 2, 'gosterge_puani' => 1380),
            array('derece' => 1, 'kademe' => 3, 'gosterge_puani' => 1440),
            array('derece' => 1, 'kademe' => 4, 'gosterge_puani' => 1500),
            array('derece' => 1, 'kademe' => 5, 'gosterge_puani' => 1560),
            array('derece' => 1, 'kademe' => 6, 'gosterge_puani' => 1620),
            array('derece' => 1, 'kademe' => 7, 'gosterge_puani' => 1680),
            array('derece' => 1, 'kademe' => 8, 'gosterge_puani' => 1740),
            
            // 2. Derece
            array('derece' => 2, 'kademe' => 1, 'gosterge_puani' => 1155),
            array('derece' => 2, 'kademe' => 2, 'gosterge_puani' => 1210),
            array('derece' => 2, 'kademe' => 3, 'gosterge_puani' => 1265),
            array('derece' => 2, 'kademe' => 4, 'gosterge_puani' => 1320),
            array('derece' => 2, 'kademe' => 5, 'gosterge_puani' => 1380),
            array('derece' => 2, 'kademe' => 6, 'gosterge_puani' => 1440),
            array('derece' => 2, 'kademe' => 7, 'gosterge_puani' => 1500),
            array('derece' => 2, 'kademe' => 8, 'gosterge_puani' => 1560),
            
            // 3. Derece
            array('derece' => 3, 'kademe' => 1, 'gosterge_puani' => 1020),
            array('derece' => 3, 'kademe' => 2, 'gosterge_puani' => 1065),
            array('derece' => 3, 'kademe' => 3, 'gosterge_puani' => 1110),
            array('derece' => 3, 'kademe' => 4, 'gosterge_puani' => 1155),
            array('derece' => 3, 'kademe' => 5, 'gosterge_puani' => 1210),
            array('derece' => 3, 'kademe' => 6, 'gosterge_puani' => 1265),
            array('derece' => 3, 'kademe' => 7, 'gosterge_puani' => 1320),
            array('derece' => 3, 'kademe' => 8, 'gosterge_puani' => 1380),
            array('derece' => 3, 'kademe' => 9, 'gosterge_puani' => 1440),
            
            // 4. Derece
            array('derece' => 4, 'kademe' => 1, 'gosterge_puani' => 915),
            array('derece' => 4, 'kademe' => 2, 'gosterge_puani' => 950),
            array('derece' => 4, 'kademe' => 3, 'gosterge_puani' => 985),
            array('derece' => 4, 'kademe' => 4, 'gosterge_puani' => 1020),
            array('derece' => 4, 'kademe' => 5, 'gosterge_puani' => 1065),
            array('derece' => 4, 'kademe' => 6, 'gosterge_puani' => 1110),
            array('derece' => 4, 'kademe' => 7, 'gosterge_puani' => 1155),
            array('derece' => 4, 'kademe' => 8, 'gosterge_puani' => 1210),
            array('derece' => 4, 'kademe' => 9, 'gosterge_puani' => 1265),
            
            // 5. Derece
            array('derece' => 5, 'kademe' => 1, 'gosterge_puani' => 835),
            array('derece' => 5, 'kademe' => 2, 'gosterge_puani' => 870),
            array('derece' => 5, 'kademe' => 3, 'gosterge_puani' => 905),
            array('derece' => 5, 'kademe' => 4, 'gosterge_puani' => 915),
            array('derece' => 5, 'kademe' => 5, 'gosterge_puani' => 950),
            array('derece' => 5, 'kademe' => 6, 'gosterge_puani' => 985),
            array('derece' => 5, 'kademe' => 7, 'gosterge_puani' => 1020),
            array('derece' => 5, 'kademe' => 8, 'gosterge_puani' => 1065),
            array('derece' => 5, 'kademe' => 9, 'gosterge_puani' => 1110),
            
            // 6. Derece
            array('derece' => 6, 'kademe' => 1, 'gosterge_puani' => 760),
            array('derece' => 6, 'kademe' => 2, 'gosterge_puani' => 785),
            array('derece' => 6, 'kademe' => 3, 'gosterge_puani' => 810),
            array('derece' => 6, 'kademe' => 4, 'gosterge_puani' => 835),
            array('derece' => 6, 'kademe' => 5, 'gosterge_puani' => 870),
            array('derece' => 6, 'kademe' => 6, 'gosterge_puani' => 905),
            array('derece' => 6, 'kademe' => 7, 'gosterge_puani' => 915),
            array('derece' => 6, 'kademe' => 8, 'gosterge_puani' => 950),
            array('derece' => 6, 'kademe' => 9, 'gosterge_puani' => 985),
            
            // 7. Derece
            array('derece' => 7, 'kademe' => 1, 'gosterge_puani' => 705),
            array('derece' => 7, 'kademe' => 2, 'gosterge_puani' => 720),
            array('derece' => 7, 'kademe' => 3, 'gosterge_puani' => 740),
            array('derece' => 7, 'kademe' => 4, 'gosterge_puani' => 760),
            array('derece' => 7, 'kademe' => 5, 'gosterge_puani' => 785),
            array('derece' => 7, 'kademe' => 6, 'gosterge_puani' => 810),
            array('derece' => 7, 'kademe' => 7, 'gosterge_puani' => 835),
            array('derece' => 7, 'kademe' => 8, 'gosterge_puani' => 870),
            array('derece' => 7, 'kademe' => 9, 'gosterge_puani' => 905),
            
            // 8. Derece
            array('derece' => 8, 'kademe' => 1, 'gosterge_puani' => 660),
            array('derece' => 8, 'kademe' => 2, 'gosterge_puani' => 675),
            array('derece' => 8, 'kademe' => 3, 'gosterge_puani' => 690),
            array('derece' => 8, 'kademe' => 4, 'gosterge_puani' => 705),
            array('derece' => 8, 'kademe' => 5, 'gosterge_puani' => 720),
            array('derece' => 8, 'kademe' => 6, 'gosterge_puani' => 740),
            array('derece' => 8, 'kademe' => 7, 'gosterge_puani' => 760),
            array('derece' => 8, 'kademe' => 8, 'gosterge_puani' => 785),
            array('derece' => 8, 'kademe' => 9, 'gosterge_puani' => 810),
            
            // 9. Derece
            array('derece' => 9, 'kademe' => 1, 'gosterge_puani' => 620),
            array('derece' => 9, 'kademe' => 2, 'gosterge_puani' => 630),
            array('derece' => 9, 'kademe' => 3, 'gosterge_puani' => 645),
            array('derece' => 9, 'kademe' => 4, 'gosterge_puani' => 660),
            array('derece' => 9, 'kademe' => 5, 'gosterge_puani' => 675),
            array('derece' => 9, 'kademe' => 6, 'gosterge_puani' => 690),
            array('derece' => 9, 'kademe' => 7, 'gosterge_puani' => 705),
            array('derece' => 9, 'kademe' => 8, 'gosterge_puani' => 720),
            array('derece' => 9, 'kademe' => 9, 'gosterge_puani' => 740),
            
            // 10. Derece
            array('derece' => 10, 'kademe' => 1, 'gosterge_puani' => 590),
            array('derece' => 10, 'kademe' => 2, 'gosterge_puani' => 600),
            array('derece' => 10, 'kademe' => 3, 'gosterge_puani' => 610),
            array('derece' => 10, 'kademe' => 4, 'gosterge_puani' => 620),
            array('derece' => 10, 'kademe' => 5, 'gosterge_puani' => 630),
            array('derece' => 10, 'kademe' => 6, 'gosterge_puani' => 645),
            array('derece' => 10, 'kademe' => 7, 'gosterge_puani' => 660),
            array('derece' => 10, 'kademe' => 8, 'gosterge_puani' => 675),
            array('derece' => 10, 'kademe' => 9, 'gosterge_puani' => 690),
            
            // 11. Derece
            array('derece' => 11, 'kademe' => 1, 'gosterge_puani' => 560),
            array('derece' => 11, 'kademe' => 2, 'gosterge_puani' => 570),
            array('derece' => 11, 'kademe' => 3, 'gosterge_puani' => 580),
            array('derece' => 11, 'kademe' => 4, 'gosterge_puani' => 590),
            array('derece' => 11, 'kademe' => 5, 'gosterge_puani' => 600),
            array('derece' => 11, 'kademe' => 6, 'gosterge_puani' => 610),
            array('derece' => 11, 'kademe' => 7, 'gosterge_puani' => 620),
            array('derece' => 11, 'kademe' => 8, 'gosterge_puani' => 630),
            array('derece' => 11, 'kademe' => 9, 'gosterge_puani' => 645),
            
            // 12. Derece
            array('derece' => 12, 'kademe' => 1, 'gosterge_puani' => 545),
            array('derece' => 12, 'kademe' => 2, 'gosterge_puani' => 550),
            array('derece' => 12, 'kademe' => 3, 'gosterge_puani' => 555),
            array('derece' => 12, 'kademe' => 4, 'gosterge_puani' => 560),
            array('derece' => 12, 'kademe' => 5, 'gosterge_puani' => 570),
            array('derece' => 12, 'kademe' => 6, 'gosterge_puani' => 580),
            array('derece' => 12, 'kademe' => 7, 'gosterge_puani' => 590),
            array('derece' => 12, 'kademe' => 8, 'gosterge_puani' => 600),
            array('derece' => 12, 'kademe' => 9, 'gosterge_puani' => 610),
            
            // 13. Derece
            array('derece' => 13, 'kademe' => 1, 'gosterge_puani' => 530),
            array('derece' => 13, 'kademe' => 2, 'gosterge_puani' => 535),
            array('derece' => 13, 'kademe' => 3, 'gosterge_puani' => 540),
            array('derece' => 13, 'kademe' => 4, 'gosterge_puani' => 545),
            array('derece' => 13, 'kademe' => 5, 'gosterge_puani' => 550),
            array('derece' => 13, 'kademe' => 6, 'gosterge_puani' => 555),
            array('derece' => 13, 'kademe' => 7, 'gosterge_puani' => 560),
            array('derece' => 13, 'kademe' => 8, 'gosterge_puani' => 570),
            array('derece' => 13, 'kademe' => 9, 'gosterge_puani' => 580),
            
            // 14. Derece
            array('derece' => 14, 'kademe' => 1, 'gosterge_puani' => 515),
            array('derece' => 14, 'kademe' => 2, 'gosterge_puani' => 520),
            array('derece' => 14, 'kademe' => 3, 'gosterge_puani' => 525),
            array('derece' => 14, 'kademe' => 4, 'gosterge_puani' => 530),
            array('derece' => 14, 'kademe' => 5, 'gosterge_puani' => 535),
            array('derece' => 14, 'kademe' => 6, 'gosterge_puani' => 540),
            array('derece' => 14, 'kademe' => 7, 'gosterge_puani' => 545),
            array('derece' => 14, 'kademe' => 8, 'gosterge_puani' => 550),
            array('derece' => 14, 'kademe' => 9, 'gosterge_puani' => 555),
            
            // 15. Derece
            array('derece' => 15, 'kademe' => 1, 'gosterge_puani' => 500),
            array('derece' => 15, 'kademe' => 2, 'gosterge_puani' => 505),
            array('derece' => 15, 'kademe' => 3, 'gosterge_puani' => 510),
            array('derece' => 15, 'kademe' => 4, 'gosterge_puani' => 515),
            array('derece' => 15, 'kademe' => 5, 'gosterge_puani' => 520),
            array('derece' => 15, 'kademe' => 6, 'gosterge_puani' => 525),
            array('derece' => 15, 'kademe' => 7, 'gosterge_puani' => 530),
            array('derece' => 15, 'kademe' => 8, 'gosterge_puani' => 535),
            array('derece' => 15, 'kademe' => 9, 'gosterge_puani' => 540)
        );
        
        foreach ($gosterge_puanlari as $gosterge) {
            $wpdb->insert(
                $wpdb->prefix . 'matas_gosterge_puanlari',
                $gosterge
            );
        }
        
        // Yabancı dil göstergeleri için varsayılan verileri ekle
        $dil_gostergeleri = array(
            array('seviye_kodu' => 'a', 'seviye_adi' => 'A Seviyesi (90-100)', 'gosterge' => 1500, 'kullanim' => 'evet'),
            array('seviye_kodu' => 'b', 'seviye_adi' => 'B Seviyesi (80-89)', 'gosterge' => 600, 'kullanim' => 'evet'),
            array('seviye_kodu' => 'c', 'seviye_adi' => 'C Seviyesi (70-79)', 'gosterge' => 300, 'kullanim' => 'evet'),
            array('seviye_kodu' => 'a', 'seviye_adi' => 'A Seviyesi (90-100)', 'gosterge' => 750, 'kullanim' => 'hayir'),
            array('seviye_kodu' => 'b', 'seviye_adi' => 'B Seviyesi (80-89)', 'gosterge' => 300, 'kullanim' => 'hayir'),
            array('seviye_kodu' => 'c', 'seviye_adi' => 'C Seviyesi (70-79)', 'gosterge' => 150, 'kullanim' => 'hayir'),
        );
        
        foreach ($dil_gostergeleri as $dil) {
            $wpdb->insert(
                $wpdb->prefix . 'matas_dil_gostergeleri',
                $dil
            );
        }
        
        // Vergi dilimleri
        $vergi_dilimleri = array(
            array('yil' => $current_year, 'dilim' => 1, 'alt_limit' => 0, 'ust_limit' => 70000, 'oran' => 15),
            array('yil' => $current_year, 'dilim' => 2, 'alt_limit' => 70000, 'ust_limit' => 150000, 'oran' => 20),
            array('yil' => $current_year, 'dilim' => 3, 'alt_limit' => 150000, 'ust_limit' => 550000, 'oran' => 27),
            array('yil' => $current_year, 'dilim' => 4, 'alt_limit' => 550000, 'ust_limit' => 1900000, 'oran' => 35),
            array('yil' => $current_year, 'dilim' => 5, 'alt_limit' => 1900000, 'ust_limit' => 0, 'oran' => 40),
        );
        
        foreach ($vergi_dilimleri as $dilim) {
            $wpdb->insert(
                $wpdb->prefix . 'matas_vergiler',
                $dilim
            );
        }
        
        // Sosyal yardımlar
        $sosyal_yardimlar = array(
            array('yil' => $current_year, 'tip' => 'aile_yardimi', 'adi' => 'Aile Yardımı', 'tutar' => 1200),
            array('yil' => $current_year, 'tip' => 'cocuk_normal', 'adi' => 'Çocuk Yardımı', 'tutar' => 150),
array('yil' => $current_year, 'tip' => 'cocuk_0_6', 'adi' => '0-6 Yaş Çocuk Yardımı', 'tutar' => 300),
            array('yil' => $current_year, 'tip' => 'cocuk_engelli', 'adi' => 'Engelli Çocuk Yardımı', 'tutar' => 600),
            array('yil' => $current_year, 'tip' => 'cocuk_ogrenim', 'adi' => 'Öğrenim Çocuk Yardımı', 'tutar' => 250),
            array('yil' => $current_year, 'tip' => 'kira_yardimi', 'adi' => 'Kira Yardımı', 'tutar' => 2000),
            array('yil' => $current_year, 'tip' => 'sendika_yardimi', 'adi' => 'Sendika Yardımı', 'tutar' => 500),
            array('yil' => $current_year, 'tip' => 'yemek_yardimi', 'adi' => 'Yemek Yardımı', 'tutar' => 1200),
            array('yil' => $current_year, 'tip' => 'giyecek_yardimi', 'adi' => 'Giyecek Yardımı', 'tutar' => 800),
            array('yil' => $current_year, 'tip' => 'yakacak_yardimi', 'adi' => 'Yakacak Yardımı', 'tutar' => 1100),
        );
        
        foreach ($sosyal_yardimlar as $yardim) {
            $wpdb->insert(
                $wpdb->prefix . 'matas_sosyal_yardimlar',
                $yardim
            );
        }
        
        // Örnek ünvan bilgileri ekle
        $unvanlar = array(
            array(
                'unvan_kodu' => 'ogretmen_mudur',
                'unvan_adi' => 'Okul Müdürü',
                'ekgosterge' => 3000,
                'ozel_hizmet' => 120,
                'yan_odeme' => 1000,
                'is_guclugu' => 500,
                'makam_tazminat' => 2000,
                'egitim_tazminat' => 0.20
            ),
            array(
                'unvan_kodu' => 'ogretmen_mudur_yrd',
                'unvan_adi' => 'Müdür Yardımcısı',
                'ekgosterge' => 2200,
                'ozel_hizmet' => 100,
                'yan_odeme' => 800,
                'is_guclugu' => 400,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0.20
            ),
            array(
                'unvan_kodu' => 'ogretmen_rehber',
                'unvan_adi' => 'Rehber Öğretmen',
                'ekgosterge' => 2200,
                'ozel_hizmet' => 80,
                'yan_odeme' => 800,
                'is_guclugu' => 300,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0.20
            ),
            array(
                'unvan_kodu' => 'ogretmen_sinif',
                'unvan_adi' => 'Sınıf Öğretmeni',
                'ekgosterge' => 2200,
                'ozel_hizmet' => 80,
                'yan_odeme' => 800,
                'is_guclugu' => 300,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0.20
            ),
            array(
                'unvan_kodu' => 'ogretmen_brans',
                'unvan_adi' => 'Branş Öğretmeni',
                'ekgosterge' => 2200,
                'ozel_hizmet' => 80,
                'yan_odeme' => 800,
                'is_guclugu' => 300,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0.20
            ),
            array(
                'unvan_kodu' => 'memur_sef',
                'unvan_adi' => 'Şef',
                'ekgosterge' => 2200,
                'ozel_hizmet' => 75,
                'yan_odeme' => 800,
                'is_guclugu' => 300,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0
            ),
            array(
                'unvan_kodu' => 'memur_veri_haz',
                'unvan_adi' => 'Veri Hazırlama ve Kontrol İşletmeni',
                'ekgosterge' => 1600,
                'ozel_hizmet' => 60,
                'yan_odeme' => 700,
                'is_guclugu' => 300,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0
            ),
            array(
                'unvan_kodu' => 'memur_bilg_isl',
                'unvan_adi' => 'Bilgisayar İşletmeni',
                'ekgosterge' => 1600,
                'ozel_hizmet' => 60,
                'yan_odeme' => 700,
                'is_guclugu' => 300,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0
            ),
            array(
                'unvan_kodu' => 'memur_genel',
                'unvan_adi' => 'Memur',
                'ekgosterge' => 1300,
                'ozel_hizmet' => 50,
                'yan_odeme' => 600,
                'is_guclugu' => 250,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0
            ),
            array(
                'unvan_kodu' => 'tekniker',
                'unvan_adi' => 'Tekniker',
                'ekgosterge' => 2200,
                'ozel_hizmet' => 60,
                'yan_odeme' => 750,
                'is_guclugu' => 350,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0
            ),
            array(
                'unvan_kodu' => 'teknisyen',
                'unvan_adi' => 'Teknisyen',
                'ekgosterge' => 1600,
                'ozel_hizmet' => 50,
                'yan_odeme' => 600,
                'is_guclugu' => 300,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0
            ),
            array(
                'unvan_kodu' => 'hizmetli',
                'unvan_adi' => 'Hizmetli',
                'ekgosterge' => 1100,
                'ozel_hizmet' => 40,
                'yan_odeme' => 500,
                'is_guclugu' => 200,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0
            )
        );
        
        foreach ($unvanlar as $unvan) {
            $wpdb->insert(
                $wpdb->prefix . 'matas_unvan_bilgileri',
                $unvan
            );
        }
    }
}

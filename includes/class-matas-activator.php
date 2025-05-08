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
        
        // Katsayılar tablosuna varsayılan veri ekle
        $wpdb->insert(
            $wpdb->prefix . 'matas_katsayilar',
            array(
                'donem' => '2025 Ocak-Haziran',
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
            // Diğer dereceler için de eklenecek...
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
        
        // 2025 yılı vergi dilimleri
        $vergi_dilimleri = array(
            array('yil' => 2025, 'dilim' => 1, 'alt_limit' => 0, 'ust_limit' => 70000, 'oran' => 15),
            array('yil' => 2025, 'dilim' => 2, 'alt_limit' => 70000, 'ust_limit' => 150000, 'oran' => 20),
            array('yil' => 2025, 'dilim' => 3, 'alt_limit' => 150000, 'ust_limit' => 550000, 'oran' => 27),
            array('yil' => 2025, 'dilim' => 4, 'alt_limit' => 550000, 'ust_limit' => 1900000, 'oran' => 35),
            array('yil' => 2025, 'dilim' => 5, 'alt_limit' => 1900000, 'ust_limit' => 0, 'oran' => 40),
        );
        
        foreach ($vergi_dilimleri as $dilim) {
            $wpdb->insert(
                $wpdb->prefix . 'matas_vergiler',
                $dilim
            );
        }
        
        // 2025 sosyal yardımlar
        $sosyal_yardimlar = array(
            array('yil' => 2025, 'tip' => 'aile_yardimi', 'adi' => 'Aile Yardımı', 'tutar' => 1200),
            array('yil' => 2025, 'tip' => 'cocuk_normal', 'adi' => 'Çocuk Yardımı', 'tutar' => 150),
            array('yil' => 2025, 'tip' => 'cocuk_0_6', 'adi' => '0-6 Yaş Çocuk Yardımı', 'tutar' => 300),
            array('yil' => 2025, 'tip' => 'cocuk_engelli', 'adi' => 'Engelli Çocuk Yardımı', 'tutar' => 600),
            array('yil' => 2025, 'tip' => 'cocuk_ogrenim', 'adi' => 'Öğrenim Çocuk Yardımı', 'tutar' => 250),
            array('yil' => 2025, 'tip' => 'kira_yardimi', 'adi' => 'Kira Yardımı', 'tutar' => 2000),
            array('yil' => 2025, 'tip' => 'sendika_yardimi', 'adi' => 'Sendika Yardımı', 'tutar' => 500),
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
            // Diğer ünvanlar da eklenecek...
        );
        
        foreach ($unvanlar as $unvan) {
            $wpdb->insert(
                $wpdb->prefix . 'matas_unvan_bilgileri',
                $unvan
            );
        }
    }
}
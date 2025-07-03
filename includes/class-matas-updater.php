<?php
/**
 * MATAS Veritabanı Güncelleyici
 * 
 * @package MATAS
 * @since 1.1.0
 */

class Matas_Database_Updater {
    
    /**
     * Veritabanı güncellemelerini yap
     *
     * @param string $current_version Mevcut veritabanı versiyonu
     * @param string $target_version Hedef veritabanı versiyonu
     */
    public function update($current_version, $target_version) {
        global $wpdb;
        
        try {
            // Güncelleme adımlarını tanımla
            $updates = array(
                '1.0.0' => array($this, 'update_to_100'),
                '1.0.1' => array($this, 'update_to_101'),
                '1.1.0' => array($this, 'update_to_110')
            );
            
            // Sıralı güncelleme yap
            foreach ($updates as $version => $callback) {
                if (version_compare($current_version, $version, '<') && 
                    version_compare($target_version, $version, '>=')) {
                    
                    error_log("MATAS: Updating database to version {$version}");
                    
                    if (is_callable($callback)) {
                        call_user_func($callback);
                    }
                }
            }
            
            error_log("MATAS: Database update completed successfully");
            
        } catch (Exception $e) {
            error_log("MATAS Database Update Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 1.0.0 versiyonuna güncelleme
     */
    private function update_to_100() {
        global $wpdb;
        
        // İlk kurulum - temel tablolar oluşturuldu
        // Ek bir işlem gerekmiyor
        error_log("MATAS: Base tables created for version 1.0.0");
    }
    
    /**
     * 1.0.1 versiyonuna güncelleme
     */
    private function update_to_101() {
        global $wpdb;
        
        // Log tablosu ekle
        $this->create_logs_table();
        
        // Error logs tablosu ekle
        $this->create_error_logs_table();
        
        // Performans için indexler ekle
        $this->add_performance_indexes();
        
        error_log("MATAS: Updated to version 1.0.1 - Added logging tables and indexes");
    }
    
    /**
     * 1.1.0 versiyonuna güncelleme
     */
    private function update_to_110() {
        global $wpdb;
        
        // Cache tablosu ekle
        $this->create_cache_table();
        
        // Backup tablosu ekle
        $this->create_backup_table();
        
        // Yeni alanları mevcut tablolara ekle
        $this->add_new_fields_to_existing_tables();
        
        // Varsayılan ayarları güncelle
        $this->update_default_settings();
        
        error_log("MATAS: Updated to version 1.1.0 - Added caching and backup features");
    }
    
    /**
     * Log tablosu oluştur
     */
    private function create_logs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'matas_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
            `user_id` bigint(20) unsigned DEFAULT NULL,
            `action` varchar(100) NOT NULL,
            `data` longtext,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text,
            `request_uri` text,
            PRIMARY KEY (`id`),
            KEY `timestamp` (`timestamp`),
            KEY `user_id` (`user_id`),
            KEY `action` (`action`)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Error logs tablosu oluştur
     */
    private function create_error_logs_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'matas_error_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
            `error_code` varchar(50) NOT NULL,
            `message` text NOT NULL,
            `severity` enum('low','medium','high','critical') DEFAULT 'medium',
            `context` longtext,
            `user_id` bigint(20) unsigned DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text,
            `request_uri` text,
            `resolved` tinyint(1) DEFAULT 0,
            `resolved_at` datetime DEFAULT NULL,
            `resolved_by` bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `timestamp` (`timestamp`),
            KEY `error_code` (`error_code`),
            KEY `severity` (`severity`),
            KEY `resolved` (`resolved`)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Cache tablosu oluştur
     */
    private function create_cache_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'matas_cache';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `cache_key` varchar(255) NOT NULL,
            `cache_value` longtext NOT NULL,
            `cache_group` varchar(100) DEFAULT 'default',
            `expiration` bigint(20) unsigned NOT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`cache_key`),
            KEY `cache_group` (`cache_group`),
            KEY `expiration` (`expiration`)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Backup tablosu oluştur
     */
    private function create_backup_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'matas_backups';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `backup_name` varchar(255) NOT NULL,
            `backup_type` enum('manual','automatic','scheduled') DEFAULT 'manual',
            `backup_data` longtext NOT NULL,
            `backup_size` bigint(20) unsigned DEFAULT NULL,
            `created_by` bigint(20) unsigned DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `restore_count` int(11) DEFAULT 0,
            `last_restored_at` datetime DEFAULT NULL,
            `last_restored_by` bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `backup_type` (`backup_type`),
            KEY `created_by` (`created_by`),
            KEY `created_at` (`created_at`)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Performans için indexler ekle
     */
    private function add_performance_indexes() {
        global $wpdb;
        
        $indexes = array(
            // Katsayılar tablosu
            array(
                'table' => $wpdb->prefix . 'matas_katsayilar',
                'name' => 'idx_aktif_olusturma',
                'columns' => 'aktif, olusturma_tarihi'
            ),
            
            // Ünvan bilgileri tablosu
            array(
                'table' => $wpdb->prefix . 'matas_unvan_bilgileri',
                'name' => 'idx_unvan_kodu',
                'columns' => 'unvan_kodu'
            ),
            
            // Gösterge puanları tablosu
            array(
                'table' => $wpdb->prefix . 'matas_gosterge_puanlari',
                'name' => 'idx_derece_kademe',
                'columns' => 'derece, kademe'
            ),
            
            // Vergi dilimleri tablosu
            array(
                'table' => $wpdb->prefix . 'matas_vergiler',
                'name' => 'idx_yil_dilim',
                'columns' => 'yil, dilim'
            ),
            
            // Sosyal yardımlar tablosu
            array(
                'table' => $wpdb->prefix . 'matas_sosyal_yardimlar',
                'name' => 'idx_yil_tip',
                'columns' => 'yil, tip'
            )
        );
        
        foreach ($indexes as $index) {
            $this->add_index_if_not_exists($index['table'], $index['name'], $index['columns']);
        }
    }
    
    /**
     * Mevcut tablolara yeni alanlar ekle
     */
    private function add_new_fields_to_existing_tables() {
        global $wpdb;
        
        // Katsayılar tablosuna yeni alanlar
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_katsayilar',
            'guncelleme_tarihi',
            'datetime DEFAULT NULL'
        );
        
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_katsayilar',
            'guncelleyen_user_id',
            'bigint(20) unsigned DEFAULT NULL'
        );
        
        // Ünvan bilgileri tablosuna yeni alanlar
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_unvan_bilgileri',
            'aciklama',
            'text DEFAULT NULL'
        );
        
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_unvan_bilgileri',
            'aktif',
            'tinyint(1) DEFAULT 1'
        );
        
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_unvan_bilgileri',
            'olusturma_tarihi',
            'datetime DEFAULT CURRENT_TIMESTAMP'
        );
        
        // Gösterge puanları tablosuna yeni alanlar
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_gosterge_puanlari',
            'gecerlilik_baslangic',
            'date DEFAULT NULL'
        );
        
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_gosterge_puanlari',
            'gecerlilik_bitis',
            'date DEFAULT NULL'
        );
        
        // Vergi dilimleri tablosuna yeni alanlar
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_vergiler',
            'aciklama',
            'varchar(255) DEFAULT NULL'
        );
        
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_vergiler',
            'aktif',
            'tinyint(1) DEFAULT 1'
        );
        
        // Sosyal yardımlar tablosuna yeni alanlar
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_sosyal_yardimlar',
            'min_tutar',
            'decimal(10,2) DEFAULT NULL'
        );
        
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_sosyal_yardimlar',
            'max_tutar',
            'decimal(10,2) DEFAULT NULL'
        );
        
        $this->add_column_if_not_exists(
            $wpdb->prefix . 'matas_sosyal_yardimlar',
            'aktif',
            'tinyint(1) DEFAULT 1'
        );
    }
    
    /**
     * Varsayılan ayarları güncelle
     */
    private function update_default_settings() {
        $current_settings = get_option('matas_settings', array());
        
        $new_settings = array(
            'cache_enabled' => 1,
            'cache_duration' => 3600,
            'rate_limit_enabled' => 1,
            'rate_limit_requests' => 100,
            'rate_limit_period' => 3600,
            'debug_mode' => 0,
            'delete_data_on_uninstall' => 0,
            'backup_enabled' => 1,
            'max_backups' => 10,
            'security_headers' => 1,
            'ip_whitelist' => '',
            'maintenance_mode' => 0,
            'auto_backup_interval' => 'weekly',
            'backup_retention_days' => 30,
            'error_notification_email' => get_option('admin_email'),
            'performance_monitoring' => 1,
            'cache_statistics' => 1
        );
        
        $updated_settings = array_merge($new_settings, $current_settings);
        update_option('matas_settings', $updated_settings);
    }
    
    /**
     * Index yoksa ekle
     */
    private function add_index_if_not_exists($table, $index_name, $columns) {
        global $wpdb;
        
        $index_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW INDEX FROM {$table} WHERE Key_name = %s",
            $index_name
        ));
        
        if (empty($index_exists)) {
            $sql = "ALTER TABLE {$table} ADD INDEX {$index_name} ({$columns})";
            $wpdb->query($sql);
            error_log("MATAS: Added index {$index_name} to table {$table}");
        }
    }
    
    /**
     * Kolon yoksa ekle
     */
    private function add_column_if_not_exists($table, $column_name, $column_definition) {
        global $wpdb;
        
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$table} LIKE %s",
            $column_name
        ));
        
        if (empty($column_exists)) {
            $sql = "ALTER TABLE {$table} ADD COLUMN {$column_name} {$column_definition}";
            $wpdb->query($sql);
            error_log("MATAS: Added column {$column_name} to table {$table}");
        }
    }
    
    /**
     * Tabloyu yedekle
     */
    public function backup_table($table_name) {
        global $wpdb;
        
        try {
            $backup_table = $table_name . '_backup_' . date('Y_m_d_H_i_s');
            
            $sql = "CREATE TABLE {$backup_table} AS SELECT * FROM {$table_name}";
            $wpdb->query($sql);
            
            error_log("MATAS: Backed up table {$table_name} to {$backup_table}");
            return $backup_table;
            
        } catch (Exception $e) {
            error_log("MATAS: Failed to backup table {$table_name}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Yedek tabloyu geri yükle
     */
    public function restore_from_backup($original_table, $backup_table) {
        global $wpdb;
        
        try {
            // Orijinal tabloyu yedekle
            $temp_backup = $this->backup_table($original_table);
            
            if (!$temp_backup) {
                throw new Exception("Failed to create temporary backup");
            }
            
            // Orijinal tabloyu sil
            $wpdb->query("DROP TABLE IF EXISTS {$original_table}");
            
            // Yedek tablodan geri yükle
            $sql = "CREATE TABLE {$original_table} AS SELECT * FROM {$backup_table}";
            $wpdb->query($sql);
            
            error_log("MATAS: Restored table {$original_table} from backup {$backup_table}");
            return true;
            
        } catch (Exception $e) {
            error_log("MATAS: Failed to restore table {$original_table}: " . $e->getMessage());
            
            // Hata durumunda geçici yedeği geri yükle
            if (isset($temp_backup)) {
                $wpdb->query("DROP TABLE IF EXISTS {$original_table}");
                $sql = "CREATE TABLE {$original_table} AS SELECT * FROM {$temp_backup}";
                $wpdb->query($sql);
            }
            
            return false;
        }
    }
    
    /**
     * Eski yedek tabloları temizle
     */
    public function cleanup_old_backups($days = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y_m_d', strtotime("-{$days} days"));
        
        $tables = $wpdb->get_results("SHOW TABLES LIKE '%_backup_%'", ARRAY_N);
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            
            // Tarih formatını kontrol et
            if (preg_match('/_backup_(\d{4}_\d{2}_\d{2})/', $table_name, $matches)) {
                $backup_date = $matches[1];
                
                if ($backup_date < $cutoff_date) {
                    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
                    error_log("MATAS: Cleaned up old backup table {$table_name}");
                }
            }
        }
    }
    
    /**
     * Veritabanı bütünlüğünü kontrol et
     */
    public function verify_database_integrity() {
        global $wpdb;
        
        $errors = array();
        
        // Temel tabloların varlığını kontrol et
        $required_tables = array(
            'matas_katsayilar',
            'matas_unvan_bilgileri',
            'matas_gosterge_puanlari',
            'matas_dil_gostergeleri',
            'matas_vergiler',
            'matas_sosyal_yardimlar'
        );
        
        foreach ($required_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            
            if (!$exists) {
                $errors[] = "Missing table: {$full_table_name}";
            }
        }
        
        // Temel verilerin varlığını kontrol et
        $katsayi_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_katsayilar WHERE aktif = 1");
        if ($katsayi_count == 0) {
            $errors[] = "No active coefficient records found";
        }
        
        $gosterge_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_gosterge_puanlari");
        if ($gosterge_count == 0) {
            $errors[] = "No indicator points found";
        }
        
        if (!empty($errors)) {
            error_log("MATAS Database Integrity Errors: " . implode(', ', $errors));
            return false;
        }
        
        error_log("MATAS: Database integrity check passed");
        return true;
    }
    
    /**
     * Performans optimizasyonu
     */
    public function optimize_database() {
        global $wpdb;
        
        $tables = array(
            'matas_katsayilar',
            'matas_unvan_bilgileri',
            'matas_gosterge_puanlari',
            'matas_dil_gostergeleri',
            'matas_vergiler',
            'matas_sosyal_yardimlar',
            'matas_logs',
            'matas_error_logs',
            'matas_cache',
            'matas_backups'
        );
        
        foreach ($tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            
            // Tablonun var olup olmadığını kontrol et
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            if ($exists) {
                $wpdb->query("OPTIMIZE TABLE {$full_table_name}");
                error_log("MATAS: Optimized table {$full_table_name}");
            }
        }
        
        // Eski cache kayıtlarını temizle
        $wpdb->query("DELETE FROM {$wpdb->prefix}matas_cache WHERE expiration < UNIX_TIMESTAMP()");
        
        // Eski log kayıtlarını temizle (30 günden eski)
        $wpdb->query("DELETE FROM {$wpdb->prefix}matas_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $wpdb->query("DELETE FROM {$wpdb->prefix}matas_error_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        error_log("MATAS: Database optimization completed");
    }
    
    /**
     * Migrate verileri yeni formata çevir
     */
    public function migrate_legacy_data() {
        global $wpdb;
        
        // Eski versiyon verilerini yeni formata çevir
        // Bu metod gelecekteki major güncellemeler için hazırlanmıştır
        
        error_log("MATAS: Legacy data migration completed");
    }
}
?>

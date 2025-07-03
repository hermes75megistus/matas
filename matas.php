<?php
/**
 * Plugin Name: MATAS - Maaş Takip Sistemi (İyileştirilmiş)
 * Plugin URI: https://example.com/matas
 * Description: Memur maaşlarını hesaplama ve takip etme uygulaması - Gelişmiş güvenlik ve performans özellikleri ile
 * Version: 1.1.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: matas
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Doğrudan erişimi engelle
if (!defined('WPINC')) {
    die('Direct access is not allowed.');
}

// Minimum gereksinimler kontrolü
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>MATAS:</strong> Bu eklenti PHP 7.4 veya üzeri sürüm gerektirir. Mevcut sürüm: ' . PHP_VERSION . '</p></div>';
    });
    return;
}

if (version_compare(get_bloginfo('version'), '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>MATAS:</strong> Bu eklenti WordPress 5.0 veya üzeri sürüm gerektirir.</p></div>';
    });
    return;
}

// Eklenti sabitleri
define('MATAS_VERSION', '1.1.0');
define('MATAS_DB_VERSION', '1.1.0');
define('MATAS_PLUGIN_FILE', __FILE__);
define('MATAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MATAS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MATAS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Güvenlik sabitleri
define('MATAS_NONCE_LIFETIME', 3600); // 1 saat
define('MATAS_MAX_LOGIN_ATTEMPTS', 5);
define('MATAS_LOCKOUT_TIME', 900); // 15 dakika

// Performans sabitleri
define('MATAS_CACHE_GROUP', 'matas');
define('MATAS_CACHE_EXPIRATION', 3600); // 1 saat
define('MATAS_MAX_EXECUTION_TIME', 30);

// Debug modu
define('MATAS_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

/**
 * Eklenti güvenlik kontrolü
 */
function matas_security_check() {
    // IP engelleme kontrolü
    $blocked_ips = get_option('matas_blocked_ips', array());
    $user_ip = matas_get_user_ip();
    
    if (in_array($user_ip, $blocked_ips)) {
        wp_die('Access denied from your IP address.', 'Access Denied', array('response' => 403));
    }
    
    // Rate limiting
    $settings = get_option('matas_settings', array());
    if (!empty($settings['rate_limit_enabled'])) {
        matas_check_rate_limit();
    }
    
    // Güvenlik başlıkları
    if (!empty($settings['security_headers'])) {
        matas_add_security_headers();
    }
}

/**
 * Kullanıcı IP adresini güvenli şekilde al
 */
function matas_get_user_ip() {
    $ip_fields = array(
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    );
    
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
 * Rate limiting kontrolü
 */
function matas_check_rate_limit() {
    $settings = get_option('matas_settings', array());
    $user_ip = matas_get_user_ip();
    $cache_key = 'matas_rate_limit_' . md5($user_ip);
    
    $requests = wp_cache_get($cache_key, MATAS_CACHE_GROUP);
    if ($requests === false) {
        $requests = 0;
    }
    
    $requests++;
    $limit = $settings['rate_limit_requests'] ?? 100;
    $period = $settings['rate_limit_period'] ?? 3600;
    
    if ($requests > $limit) {
        // IP'yi geçici olarak engelle
        $blocked_ips = get_option('matas_blocked_ips', array());
        $blocked_ips[] = $user_ip;
        update_option('matas_blocked_ips', $blocked_ips);
        
        // Log yaz
        error_log("MATAS: Rate limit exceeded for IP: $user_ip ($requests requests)");
        
        wp_die('Too many requests. Please try again later.', 'Rate Limit Exceeded', array(
            'response' => 429,
            'headers' => array('Retry-After' => $period)
        ));
    }
    
    wp_cache_set($cache_key, $requests, MATAS_CACHE_GROUP, $period);
}

/**
 * Güvenlik başlıklarını ekle
 */
function matas_add_security_headers() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'');
    }
}

/**
 * Hata yönetimi
 */
function matas_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $error_info = array(
        'severity' => $severity,
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'timestamp' => current_time('mysql'),
        'user_ip' => matas_get_user_ip(),
        'user_id' => get_current_user_id()
    );
    
    // Log yaz
    error_log('MATAS Error: ' . json_encode($error_info));
    
    // Kritik hatalar için email gönder
    if (in_array($severity, array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))) {
        matas_send_error_notification($error_info);
    }
    
    return true;
}

/**
 * Hata bildirimi gönder
 */
function matas_send_error_notification($error_info) {
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    
    $subject = "[{$site_name}] MATAS Kritik Hata";
    $message = "MATAS eklentisinde kritik bir hata oluştu:\n\n";
    $message .= "Hata: {$error_info['message']}\n";
    $message .= "Dosya: {$error_info['file']}:{$error_info['line']}\n";
    $message .= "Zaman: {$error_info['timestamp']}\n";
    $message .= "IP: {$error_info['user_ip']}\n";
    
    wp_mail($admin_email, $subject, $message);
}

/**
 * Performans izleme
 */
class Matas_Performance_Monitor {
    private static $start_time;
    private static $memory_start;
    private static $db_queries_start;
    
    public static function start() {
        self::$start_time = microtime(true);
        self::$memory_start = memory_get_usage();
        self::$db_queries_start = get_num_queries();
    }
    
    public static function end($context = '') {
        $execution_time = microtime(true) - self::$start_time;
        $memory_used = memory_get_usage() - self::$memory_start;
        $db_queries = get_num_queries() - self::$db_queries_start;
        
        $stats = array(
            'context' => $context,
            'execution_time' => round($execution_time * 1000, 2), // ms
            'memory_used' => round($memory_used / 1024 / 1024, 2), // MB
            'db_queries' => $db_queries,
            'timestamp' => current_time('mysql')
        );
        
        if (MATAS_DEBUG) {
            error_log('MATAS Performance: ' . json_encode($stats));
        }
        
        // Yavaş işlemler için uyarı
        if ($execution_time > 5) {
            error_log("MATAS: Slow operation detected ($context): {$execution_time}s");
        }
        
        return $stats;
    }
}

/**
 * Bakım modu kontrolü
 */
function matas_check_maintenance_mode() {
    $settings = get_option('matas_settings', array());
    
    if (!empty($settings['maintenance_mode']) && !current_user_can('manage_options')) {
        wp_die(
            'MATAS sistemi şu anda bakım modunda. Lütfen daha sonra tekrar deneyin.',
            'Bakım Modu',
            array('response' => 503, 'headers' => array('Retry-After' => 3600))
        );
    }
}

/**
 * Aktivasyon işlemi
 */
function activate_matas() {
    Matas_Performance_Monitor::start();
    
    try {
        require_once MATAS_PLUGIN_DIR . 'includes/class-matas-activator.php';
        Matas_Activator::activate();
        
        // Güvenlik ayarlarını başlat
        $default_settings = array(
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
            'maintenance_mode' => 0
        );
        
        add_option('matas_settings', $default_settings);
        add_option('matas_blocked_ips', array());
        add_option('matas_install_date', current_time('mysql'));
        add_option('matas_version', MATAS_VERSION);
        add_option('matas_db_version', MATAS_DB_VERSION);
        
        // Rewrite rules'ları temizle
        flush_rewrite_rules();
        
        // Aktivasyon hook'u
        do_action('matas_activated');
        
    } catch (Exception $e) {
        error_log('MATAS Activation Error: ' . $e->getMessage());
        deactivate_plugins(MATAS_PLUGIN_BASENAME);
        wp_die('MATAS aktivasyonu sırasında hata oluştu: ' . $e->getMessage());
    }
    
    Matas_Performance_Monitor::end('activation');
}

/**
 * Deaktivasyon işlemi
 */
function deactivate_matas() {
    try {
        require_once MATAS_PLUGIN_DIR . 'includes/class-matas-deactivator.php';
        Matas_Deactivator::deactivate();
        
        // Cache temizle
        wp_cache_flush();
        
        // Rewrite rules'ları temizle
        flush_rewrite_rules();
        
        // Geçici IP engellemelerini kaldır
        delete_option('matas_blocked_ips');
        
        // Deaktivasyon hook'u
        do_action('matas_deactivated');
        
    } catch (Exception $e) {
        error_log('MATAS Deactivation Error: ' . $e->getMessage());
    }
}

/**
 * Veritabanı güncelleme kontrolü
 */
function matas_check_database_update() {
    $current_db_version = get_option('matas_db_version', '1.0.0');
    
    if (version_compare($current_db_version, MATAS_DB_VERSION, '<')) {
        require_once MATAS_PLUGIN_DIR . 'includes/class-matas-updater.php';
        $updater = new Matas_Database_Updater();
        $updater->update($current_db_version, MATAS_DB_VERSION);
        
        update_option('matas_db_version', MATAS_DB_VERSION);
    }
}

/**
 * Dil dosyalarını yükle
 */
function matas_load_textdomain() {
    $domain = 'matas';
    $locale = apply_filters('plugin_locale', get_locale(), $domain);
    
    // wp-content/languages/plugins/ dizininden yükle
    load_textdomain($domain, WP_LANG_DIR . '/plugins/' . $domain . '-' . $locale . '.mo');
    
    // Eklenti dizininden yükle
    load_plugin_textdomain($domain, false, dirname(MATAS_PLUGIN_BASENAME) . '/languages/');
}

/**
 * AJAX güvenlik kontrolü
 */
function matas_ajax_security_check() {
    // AJAX istekleri için ek güvenlik
    if (defined('DOING_AJAX') && DOING_AJAX) {
        // Referer kontrolü
        if (!wp_get_referer()) {
            wp_die('Invalid request');
        }
        
        // Rate limiting
        matas_check_rate_limit();
    }
}

/**
 * Admin bildirimleri
 */
function matas_admin_notices() {
    // Güvenlik uyarıları
    $settings = get_option('matas_settings', array());
    
    if (empty($settings['security_headers'])) {
        echo '<div class="notice notice-warning"><p><strong>MATAS:</strong> Güvenlik başlıkları devre dışı. Güvenlik için etkinleştirmeniz önerilir.</p></div>';
    }
    
    if (empty($settings['rate_limit_enabled'])) {
        echo '<div class="notice notice-warning"><p><strong>MATAS:</strong> Rate limiting devre dışı. DDoS koruması için etkinleştirmeniz önerilir.</p></div>';
    }
    
    // PHP versiyon uyarısı
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        echo '<div class="notice notice-info"><p><strong>MATAS:</strong> PHP 8.0+ kullanmanız performans için önerilir. Mevcut sürüm: ' . PHP_VERSION . '</p></div>';
    }
}

/**
 * Cleanup işlemi (günlük)
 */
function matas_daily_cleanup() {
    // Eski log kayıtlarını temizle
    global $wpdb;
    $table_name = $wpdb->prefix . 'matas_logs';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {
        $wpdb->query("DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    }
    
    // Geçici IP engellemelerini temizle
    delete_option('matas_blocked_ips');
    
    // Cache istatistiklerini sıfırla
    wp_cache_delete('matas_cache_hits', MATAS_CACHE_GROUP);
    wp_cache_delete('matas_cache_misses', MATAS_CACHE_GROUP);
    wp_cache_delete('matas_error_count', MATAS_CACHE_GROUP);
}

// Hook'ları kaydet
register_activation_hook(__FILE__, 'activate_matas');
register_deactivation_hook(__FILE__, 'deactivate_matas');

// WordPress yüklendikten sonra çalıştır
add_action('plugins_loaded', function() {
    // Dil dosyalarını yükle
    matas_load_textdomain();
    
    // Veritabanı güncellemelerini kontrol et
    matas_check_database_update();
    
    // Güvenlik kontrolü
    matas_security_check();
    
    // Bakım modu kontrolü
    matas_check_maintenance_mode();
    
    // Hata yakalayıcı
    if (MATAS_DEBUG) {
        set_error_handler('matas_error_handler');
    }
});

// Admin hooks
add_action('admin_init', 'matas_ajax_security_check');
add_action('admin_notices', 'matas_admin_notices');

// Günlük temizlik
if (!wp_next_scheduled('matas_daily_cleanup')) {
    wp_schedule_event(time(), 'daily', 'matas_daily_cleanup');
}
add_action('matas_daily_cleanup', 'matas_daily_cleanup');

// AJAX güvenlik
add_action('wp_ajax_nopriv_*', 'matas_ajax_security_check', 1);

// Ana eklenti sınıfını yükle
require_once MATAS_PLUGIN_DIR . 'includes/class-matas.php';

/**
 * Eklentiyi başlat
 */
function run_matas() {
    try {
        Matas_Performance_Monitor::start();
        
        $plugin = new Matas();
        $plugin->run();
        
        Matas_Performance_Monitor::end('plugin_init');
        
    } catch (Exception $e) {
        error_log('MATAS Runtime Error: ' . $e->getMessage());
        
        if (MATAS_DEBUG) {
            wp_die('MATAS çalışma hatası: ' . $e->getMessage());
        }
    }
}

// Eklentiyi başlat
add_action('init', 'run_matas');

/**
 * Admin stil dosyası ekleme
 */
function matas_admin_styles() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'matas') === 0) {
        wp_enqueue_style(
            'matas-admin-preview', 
            MATAS_PLUGIN_URL . 'admin/css/admin.css', 
            array(), 
            MATAS_VERSION
        );
    }
}
add_action('admin_enqueue_scripts', 'matas_admin_styles');

/**
 * Ayarlar sayfasına link ekle
 */
function matas_add_settings_link($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=matas'),
        __('Ayarlar', 'matas')
    );
    array_unshift($links, $settings_link);
    return $links;
}
add_filter("plugin_action_links_" . MATAS_PLUGIN_BASENAME, 'matas_add_settings_link');

/**
 * Meta links ekle
 */
function matas_add_meta_links($links, $file) {
    if ($file === MATAS_PLUGIN_BASENAME) {
        $links[] = '<a href="' . admin_url('admin.php?page=matas-logs') . '">' . __('Log Kayıtları', 'matas') . '</a>';
        $links[] = '<a href="' . admin_url('admin.php?page=matas-settings') . '">' . __('Ayarlar', 'matas') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'matas_add_meta_links', 10, 2);

/**
 * Shortcode güvenlik filtresi
 */
function matas_secure_shortcode($content) {
    // XSS koruması
    $content = wp_kses_post($content);
    
    // CSP nonce ekle
    if (function_exists('wp_create_nonce')) {
        $nonce = wp_create_nonce('matas_shortcode');
        $content = str_replace('<script', '<script nonce="' . $nonce . '"', $content);
    }
    
    return $content;
}
add_filter('matas_shortcode_output', 'matas_secure_shortcode');

/**
 * Emergency disable mechanism
 */
if (defined('MATAS_EMERGENCY_DISABLE') && MATAS_EMERGENCY_DISABLE) {
    return;
}

/**
 * Plugin yükleme tamamlandı
 */
do_action('matas_loaded');

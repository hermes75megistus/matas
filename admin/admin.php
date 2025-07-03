<?php
/**
 * MATAS Admin sınıfı (İyileştirilmiş)
 * 
 * @package MATAS
 * @since 1.0.0
 */

class Matas_Admin {
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
     * Cache süresi
     *
     * @var int
     */
    private $cache_duration = 3600;

    /**
     * Rate limit ayarları
     *
     * @var array
     */
    private $rate_limits = array(
        'default' => array('limit' => 100, 'period' => 3600),
        'save' => array('limit' => 20, 'period' => 300),
        'delete' => array('limit' => 10, 'period' => 300)
    );

    /**
     * Constructor
     *
     * @param string $plugin_name Eklenti ismi
     * @param string $version Eklenti versiyonu
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Rate limiting kontrolü
     */
    private function check_rate_limit($action = 'default') {
        if (!isset($this->rate_limits[$action])) {
            $action = 'default';
        }

        $user_id = get_current_user_id();
        $cache_key = "matas_rate_limit_{$action}_{$user_id}";
        $attempts = wp_cache_get($cache_key, 'matas');

        if (!$attempts) {
            $attempts = 0;
        }

        $attempts++;

        if ($attempts > $this->rate_limits[$action]['limit']) {
            wp_send_json_error(array(
                'message' => __('Çok fazla istek gönderildi. Lütfen bekleyiniz.', 'matas'),
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            ));
            return;
        }

        wp_cache_set($cache_key, $attempts, 'matas', $this->rate_limits[$action]['period']);
    }

    /**
     * Güvenlik kontrolü (geliştirilmiş)
     */
    private function verify_security($nonce_action = 'matas_admin_nonce') {
        // Nonce kontrolü
        if (!check_ajax_referer($nonce_action, 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Güvenlik doğrulaması başarısız!', 'matas'),
                'error_code' => 'NONCE_FAILED'
            ));
            return false;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Yetkiniz yok!', 'matas'),
                'error_code' => 'INSUFFICIENT_PERMISSIONS'
            ));
            return false;
        }

        // Referer kontrolü
        if (!wp_verify_nonce($_POST['nonce'], $nonce_action)) {
            wp_send_json_error(array(
                'message' => __('Geçersiz istek!', 'matas'),
                'error_code' => 'INVALID_REQUEST'
            ));
            return false;
        }

        return true;
    }

    /**
     * Input sanitization (geliştirilmiş)
     */
    private function sanitize_input($data, $type = 'text') {
        switch ($type) {
            case 'text':
                return sanitize_text_field($data);
            case 'email':
                return sanitize_email($data);
            case 'int':
                return intval($data);
            case 'float':
                return floatval($data);
            case 'textarea':
                return sanitize_textarea_field($data);
            case 'html':
                return wp_kses_post($data);
            case 'slug':
                return sanitize_title($data);
            case 'array':
                return is_array($data) ? array_map('sanitize_text_field', $data) : array();
            default:
                return sanitize_text_field($data);
        }
    }

    /**
     * Veritabanı işlemi wrapper (transaction desteği)
     */
    private function execute_db_operation($callback, $rollback_callback = null) {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            $result = call_user_func($callback);
            
            if ($result === false) {
                throw new Exception('Database operation failed');
            }
            
            $wpdb->query('COMMIT');
            return $result;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            
            if ($rollback_callback) {
                call_user_func($rollback_callback);
            }
            
            error_log('MATAS DB Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cache yönetimi
     */
    private function clear_related_cache($type) {
        $cache_keys = array(
            'katsayilar' => array('matas_katsayilar_active'),
            'unvanlar' => array('matas_unvanlar_all'),
            'gostergeler' => array('matas_gostergeler_all'),
            'vergiler' => array('matas_vergiler_' . date('Y')),
            'sosyal_yardimlar' => array('matas_sosyal_yardimlar_' . date('Y'))
        );

        if (isset($cache_keys[$type])) {
            foreach ($cache_keys[$type] as $cache_key) {
                wp_cache_delete($cache_key, 'matas');
            }
        }
    }
    
    /**
     * Admin stil dosyalarını ekler
     */
    public function enqueue_styles() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_style(
            $this->plugin_name . '-admin', 
            MATAS_PLUGIN_URL . 'admin/css/admin' . $suffix . '.css', 
            array(), 
            $this->version, 
            'all'
        );
    }
    
    /**
     * Admin script dosyalarını ekler
     */
    public function enqueue_scripts() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_script(
            $this->plugin_name . '-admin', 
            MATAS_PLUGIN_URL . 'admin/js/admin' . $suffix . '.js', 
            array('jquery'), 
            $this->version, 
            true
        );
        
        wp_localize_script($this->plugin_name . '-admin', 'matas_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('matas_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Bu öğeyi silmek istediğinize emin misiniz?', 'matas'),
                'loading' => __('Yükleniyor...', 'matas'),
                'error' => __('Bir hata oluştu!', 'matas'),
                'success' => __('İşlem başarılı!', 'matas')
            )
        ));
    }
    
    /**
     * Admin menüsünü ekler
     */
    public function add_plugin_admin_menu() {
        // Ana menü
        add_menu_page(
            __('MATAS - Maaş Takip Sistemi', 'matas'),
            __('MATAS', 'matas'),
            'manage_options',
            'matas',
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-calculator',
            26
        );
        
        // Alt sayfalar
        $subpages = array(
            'katsayilar' => __('Katsayılar', 'matas'),
            'unvanlar' => __('Ünvan Bilgileri', 'matas'),
            'gostergeler' => __('Gösterge Puanları', 'matas'),
            'vergiler' => __('Vergi Dilimleri', 'matas'),
            'sosyal-yardimlar' => __('Sosyal Yardımlar', 'matas'),
            'settings' => __('Ayarlar', 'matas'),
            'logs' => __('Log Kayıtları', 'matas')
        );

        foreach ($subpages as $slug => $title) {
            add_submenu_page(
                'matas',
                $title,
                $title,
                'manage_options',
                'matas-' . $slug,
                array($this, 'display_' . str_replace('-', '_', $slug) . '_page')
            );
        }
    }

    /**
     * Dashboard sayfası
     */
    public function display_plugin_admin_dashboard() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Katsayılar sayfası
     */
    public function display_katsayilar_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/katsayilar.php';
    }

    /**
     * Ünvanlar sayfası
     */
    public function display_unvanlar_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/unvanlar.php';
    }

    /**
     * Göstergeler sayfası
     */
    public function display_gostergeler_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/gostergeler.php';
    }

    /**
     * Vergiler sayfası
     */
    public function display_vergiler_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/vergiler.php';
    }

    /**
     * Sosyal yardımlar sayfası
     */
    public function display_sosyal_yardimlar_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/sosyal-yardimlar.php';
    }

    /**
     * Ayarlar sayfası
     */
    public function display_settings_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * Log kayıtları sayfası
     */
    public function display_logs_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/logs.php';
    }
    
    /**
     * Katsayıları kaydetme AJAX işleyicisi (iyileştirilmiş)
     */
    public function save_katsayilar() {
        try {
            // Güvenlik ve rate limit kontrolleri
            if (!$this->verify_security()) return;
            $this->check_rate_limit('save');
            
            // Form verilerini al ve sanitize et
            $data = array(
                'donem' => $this->sanitize_input($_POST['donem'] ?? ''),
                'aylik_katsayi' => $this->sanitize_input($_POST['aylik_katsayi'] ?? 0, 'float'),
                'taban_katsayi' => $this->sanitize_input($_POST['taban_katsayi'] ?? 0, 'float'),
                'yan_odeme_katsayi' => $this->sanitize_input($_POST['yan_odeme_katsayi'] ?? 0, 'float')
            );
            
            // Veri doğrulama
            $validation = $this->validate_katsayilar_data($data);
            if (!$validation['valid']) {
                wp_send_json_error(array('message' => $validation['message']));
                return;
            }
            
            // Veritabanı işlemi
            $this->execute_db_operation(function() use ($data) {
                global $wpdb;
                
                // Eski aktif katsayıları pasif yap
                $wpdb->update(
                    $wpdb->prefix . 'matas_katsayilar',
                    array('aktif' => 0),
                    array('aktif' => 1),
                    array('%d'),
                    array('%d')
                );
                
                // Yeni katsayıları ekle
                return $wpdb->insert(
                    $wpdb->prefix . 'matas_katsayilar',
                    array_merge($data, array('aktif' => 1)),
                    array('%s', '%f', '%f', '%f', '%d')
                );
            });
            
            // Cache temizle
            $this->clear_related_cache('katsayilar');
            
            // Log yaz
            $this->log_admin_action('katsayilar_saved', $data);
            
            wp_send_json_success(array(
                'message' => __('Katsayılar başarıyla kaydedildi.', 'matas')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Katsayılar kaydedilirken bir hata oluştu.', 'matas'),
                'error_code' => 'SAVE_FAILED'
            ));
        }
    }

    /**
     * Katsayılar veri doğrulama
     */
    private function validate_katsayilar_data($data) {
        if (empty($data['donem'])) {
            return array('valid' => false, 'message' => __('Dönem adı gereklidir.', 'matas'));
        }

        if ($data['aylik_katsayi'] <= 0 || $data['taban_katsayi'] <= 0 || $data['yan_odeme_katsayi'] <= 0) {
            return array('valid' => false, 'message' => __('Tüm katsayılar 0\'dan büyük olmalıdır.', 'matas'));
        }

        if ($data['aylik_katsayi'] > 10 || $data['taban_katsayi'] > 100 || $data['yan_odeme_katsayi'] > 10) {
            return array('valid' => false, 'message' => __('Katsayı değerleri makul aralıkta olmalıdır.', 'matas'));
        }

        return array('valid' => true);
    }
    
    /**
     * Ünvan kaydetme AJAX işleyicisi (iyileştirilmiş)
     */
    public function save_unvan() {
        try {
            if (!$this->verify_security()) return;
            $this->check_rate_limit('save');
            
            // Form verilerini al ve sanitize et
            $data = array(
                'id' => $this->sanitize_input($_POST['unvan_id'] ?? 0, 'int'),
                'unvan_kodu' => $this->sanitize_input($_POST['unvan_kodu'] ?? '', 'slug'),
                'unvan_adi' => $this->sanitize_input($_POST['unvan_adi'] ?? ''),
                'ekgosterge' => $this->sanitize_input($_POST['ekgosterge'] ?? 0, 'int'),
                'ozel_hizmet' => $this->sanitize_input($_POST['ozel_hizmet'] ?? 0, 'int'),
                'yan_odeme' => $this->sanitize_input($_POST['yan_odeme'] ?? 0, 'int'),
                'is_guclugu' => $this->sanitize_input($_POST['is_guclugu'] ?? 0, 'int'),
                'makam_tazminat' => $this->sanitize_input($_POST['makam_tazminat'] ?? 0, 'int'),
                'egitim_tazminat' => $this->sanitize_input($_POST['egitim_tazminat'] ?? 0, 'float')
            );
            
            // Veri doğrulama
            $validation = $this->validate_unvan_data($data);
            if (!$validation['valid']) {
                wp_send_json_error(array('message' => $validation['message']));
                return;
            }
            
            // Veritabanı işlemi
            $result = $this->execute_db_operation(function() use ($data) {
                global $wpdb;
                
                // Unique kontrolü
                if ($data['id'] === 0) {
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}matas_unvan_bilgileri WHERE unvan_kodu = %s",
                        $data['unvan_kodu']
                    ));
                    
                    if ($existing > 0) {
                        throw new Exception(__('Bu ünvan kodu zaten kullanılıyor.', 'matas'));
                    }
                }
                
                $db_data = $data;
                unset($db_data['id']);
                
                if ($data['id'] > 0) {
                    // Güncelleme
                    return $wpdb->update(
                        $wpdb->prefix . 'matas_unvan_bilgileri',
                        $db_data,
                        array('id' => $data['id']),
                        array('%s', '%s', '%d', '%d', '%d', '%d', '%d', '%f'),
                        array('%d')
                    );
                } else {
                    // Yeni ekleme
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'matas_unvan_bilgileri',
                        $db_data,
                        array('%s', '%s', '%d', '%d', '%d', '%d', '%d', '%f')
                    );
                    
                    return $result ? $wpdb->insert_id : false;
                }
            });
            
            // Cache temizle
            $this->clear_related_cache('unvanlar');
            
            // Log yaz
            $this->log_admin_action('unvan_saved', $data);
            
            $message = $data['id'] > 0 ? 
                __('Ünvan bilgileri başarıyla güncellendi.', 'matas') : 
                __('Ünvan bilgileri başarıyla kaydedildi.', 'matas');
            
            wp_send_json_success(array(
                'message' => $message,
                'unvan_id' => $data['id'] > 0 ? $data['id'] : $result
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'error_code' => 'SAVE_FAILED'
            ));
        }
    }

    /**
     * Ünvan veri doğrulama
     */
    private function validate_unvan_data($data) {
        if (empty($data['unvan_kodu']) || empty($data['unvan_adi'])) {
            return array('valid' => false, 'message' => __('Ünvan kodu ve adı zorunludur!', 'matas'));
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['unvan_kodu'])) {
            return array('valid' => false, 'message' => __('Ünvan kodu sadece harf, rakam, tire ve alt çizgi içerebilir.', 'matas'));
        }

        if (strlen($data['unvan_kodu']) > 30 || strlen($data['unvan_adi']) > 100) {
            return array('valid' => false, 'message' => __('Ünvan kodu veya adı çok uzun.', 'matas'));
        }

        $numeric_fields = array('ekgosterge', 'ozel_hizmet', 'yan_odeme', 'is_guclugu', 'makam_tazminat');
        foreach ($numeric_fields as $field) {
            if ($data[$field] < 0 || $data[$field] > 999999) {
                return array('valid' => false, 'message' => sprintf(__('%s değeri geçersiz.', 'matas'), $field));
            }
        }

        if ($data['egitim_tazminat'] < 0 || $data['egitim_tazminat'] > 1) {
            return array('valid' => false, 'message' => __('Eğitim tazminat oranı 0-1 arasında olmalıdır.', 'matas'));
        }

        return array('valid' => true);
    }
    
    /**
     * Ünvan silme AJAX işleyicisi (iyileştirilmiş)
     */
    public function delete_unvan() {
        try {
            if (!$this->verify_security()) return;
            $this->check_rate_limit('delete');
            
            $unvan_id = $this->sanitize_input($_POST['unvan_id'] ?? 0, 'int');
            
            if ($unvan_id <= 0) {
                wp_send_json_error(array('message' => __('Geçersiz ünvan ID!', 'matas')));
                return;
            }
            
            // Kullanım kontrolü
            $usage_check = $this->check_unvan_usage($unvan_id);
            if (!$usage_check['can_delete']) {
                wp_send_json_error(array('message' => $usage_check['message']));
                return;
            }
            
            // Veritabanı işlemi
            $this->execute_db_operation(function() use ($unvan_id) {
                global $wpdb;
                
                $result = $wpdb->delete(
                    $wpdb->prefix . 'matas_unvan_bilgileri',
                    array('id' => $unvan_id),
                    array('%d')
                );
                
                if ($result === false) {
                    throw new Exception('Delete operation failed');
                }
                
                return $result;
            });
            
            // Cache temizle
            $this->clear_related_cache('unvanlar');
            
            // Log yaz
            $this->log_admin_action('unvan_deleted', array('id' => $unvan_id));
            
            wp_send_json_success(array('message' => __('Ünvan başarıyla silindi.', 'matas')));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Ünvan silinirken bir hata oluştu.', 'matas'),
                'error_code' => 'DELETE_FAILED'
            ));
        }
    }

    /**
     * Ünvan kullanım kontrolü
     */
    private function check_unvan_usage($unvan_id) {
        global $wpdb;
        
        // Örnek: Hesaplama geçmişinde kullanılıyor mu?
        // Bu örnekte basit kontrol yapıyoruz
        $unvan = $wpdb->get_row($wpdb->prepare(
            "SELECT unvan_kodu FROM {$wpdb->prefix}matas_unvan_bilgileri WHERE id = %d",
            $unvan_id
        ));
        
        if (!$unvan) {
            return array('can_delete' => false, 'message' => __('Ünvan bulunamadı.', 'matas'));
        }
        
        // Sistem ünvanları korunabilir
        $protected_codes = array('memur_genel', 'ogretmen_sinif');
        if (in_array($unvan->unvan_kodu, $protected_codes)) {
            return array('can_delete' => false, 'message' => __('Sistem ünvanları silinemez.', 'matas'));
        }
        
        return array('can_delete' => true);
    }

    /**
     * Ünvan detaylarını getirme AJAX işleyicisi
     */
    public function get_unvan() {
        try {
            if (!$this->verify_security()) return;
            
            $unvan_id = $this->sanitize_input($_POST['unvan_id'] ?? 0, 'int');
            
            if ($unvan_id <= 0) {
                wp_send_json_error(array('message' => __('Geçersiz ünvan ID!', 'matas')));
                return;
            }
            
            global $wpdb;
            
            $unvan = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}matas_unvan_bilgileri WHERE id = %d",
                    $unvan_id
                ),
                ARRAY_A
            );
            
            if (!$unvan) {
                wp_send_json_error(array('message' => __('Ünvan bulunamadı!', 'matas')));
                return;
            }
            
            wp_send_json_success(array('unvan' => $unvan));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Ünvan bilgileri alınırken hata oluştu.', 'matas'),
                'error_code' => 'FETCH_FAILED'
            ));
        }
    }

    /**
     * Gösterge kaydetme AJAX işleyicisi (iyileştirilmiş)
     */
    public function save_gosterge() {
        try {
            if (!$this->verify_security()) return;
            $this->check_rate_limit('save');
            
            $data = array(
                'id' => $this->sanitize_input($_POST['gosterge_id'] ?? 0, 'int'),
                'derece' => $this->sanitize_input($_POST['derece'] ?? 0, 'int'),
                'kademe' => $this->sanitize_input($_POST['kademe'] ?? 0, 'int'),
                'gosterge_puani' => $this->sanitize_input($_POST['gosterge_puani'] ?? 0, 'int')
            );
            
            // Veri doğrulama
            $validation = $this->validate_gosterge_data($data);
            if (!$validation['valid']) {
                wp_send_json_error(array('message' => $validation['message']));
                return;
            }
            
            // Veritabanı işlemi
            $result = $this->execute_db_operation(function() use ($data) {
                global $wpdb;
                
                // Unique kontrolü
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}matas_gosterge_puanlari WHERE derece = %d AND kademe = %d AND id != %d",
                    $data['derece'], $data['kademe'], $data['id']
                ));
                
                if ($existing) {
                    throw new Exception(__('Bu derece ve kademe için zaten bir gösterge puanı tanımlanmış.', 'matas'));
                }
                
                $db_data = $data;
                unset($db_data['id']);
                
                if ($data['id'] > 0) {
                    return $wpdb->update(
                        $wpdb->prefix . 'matas_gosterge_puanlari',
                        $db_data,
                        array('id' => $data['id']),
                        array('%d', '%d', '%d'),
                        array('%d')
                    );
                } else {
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'matas_gosterge_puanlari',
                        $db_data,
                        array('%d', '%d', '%d')
                    );
                    return $result ? $wpdb->insert_id : false;
                }
            });
            
            // Cache temizle
            $this->clear_related_cache('gostergeler');
            
            // Log yaz
            $this->log_admin_action('gosterge_saved', $data);
            
            $message = $data['id'] > 0 ? 
                __('Gösterge puanı başarıyla güncellendi.', 'matas') : 
                __('Gösterge puanı başarıyla kaydedildi.', 'matas');
            
            wp_send_json_success(array(
                'message' => $message,
                'gosterge_id' => $data['id'] > 0 ? $data['id'] : $result
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'error_code' => 'SAVE_FAILED'
            ));
        }
    }

    /**
     * Gösterge veri doğrulama
     */
    private function validate_gosterge_data($data) {
        if ($data['derece'] <= 0 || $data['derece'] > 15) {
            return array('valid' => false, 'message' => __('Derece 1-15 arasında olmalıdır.', 'matas'));
        }

        if ($data['kademe'] <= 0 || $data['kademe'] > 9) {
            return array('valid' => false, 'message' => __('Kademe 1-9 arasında olmalıdır.', 'matas'));
        }

        if ($data['gosterge_puani'] <= 0 || $data['gosterge_puani'] > 5000) {
            return array('valid' => false, 'message' => __('Gösterge puanı 1-5000 arasında olmalıdır.', 'matas'));
        }

        return array('valid' => true);
    }

    /**
     * Gösterge detaylarını getirme
     */
    public function get_gosterge() {
        try {
            if (!$this->verify_security()) return;
            
            $gosterge_id = $this->sanitize_input($_POST['gosterge_id'] ?? 0, 'int');
            
            if ($gosterge_id <= 0) {
                wp_send_json_error(array('message' => __('Geçersiz gösterge ID!', 'matas')));
                return;
            }
            
            global $wpdb;
            
            $gosterge = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}matas_gosterge_puanlari WHERE id = %d",
                    $gosterge_id
                ),
                ARRAY_A
            );
            
            if (!$gosterge) {
                wp_send_json_error(array('message' => __('Gösterge bulunamadı!', 'matas')));
                return;
            }
            
            wp_send_json_success(array('gosterge' => $gosterge));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Gösterge bilgileri alınırken hata oluştu.', 'matas'),
                'error_code' => 'FETCH_FAILED'
            ));
        }
    }

    /**
     * Gösterge silme
     */
    public function delete_gosterge() {
        try {
            if (!$this->verify_security()) return;
            $this->check_rate_limit('delete');
            
            $gosterge_id = $this->sanitize_input($_POST['gosterge_id'] ?? 0, 'int');
            
            if ($gosterge_id <= 0) {
                wp_send_json_error(array('message' => __('Geçersiz gösterge ID!', 'matas')));
                return;
            }
            
            $this->execute_db_operation(function() use ($gosterge_id) {
                global $wpdb;
                
                $result = $wpdb->delete(
                    $wpdb->prefix . 'matas_gosterge_puanlari',
                    array('id' => $gosterge_id),
                    array('%d')
                );
                
                if ($result === false) {
                    throw new Exception('Delete operation failed');
                }
                
                return $result;
            });
            
            // Cache temizle
            $this->clear_related_cache('gostergeler');
            
            // Log yaz
            $this->log_admin_action('gosterge_deleted', array('id' => $gosterge_id));
            
            wp_send_json_success(array('message' => __('Gösterge başarıyla silindi.', 'matas')));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Gösterge silinirken bir hata oluştu.', 'matas'),
                'error_code' => 'DELETE_FAILED'
            ));
        }
    }

    /**
     * Varsayılan göstergeleri yükleme
     */
    public function load_default_gostergeler() {
        try {
            if (!$this->verify_security()) return;
            $this->check_rate_limit('save');
            
            $default_gostergeler = $this->get_default_gosterge_data();
            
            $success_count = $this->execute_db_operation(function() use ($default_gostergeler) {
                global $wpdb;
                
                // Mevcut göstergeleri sil
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}matas_gosterge_puanlari");
                
                $success_count = 0;
                foreach ($default_gostergeler as $gosterge) {
                    $result = $wpdb->insert(
                        $wpdb->prefix . 'matas_gosterge_puanlari',
                        $gosterge,
                        array('%d', '%d', '%d')
                    );
                    
                    if ($result) {
                        $success_count++;
                    }
                }
                
                return $success_count;
            });
            
            // Cache temizle
            $this->clear_related_cache('gostergeler');
            
            // Log yaz
            $this->log_admin_action('default_gostergeler_loaded', array('count' => $success_count));
            
            wp_send_json_success(array(
                'message' => sprintf(__('Varsayılan gösterge puanları başarıyla yüklendi. Toplam %d gösterge eklendi.', 'matas'), $success_count)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Varsayılan göstergeler yüklenirken hata oluştu.', 'matas'),
                'error_code' => 'LOAD_FAILED'
            ));
        }
    }

    /**
     * Varsayılan gösterge verilerini döndür
     */
    private function get_default_gosterge_data() {
        return array(
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
            array('derece' => 4, 'kademe' => 9, 'gosterge_puani' => 1265)
        );
    }

    /**
     * Admin aktivite logları
     */
    private function log_admin_action($action, $data = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'user_login' => wp_get_current_user()->user_login,
            'action' => $action,
            'data' => $data,
            'ip' => $this->get_user_ip()
        );

        error_log('MATAS Admin Action: ' . json_encode($log_entry));
        
        // Veritabanına da kaydedilebilir
        $this->save_log_to_db($log_entry);
    }

    /**
     * Log'u veritabanına kaydet
     */
    private function save_log_to_db($log_entry) {
        global $wpdb;
        
        // Log tablosu varsa kaydet
        $table_name = $wpdb->prefix . 'matas_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
        
        if ($table_exists) {
            $wpdb->insert(
                $table_name,
                array(
                    'timestamp' => $log_entry['timestamp'],
                    'user_id' => $log_entry['user_id'],
                    'action' => $log_entry['action'],
                    'data' => json_encode($log_entry['data']),
                    'ip_address' => $log_entry['ip']
                ),
                array('%s', '%d', '%s', '%s', '%s')
            );
        }
    }

    /**
     * Kullanıcı IP'sini al
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


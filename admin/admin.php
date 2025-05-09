<?php
/**
 * MATAS Admin sınıfı
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
            'nonce' => wp_create_nonce('matas_admin_nonce')
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
        add_submenu_page(
            'matas',
            __('Katsayılar', 'matas'),
            __('Katsayılar', 'matas'),
            'manage_options',
            'matas-katsayilar',
            array($this, 'display_katsayilar_page')
        );
        
        add_submenu_page(
            'matas',
            __('Ünvan Bilgileri', 'matas'),
            __('Ünvan Bilgileri', 'matas'),
            'manage_options',
            'matas-unvanlar',
            array($this, 'display_unvanlar_page')
        );
        
        add_submenu_page(
            'matas',
            __('Gösterge Puanları', 'matas'),
            __('Gösterge Puanları', 'matas'),
            'manage_options',
            'matas-gostergeler',
            array($this, 'display_gostergeler_page')
        );
        
        add_submenu_page(
            'matas',
            __('Vergi Dilimleri', 'matas'),
            __('Vergi Dilimleri', 'matas'),
            'manage_options',
            'matas-vergiler',
            array($this, 'display_vergiler_page')
        );
        
        add_submenu_page(
            'matas',
            __('Sosyal Yardımlar', 'matas'),
            __('Sosyal Yardımlar', 'matas'),
            'manage_options',
            'matas-sosyal-yardimlar',
            array($this, 'display_sosyal_yardimlar_page')
        );
    }
    
    /**
     * Admin dashboard sayfasını gösterir
     */
    public function display_plugin_admin_dashboard() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }
    
    /**
     * Katsayılar sayfasını gösterir
     */
    public function display_katsayilar_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/katsayilar.php';
    }
    
    /**
     * Ünvanlar sayfasını gösterir
     */
    public function display_unvanlar_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/unvanlar.php';
    }
    
    /**
     * Göstergeler sayfasını gösterir
     */
    public function display_gostergeler_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/gostergeler.php';
    }
    
    /**
     * Vergiler sayfasını gösterir
     */
    public function display_vergiler_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/vergiler.php';
    }
    
    /**
     * Sosyal yardımlar sayfasını gösterir
     */
    public function display_sosyal_yardimlar_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/sosyal-yardimlar.php';
    }
    
    /**
     * Katsayıları kaydetme AJAX işleyicisi
     */
    public function save_katsayilar() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Form verilerini al ve sanitize et
        $donem = sanitize_text_field(isset($_POST['donem']) ? $_POST['donem'] : '');
        $aylik_katsayi = floatval(isset($_POST['aylik_katsayi']) ? $_POST['aylik_katsayi'] : 0);
        $taban_katsayi = floatval(isset($_POST['taban_katsayi']) ? $_POST['taban_katsayi'] : 0);
        $yan_odeme_katsayi = floatval(isset($_POST['yan_odeme_katsayi']) ? $_POST['yan_odeme_katsayi'] : 0);
        
        // Verileri doğrula
        if (empty($donem) || $aylik_katsayi <= 0 || $taban_katsayi <= 0 || $yan_odeme_katsayi <= 0) {
            wp_send_json_error(array('message' => __('Lütfen tüm alanları doldurunuz!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Veritabanı işlemlerini transaction içine al
        $wpdb->query('START TRANSACTION');
        
        try {
            // Eski aktif katsayıları pasif yap
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}matas_katsayilar SET aktif = %d WHERE aktif = %d",
                0, 1
            ));
            
            // Yeni katsayıları ekle
            $result = $wpdb->insert(
                $wpdb->prefix . 'matas_katsayilar',
                array(
                    'donem' => $donem,
                    'aylik_katsayi' => $aylik_katsayi,
                    'taban_katsayi' => $taban_katsayi,
                    'yan_odeme_katsayi' => $yan_odeme_katsayi,
                    'aktif' => 1
                ),
                array('%s', '%f', '%f', '%f', '%d')
            );
            
            if ($result === false) {
                throw new Exception(__('Veritabanı hatası: Katsayılar kaydedilemedi.', 'matas'));
            }
            
            $wpdb->query('COMMIT');
            wp_send_json_success(array('message' => __('Katsayılar başarıyla kaydedildi.', 'matas')));
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Ünvan kaydetme AJAX işleyicisi
     */
    public function save_unvan() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Form verilerini al ve sanitize et
        $unvan_id = isset($_POST['unvan_id']) ? intval($_POST['unvan_id']) : 0;
        $unvan_kodu = sanitize_text_field(isset($_POST['unvan_kodu']) ? $_POST['unvan_kodu'] : '');
        $unvan_adi = sanitize_text_field(isset($_POST['unvan_adi']) ? $_POST['unvan_adi'] : '');
        $ekgosterge = intval(isset($_POST['ekgosterge']) ? $_POST['ekgosterge'] : 0);
        $ozel_hizmet = intval(isset($_POST['ozel_hizmet']) ? $_POST['ozel_hizmet'] : 0);
        $yan_odeme = intval(isset($_POST['yan_odeme']) ? $_POST['yan_odeme'] : 0);
        $is_guclugu = intval(isset($_POST['is_guclugu']) ? $_POST['is_guclugu'] : 0);
        $makam_tazminat = intval(isset($_POST['makam_tazminat']) ? $_POST['makam_tazminat'] : 0);
        $egitim_tazminat = floatval(isset($_POST['egitim_tazminat']) ? $_POST['egitim_tazminat'] : 0);
        
        // Verileri doğrula
        if (empty($unvan_kodu) || empty($unvan_adi)) {
            wp_send_json_error(array('message' => __('Ünvan kodu ve adı zorunludur!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Veritabanı işlemlerini transaction içine al
        $wpdb->query('START TRANSACTION');
        
        try {
            // Ünvan kodunun benzersiz olup olmadığını kontrol et
            if ($unvan_id === 0) { // Yeni ekleme
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}matas_unvan_bilgileri WHERE unvan_kodu = %s",
                    $unvan_kodu
                ));
                
                if ($existing > 0) {
                    throw new Exception(__('Bu ünvan kodu zaten kullanılıyor. Lütfen başka bir kod kullanın.', 'matas'));
                }
            }
            
            $data = array(
                'unvan_kodu' => $unvan_kodu,
                'unvan_adi' => $unvan_adi,
                'ekgosterge' => $ekgosterge,
                'ozel_hizmet' => $ozel_hizmet,
                'yan_odeme' => $yan_odeme,
                'is_guclugu' => $is_guclugu,
                'makam_tazminat' => $makam_tazminat,
                'egitim_tazminat' => $egitim_tazminat
            );
            
            $formats = array('%s', '%s', '%d', '%d', '%d', '%d', '%d', '%f');
            
            // Yeni ünvan mı güncelleme mi kontrol et
            if ($unvan_id > 0) {
                // Güncelleme
                $result = $wpdb->update(
                    $wpdb->prefix . 'matas_unvan_bilgileri',
                    $data,
                    array('id' => $unvan_id),
                    $formats,
                    array('%d')
                );
                
                if ($result === false) {
                    throw new Exception(__('Veritabanı hatası: Ünvan bilgileri güncellenemedi.', 'matas'));
                }
                
                wp_send_json_success(array('message' => __('Ünvan bilgileri başarıyla güncellendi.', 'matas')));
            } else {
                // Yeni ünvan ekle
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_unvan_bilgileri',
                    $data,
                    $formats
                );
                
                if ($result === false) {
                    throw new Exception(__('Veritabanı hatası: Ünvan bilgileri kaydedilemedi.', 'matas'));
                }
                
                wp_send_json_success(array(
                    'message' => __('Ünvan bilgileri başarıyla kaydedildi.', 'matas'),
                    'unvan_id' => $wpdb->insert_id
                ));
            }
            
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Ünvan silme AJAX işleyicisi
     */
    public function delete_unvan() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Silinecek ID'yi al
        $unvan_id = isset($_POST['unvan_id']) ? intval($_POST['unvan_id']) : 0;
        
        if ($unvan_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz ünvan ID!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Ünvanı sil
        $result = $wpdb->delete(
            $wpdb->prefix . 'matas_unvan_bilgileri',
            array('id' => $unvan_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Ünvan silinirken bir hata oluştu.', 'matas')));
            return;
        }
        
        wp_send_json_success(array('message' => __('Ünvan başarıyla silindi.', 'matas')));
    }
    
    /**
     * Gösterge puanı kaydetme AJAX işleyicisi
     */
    public function save_gosterge() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Form verilerini al ve sanitize et
        $gosterge_id = isset($_POST['gosterge_id']) ? intval($_POST['gosterge_id']) : 0;
        $derece = isset($_POST['derece']) ? intval($_POST['derece']) : 0;
        $kademe = isset($_POST['kademe']) ? intval($_POST['kademe']) : 0;
        $gosterge_puani = isset($_POST['gosterge_puani']) ? intval($_POST['gosterge_puani']) : 0;
        
        // Verileri doğrula
        if ($derece <= 0 || $derece > 15 || $kademe <= 0 || $kademe > 9 || $gosterge_puani <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz değerler! Lütfen tüm alanları kontrol ediniz.', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Veritabanı işlemlerini transaction içine al
        $wpdb->query('START TRANSACTION');
        
        try {
            // Göstergenin zaten var olup olmadığını kontrol et
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}matas_gosterge_puanlari WHERE derece = %d AND kademe = %d AND id != %d",
                $derece, $kademe, $gosterge_id
            ));
            
            if ($existing) {
                throw new Exception(__('Bu derece ve kademe için zaten bir gösterge puanı tanımlanmış.', 'matas'));
            }
            
            $data = array(
                'derece' => $derece,
                'kademe' => $kademe,
                'gosterge_puani' => $gosterge_puani
            );
            
            $formats = array('%d', '%d', '%d');
            
            // Yeni gösterge mi güncelleme mi kontrol et
            if ($gosterge_id > 0) {
                // Güncelleme
                $result = $wpdb->update(
                    $wpdb->prefix . 'matas_gosterge_puanlari',
                    $data,
                    array('id' => $gosterge_id),
                    $formats,
                    array('%d')
                );
                
                if ($result === false) {
                    throw new Exception(__('Veritabanı hatası: Gösterge puanı güncellenemedi.', 'matas'));
                }
                
                wp_send_json_success(array('message' => __('Gösterge puanı başarıyla güncellendi.', 'matas')));
            } else {
                // Yeni gösterge ekle
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_gosterge_puanlari',
                    $data,
                    $formats
                );
                
                if ($result === false) {
                    throw new Exception(__('Veritabanı hatası: Gösterge puanı kaydedilemedi.', 'matas'));
                }
                
                wp_send_json_success(array(
                    'message' => __('Gösterge puanı başarıyla kaydedildi.', 'matas'),
                    'gosterge_id' => $wpdb->insert_id
                ));
            }
            
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Vergi dilimi kaydetme AJAX işleyicisi
     */
    public function save_vergi_dilimi() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Form verilerini al ve sanitize et
        $vergi_id = isset($_POST['vergi_id']) ? intval($_POST['vergi_id']) : 0;
        $yil = isset($_POST['yil']) ? intval($_POST['yil']) : 0;
        $dilim = isset($_POST['dilim']) ? intval($_POST['dilim']) : 0;
        $alt_limit = isset($_POST['alt_limit']) ? floatval($_POST['alt_limit']) : 0;
        $ust_limit = isset($_POST['ust_limit']) ? floatval($_POST['ust_limit']) : 0;
        $oran = isset($_POST['oran']) ? floatval($_POST['oran']) : 0;
        
        // Verileri doğrula
        if ($yil <= 0 || $dilim <= 0 || $oran <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz değerler! Lütfen tüm alanları kontrol ediniz.', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Veritabanı işlemlerini transaction içine al
        $wpdb->query('START TRANSACTION');
        
        try {
            // Dilimin zaten var olup olmadığını kontrol et
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}matas_vergiler WHERE yil = %d AND dilim = %d AND id != %d",
                $yil, $dilim, $vergi_id
            ));
            
            if ($existing) {
                throw new Exception(__('Bu yıl ve dilim için zaten bir vergi dilimi tanımlanmış.', 'matas'));
            }
            
            $data = array(
                'yil' => $yil,
                'dilim' => $dilim,
                'alt_limit' => $alt_limit,
                'ust_limit' => $ust_limit,
                'oran' => $oran
            );
            
            $formats = array('%d', '%d', '%f', '%f', '%f');
            
            // Yeni dilim mi güncelleme mi kontrol et
            if ($vergi_id > 0) {
                // Güncelleme
                $result = $wpdb->update(
                    $wpdb->prefix . 'matas_vergiler',
                    $data,
                    array('id' => $vergi_id),
                    $formats,
                    array('%d')
                );
                
                if ($result === false) {
                    throw new Exception(__('Veritabanı hatası: Vergi dilimi güncellenemedi.', 'matas'));
                }
                
                wp_send_json_success(array('message' => __('Vergi dilimi başarıyla güncellendi.', 'matas')));
            } else {
                // Yeni dilim ekle
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_vergiler',
                    $data,
                    $formats
                );
                
                if ($result === false) {
                    throw new Exception(__('Veritabanı hatası: Vergi dilimi kaydedilemedi.', 'matas'));
                }
                
                wp_send_json_success(array(
                    'message' => __('Vergi dilimi başarıyla kaydedildi.', 'matas'),
                    'vergi_id' => $wpdb->insert_id
                ));
            }
            
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Sosyal yardım kaydetme AJAX işleyicisi
     */
    public function save_sosyal_yardim() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Form verilerini al ve sanitize et
        $yardim_id = isset($_POST['yardim_id']) ? intval($_POST['yardim_id']) : 0;
        $yil = isset($_POST['yil']) ? intval($_POST['yil']) : 0;
        $tip = isset($_POST['tip']) ? sanitize_text_field($_POST['tip']) : '';
        $adi = isset($_POST['adi']) ? sanitize_text_field($_POST['adi']) : '';
        $tutar = isset($_POST['tutar']) ? floatval($_POST['tutar']) : 0;
        
        // Verileri doğrula
        if ($yil <= 0 || empty($tip) || empty($adi) || $tutar <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz değerler! Lütfen tüm alanları kontrol ediniz.', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Veritabanı işlemlerini transaction içine al
        $wpdb->query('START TRANSACTION');
        
        try {
            // Yardımın zaten var olup olmadığını kontrol et
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}matas_sosyal_yardimlar WHERE yil = %d AND tip = %s AND id != %d",
                $yil, $tip, $yardim_id
            ));
            
            if ($existing) {
                throw new Exception(__('Bu yıl ve tip için zaten bir sosyal yardım tanımlanmış.', 'matas'));
            }
            
            $data = array(
                'yil' => $yil,
                'tip' => $tip,
                'adi' => $adi,
                'tutar' => $tutar
            );
            
            $formats = array('%d', '%s', '%s', '%f');
            
            // Yeni yardım mı güncelleme mi kontrol et
            if ($yardim_id > 0) {
                // Güncelleme
                $result = $wpdb->update(
                    $wpdb->prefix . 'matas_sosyal_yardimlar',
                    $data,
                    array('id' => $yardim_id),
                    $formats,
                    array('%d')
                );
                
                if ($result === false) {
                    throw new Exception(__('Veritabanı hatası: Sosyal yardım güncellenemedi.', 'matas'));
                }
                
                wp_send_json_success(array('message' => __('Sosyal yardım başarıyla güncellendi.', 'matas')));
            } else {
                // Yeni yardım ekle
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_sosyal_yardimlar',
                    $data,
                    $formats
                );
                
                if ($result === false) {
                    throw new Exception(__('Veritabanı hatası: Sosyal yardım kaydedilemedi.', 'matas'));
                }
                
                wp_send_json_success(array(
                    'message' => __('Sosyal yardım başarıyla kaydedildi.', 'matas'),
                    'yardim_id' => $wpdb->insert_id
                ));
            }
            
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Ünvan detaylarını getirme AJAX işleyicisi
     */
    public function get_unvan() {
      public function get_unvan() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Unvan ID'sini al
        $unvan_id = isset($_POST['unvan_id']) ? intval($_POST['unvan_id']) : 0;
        
        if ($unvan_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz ünvan ID!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Unvan bilgilerini al
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
    }
    
    /**
     * Gösterge detaylarını getirme AJAX işleyicisi
     */
    public function get_gosterge() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Gösterge ID'sini al
        $gosterge_id = isset($_POST['gosterge_id']) ? intval($_POST['gosterge_id']) : 0;
        
        if ($gosterge_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz gösterge ID!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Gösterge bilgilerini al
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
    }
    
    /**
     * Gösterge silme AJAX işleyicisi
     */
    public function delete_gosterge() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Gösterge ID'sini al
        $gosterge_id = isset($_POST['gosterge_id']) ? intval($_POST['gosterge_id']) : 0;
        
        if ($gosterge_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz gösterge ID!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Göstergeyi sil
        $result = $wpdb->delete(
            $wpdb->prefix . 'matas_gosterge_puanlari',
            array('id' => $gosterge_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Gösterge silinirken bir hata oluştu.', 'matas')));
            return;
        }
        
        wp_send_json_success(array('message' => __('Gösterge başarıyla silindi.', 'matas')));
    }
    
    /**
     * Varsayılan göstergeleri yükleme AJAX işleyicisi
     */
    public function load_default_gostergeler() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Veritabanı işlemlerini transaction içine al
        $wpdb->query('START TRANSACTION');
        
        try {
            // Mevcut göstergeleri sil
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}matas_gosterge_puanlari");
            
            // Varsayılan göstergeleri ekle
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
                
                // Diğer dereceler ve kademeler...
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
            );
            
            $success_count = 0;
            foreach ($gosterge_puanlari as $gosterge) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_gosterge_puanlari',
                    $gosterge,
                    array('%d', '%d', '%d')
                );
                
                if ($result) {
                    $success_count++;
                }
            }
            
            $wpdb->query('COMMIT');
            
            wp_send_json_success(array(
                'message' => sprintf(__('Varsayılan gösterge puanları başarıyla yüklendi. Toplam %d gösterge eklendi.', 'matas'), $success_count)
            ));
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Vergi dilimi detaylarını getirme AJAX işleyicisi
     */
    public function get_vergi_dilimi() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Vergi ID'sini al
        $vergi_id = isset($_POST['vergi_id']) ? intval($_POST['vergi_id']) : 0;
        
        if ($vergi_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz vergi ID!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Vergi bilgilerini al
        $vergi = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}matas_vergiler WHERE id = %d",
                $vergi_id
            ),
            ARRAY_A
        );
        
        if (!$vergi) {
            wp_send_json_error(array('message' => __('Vergi dilimi bulunamadı!', 'matas')));
            return;
        }
        
        wp_send_json_success(array('vergi' => $vergi));
    }
    
    /**
     * Vergi dilimi silme AJAX işleyicisi
     */
    public function delete_vergi_dilimi() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Vergi ID'sini al
        $vergi_id = isset($_POST['vergi_id']) ? intval($_POST['vergi_id']) : 0;
        
        if ($vergi_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz vergi ID!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Vergiyi sil
        $result = $wpdb->delete(
            $wpdb->prefix . 'matas_vergiler',
            array('id' => $vergi_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Vergi dilimi silinirken bir hata oluştu.', 'matas')));
            return;
        }
        
        wp_send_json_success(array('message' => __('Vergi dilimi başarıyla silindi.', 'matas')));
    }
    
    /**
     * Varsayılan vergi dilimlerini yükleme AJAX işleyicisi
     */
    public function load_default_vergiler() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Yıl parametresini al
        $yil = isset($_POST['yil']) ? intval($_POST['yil']) : date('Y');
        
        if ($yil <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz yıl değeri!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Veritabanı işlemlerini transaction içine al
        $wpdb->query('START TRANSACTION');
        
        try {
            // Mevcut vergi dilimlerini sil
            $wpdb->delete(
                $wpdb->prefix . 'matas_vergiler',
                array('yil' => $yil),
                array('%d')
            );
            
            // Varsayılan vergi dilimlerini ekle
            $vergi_dilimleri = array(
                array('yil' => $yil, 'dilim' => 1, 'alt_limit' => 0, 'ust_limit' => 70000, 'oran' => 15),
                array('yil' => $yil, 'dilim' => 2, 'alt_limit' => 70000, 'ust_limit' => 150000, 'oran' => 20),
                array('yil' => $yil, 'dilim' => 3, 'alt_limit' => 150000, 'ust_limit' => 550000, 'oran' => 27),
                array('yil' => $yil, 'dilim' => 4, 'alt_limit' => 550000, 'ust_limit' => 1900000, 'oran' => 35),
                array('yil' => $yil, 'dilim' => 5, 'alt_limit' => 1900000, 'ust_limit' => 0, 'oran' => 40),
            );
            
            $success_count = 0;
            foreach ($vergi_dilimleri as $dilim) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_vergiler',
                    $dilim,
                    array('%d', '%d', '%f', '%f', '%f')
                );
                
                if ($result) {
                    $success_count++;
                }
            }
            
            $wpdb->query('COMMIT');
            
            wp_send_json_success(array(
                'message' => sprintf(__('%s yılı için varsayılan vergi dilimleri başarıyla yüklendi. Toplam %d dilim eklendi.', 'matas'), $yil, $success_count)
            ));
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Sosyal yardım detaylarını getirme AJAX işleyicisi
     */
    public function get_sosyal_yardim() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Yardım ID'sini al
        $yardim_id = isset($_POST['yardim_id']) ? intval($_POST['yardim_id']) : 0;
        
        if ($yardim_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz yardım ID!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Yardım bilgilerini al
        $yardim = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}matas_sosyal_yardimlar WHERE id = %d",
                $yardim_id
            ),
            ARRAY_A
        );
        
        if (!$yardim) {
            wp_send_json_error(array('message' => __('Sosyal yardım bulunamadı!', 'matas')));
            return;
        }
        
        wp_send_json_success(array('yardim' => $yardim));
    }
    
    /**
     * Sosyal yardım silme AJAX işleyicisi
     */
    public function delete_sosyal_yardim() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Yardım ID'sini al
        $yardim_id = isset($_POST['yardim_id']) ? intval($_POST['yardim_id']) : 0;
        
        if ($yardim_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz yardım ID!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Yardımı sil
        $result = $wpdb->delete(
            $wpdb->prefix . 'matas_sosyal_yardimlar',
            array('id' => $yardim_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Sosyal yardım silinirken bir hata oluştu.', 'matas')));
            return;
        }
        
        wp_send_json_success(array('message' => __('Sosyal yardım başarıyla silindi.', 'matas')));
    }
    
    /**
     * Varsayılan sosyal yardımları yükleme AJAX işleyicisi
     */
    public function load_default_sosyal_yardimlar() {
        // Güvenlik kontrolü
        if (!check_ajax_referer('matas_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Güvenlik doğrulaması başarısız!', 'matas')));
            return;
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Yetkiniz yok!', 'matas')));
            return;
        }
        
        // Yıl parametresini al
        $yil = isset($_POST['yil']) ? intval($_POST['yil']) : date('Y');
        
        if ($yil <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz yıl değeri!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        // Veritabanı işlemlerini transaction içine al
        $wpdb->query('START TRANSACTION');
        
        try {
            // Mevcut sosyal yardımları sil
            $wpdb->delete(
                $wpdb->prefix . 'matas_sosyal_yardimlar',
                array('yil' => $yil),
                array('%d')
            );
            
            // Varsayılan sosyal yardımları ekle
            $sosyal_yardimlar = array(
                array('yil' => $yil, 'tip' => 'aile_yardimi', 'adi' => 'Aile Yardımı', 'tutar' => 1200),
                array('yil' => $yil, 'tip' => 'cocuk_normal', 'adi' => 'Çocuk Yardımı', 'tutar' => 150),
                array('yil' => $yil, 'tip' => 'cocuk_0_6', 'adi' => '0-6 Yaş Çocuk Yardımı', 'tutar' => 300),
                array('yil' => $yil, 'tip' => 'cocuk_engelli', 'adi' => 'Engelli Çocuk Yardımı', 'tutar' => 600),
                array('yil' => $yil, 'tip' => 'cocuk_ogrenim', 'adi' => 'Öğrenim Çocuk Yardımı', 'tutar' => 250),
                array('yil' => $yil, 'tip' => 'kira_yardimi', 'adi' => 'Kira Yardımı', 'tutar' => 2000),
                array('yil' => $yil, 'tip' => 'sendika_yardimi', 'adi' => 'Sendika Yardımı', 'tutar' => 500),
                array('yil' => $yil, 'tip' => 'yemek_yardimi', 'adi' => 'Yemek Yardımı', 'tutar' => 1200),
                array('yil' => $yil, 'tip' => 'giyecek_yardimi', 'adi' => 'Giyecek Yardımı', 'tutar' => 800),
                array('yil' => $yil, 'tip' => 'yakacak_yardimi', 'adi' => 'Yakacak Yardımı', 'tutar' => 1100),
            );
            
            $success_count = 0;
            foreach ($sosyal_yardimlar as $yardim) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_sosyal_yardimlar',
                    $yardim,
                    array('%d', '%s', '%s', '%f')
                );
                
                if ($result) {
                    $success_count++;
                }
            }
            
            $wpdb->query('COMMIT');
            
            wp_send_json_success(array(
                'message' => sprintf(__('%s yılı için varsayılan sosyal yardımlar başarıyla yüklendi. Toplam %d yardım eklendi.', 'matas'), $yil, $success_count)
            ));
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
}

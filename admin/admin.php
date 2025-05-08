<?php
class Matas_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name . '-admin', MATAS_PLUGIN_URL . 'admin/css/admin.css', array(), $this->version, 'all');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name . '-admin', MATAS_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), $this->version, false);
        
        wp_localize_script($this->plugin_name . '-admin', 'matas_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('matas_admin_nonce')
        ));
    }
    
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
    
    public function display_plugin_admin_dashboard() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }
    
    public function display_katsayilar_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/katsayilar.php';
    }
    
    public function display_unvanlar_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/unvanlar.php';
    }
    
    public function display_gostergeler_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/gostergeler.php';
    }
    
    public function display_vergiler_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/vergiler.php';
    }
    
    public function display_sosyal_yardimlar_page() {
        include_once MATAS_PLUGIN_DIR . 'admin/partials/sosyal-yardimlar.php';
    }
    
    // AJAX işleyicileri
    public function save_katsayilar() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }
        
        // Form verilerini al ve veritabanına kaydet
        $donem = sanitize_text_field($_POST['donem']);
        $aylik_katsayi = floatval($_POST['aylik_katsayi']);
        $taban_katsayi = floatval($_POST['taban_katsayi']);
        $yan_odeme_katsayi = floatval($_POST['yan_odeme_katsayi']);
        
        global $wpdb;
        
        // Eski aktif katsayıları pasif yap
        $wpdb->update(
            $wpdb->prefix . 'matas_katsayilar',
            array('aktif' => 0),
            array('aktif' => 1)
        );
        
        // Yeni katsayıları ekle
        $result = $wpdb->insert(
            $wpdb->prefix . 'matas_katsayilar',
            array(
                'donem' => $donem,
                'aylik_katsayi' => $aylik_katsayi,
                'taban_katsayi' => $taban_katsayi,
                'yan_odeme_katsayi' => $yan_odeme_katsayi,
                'aktif' => 1
            )
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Katsayılar başarıyla kaydedildi.'));
        } else {
            wp_send_json_error(array('message' => 'Katsayılar kaydedilirken bir hata oluştu.'));
        }
    }
    
 public function save_unvan() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }
        
        // Form verilerini al
        $unvan_id = isset($_POST['unvan_id']) ? intval($_POST['unvan_id']) : 0;
        $unvan_kodu = sanitize_text_field($_POST['unvan_kodu']);
        $unvan_adi = sanitize_text_field($_POST['unvan_adi']);
        $ekgosterge = intval($_POST['ekgosterge']);
        $ozel_hizmet = intval($_POST['ozel_hizmet']);
        $yan_odeme = intval($_POST['yan_odeme']);
        $is_guclugu = intval($_POST['is_guclugu']);
        $makam_tazminat = intval($_POST['makam_tazminat']);
        $egitim_tazminat = floatval($_POST['egitim_tazminat']);
        
        global $wpdb;
        
        // Yeni ünvan mı güncelleme mi kontrol et
        if ($unvan_id > 0) {
            // Güncelleme
            $result = $wpdb->update(
                $wpdb->prefix . 'matas_unvan_bilgileri',
                array(
                    'unvan_kodu' => $unvan_kodu,
                    'unvan_adi' => $unvan_adi,
                    'ekgosterge' => $ekgosterge,
                    'ozel_hizmet' => $ozel_hizmet,
                    'yan_odeme' => $yan_odeme,
                    'is_guclugu' => $is_guclugu,
                    'makam_tazminat' => $makam_tazminat,
                    'egitim_tazminat' => $egitim_tazminat
                ),
                array('id' => $unvan_id)
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Ünvan bilgileri başarıyla güncellendi.'));
            } else {
                wp_send_json_error(array('message' => 'Ünvan bilgileri güncellenirken bir hata oluştu.'));
            }
        } else {
            // Yeni ünvan ekle
            $result = $wpdb->insert(
                $wpdb->prefix . 'matas_unvan_bilgileri',
                array(
                    'unvan_kodu' => $unvan_kodu,
                    'unvan_adi' => $unvan_adi,
                    'ekgosterge' => $ekgosterge,
                    'ozel_hizmet' => $ozel_hizmet,
                    'yan_odeme' => $yan_odeme,
                    'is_guclugu' => $is_guclugu,
                    'makam_tazminat' => $makam_tazminat,
                    'egitim_tazminat' => $egitim_tazminat
                )
            );
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Ünvan bilgileri başarıyla kaydedildi.',
                    'unvan_id' => $wpdb->insert_id
                ));
            } else {
                wp_send_json_error(array('message' => 'Ünvan bilgileri kaydedilirken bir hata oluştu.'));
            }
        }
    }
    
    public function delete_unvan() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }
        
        // Silinecek ID'yi al
        $unvan_id = intval($_POST['unvan_id']);
        
        global $wpdb;
        
        // Ünvanı sil
        $result = $wpdb->delete(
            $wpdb->prefix . 'matas_unvan_bilgileri',
            array('id' => $unvan_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Ünvan başarıyla silindi.'));
        } else {
            wp_send_json_error(array('message' => 'Ünvan silinirken bir hata oluştu.'));
        }
    }
    
    public function save_gosterge() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }
        
        // Form verilerini al
        $gosterge_id = isset($_POST['gosterge_id']) ? intval($_POST['gosterge_id']) : 0;
        $derece = intval($_POST['derece']);
        $kademe = intval($_POST['kademe']);
        $gosterge_puani = intval($_POST['gosterge_puani']);
        
        global $wpdb;
        
        // Göstergenin zaten var olup olmadığını kontrol et
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}matas_gosterge_puanlari WHERE derece = %d AND kademe = %d AND id != %d",
            $derece, $kademe, $gosterge_id
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => 'Bu derece ve kademe için zaten bir gösterge puanı tanımlanmış.'));
            return;
        }
        
        // Yeni gösterge mi güncelleme mi kontrol et
        if ($gosterge_id > 0) {
            // Güncelleme
            $result = $wpdb->update(
                $wpdb->prefix . 'matas_gosterge_puanlari',
                array(
                    'derece' => $derece,
                    'kademe' => $kademe,
                    'gosterge_puani' => $gosterge_puani
                ),
                array('id' => $gosterge_id)
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Gösterge puanı başarıyla güncellendi.'));
            } else {
                wp_send_json_error(array('message' => 'Gösterge puanı güncellenirken bir hata oluştu.'));
            }
        } else {
            // Yeni gösterge ekle
            $result = $wpdb->insert(
                $wpdb->prefix . 'matas_gosterge_puanlari',
                array(
                    'derece' => $derece,
                    'kademe' => $kademe,
                    'gosterge_puani' => $gosterge_puani
                )
            );
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Gösterge puanı başarıyla kaydedildi.',
                    'gosterge_id' => $wpdb->insert_id
                ));
            } else {
                wp_send_json_error(array('message' => 'Gösterge puanı kaydedilirken bir hata oluştu.'));
            }
        }
    }
    
    public function save_vergi_dilimi() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }
        
        // Form verilerini al
        $vergi_id = isset($_POST['vergi_id']) ? intval($_POST['vergi_id']) : 0;
        $yil = intval($_POST['yil']);
        $dilim = intval($_POST['dilim']);
        $alt_limit = floatval($_POST['alt_limit']);
        $ust_limit = floatval($_POST['ust_limit']);
        $oran = floatval($_POST['oran']);
        
        global $wpdb;
        
        // Dilimin zaten var olup olmadığını kontrol et
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}matas_vergiler WHERE yil = %d AND dilim = %d AND id != %d",
            $yil, $dilim, $vergi_id
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => 'Bu yıl ve dilim için zaten bir vergi dilimi tanımlanmış.'));
            return;
        }
        
        // Yeni dilim mi güncelleme mi kontrol et
        if ($vergi_id > 0) {
            // Güncelleme
            $result = $wpdb->update(
                $wpdb->prefix . 'matas_vergiler',
                array(
                    'yil' => $yil,
                    'dilim' => $dilim,
                    'alt_limit' => $alt_limit,
                    'ust_limit' => $ust_limit,
                    'oran' => $oran
                ),
                array('id' => $vergi_id)
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Vergi dilimi başarıyla güncellendi.'));
            } else {
                wp_send_json_error(array('message' => 'Vergi dilimi güncellenirken bir hata oluştu.'));
            }
        } else {
            // Yeni dilim ekle
            $result = $wpdb->insert(
                $wpdb->prefix . 'matas_vergiler',
                array(
                    'yil' => $yil,
                    'dilim' => $dilim,
                    'alt_limit' => $alt_limit,
                    'ust_limit' => $ust_limit,
                    'oran' => $oran
                )
            );
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Vergi dilimi başarıyla kaydedildi.',
                    'vergi_id' => $wpdb->insert_id
                ));
            } else {
                wp_send_json_error(array('message' => 'Vergi dilimi kaydedilirken bir hata oluştu.'));
            }
        }
    }
    
    public function save_sosyal_yardim() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
        }
        
        // Form verilerini al
        $yardim_id = isset($_POST['yardim_id']) ? intval($_POST['yardim_id']) : 0;
        $yil = intval($_POST['yil']);
        $tip = sanitize_text_field($_POST['tip']);
        $adi = sanitize_text_field($_POST['adi']);
        $tutar = floatval($_POST['tutar']);
        
        global $wpdb;
        
        // Yardımın zaten var olup olmadığını kontrol et
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}matas_sosyal_yardimlar WHERE yil = %d AND tip = %s AND id != %d",
            $yil, $tip, $yardim_id
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => 'Bu yıl ve tip için zaten bir sosyal yardım tanımlanmış.'));
            return;
        }
        
        // Yeni yardım mı güncelleme mi kontrol et
        if ($yardim_id > 0) {
            // Güncelleme
            $result = $wpdb->update(
                $wpdb->prefix . 'matas_sosyal_yardimlar',
                array(
                    'yil' => $yil,
                    'tip' => $tip,
                    'adi' => $adi,
                    'tutar' => $tutar
                ),
                array('id' => $yardim_id)
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Sosyal yardım başarıyla güncellendi.'));
            } else {
                wp_send_json_error(array('message' => 'Sosyal yardım güncellenirken bir hata oluştu.'));
            }
        } else {
            // Yeni yardım ekle
            $result = $wpdb->insert(
                $wpdb->prefix . 'matas_sosyal_yardimlar',
                array(
                    'yil' => $yil,
                    'tip' => $tip,
                    'adi' => $adi,
                    'tutar' => $tutar
                )
            );
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => 'Sosyal yardım başarıyla kaydedildi.',
                    'yardim_id' => $wpdb->insert_id
                ));
            } else {
                wp_send_json_error(array('message' => 'Sosyal yardım kaydedilirken bir hata oluştu.'));
            }
        }
    } => $makam_tazminat,
                    'egitim_tazminat' => $egitim_tazminat
                ),
                array('id' => $unvan_id)
            );
            
            if ($result !== false) {
                wp_send_json_success(array('message' => 'Ünvan bilgileri başarıyla güncellendi.'));
            } else {
                wp_send_json_error(array('message' => 'Ünvan bilgileri güncellenirken bir hata oluştu.'));
            }
        } else {
            // Yeni ünvan ekle
            $result = $wpdb->insert(
                $wpdb->prefix . 'matas_unvan_bilgileri',
                array(
                    'unvan_kodu' => $unvan_kodu,
                    'unvan_adi' => $unvan_adi,
                    'ekgosterge' => $ekgosterge,
                    'ozel_hizmet' => $ozel_hizmet,
                    'yan_odeme' => $yan_odeme,
                    'is_guclugu' => $is_guclugu,
                    'makam_tazminat' 

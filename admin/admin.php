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
    }

/**
     * AJAX handler for getting unvan details
     */
    public function get_unvan() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
            return;
        }
        
        // Unvan ID'sini al
        $unvan_id = isset($_POST['unvan_id']) ? intval($_POST['unvan_id']) : 0;
        
        if ($unvan_id <= 0) {
            wp_send_json_error(array('message' => 'Geçersiz ünvan ID!'));
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
            wp_send_json_error(array('message' => 'Ünvan bulunamadı!'));
            return;
        }
        
        wp_send_json_success(array('unvan' => $unvan));
    }
    
    /**
     * AJAX handler for getting gosterge details
     */
    public function get_gosterge() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
            return;
        }
        
        // Gösterge ID'sini al
        $gosterge_id = isset($_POST['gosterge_id']) ? intval($_POST['gosterge_id']) : 0;
        
        if ($gosterge_id <= 0) {
            wp_send_json_error(array('message' => 'Geçersiz gösterge ID!'));
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
            wp_send_json_error(array('message' => 'Gösterge bulunamadı!'));
            return;
        }
        
        wp_send_json_success(array('gosterge' => $gosterge));
    }
    
    /**
     * AJAX handler for deleting gosterge
     */
    public function delete_gosterge() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
            return;
        }
        
        // Gösterge ID'sini al
        $gosterge_id = isset($_POST['gosterge_id']) ? intval($_POST['gosterge_id']) : 0;
        
        if ($gosterge_id <= 0) {
            wp_send_json_error(array('message' => 'Geçersiz gösterge ID!'));
            return;
        }
        
        global $wpdb;
        
        // Göstergeyi sil
        $result = $wpdb->delete(
            $wpdb->prefix . 'matas_gosterge_puanlari',
            array('id' => $gosterge_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Gösterge başarıyla silindi.'));
        } else {
            wp_send_json_error(array('message' => 'Gösterge silinirken bir hata oluştu.'));
        }
    }
    
    /**
     * AJAX handler for loading default gostergeler
     */
    public function load_default_gostergeler() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
            return;
        }
        
        global $wpdb;
        
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
        );
        
        $success_count = 0;
        foreach ($gosterge_puanlari as $gosterge) {
            $result = $wpdb->insert(
                $wpdb->prefix . 'matas_gosterge_puanlari',
                $gosterge
            );
            
            if ($result) {
                $success_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('Varsayılan gösterge puanları başarıyla yüklendi. Toplam %d gösterge eklendi.', $success_count)
        ));
    }
    /**
     * AJAX handler for getting vergi dilimi details
     */
    public function get_vergi_dilimi() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
            return;
        }
        
        // Vergi ID'sini al
        $vergi_id = isset($_POST['vergi_id']) ? intval($_POST['vergi_id']) : 0;
        
        if ($vergi_id <= 0) {
            wp_send_json_error(array('message' => 'Geçersiz vergi ID!'));
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
            wp_send_json_error(array('message' => 'Vergi dilimi bulunamadı!'));
            return;
        }
        
        wp_send_json_success(array('vergi' => $vergi));
    }
    
    /**
     * AJAX handler for deleting vergi dilimi
     */
    public function delete_vergi_dilimi() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
            return;
        }
        
        // Vergi ID'sini al
        $vergi_id = isset($_POST['vergi_id']) ? intval($_POST['vergi_id']) : 0;
        
        if ($vergi_id <= 0) {
            wp_send_json_error(array('message' => 'Geçersiz vergi ID!'));
            return;
        }
        
        global $wpdb;
        
        // Vergiyi sil
        $result = $wpdb->delete(
            $wpdb->prefix . 'matas_vergiler',
            array('id' => $vergi_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Vergi dilimi başarıyla silindi.'));
        } else {
            wp_send_json_error(array('message' => 'Vergi dilimi silinirken bir hata oluştu.'));
        }
    }
    
    /**
     * AJAX handler for loading default vergiler
     */
    public function load_default_vergiler() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
            return;
        }
        
        // Yıl parametresini al
        $yil = isset($_POST['yil']) ? intval($_POST['yil']) : date('Y');
        
        global $wpdb;
        
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
                $dilim
            );
            
            if ($result) {
                $success_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%s yılı için varsayılan vergi dilimleri başarıyla yüklendi. Toplam %d dilim eklendi.', $yil, $success_count)
        ));
    }
    
    /**
     * AJAX handler for getting sosyal yardim details
     */
    public function get_sosyal_yardim() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
            return;
        }
        
        // Yardım ID'sini al
        $yardim_id = isset($_POST['yardim_id']) ? intval($_POST['yardim_id']) : 0;
        
        if ($yardim_id <= 0) {
            wp_send_json_error(array('message' => 'Geçersiz yardım ID!'));
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
            wp_send_json_error(array('message' => 'Sosyal yardım bulunamadı!'));
            return;
        }
        
        wp_send_json_success(array('yardim' => $yardim));
    }
    
    /**
     * AJAX handler for deleting sosyal yardim
     */
    public function delete_sosyal_yardim() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
            return;
        }
        
        // Yardım ID'sini al
        $yardim_id = isset($_POST['yardim_id']) ? intval($_POST['yardim_id']) : 0;
        
        if ($yardim_id <= 0) {
            wp_send_json_error(array('message' => 'Geçersiz yardım ID!'));
            return;
        }
        
        global $wpdb;
        
        // Yardımı sil
        $result = $wpdb->delete(
            $wpdb->prefix . 'matas_sosyal_yardimlar',
            array('id' => $yardim_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => 'Sosyal yardım başarıyla silindi.'));
        } else {
            wp_send_json_error(array('message' => 'Sosyal yardım silinirken bir hata oluştu.'));
        }
    }
    
    /**
     * AJAX handler for loading default sosyal yardimlar
     */
    public function load_default_sosyal_yardimlar() {
        // Güvenlik kontrolü
        check_ajax_referer('matas_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok!'));
            return;
        }
        
        // Yıl parametresini al
        $yil = isset($_POST['yil']) ? intval($_POST['yil']) : date('Y');
        
        global $wpdb;
        
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
        );
        
        $success_count = 0;
        foreach ($sosyal_yardimlar as $yardim) {
            $result = $wpdb->insert(
                $wpdb->prefix . 'matas_sosyal_yardimlar',
                $yardim
            );
            
            if ($result) {
                $success_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%s yılı için varsayılan sosyal yardımlar başarıyla yüklendi. Toplam %d yardım eklendi.', $yil, $success_count)
        ));
    }
}

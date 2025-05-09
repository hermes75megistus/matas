<?php
/**
 * Maaş hesaplama kısa kodu sınıfı
 * 
 * @package MATAS
 * @since 1.0.0
 */

class Matas_Shortcode {
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
     * Hesaplama sınıfı
     *
     * @var Matas_Calculator
     */
    private $calculator;

    /**
     * Sınıfı başlat
     *
     * @param string $plugin_name Eklenti ismi
     * @param string $version Eklenti versiyonu
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Calculator sınıfını yükle
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-matas-calculator.php';
        $this->calculator = new Matas_Calculator($plugin_name, $version);
    }
    
    /**
     * Frontend stil dosyalarını ekler
     */
    public function enqueue_styles() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_style(
            $this->plugin_name . '-calculator', 
            MATAS_PLUGIN_URL . 'public/css/style' . $suffix . '.css', 
            array(), 
            $this->version, 
            'all'
        );
    }
    
    /**
     * Frontend script dosyalarını ekler
     */
    public function enqueue_scripts() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_script(
            $this->plugin_name . '-constants', 
            MATAS_PLUGIN_URL . 'public/js/constants' . $suffix . '.js', 
            array('jquery'), 
            $this->version, 
            true
        );
        
        wp_enqueue_script(
            $this->plugin_name . '-functions', 
            MATAS_PLUGIN_URL . 'public/js/functions' . $suffix . '.js', 
            array('jquery', $this->plugin_name . '-constants'), 
            $this->version, 
            true
        );
        
        wp_enqueue_script(
            $this->plugin_name . '-calculator', 
            MATAS_PLUGIN_URL . 'public/js/calculator' . $suffix . '.js', 
            array('jquery', $this->plugin_name . '-functions'), 
            $this->version, 
            true
        );
        
        wp_localize_script($this->plugin_name . '-calculator', 'matas_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('matas_calculator_nonce'),
            'current_year' => date('Y'),
            'plugin_url' => MATAS_PLUGIN_URL,
        ));
    }
    
    /**
     * Maaş hesaplama formunu gösteren kısa kodu işler
     *
     * @param array $atts Kısa kod parametreleri
     * @return string HTML çıktısı
     */
    public function display_calculator($atts) {
        // Kısa kod parametrelerini birleştir
        $atts = shortcode_atts(array(
            'baslik' => __('Memur Maaş Hesaplama', 'matas'),
            'stil' => 'modern',
        ), $atts, 'matas_hesaplama');
        
        // CSS sınıfları oluştur
        $container_class = 'matas-container';
        if (!empty($atts['stil'])) {
            $container_class .= ' matas-' . sanitize_html_class($atts['stil']);
        }
        
        // Çıktı tamponlamasını başlat
        ob_start();
        
        // Form şablonunu dahil et
        include MATAS_PLUGIN_DIR . 'public/partials/calculator-form.php';
        
        // Tamponu döndür
        return ob_get_clean();
    }
    
    /**
     * Maaş hesaplama işlemini gerçekleştirir (AJAX)
     */
    public function calculate_salary() {
        // Nonce kontrolü
        if (!check_ajax_referer('matas_calculator_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'success' => false,
                'message' => __('Güvenlik doğrulaması başarısız!', 'matas')
            ));
            return;
        }
        
        // Form verilerini al ve sanitize et
        $params = array();
        $form_fields = array(
            'unvan', 'derece', 'kademe', 'hizmet_yili', 'medeni_hal', 'es_calisiyor',
            'cocuk_sayisi', 'cocuk_06', 'engelli_cocuk', 'ogrenim_cocuk', 'egitim_durumu',
            'dil_seviyesi', 'dil_kullanimi', 'gorev_tazminati', 'gelistirme_odenegi',
            'asgari_gecim_indirimi', 'kira_yardimi', 'sendika_uyesi'
        );
        
        foreach ($form_fields as $field) {
            if (isset($_POST[$field])) {
                if (in_array($field, array('unvan', 'medeni_hal', 'es_calisiyor', 'egitim_durumu', 'dil_seviyesi', 'dil_kullanimi'))) {
                    $params[$field] = sanitize_text_field($_POST[$field]);
                } elseif (in_array($field, array('derece', 'kademe', 'hizmet_yili', 'cocuk_sayisi', 'cocuk_06', 'engelli_cocuk', 'ogrenim_cocuk'))) {
                    $params[$field] = intval($_POST[$field]);
                } elseif (in_array($field, array('gorev_tazminati', 'gelistirme_odenegi', 'asgari_gecim_indirimi', 'kira_yardimi', 'sendika_uyesi'))) {
                    $params[$field] = (isset($_POST[$field]) && $_POST[$field] == '1') ? 1 : 0;
                }
            }
        }
        
        // Veri doğrulama
        $required_fields = array('unvan', 'derece', 'kademe', 'hizmet_yili');
        foreach ($required_fields as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                wp_send_json_error(array(
                    'success' => false,
                    'message' => __('Lütfen tüm zorunlu alanları doldurunuz.', 'matas')
                ));
                return;
            }
        }
        
        // Hesaplama işlemini yap
        try {
            $result = $this->calculator->calculate_salary($params);
            
            // Sonucu döndür
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(array(
                    'success' => false,
                    'message' => $result['message']
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }
}

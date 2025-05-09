<?php
class Matas_Shortcode {
    private $plugin_name;
    private $version;
    private $calculator;

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
        wp_enqueue_style($this->plugin_name . '-calculator', MATAS_PLUGIN_URL . 'public/css/style.css', array(), $this->version, 'all');
    }
    
    /**
     * Frontend script dosyalarını ekler
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name . '-constants', MATAS_PLUGIN_URL . 'public/js/constants.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name . '-functions', MATAS_PLUGIN_URL . 'public/js/functions.js', array('jquery', $this->plugin_name . '-constants'), $this->version, false);
        wp_enqueue_script($this->plugin_name . '-calculator', MATAS_PLUGIN_URL . 'public/js/calculator.js', array('jquery', $this->plugin_name . '-functions'), $this->version, false);
        
        wp_localize_script($this->plugin_name . '-calculator', 'matas_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('matas_calculator_nonce')
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
            'baslik' => 'Memur Maaş Hesaplama',
            'stil' => 'modern',
        ), $atts, 'matas_hesaplama');
        
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
        check_ajax_referer('matas_calculator_nonce', 'nonce');
        
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
                } elseif (in_array($field, array('derece', 'kademe', 'hizmet_yili', 'cocuk_sayisi', 'cocuk_06', 'engelli_cocuk', 'ogrenim_cocuk',
                                             'gorev_tazminati', 'gelistirme_odenegi', 'asgari_gecim_indirimi', 'kira_yardimi', 'sendika_uyesi'))) {
                    $params[$field] = intval($_POST[$field]);
                }
            }
        }
        
        // Hesaplama işlemini yap
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
        
        wp_die(); // AJAX işlemini sonlandır
    }
}

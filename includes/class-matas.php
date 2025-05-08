<?php
class Matas {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = MATAS_VERSION;
        $this->plugin_name = 'matas';
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    private function load_dependencies() {
        require_once MATAS_PLUGIN_DIR . 'includes/class-matas-loader.php';
        require_once MATAS_PLUGIN_DIR . 'includes/class-matas-calculator.php';
        require_once MATAS_PLUGIN_DIR . 'includes/class-matas-shortcode.php';
        require_once MATAS_PLUGIN_DIR . 'admin/admin.php';
        
        $this->loader = new Matas_Loader();
    }
    
    private function define_admin_hooks() {
        $plugin_admin = new Matas_Admin($this->get_plugin_name(), $this->get_version());
        
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // AJAX işleyicileri
        $this->loader->add_action('wp_ajax_matas_save_katsayilar', $plugin_admin, 'save_katsayilar');
        $this->loader->add_action('wp_ajax_matas_save_unvan', $plugin_admin, 'save_unvan');
        // Diğer AJAX işleyicileri...
    }
    
    private function define_public_hooks() {
        $plugin_shortcode = new Matas_Shortcode($this->get_plugin_name(), $this->get_version());
        
        // Kısa kodları kaydet
        $this->loader->add_shortcode('matas_hesaplama', $plugin_shortcode, 'display_calculator');
        
        // Frontend kaynaklarını ekle
        $this->loader->add_action('wp_enqueue_scripts', $plugin_shortcode, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_shortcode, 'enqueue_scripts');
        
        // AJAX işleyicileri
        $this->loader->add_action('wp_ajax_matas_hesapla', $plugin_shortcode, 'calculate_salary');
        $this->loader->add_action('wp_ajax_nopriv_matas_hesapla', $plugin_shortcode, 'calculate_salary');
    }
    
    public function run() {
        $this->loader->run();
    }
    
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    public function get_version() {
        return $this->version;
    }
}
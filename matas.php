<?php
/**
 * Plugin Name: MATAS - Maaş Takip Sistemi
 * Plugin URI: https://example.com/matas
 * Description: Memur maaşlarını hesaplama ve takip etme uygulaması
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: matas
 * Domain Path: /languages
 */

// Doğrudan erişimi engelle
if (!defined('WPINC')) {
    die;
}

// Eklenti versiyonu
define('MATAS_VERSION', '1.0.0');

// Eklenti dizin yolları
define('MATAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MATAS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Aktivasyon ve deaktivasyon işlemleri
function activate_matas() {
    require_once MATAS_PLUGIN_DIR . 'includes/class-matas-activator.php';
    Matas_Activator::activate();
}

function deactivate_matas() {
    require_once MATAS_PLUGIN_DIR . 'includes/class-matas-deactivator.php';
    Matas_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_matas');
register_deactivation_hook(__FILE__, 'deactivate_matas');

// Ana eklenti sınıfını içe aktar
require_once MATAS_PLUGIN_DIR . 'includes/class-matas.php';

// Eklentiyi başlat
function run_matas() {
    $plugin = new Matas();
    $plugin->run();
}
run_matas();
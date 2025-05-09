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
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
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
    flush_rewrite_rules();
}

function deactivate_matas() {
    require_once MATAS_PLUGIN_DIR . 'includes/class-matas-deactivator.php';
    Matas_Deactivator::deactivate();
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'activate_matas');
register_deactivation_hook(__FILE__, 'deactivate_matas');

// Dil dosyalarını yükle
function matas_load_textdomain() {
    load_plugin_textdomain('matas', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'matas_load_textdomain');

// Ana eklenti sınıfını içe aktar
require_once MATAS_PLUGIN_DIR . 'includes/class-matas.php';

// Eklentiyi başlat
function run_matas() {
    $plugin = new Matas();
    $plugin->run();
}
run_matas();

// Kısa kod önizlemesi için stil
function matas_admin_styles() {
    wp_enqueue_style('matas-admin-preview', MATAS_PLUGIN_URL . 'admin/css/preview.css', array(), MATAS_VERSION, 'all');
}
add_action('admin_enqueue_scripts', 'matas_admin_styles');

// Ayarlar sayfasına link ekle
function matas_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=matas') . '">' . __('Ayarlar', 'matas') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin_basename = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin_basename", 'matas_add_settings_link');

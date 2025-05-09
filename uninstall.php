<?php
/**
 * MATAS Kaldırma Dosyası
 * 
 * Eklenti kaldırıldığında çalışacak işlemleri içerir.
 * 
 * @package MATAS
 * @since 1.0.0
 */

// Doğrudan erişimi engelle
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Kaldırma eylemini yapıp yapmayacağımızı kontrol et
$matas_delete_data = get_option('matas_delete_data_on_uninstall', false);

// Veriler korunacak ise işlem yapmadan çık
if (!$matas_delete_data) {
    return;
}

// Eklentinin veritabanı tablolarını sil
global $wpdb;

// Tablolar
$tables = array(
    'matas_katsayilar',
    'matas_unvan_bilgileri',
    'matas_gosterge_puanlari',
    'matas_dil_gostergeleri',
    'matas_vergiler',
    'matas_sosyal_yardimlar'
);

// Tabloları sil
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$table");
}

// Eklenti ile ilgili diğer ayarları da sil
$options = array(
    'matas_version',
    'matas_install_date',
    'matas_settings',
    'matas_delete_data_on_uninstall'
);

foreach ($options as $option) {
    delete_option($option);
}

// Yönlendirme önbelleğini temizle
flush_rewrite_rules();

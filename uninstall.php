<?php
// Doğrudan erişimi engelle
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Eklentinin veritabanı tablolarını sil
global $wpdb;

// Tablolar
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}matas_katsayilar");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}matas_unvan_bilgileri");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}matas_gosterge_puanlari");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}matas_dil_gostergeleri");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}matas_vergiler");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}matas_sosyal_yardimlar");

// Eklenti ile ilgili diğer ayarları da sil
delete_option('matas_version');
delete_option('matas_install_date');
delete_option('matas_settings'); 

<?php
/**
 * MATAS Ayarlar Sayfası
 * 
 * @package MATAS
 * @since 1.0.0
 */

// Yetkisiz erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Ayarları kaydet
if (isset($_POST['submit']) && wp_verify_nonce($_POST['matas_settings_nonce'], 'matas_settings')) {
    $settings = array(
        'cache_enabled' => isset($_POST['cache_enabled']) ? 1 : 0,
        'cache_duration' => intval($_POST['cache_duration']),
        'rate_limit_enabled' => isset($_POST['rate_limit_enabled']) ? 1 : 0,
        'rate_limit_requests' => intval($_POST['rate_limit_requests']),
        'rate_limit_period' => intval($_POST['rate_limit_period']),
        'debug_mode' => isset($_POST['debug_mode']) ? 1 : 0,
        'delete_data_on_uninstall' => isset($_POST['delete_data_on_uninstall']) ? 1 : 0,
        'backup_enabled' => isset($_POST['backup_enabled']) ? 1 : 0,
        'max_backups' => intval($_POST['max_backups']),
        'security_headers' => isset($_POST['security_headers']) ? 1 : 0,
        'ip_whitelist' => sanitize_textarea_field($_POST['ip_whitelist']),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
    );
    
    update_option('matas_settings', $settings);
    echo '<div class="notice notice-success"><p>Ayarlar kaydedildi!</p></div>';
}

// Mevcut ayarları al
$settings = get_option('matas_settings', array(
    'cache_enabled' => 1,
    'cache_duration' => 3600,
    'rate_limit_enabled' => 1,
    'rate_limit_requests' => 100,
    'rate_limit_period' => 3600,
    'debug_mode' => 0,
    'delete_data_on_uninstall' => 0,
    'backup_enabled' => 1,
    'max_backups' => 10,
    'security_headers' => 1,
    'ip_whitelist' => '',
    'maintenance_mode' => 0
));

// Sistem bilgileri
$system_info = array(
    'php_version' => PHP_VERSION,
    'wp_version' => get_bloginfo('version'),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
);

// İstatistikler
global $wpdb;
$stats = array(
    'total_calculations' => wp_cache_get('matas_total_calculations') ?: 0,
    'today_calculations' => wp_cache_get('matas_today_calculations') ?: 0,
    'cache_hits' => wp_cache_get('matas_cache_hits') ?: 0,
    'cache_misses' => wp_cache_get('matas_cache_misses') ?: 0,
    'error_count' => wp_cache_get('matas_error_count') ?: 0
);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="matas-container">
        <!-- Sistem Durumu -->
        <div class="matas-card">
            <h2>🔧 Sistem Durumu</h2>
            <div class="system-status">
                <div class="status-grid">
                    <div class="status-item">
                        <span class="status-label">PHP Versiyonu:</span>
                        <span class="status-value <?php echo version_compare($system_info['php_version'], '7.4', '>=') ? 'status-good' : 'status-warning'; ?>">
                            <?php echo esc_html($system_info['php_version']); ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">WordPress Versiyonu:</span>
                        <span class="status-value status-good"><?php echo esc_html($system_info['wp_version']); ?></span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Sunucu:</span>
                        <span class="status-value"><?php echo esc_html($system_info['server_software']); ?></span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Bellek Limiti:</span>
                        <span class="status-value"><?php echo esc_html($system_info['memory_limit']); ?></span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Yürütme Süresi:</span>
                        <span class="status-value"><?php echo esc_html($system_info['max_execution_time']); ?>s</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Maks. Dosya Boyutu:</span>
                        <span class="status-value"><?php echo esc_html($system_info['upload_max_filesize']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- İstatistikler -->
        <div class="matas-card">
            <h2>📊 Kullanım İstatistikleri</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['total_calculations']); ?></div>
                    <div class="stat-label">Toplam Hesaplama</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['today_calculations']); ?></div>
                    <div class="stat-label">Bugünkü Hesaplamalar</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['cache_hits']); ?></div>
                    <div class="stat-label">Cache İsabetleri</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['error_count']); ?></div>
                    <div class="stat-label">Hata Sayısı</div>
                </div>
            </div>
        </div>

        <!-- Ayarlar Formu -->
        <form method="post" action="">
            <?php wp_nonce_field('matas_settings', 'matas_settings_nonce'); ?>
            
            <!-- Performans Ayarları -->
            <div class="matas-card">
                <h2>⚡ Performans Ayarları</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Cache Sistemi</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cache_enabled" value="1" <?php checked($settings['cache_enabled']); ?>>
                                Cache sistemini etkinleştir
                            </label>
                            <p class="description">Veritabanı sorgularını cache'leyerek performansı artırır.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cache Süresi</th>
                        <td>
                            <input type="number" name="cache_duration" value="<?php echo esc_attr($settings['cache_duration']); ?>" min="300" max="86400" class="regular-text">
                            <p class="description">Cache verilerinin saklanma süresi (saniye). Önerilen: 3600 (1 saat)</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Güvenlik Ayarları -->
            <div class="matas-card">
                <h2>🛡️ Güvenlik Ayarları</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Rate Limiting</th>
                        <td>
                            <label>
                                <input type="checkbox" name="rate_limit_enabled" value="1" <?php checked($settings['rate_limit_enabled']); ?>>
                                Rate limiting'i etkinleştir
                            </label>
                            <p class="description">Aşırı istekleri engelleyerek DDoS saldırılarına karşı korur.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">İstek Limiti</th>
                        <td>
                            <input type="number" name="rate_limit_requests" value="<?php echo esc_attr($settings['rate_limit_requests']); ?>" min="10" max="1000" class="regular-text">
                            <span>istek /</span>
                            <input type="number" name="rate_limit_period" value="<?php echo esc_attr($settings['rate_limit_period']); ?>" min="60" max="86400" class="regular-text">
                            <span>saniye</span>
                            <p class="description">Belirtilen süre içinde maksimum istek sayısı.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Güvenlik Başlıkları</th>
                        <td>
                            <label>
                                <input type="checkbox" name="security_headers" value="1" <?php checked($settings['security_headers']); ?>>
                                HTTP güvenlik başlıklarını ekle
                            </label>
                            <p class="description">X-Frame-Options, X-Content-Type-Options gibi güvenlik başlıklarını ekler.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">IP Beyaz Listesi</th>
                        <td>
                            <textarea name="ip_whitelist" rows="4" cols="50" class="regular-text"><?php echo esc_textarea($settings['ip_whitelist']); ?></textarea>
                            <p class="description">Admin paneline erişebilecek IP adresleri (her satırda bir IP). Boş bırakılırsa tüm IP'ler erişebilir.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Yedekleme Ayarları -->
            <div class="matas-card">
                <h2>💾 Yedekleme Ayarları</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Otomatik Yedekleme</th>
                        <td>
                            <label>
                                <input type="checkbox" name="backup_enabled" value="1" <?php checked($settings['backup_enabled']); ?>>
                                Otomatik yedekleme sistemini etkinleştir
                            </label>
                            <p class="description">Form verilerini otomatik olarak yedekler.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Maksimum Yedek Sayısı</th>
                        <td>
                            <input type="number" name="max_backups" value="<?php echo esc_attr($settings['max_backups']); ?>" min="5" max="50" class="regular-text">
                            <p class="description">Saklanacak maksimum yedek sayısı. Eski yedekler otomatik silinir.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Geliştirici Ayarları -->
            <div class="matas-card">
                <h2>🔧 Geliştirici Ayarları</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Debug Modu</th>
                        <td>
                            <label>
                                <input type="checkbox" name="debug_mode" value="1" <?php checked($settings['debug_mode']); ?>>
                                Debug modunu etkinleştir
                            </label>
                            <p class="description">Detaylı hata logları ve performans bilgileri yazdırır.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Bakım Modu</th>
                        <td>
                            <label>
                                <input type="checkbox" name="maintenance_mode" value="1" <?php checked($settings['maintenance_mode']); ?>>
                                Bakım modunu etkinleştir
                            </label>
                            <p class="description">Hesaplama formunu geçici olarak devre dışı bırakır.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Kaldırma Ayarları -->
            <div class="matas-card">
                <h2>🗑️ Kaldırma Ayarları</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Veri Silme</th>
                        <td>
                            <label>
                                <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked($settings['delete_data_on_uninstall']); ?>>
                                Eklenti kaldırıldığında tüm verileri sil
                            </label>
                            <p class="description"><strong>Dikkat:</strong> Bu seçenek işaretliyse, eklenti kaldırıldığında tüm veriler kalıcı olarak silinir.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Araçlar -->
            <div class="matas-card">
                <h2>🔧 Araçlar</h2>
                
                <div class="tools-grid">
                    <div class="tool-item">
                        <h3>Veritabanı Optimizasyonu</h3>
                        <p>Veritabanı tablolarını optimize eder.</p>
                        <button type="button" class="button" onclick="optimizeDatabase()">Optimize Et</button>
                    </div>
                    
                    <div class="tool-item">
                        <h3>Log Dosyalarını Temizle</h3>
                        <p>Eski log kayıtlarını temizler.</p>
                        <button type="button" class="button" onclick="clearLogs()">Logları Temizle</button>
                    </div>
                    
                    <div class="tool-item">
                        <h3>Test Hesaplama</h3>
                        <p>Sistem test hesaplaması yapar.</p>
                        <button type="button" class="button" onclick="runTest()">Test Çalıştır</button>
                    </div>
                    
                    <div class="tool-item">
                        <h3>Sistem Raporu</h3>
                        <p>Detaylı sistem raporu oluşturur.</p>
                        <button type="button" class="button" onclick="generateReport()">Rapor Oluştur</button>
                    </div>
                    
                    <div class="tool-item">
                        <h3>Veri Dışa Aktar</h3>
                        <p>Tüm ayarları JSON formatında dışa aktarır.</p>
                        <button type="button" class="button" onclick="exportData()">Dışa Aktar</button>
                    </div>
                </div>
            </div>

            <?php submit_button('Ayarları Kaydet', 'primary', 'submit'); ?>
        </form>
    </div>
</div>

<style>
.matas-container {
    max-width: 1200px;
}

.matas-card {
    background: white;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.matas-card h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
    color: #1d2327;
    border-bottom: 1px solid #c3c4c7;
    padding-bottom: 10px;
}

.system-status .status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #f6f7f7;
    border-radius: 4px;
    border-left: 4px solid #0073aa;
}

.status-label {
    font-weight: 500;
    color: #1d2327;
}

.status-value {
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 3px;
    background: #f0f0f1;
}

.status-good {
    color: #00a32a;
    background: #e6f7e6;
}

.status-warning {
    color: #b32d2e;
    background: #fbeaea;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: linear-gradient(135deg, #0073aa, #005177);
    color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9em;
    opacity: 0.9;
}

.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.tool-item {
    padding: 15px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    background: #f9f9f9;
    text-align: center;
}

.tool-item h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #1d2327;
    font-size: 16px;
}

.tool-item p {
    color: #646970;
    margin-bottom: 15px;
    font-size: 14px;
}

.tool-item .button {
    width: 100%;
}

.form-table th {
    width: 200px;
    padding: 20px 10px 20px 0;
    vertical-align: top;
}

.form-table td {
    padding: 15px 10px;
}

.description {
    font-style: italic;
    color: #646970;
    margin-top: 5px !important;
}

@media (max-width: 768px) {
    .status-grid,
    .stats-grid,
    .tools-grid {
        grid-template-columns: 1fr;
    }
    
    .form-table th,
    .form-table td {
        display: block;
        width: 100%;
        padding: 10px 0;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Cache temizleme
    window.clearCache = function() {
        if (confirm('Cache verilerini temizlemek istediğinize emin misiniz?')) {
            $.post(ajaxurl, {
                action: 'matas_clear_cache',
                nonce: '<?php echo wp_create_nonce("matas_clear_cache"); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Cache başarıyla temizlendi!');
                    location.reload();
                } else {
                    alert('Cache temizlenirken hata oluştu: ' + (response.data.message || 'Bilinmeyen hata'));
                }
            });
        }
    };

    // Veritabanı optimizasyonu
    window.optimizeDatabase = function() {
        if (confirm('Veritabanını optimize etmek istediğinize emin misiniz? Bu işlem birkaç dakika sürebilir.')) {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Optimize ediliyor...';
            
            $.post(ajaxurl, {
                action: 'matas_optimize_database',
                nonce: '<?php echo wp_create_nonce("matas_optimize_database"); ?>'
            }, function(response) {
                button.disabled = false;
                button.textContent = 'Optimize Et';
                
                if (response.success) {
                    alert('Veritabanı başarıyla optimize edildi!');
                } else {
                    alert('Optimizasyon sırasında hata oluştu: ' + (response.data.message || 'Bilinmeyen hata'));
                }
            });
        }
    };

    // Log temizleme
    window.clearLogs = function() {
        if (confirm('Log dosyalarını temizlemek istediğinize emin misiniz?')) {
            $.post(ajaxurl, {
                action: 'matas_clear_logs',
                nonce: '<?php echo wp_create_nonce("matas_clear_logs"); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Log dosyaları başarıyla temizlendi!');
                    location.reload();
                } else {
                    alert('Log temizlenirken hata oluştu: ' + (response.data.message || 'Bilinmeyen hata'));
                }
            });
        }
    };

    // Test çalıştırma
    window.runTest = function() {
        const button = event.target;
        button.disabled = true;
        button.textContent = 'Test çalışıyor...';
        
        $.post(ajaxurl, {
            action: 'matas_run_test',
            nonce: '<?php echo wp_create_nonce("matas_run_test"); ?>'
        }, function(response) {
            button.disabled = false;
            button.textContent = 'Test Çalıştır';
            
            if (response.success) {
                alert('Test başarıyla tamamlandı!\nSonuç: ' + JSON.stringify(response.data, null, 2));
            } else {
                alert('Test sırasında hata oluştu: ' + (response.data.message || 'Bilinmeyen hata'));
            }
        });
    };

    // Sistem raporu oluşturma
    window.generateReport = function() {
        const button = event.target;
        button.disabled = true;
        button.textContent = 'Rapor oluşturuluyor...';
        
        $.post(ajaxurl, {
            action: 'matas_generate_report',
            nonce: '<?php echo wp_create_nonce("matas_generate_report"); ?>'
        }, function(response) {
            button.disabled = false;
            button.textContent = 'Rapor Oluştur';
            
            if (response.success) {
                // Raporu yeni pencerede aç
                const newWindow = window.open('', '_blank');
                newWindow.document.write('<pre>' + JSON.stringify(response.data, null, 2) + '</pre>');
                newWindow.document.title = 'MATAS Sistem Raporu';
            } else {
                alert('Rapor oluşturulurken hata oluştu: ' + (response.data.message || 'Bilinmeyen hata'));
            }
        });
    };

    // Veri dışa aktarma
    window.exportData = function() {
        $.post(ajaxurl, {
            action: 'matas_export_data',
            nonce: '<?php echo wp_create_nonce("matas_export_data"); ?>'
        }, function(response) {
            if (response.success) {
                // JSON dosyası olarak indir
                const dataStr = JSON.stringify(response.data, null, 2);
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                const url = URL.createObjectURL(dataBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'matas_data_export_' + new Date().toISOString().split('T')[0] + '.json';
                link.click();
                URL.revokeObjectURL(url);
            } else {
                alert('Dışa aktarma sırasında hata oluştu: ' + (response.data.message || 'Bilinmeyen hata'));
            }
        });
    };

    // Form değişikliklerini izle
    let hasChanges = false;
    $('form input, form textarea, form select').on('change', function() {
        hasChanges = true;
    });

    // Sayfa kapatılmadan önce uyarı
    $(window).on('beforeunload', function() {
        if (hasChanges) {
            return 'Kaydedilmemiş değişiklikler var. Sayfayı kapatmak istediğinizden emin misiniz?';
        }
    });

    // Form submit edildiğinde uyarıyı kaldır
    $('form').on('submit', function() {
        hasChanges = false;
    });

    // Rate limit ayarlarını dinamik olarak güncelle
    $('input[name="rate_limit_enabled"]').on('change', function() {
        const $relatedFields = $('input[name="rate_limit_requests"], input[name="rate_limit_period"]');
        if ($(this).is(':checked')) {
            $relatedFields.prop('disabled', false).closest('tr').show();
        } else {
            $relatedFields.prop('disabled', true).closest('tr').hide();
        }
    }).trigger('change');

    // Cache ayarlarını dinamik olarak güncelle
    $('input[name="cache_enabled"]').on('change', function() {
        const $relatedFields = $('input[name="cache_duration"]');
        if ($(this).is(':checked')) {
            $relatedFields.prop('disabled', false).closest('tr').show();
        } else {
            $relatedFields.prop('disabled', true).closest('tr').hide();
        }
    }).trigger('change');

    // Backup ayarlarını dinamik olarak güncelle
    $('input[name="backup_enabled"]').on('change', function() {
        const $relatedFields = $('input[name="max_backups"]');
        if ($(this).is(':checked')) {
            $relatedFields.prop('disabled', false).closest('tr').show();
        } else {
            $relatedFields.prop('disabled', true).closest('tr').hide();
        }
    }).trigger('change');

    // Sayı alanları için doğrulama
    $('input[type="number"]').on('blur', function() {
        const $this = $(this);
        const min = parseInt($this.attr('min'));
        const max = parseInt($this.attr('max'));
        const val = parseInt($this.val());

        if (val < min) {
            $this.val(min);
            alert('Değer minimum ' + min + ' olmalıdır.');
        } else if (val > max) {
            $this.val(max);
            alert('Değer maksimum ' + max + ' olmalıdır.');
        }
    });

    // IP whitelist doğrulama
    $('textarea[name="ip_whitelist"]').on('blur', function() {
        const ips = $(this).val().split('\n').filter(line => line.trim());
        const invalidIps = [];

        ips.forEach(ip => {
            ip = ip.trim();
            if (ip && !isValidIP(ip)) {
                invalidIps.push(ip);
            }
        });

        if (invalidIps.length > 0) {
            alert('Geçersiz IP adresleri tespit edildi:\n' + invalidIps.join('\n'));
        }
    });

    // IP doğrulama fonksiyonu
    function isValidIP(ip) {
        const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        return ipRegex.test(ip);
    }

    // Tooltips ekle
    $('[title]').each(function() {
        $(this).tooltip({
            position: { my: "center bottom-20", at: "center top", using: function( position, feedback ) {
                $(this).css(position);
                $("<div>")
                    .addClass("arrow")
                    .addClass(feedback.vertical)
                    .addClass(feedback.horizontal)
                    .appendTo(this);
            }}
        });
    });
});
</script><?php

// AJAX işleyicilerini kaydet
add_action('wp_ajax_matas_clear_cache', 'matas_ajax_clear_cache');
add_action('wp_ajax_matas_optimize_database', 'matas_ajax_optimize_database');
add_action('wp_ajax_matas_clear_logs', 'matas_ajax_clear_logs');
add_action('wp_ajax_matas_run_test', 'matas_ajax_run_test');
add_action('wp_ajax_matas_generate_report', 'matas_ajax_generate_report');
add_action('wp_ajax_matas_export_data', 'matas_ajax_export_data');

function matas_ajax_clear_cache() {
    check_ajax_referer('matas_clear_cache', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok!'));
    }
    
    // Cache temizle
    wp_cache_flush();
    
    // MATAS cache'lerini temizle
    $cache_keys = array(
        'matas_katsayilar_active',
        'matas_unvanlar_all',
        'matas_gostergeler_all',
        'matas_dil_gostergeleri_all',
        'matas_vergiler_' . date('Y'),
        'matas_sosyal_yardimlar_' . date('Y')
    );
    
    foreach ($cache_keys as $key) {
        wp_cache_delete($key, 'matas');
    }
    
    wp_send_json_success(array('message' => 'Cache temizlendi'));
}

function matas_ajax_optimize_database() {
    check_ajax_referer('matas_optimize_database', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok!'));
    }
    
    global $wpdb;
    
    $tables = array(
        $wpdb->prefix . 'matas_katsayilar',
        $wpdb->prefix . 'matas_unvan_bilgileri',
        $wpdb->prefix . 'matas_gosterge_puanlari',
        $wpdb->prefix . 'matas_dil_gostergeleri',
        $wpdb->prefix . 'matas_vergiler',
        $wpdb->prefix . 'matas_sosyal_yardimlar'
    );
    
    $optimized = 0;
    foreach ($tables as $table) {
        $result = $wpdb->query("OPTIMIZE TABLE $table");
        if ($result !== false) {
            $optimized++;
        }
    }
    
    wp_send_json_success(array(
        'message' => "Veritabanı optimize edildi ($optimized tablo)",
        'optimized_tables' => $optimized
    ));
}

function matas_ajax_clear_logs() {
    check_ajax_referer('matas_clear_logs', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok!'));
    }
    
    global $wpdb;
    
    // Log tablosu varsa temizle
    $table_name = $wpdb->prefix . 'matas_logs';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
    
    if ($table_exists) {
        $deleted = $wpdb->query("DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        wp_send_json_success(array('message' => "$deleted log kaydı silindi"));
    } else {
        wp_send_json_success(array('message' => 'Log tablosu bulunamadı'));
    }
}

function matas_ajax_run_test() {
    check_ajax_referer('matas_run_test', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok!'));
    }
    
    // Test hesaplama parametreleri
    $test_params = array(
        'unvan' => 'ogretmen_sinif',
        'derece' => 8,
        'kademe' => 5,
        'hizmet_yili' => 15,
        'medeni_hal' => 'evli',
        'es_calisiyor' => 'hayir',
        'cocuk_sayisi' => 2,
        'cocuk_06' => 1,
        'engelli_cocuk' => 0,
        'ogrenim_cocuk' => 0,
        'egitim_durumu' => 'lisans',
        'dil_seviyesi' => 'yok',
        'gorev_tazminati' => 1,
        'asgari_gecim_indirimi' => 1
    );
    
    try {
        require_once MATAS_PLUGIN_DIR . 'includes/class-matas-calculator.php';
        $calculator = new Matas_Calculator('matas', '1.0.0');
        
        $start_time = microtime(true);
        $result = $calculator->calculate_salary($test_params);
        $end_time = microtime(true);
        
        $execution_time = round(($end_time - $start_time) * 1000, 2);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => 'Test başarılı',
                'execution_time' => $execution_time . ' ms',
                'net_salary' => $result['netMaas'],
                'calculation_details' => $result
            ));
        } else {
            wp_send_json_error(array('message' => 'Test hesaplama hatası: ' . $result['message']));
        }
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Test sırasında hata: ' . $e->getMessage()));
    }
}

function matas_ajax_generate_report() {
    check_ajax_referer('matas_generate_report', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok!'));
    }
    
    global $wpdb;
    
    $report = array(
        'timestamp' => current_time('mysql'),
        'system_info' => array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => MATAS_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ),
        'database_stats' => array(
            'katsayilar_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_katsayilar"),
            'unvanlar_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_unvan_bilgileri"),
            'gostergeler_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_gosterge_puanlari"),
            'vergiler_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_vergiler"),
            'sosyal_yardimlar_count' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_sosyal_yardimlar")
        ),
        'settings' => get_option('matas_settings', array()),
        'cache_stats' => array(
            'cache_hits' => wp_cache_get('matas_cache_hits') ?: 0,
            'cache_misses' => wp_cache_get('matas_cache_misses') ?: 0
        )
    );
    
    wp_send_json_success($report);
}

function matas_ajax_export_data() {
    check_ajax_referer('matas_export_data', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok!'));
    }
    
    global $wpdb;
    
    $export_data = array(
        'export_date' => current_time('mysql'),
        'plugin_version' => MATAS_VERSION,
        'settings' => get_option('matas_settings', array()),
        'katsayilar' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}matas_katsayilar ORDER BY id", ARRAY_A),
        'unvanlar' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}matas_unvan_bilgileri ORDER BY id", ARRAY_A),
        'gostergeler' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}matas_gosterge_puanlari ORDER BY derece, kademe", ARRAY_A),
        'dil_gostergeleri' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}matas_dil_gostergeleri ORDER BY id", ARRAY_A),
        'vergiler' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}matas_vergiler ORDER BY yil, dilim", ARRAY_A),
        'sosyal_yardimlar' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}matas_sosyal_yardimlar ORDER BY yil, tip", ARRAY_A)
    );
    
    wp_send_json_success($export_data);
}
?>

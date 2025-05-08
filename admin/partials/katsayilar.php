<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="matas-container">
        <div class="matas-card">
            <h2>Yeni Katsayı Ekle</h2>
            <form id="matas-katsayi-form">
                <div class="matas-form-row">
                    <div class="matas-form-group">
                        <label for="donem">Dönem Adı:</label>
                        <input type="text" id="donem" name="donem" class="regular-text" required placeholder="Örn: 2025 Ocak-Haziran">
                    </div>
                </div>
                
                <div class="matas-form-row">
                    <div class="matas-form-group">
                        <label for="aylik_katsayi">Aylık Katsayı:</label>
                        <input type="number" id="aylik_katsayi" name="aylik_katsayi" class="regular-text" required step="0.000001" min="0" placeholder="Örn: 0.354507">
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="taban_katsayi">Taban Aylık Katsayısı:</label>
                        <input type="number" id="taban_katsayi" name="taban_katsayi" class="regular-text" required step="0.000001" min="0" placeholder="Örn: 7.715">
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="yan_odeme_katsayi">Yan Ödeme Katsayısı:</label>
                        <input type="number" id="yan_odeme_katsayi" name="yan_odeme_katsayi" class="regular-text" required step="0.000001" min="0" placeholder="Örn: 0.0354507">
                    </div>
                </div>
                
                <div class="matas-form-actions">
                    <button type="submit" class="button button-primary">Katsayıları Kaydet</button>
                </div>
            </form>
        </div>
        
        <div class="matas-card">
            <h2>Kayıtlı Katsayılar</h2>
            <div id="matas-katsayilar-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Dönem</th>
                            <th>Aylık Katsayı</th>
                            <th>Taban Aylık Katsayısı</th>
                            <th>Yan Ödeme Katsayısı</th>
                            <th>Durum</th>
                            <th>Eklenme Tarihi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        global $wpdb;
                        $katsayilar = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}matas_katsayilar ORDER BY id DESC", ARRAY_A);
                        
                        if (count($katsayilar) > 0) {
                            foreach ($katsayilar as $katsayi) {
                                echo '<tr>';
                                echo '<td>' . esc_html($katsayi['donem']) . '</td>';
                                echo '<td>' . number_format($katsayi['aylik_katsayi'], 6) . '</td>';
                                echo '<td>' . number_format($katsayi['taban_katsayi'], 6) . '</td>';
                                echo '<td>' . number_format($katsayi['yan_odeme_katsayi'], 6) . '</td>';
                                echo '<td>' . ($katsayi['aktif'] ? '<span class="matas-status active">Aktif</span>' : '<span class="matas-status">Pasif</span>') . '</td>';
                                echo '<td>' . date('d.m.Y H:i', strtotime($katsayi['olusturma_tarihi'])) . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6">Henüz katsayı kaydı bulunmuyor.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Katsayı Formu Submit
    $('#matas-katsayi-form').on('submit', function(e) {
        e.preventDefault();
        
        var donem = $('#donem').val();
        var aylik_katsayi = parseFloat($('#aylik_katsayi').val());
        var taban_katsayi = parseFloat($('#taban_katsayi').val());
        var yan_odeme_katsayi = parseFloat($('#yan_odeme_katsayi').val());
        
        // Form doğrulama
        if (!donem || isNaN(aylik_katsayi) || isNaN(taban_katsayi) || isNaN(yan_odeme_katsayi)) {
            alert('Lütfen tüm alanları doldurunuz!');
            return;
        }
        
        // AJAX isteği gönder
        $.ajax({
            url: matas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'matas_save_katsayilar',
                nonce: matas_ajax.nonce,
                donem: donem,
                aylik_katsayi: aylik_katsayi,
                taban_katsayi: taban_katsayi,
                yan_odeme_katsayi: yan_odeme_katsayi
            },
            beforeSend: function() {
                // Buton yükleniyor durumuna getir
                $('#matas-katsayi-form button[type="submit"]').prop('disabled', true).text('Kaydediliyor...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    // Sayfayı yenile
                    location.reload();
                } else {
                    alert(response.data.message || 'Bir hata oluştu!');
                }
            },
            error: function() {
                alert('Sunucu ile iletişim kurulamadı!');
            },
            complete: function() {
                // Buton normal durumuna getir
                $('#matas-katsayi-form button[type="submit"]').prop('disabled', false).text('Katsayıları Kaydet');
            }
        });
    });
});
</script>

<style>
.matas-container {
    margin-top: 20px;
}

.matas-card {
    background: white;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    padding: 20px;
}

.matas-form-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px 15px;
}

.matas-form-group {
    flex: 1;
    min-width: 250px;
    padding: 0 10px;
    margin-bottom: 15px;
}

.matas-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.matas-form-actions {
    margin-top: 20px;
}

.matas-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    background: #e5e5e5;
    color: #777;
}

.matas-status.active {
    background: #5cb85c;
    color: white;
}
</style>
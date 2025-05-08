<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="matas-container">
        <div class="matas-card">
            <h2 id="unvan-form-title">Yeni Ünvan Ekle</h2>
            <form id="matas-unvan-form">
                <input type="hidden" id="unvan_id" name="unvan_id" value="0">
                
                <div class="matas-form-row">
                    <div class="matas-form-group">
                        <label for="unvan_kodu">Ünvan Kodu:</label>
                        <input type="text" id="unvan_kodu" name="unvan_kodu" class="regular-text" required placeholder="Örn: ogretmen_sinif">
                        <p class="description">İngilizce karakterler, alt çizgi ve rakamlardan oluşmalıdır.</p>
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="unvan_adi">Ünvan Adı:</label>
                        <input type="text" id="unvan_adi" name="unvan_adi" class="regular-text" required placeholder="Örn: Sınıf Öğretmeni">
                    </div>
                </div>
                
                <div class="matas-form-row">
                    <div class="matas-form-group">
                        <label for="ekgosterge">Ek Gösterge:</label>
                        <input type="number" id="ekgosterge" name="ekgosterge" class="regular-text" required min="0" placeholder="Örn: 2200">
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="ozel_hizmet">Özel Hizmet (%):</label>
                        <input type="number" id="ozel_hizmet" name="ozel_hizmet" class="regular-text" required min="0" placeholder="Örn: 80">
                    </div>
                </div>
                
                <div class="matas-form-row">
                    <div class="matas-form-group">
                        <label for="yan_odeme">Yan Ödeme Puanı:</label>
                        <input type="number" id="yan_odeme" name="yan_odeme" class="regular-text" required min="0" placeholder="Örn: 800">
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="is_guclugu">İş Güçlüğü Puanı:</label>
                        <input type="number" id="is_guclugu" name="is_guclugu" class="regular-text" required min="0" placeholder="Örn: 300">
                    </div>
                </div>
                
                <div class="matas-form-row">
                    <div class="matas-form-group">
                        <label for="makam_tazminat">Makam Tazminatı:</label>
                        <input type="number" id="makam_tazminat" name="makam_tazminat" class="regular-text" required min="0" placeholder="Örn: 0">
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="egitim_tazminat">Eğitim-Öğretim Tazminatı Oranı:</label>
                        <input type="number" id="egitim_tazminat" name="egitim_tazminat" class="regular-text" required min="0" max="1" step="0.01" placeholder="Örn: 0.20">
                        <p class="description">Ondalık değer olarak giriniz (0.20 = %20)</p>
                    </div>
                </div>
                
                <div class="matas-form-actions">
                    <button type="submit" class="button button-primary">Ünvanı Kaydet</button>
                    <button type="button" id="btn-cancel-edit" class="button" style="display:none;">İptal</button>
                </div>
            </form>
        </div>
        
        <div class="matas-card">
            <h2>Kayıtlı Ünvanlar</h2>
            <div id="matas-unvanlar-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Ünvan Adı</th>
                            <th>Ek Gösterge</th>
                            <th>Özel Hizmet</th>
                            <th>Yan Ödeme</th>
                            <th>İş Güçlüğü</th>
                            <th>Makam Taz.</th>
                            <th>Eğitim Taz.</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        global $wpdb;
                        $unvanlar = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}matas_unvan_bilgileri ORDER BY unvan_adi ASC", ARRAY_A);
                        
                        if (count($unvanlar) > 0) {
                            foreach ($unvanlar as $unvan) {
                                echo '<tr data-id="' . $unvan['id'] . '">';
                                echo '<td>' . esc_html($unvan['unvan_adi']) . '</td>';
                                echo '<td>' . esc_html($unvan['ekgosterge']) . '</td>';
                                echo '<td>%' . esc_html($unvan['ozel_hizmet']) . '</td>';
                                echo '<td>' . esc_html($unvan['yan_odeme']) . '</td>';
                                echo '<td>' . esc_html($unvan['is_guclugu']) . '</td>';
                                echo '<td>' . esc_html($unvan['makam_tazminat']) . '</td>';
                                echo '<td>%' . number_format($unvan['egitim_tazminat'] * 100, 0) . '</td>';
                                echo '<td>';
                                echo '<button type="button" class="button button-small unvan-edit" data-id="' . $unvan['id'] . '">Düzenle</button> ';
                                echo '<button type="button" class="button button-small button-link-delete unvan-delete" data-id="' . $unvan['id'] . '">Sil</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="8">Henüz ünvan kaydı bulunmuyor.</td></tr>';
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
    // Ünvan Formu Submit
    $('#matas-unvan-form').on('submit', function(e) {
        e.preventDefault();
        
        var unvan_id = $('#unvan_id').val();
        var unvan_kodu = $('#unvan_kodu').val();
        var unvan_adi = $('#unvan_adi').val();
        var ekgosterge = parseInt($('#ekgosterge').val());
        var ozel_hizmet = parseInt($('#ozel_hizmet').val());
        var yan_odeme = parseInt($('#yan_odeme').val());
        var is_guclugu = parseInt($('#is_guclugu').val());
        var makam_tazminat = parseInt($('#makam_tazminat').val());
        var egitim_tazminat = parseFloat($('#egitim_tazminat').val());
        
        // Form doğrulama
        if (!unvan_kodu || !unvan_adi || isNaN(ekgosterge) || isNaN(ozel_hizmet) || 
            isNaN(yan_odeme) || isNaN(is_guclugu) || isNaN(makam_tazminat) || isNaN(egitim_tazminat)) {
            alert('Lütfen tüm alanları doldurunuz!');
            return;
        }
        
        // AJAX isteği gönder
        $.ajax({
            url: matas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'matas_save_unvan',
                nonce: matas_ajax.nonce,
                unvan_id: unvan_id,
                unvan_kodu: unvan_kodu,
                unvan_adi: unvan_adi,
                ekgosterge: ekgosterge,
                ozel_hizmet: ozel_hizmet,
                yan_odeme: yan_odeme,
                is_guclugu: is_guclugu,
                makam_tazminat: makam_tazminat,
                egitim_tazminat: egitim_tazminat
            },
            beforeSend: function() {
                // Buton yükleniyor durumuna getir
                $('#matas-unvan-form button[type="submit"]').prop('disabled', true).text('Kaydediliyor...');
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
                $('#matas-unvan-form button[type="submit"]').prop('disabled', false).text('Ünvanı Kaydet');
            }
        });
    });
    
    // Ünvan Düzenle Butonu
    $(document).on('click', '.unvan-edit', function() {
        var unvan_id = $(this).data('id');
        
        // AJAX ile ünvan bilgilerini çek
        $.ajax({
            url: matas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'matas_get_unvan',
                nonce: matas_ajax.nonce,
                unvan_id: unvan_id
            },
            success: function(response) {
                if (response.success) {
                    // Form alanlarını doldur
                    var unvan = response.data.unvan;
                    $('#unvan_id').val(unvan.id);
                    $('#unvan_kodu').val(unvan.unvan_kodu);
                    $('#unvan_adi').val(unvan.unvan_adi);
                    $('#ekgosterge').val(unvan.ekgosterge);
                    $('#ozel_hizmet').val(unvan.ozel_hizmet);
                    $('#yan_odeme').val(unvan.yan_odeme);
                    $('#is_guclugu').val(unvan.is_guclugu);
                    $('#makam_tazminat').val(unvan.makam_tazminat);
                    $('#egitim_tazminat').val(unvan.egitim_tazminat);
                    
                    // Form başlığını değiştir
                    $('#unvan-form-title').text('Ünvan Düzenle');
                    // İptal butonunu göster
                    $('#btn-cancel-edit').show();
                    // Sayfayı form alanına kaydır
                    $('html, body').animate({
                        scrollTop: $('#matas-unvan-form').offset().top - 50
                    }, 500);
                } else {
                    alert(response.data.message || 'Bir hata oluştu!');
                }
            },
            error: function() {
                alert('Sunucu ile iletişim kurulamadı!');
            }
        });
    });
    
    // Ünvan Silme Butonu
    $(document).on('click', '.unvan-delete', function() {
        if (confirm('Bu ünvanı silmek istediğinize emin misiniz?')) {
            var unvan_id = $(this).data('id');
            
            // AJAX ile ünvanı sil
            $.ajax({
                url: matas_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'matas_delete_unvan',
                    nonce: matas_ajax.nonce,
                    unvan_id: unvan_id
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
                }
            });
        }
    });
    
    // İptal Butonu
    $('#btn-cancel-edit').on('click', function() {
        // Formu temizle
        $('#matas-unvan-form')[0].reset();
        $('#unvan_id').val(0);
        
        // Form başlığını değiştir
        $('#unvan-form-title').text('Yeni Ünvan Ekle');
        // İptal butonunu gizle
        $(this).hide();
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
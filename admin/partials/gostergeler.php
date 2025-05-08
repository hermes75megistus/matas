<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="matas-container">
        <div class="matas-card">
            <h2 id="gosterge-form-title">Yeni Gösterge Puanı Ekle</h2>
            <form id="matas-gosterge-form">
                <input type="hidden" id="gosterge_id" name="gosterge_id" value="0">
                
                <div class="matas-form-row">
                    <div class="matas-form-group">
                        <label for="derece">Derece:</label>
                        <select id="derece" name="derece" class="regular-text" required>
                            <option value="" disabled selected>Seçiniz</option>
                            <?php for ($i = 1; $i <= 15; $i++) : ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?>. Derece</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="kademe">Kademe:</label>
                        <select id="kademe" name="kademe" class="regular-text" required>
                            <option value="" disabled selected>Seçiniz</option>
                            <?php for ($i = 1; $i <= 9; $i++) : ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?>. Kademe</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="gosterge_puani">Gösterge Puanı:</label>
                        <input type="number" id="gosterge_puani" name="gosterge_puani" class="regular-text" required min="500" max="2000" placeholder="Örn: 1320">
                    </div>
                </div>
                
                <div class="matas-form-actions">
                    <button type="submit" class="button button-primary">Gösterge Puanını Kaydet</button>
                    <button type="button" id="btn-cancel-edit" class="button" style="display:none;">İptal</button>
                </div>
            </form>
        </div>
        
        <div class="matas-card">
            <h2>Kayıtlı Gösterge Puanları</h2>
            <div id="matas-gostergeler-list">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Derece</th>
                            <th>Kademe</th>
                            <th>Gösterge Puanı</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        global $wpdb;
                        $gostergeler = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}matas_gosterge_puanlari ORDER BY derece ASC, kademe ASC", ARRAY_A);
                        
                        if (count($gostergeler) > 0) {
                            foreach ($gostergeler as $gosterge) {
                                echo '<tr data-id="' . $gosterge['id'] . '">';
                                echo '<td>' . esc_html($gosterge['derece']) . '. Derece</td>';
                                echo '<td>' . esc_html($gosterge['kademe']) . '. Kademe</td>';
                                echo '<td>' . esc_html($gosterge['gosterge_puani']) . '</td>';
                                echo '<td>';
                                echo '<button type="button" class="button button-small gosterge-edit" data-id="' . $gosterge['id'] . '">Düzenle</button> ';
                                echo '<button type="button" class="button button-small button-link-delete gosterge-delete" data-id="' . $gosterge['id'] . '">Sil</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4">Henüz gösterge puanı kaydı bulunmuyor.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="matas-card">
            <h2>Gösterge Puanları Toplu Yükleme</h2>
            <p>Aşağıdaki butonu kullanarak varsayılan gösterge puanlarını toplu olarak yükleyebilirsiniz.</p>
            <p><strong>Dikkat:</strong> Bu işlem mevcut gösterge puanlarını silecektir.</p>
            <div class="matas-form-actions">
                <button type="button" id="matas-gosterge-yukle" class="button">Varsayılan Gösterge Puanlarını Yükle</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Gösterge Formu Submit
    $('#matas-gosterge-form').on('submit', function(e) {
        e.preventDefault();
        
        const gostergeId = $('#gosterge_id').val();
        const derece = $('#derece').val();
        const kademe = $('#kademe').val();
        const gostergePuani = $('#gosterge_puani').val();
        
        // Form doğrulama
        if (!derece || !kademe || !gostergePuani) {
            alert('Lütfen tüm alanları doldurunuz!');
            return;
        }
        
        // AJAX isteği gönder
        $.ajax({
            url: matas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'matas_save_gosterge',
                nonce: matas_ajax.nonce,
                gosterge_id: gostergeId,
                derece: derece,
                kademe: kademe,
                gosterge_puani: gostergePuani
            },
            beforeSend: function() {
                // Buton yükleniyor durumuna getir
                $('#matas-gosterge-form button[type="submit"]').prop('disabled', true).text('Kaydediliyor...');
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
                $('#matas-gosterge-form button[type="submit"]').prop('disabled', false).text('Gösterge Puanını Kaydet');
            }
        });
    });
    
    // Gösterge Düzenle Butonu
    $(document).on('click', '.gosterge-edit', function() {
        const gostergeId = $(this).data('id');
        
        // AJAX ile gösterge bilgilerini çek
        $.ajax({
            url: matas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'matas_get_gosterge',
                nonce: matas_ajax.nonce,
                gosterge_id: gostergeId
            },
            success: function(response) {
                if (response.success) {
                    // Form alanlarını doldur
                    const gosterge = response.data.gosterge;
                    $('#gosterge_id').val(gosterge.id);
                    $('#derece').val(gosterge.derece);
                    $('#kademe').val(gosterge.kademe);
                    $('#gosterge_puani').val(gosterge.gosterge_puani);
                    
                    // Form başlığını değiştir
                    $('#gosterge-form-title').text('Gösterge Puanını Düzenle');
                    // İptal butonunu göster
                    $('#btn-cancel-edit').show();
                    // Sayfayı form alanına kaydır
                    $('html, body').animate({
                        scrollTop: $('#matas-gosterge-form').offset().top - 50
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
    
    // Gösterge Silme Butonu
    $(document).on('click', '.gosterge-delete', function() {
        if (confirm('Bu gösterge puanını silmek istediğinize emin misiniz?')) {
            const gostergeId = $(this).data('id');
            
            // AJAX ile göstergeyi sil
            $.ajax({
                url: matas_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'matas_delete_gosterge',
                    nonce: matas_ajax.nonce,
                    gosterge_id: gostergeId
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
        $('#matas-gosterge-form')[0].reset();
        $('#gosterge_id').val(0);
        
        // Form başlığını değiştir
        $('#gosterge-form-title').text('Yeni Gösterge Puanı Ekle');
        // İptal butonunu gizle
        $(this).hide();
    });
    
    // Toplu Yükleme Butonu
    $('#matas-gosterge-yukle').on('click', function() {
        if (confirm('Varsayılan gösterge puanlarını yüklemek istediğinize emin misiniz? Bu işlem mevcut gösterge puanlarını silecektir.')) {
            // AJAX ile varsayılan gösterge puanlarını yükle
            $.ajax({
                url: matas_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'matas_load_default_gostergeler',
                    nonce: matas_ajax.nonce
                },
                beforeSend: function() {
                    // Buton yükleniyor durumuna getir
                    $('#matas-gosterge-yukle').prop('disabled', true).text('Yükleniyor...');
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
                    $('#matas-gosterge-yukle').prop('disabled', false).text('Varsayılan Gösterge Puanlarını Yükle');
                }
            });
        }
    });
});
</script> 

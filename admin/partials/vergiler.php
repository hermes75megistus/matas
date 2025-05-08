<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="matas-container">
        <div class="matas-card">
            <h2 id="vergi-form-title">Yeni Vergi Dilimi Ekle</h2>
            <form id="matas-vergi-form">
                <input type="hidden" id="vergi_id" name="vergi_id" value="0">
                
                <div class="matas-form-row">
                    <div class="matas-form-group">
                        <label for="yil">Yıl:</label>
                        <select id="yil" name="yil" class="regular-text" required>
                            <option value="" disabled selected>Seçiniz</option>
                            <?php 
                            $current_year = date('Y');
                            for ($i = $current_year - 1; $i <= $current_year + 1; $i++) : 
                            ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="dilim">Vergi Dilimi:</label>
                        <select id="dilim" name="dilim" class="regular-text" required>
                            <option value="" disabled selected>Seçiniz</option>
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?>. Dilim</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="matas-form-row">
                    <div class="matas-form-group">
                        <label for="alt_limit">Alt Limit (TL):</label>
                        <input type="number" id="alt_limit" name="alt_limit" class="regular-text" required min="0" step="0.01" placeholder="Örn: 0">
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="ust_limit">Üst Limit (TL):</label>
                        <input type="number" id="ust_limit" name="ust_limit" class="regular-text" min="0" step="0.01" placeholder="Örn: 70000 (Son dilim için 0 giriniz)">
                        <p class="description">Son dilim için 0 giriniz (sınırsız)</p>
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="oran">Vergi Oranı (%):</label>
                        <input type="number" id="oran" name="oran" class="regular-text" required min="0" max="100" step="0.01" placeholder="Örn: 15">
                    </div>
                </div>
                
                <div class="matas-form-actions">
                    <button type="submit" class="button button-primary">Vergi Dilimini Kaydet</button>
                    <button type="button" id="btn-cancel-edit" class="button" style="display:none;">İptal</button>
                </div>
            </form>
        </div>
        
        <div class="matas-card">
            <h2>Kayıtlı Vergi Dilimleri</h2>
            <div id="matas-vergiler-list">
                <?php
                global $wpdb;
                $current_year = date('Y');
                $years = $wpdb->get_col("SELECT DISTINCT yil FROM {$wpdb->prefix}matas_vergiler ORDER BY yil DESC");
                
                if (count($years) > 0) {
                    foreach ($years as $year) {
                ?>
                <h3><?php echo esc_html($year); ?> Vergi Dilimleri</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Dilim</th>
                            <th>Alt Limit (TL)</th>
                            <th>Üst Limit (TL)</th>
                            <th>Vergi Oranı (%)</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $vergiler = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}matas_vergiler WHERE yil = %d ORDER BY dilim ASC",
                                $year
                            ),
                            ARRAY_A
                        );
                        
                        foreach ($vergiler as $vergi) {
                            echo '<tr data-id="' . $vergi['id'] . '">';
                            echo '<td>' . esc_html($vergi['dilim']) . '. Dilim</td>';
                            echo '<td>' . number_format($vergi['alt_limit'], 2, ',', '.') . ' TL</td>';
                            echo '<td>' . ($vergi['ust_limit'] > 0 ? number_format($vergi['ust_limit'], 2, ',', '.') . ' TL' : 'Sınırsız') . '</td>';
                            echo '<td>%' . number_format($vergi['oran'], 2, ',', '.') . '</td>';
                            echo '<td>';
                            echo '<button type="button" class="button button-small vergi-edit" data-id="' . $vergi['id'] . '">Düzenle</button> ';
                            echo '<button type="button" class="button button-small button-link-delete vergi-delete" data-id="' . $vergi['id'] . '">Sil</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <?php
                    }
                } else {
                    echo '<p>Henüz vergi dilimi kaydı bulunmuyor.</p>';
                }
                ?>
            </div>
        </div>
        
        <div class="matas-card">
            <h2>Vergi Dilimleri Toplu Yükleme</h2>
            <p>Aşağıdaki butonu kullanarak varsayılan vergi dilimlerini toplu olarak yükleyebilirsiniz.</p>
            <p><strong>Dikkat:</strong> Bu işlem seçili yıl için mevcut vergi dilimlerini silecektir.</p>
            
            <div class="matas-form-row">
                <div class="matas-form-group">
                    <label for="default_yil">Yıl:</label>
                    <select id="default_yil" name="default_yil" class="regular-text" required>
                        <?php 
                        $current_year = date('Y');
                        for ($i = $current_year - 1; $i <= $current_year + 1; $i++) : 
                        ?>
                            <option value="<?php echo $i; ?>" <?php selected($i, $current_year); ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="matas-form-actions">
                <button type="button" id="matas-vergi-yukle" class="button">Varsayılan Vergi Dilimlerini Yükle</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Vergi Formu Submit
    $('#matas-vergi-form').on('submit', function(e) {
        e.preventDefault();
        
        const vergiId = $('#vergi_id').val();
        const yil = $('#yil').val();
        const dilim = $('#dilim').val();
        const altLimit = parseFloat($('#alt_limit').val());
        const ustLimit = parseFloat($('#ust_limit').val());
        const oran = parseFloat($('#oran').val());
        
        // Form doğrulama
        if (!yil || !dilim || isNaN(altLimit) || isNaN(oran)) {
            alert('Lütfen tüm alanları doldurunuz!');
            return;
        }
        
        // AJAX isteği gönder
        $.ajax({
            url: matas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'matas_save_vergi_dilimi',
                nonce: matas_ajax.nonce,
                vergi_id: vergiId,
                yil: yil,
                dilim: dilim,
                alt_limit: altLimit,
                ust_limit: ustLimit || 0,
                oran: oran
            },
            beforeSend: function() {
                // Buton yükleniyor durumuna getir
                $('#matas-vergi-form button[type="submit"]').prop('disabled', true).text('Kaydediliyor...');
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
                $('#matas-vergi-form button[type="submit"]').prop('disabled', false).text('Vergi Dilimini Kaydet');
            }
        });
    });
    
    // Vergi Düzenle Butonu
    $(document).on('click', '.vergi-edit', function() {
        const vergiId = $(this).data('id');
        
        // AJAX ile vergi bilgilerini çek
        $.ajax({
            url: matas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'matas_get_vergi_dilimi',
                nonce: matas_ajax.nonce,
                vergi_id: vergiId
            },
            success: function(response) {
                if (response.success) {
                    // Form alanlarını doldur
                    const vergi = response.data.vergi;
                    $('#vergi_id').val(vergi.id);
                    $('#yil').val(vergi.yil);
                    $('#dilim').val(vergi.dilim);
                    $('#alt_limit').val(vergi.alt_limit);
                    $('#ust_limit').val(vergi.ust_limit);
                    $('#oran').val(vergi.oran);
                    
                    // Form başlığını değiştir
                    $('#vergi-form-title').text('Vergi Dilimini Düzenle');
                    // İptal butonunu göster
                    $('#btn-cancel-edit').show();
                    // Sayfayı form alanına kaydır
                    $('html, body').animate({
                        scrollTop: $('#matas-vergi-form').offset().top - 50
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
    
    // Vergi Silme Butonu
    $(document).on('click', '.vergi-delete', function() {
        if (confirm('Bu vergi dilimini silmek istediğinize emin misiniz?')) {
            const vergiId = $(this).data('id');
            
            // AJAX ile vergiyi sil
            $.ajax({
                url: matas_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'matas_delete_vergi_dilimi',
                    nonce: matas_ajax.nonce,
                    vergi_id: vergiId
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
        $('#matas-vergi-form')[0].reset();
        $('#vergi_id').val(0);
        
        // Form başlığını değiştir
        $('#vergi-form-title').text('Yeni Vergi Dilimi Ekle');
        // İptal butonunu gizle
        $(this).hide();
    });
    
    // Toplu Yükleme Butonu
    $('#matas-vergi-yukle').on('click', function() {
        const yil = $('#default_yil').val();
        
        if (confirm(`${yil} yılı için varsayılan vergi dilimlerini yüklemek istediğinize emin misiniz? Bu işlem mevcut vergi dilimlerini silecektir.`)) {
            // AJAX ile varsayılan vergi dilimlerini yükle
            $.ajax({
                url: matas_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'matas_load_default_vergiler',
                    nonce: matas_ajax.nonce,
                    yil: yil
                },
                beforeSend: function() {
                    // Buton yükleniyor durumuna getir
                    $('#matas-vergi-yukle').prop('disabled', true).text('Yükleniyor...');
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
                    $('#matas-vergi-yukle').prop('disabled', false).text('Varsayılan Vergi Dilimlerini Yükle');
                }
            });
        }
    });
});
</script> 

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="matas-container">
        <div class="matas-card">
            <h2 id="yardim-form-title">Yeni Sosyal Yardım Ekle</h2>
            <form id="matas-yardim-form">
                <input type="hidden" id="yardim_id" name="yardim_id" value="0">
                
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
                        <label for="tip">Yardım Tipi:</label>
                        <select id="tip" name="tip" class="regular-text" required>
                            <option value="" disabled selected>Seçiniz</option>
                            <option value="aile_yardimi">Aile Yardımı</option>
                            <option value="cocuk_normal">Çocuk Yardımı</option>
                            <option value="cocuk_0_6">0-6 Yaş Çocuk Yardımı</option>
                            <option value="cocuk_engelli">Engelli Çocuk Yardımı</option>
                            <option value="cocuk_ogrenim">Öğrenim Çocuk Yardımı</option>
                            <option value="kira_yardimi">Kira Yardımı</option>
                            <option value="sendika_yardimi">Sendika Yardımı</option>
                            <option value="yemek_yardimi">Yemek Yardımı</option>
                            <option value="giyecek_yardimi">Giyecek Yardımı</option>
                            <option value="yakacak_yardimi">Yakacak Yardımı</option>
                            <option value="dogum_yardimi">Doğum Yardımı</option>
                            <option value="olum_yardimi">Ölüm Yardımı</option>
                            <option value="tedavi_yardimi">Tedavi Yardımı</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                </div>
                
                <div class="matas-form-row">
                    <div class="matas-form-group">
                        <label for="adi">Yardım Adı:</label>
                        <input type="text" id="adi" name="adi" class="regular-text" required placeholder="Örn: Aile Yardımı">
                    </div>
                    
                    <div class="matas-form-group">
                        <label for="tutar">Tutar (TL):</label>
                        <input type="number" id="tutar" name="tutar" class="regular-text" required min="0" step="0.01" placeholder="Örn: 1200">
                    </div>
                </div>
                
                <div class="matas-form-actions">
                    <button type="submit" class="button button-primary">Sosyal Yardımı Kaydet</button>
                    <button type="button" id="btn-cancel-edit" class="button" style="display:none;">İptal</button>
                </div>
            </form>
        </div>
        
        <div class="matas-card">
            <h2>Kayıtlı Sosyal Yardımlar</h2>
            <div id="matas-yardimlar-list">
                <?php
                global $wpdb;
                $current_year = date('Y');
                $years = $wpdb->get_col("SELECT DISTINCT yil FROM {$wpdb->prefix}matas_sosyal_yardimlar ORDER BY yil DESC");
                
                if (count($years) > 0) {
                    foreach ($years as $year) {
                ?>
                <h3><?php echo esc_html($year); ?> Sosyal Yardımlar</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Yardım Tipi</th>
                            <th>Yardım Adı</th>
                            <th>Tutar (TL)</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $yardimlar = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT * FROM {$wpdb->prefix}matas_sosyal_yardimlar WHERE yil = %d ORDER BY adi ASC",
                                $year
                            ),
                            ARRAY_A
                        );
                        
                        foreach ($yardimlar as $yardim) {
                            echo '<tr data-id="' . $yardim['id'] . '">';
                            echo '<td>' . esc_html($yardim['tip']) . '</td>';
                            echo '<td>' . esc_html($yardim['adi']) . '</td>';
                            echo '<td>' . number_format($yardim['tutar'], 2, ',', '.') . ' TL</td>';
                            echo '<td>';
                            echo '<button type="button" class="button button-small yardim-edit" data-id="' . $yardim['id'] . '">Düzenle</button> ';
                            echo '<button type="button" class="button button-small button-link-delete yardim-delete" data-id="' . $yardim['id'] . '">Sil</button>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
                <?php
                    }
                } else {
                    echo '<p>Henüz sosyal yardım kaydı bulunmuyor.</p>';
                }
                ?>
            </div>
        </div>
        
        <div class="matas-card">
            <h2>Sosyal Yardımlar Toplu Yükleme</h2>
            <p>Aşağıdaki butonu kullanarak varsayılan sosyal yardımları toplu olarak yükleyebilirsiniz.</p>
            <p><strong>Dikkat:</strong> Bu işlem seçili yıl için mevcut sosyal yardımları silecektir.</p>
            
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
                <button type="button" id="matas-yardim-yukle" class="button">Varsayılan Sosyal Yardımları Yükle</button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Yardım formunda tipi seçildiğinde adını da otomatik doldur
    $('#tip').on('change', function() {
        const tipDegeri = $(this).val();
        let yardimAdi = '';
        
        switch (tipDegeri) {
            case 'aile_yardimi':
                yardimAdi = 'Aile Yardımı';
                break;
            case 'cocuk_normal':
                yardimAdi = 'Çocuk Yardımı';
                break;
            case 'cocuk_0_6':
                yardimAdi = '0-6 Yaş Çocuk Yardımı';
                break;
            case 'cocuk_engelli':
                yardimAdi = 'Engelli Çocuk Yardımı';
                break;
            case 'cocuk_ogrenim':
                yardimAdi = 'Öğrenim Çocuk Yardımı';
                break;
            case 'kira_yardimi':
                yardimAdi = 'Kira Yardımı';
                break;
            case 'sendika_yardimi':
                yardimAdi = 'Sendika Yardımı';
                break;
            case 'yemek_yardimi':
                yardimAdi = 'Yemek Yardımı';
                break;
            case 'giyecek_yardimi':
                yardimAdi = 'Giyecek Yardımı';
                break;
            case 'yakacak_yardimi':
                yardimAdi = 'Yakacak Yardımı';
                break;
            case 'dogum_yardimi':
                yardimAdi = 'Doğum Yardımı';
                break;
            case 'olum_yardimi':
                yardimAdi = 'Ölüm Yardımı';
                break;
            case 'tedavi_yardimi':
                yardimAdi = 'Tedavi Yardımı';
                break;
            default:
                yardimAdi = '';
        }
        
        $('#adi').val(yardimAdi);
    });
    
    // Yardım Formu Submit
    $('#matas-yardim-form').on('submit', function(e) {
        e.preventDefault();
        
        const yardimId = $('#yardim_id').val();
        const yil = $('#yil').val();
        const tip = $('#tip').val();
        const adi = $('#adi').val();
        const tutar = parseFloat($('#tutar').val());
        
        // Form doğrulama
        if (!yil || !tip || !adi || isNaN(tutar)) {
            alert('Lütfen tüm alanları doldurunuz!');
            return;
        }
        
        // AJAX isteği gönder
        $.ajax({
            url: matas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'matas_save_sosyal_yardim',
                nonce: matas_ajax.nonce,
                yardim_id: yardimId,
                yil: yil,
                tip: tip,
                adi: adi,
                tutar: tutar
            },
            beforeSend: function() {
                // Buton yükleniyor durumuna getir
                $('#matas-yardim-form button[type="submit"]').prop('disabled', true).text('Kaydediliyor...');
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
                $('#matas-yardim-form button[type="submit"]').prop('disabled', false).text('Sosyal Yardımı Kaydet');
            }
        });
    });
    
    // Yardım Düzenle Butonu
    $(document).on('click', '.yardim-edit', function() {
        const yardimId = $(this).data('id');
        
        // AJAX ile yardım bilgilerini çek
        $.ajax({
            url: matas_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'matas_get_sosyal_yardim',
                nonce: matas_ajax.nonce,
                yardim_id: yardimId
            },
            success: function(response) {
                if (response.success) {
                    // Form alanlarını doldur
                    const yardim = response.data.yardim;
                    $('#yardim_id').val(yardim.id);
                    $('#yil').val(yardim.yil);
                    $('#tip').val(yardim.tip);
                    $('#adi').val(yardim.adi);
                    $('#tutar').val(yardim.tutar);
                    
                    // Form başlığını değiştir
                    $('#yardim-form-title').text('Sosyal Yardımı Düzenle');
                    // İptal butonunu göster
                    $('#btn-cancel-edit').show();
                    // Sayfayı form alanına kaydır
                    $('html, body').animate({
                        scrollTop: $('#matas-yardim-form').offset().top - 50
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
    
    // Yardım Silme Butonu
    $(document).on('click', '.yardim-delete', function() {
        if (confirm('Bu sosyal yardımı silmek istediğinize emin misiniz?')) {
            const yardimId = $(this).data('id');
            
            // AJAX ile yardımı sil
            $.ajax({
                url: matas_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'matas_delete_sosyal_yardim',
                    nonce: matas_ajax.nonce,
                    yardim_id: yardimId
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
        $('#matas-yardim-form')[0].reset();
        $('#yardim_id').val(0);
        
        // Form başlığını değiştir
        $('#yardim-form-title').text('Yeni Sosyal Yardım Ekle');
        // İptal butonunu gizle
        $(this).hide();
    });
    
    // Toplu Yükleme Butonu
    $('#matas-yardim-yukle').on('click', function() {
        const yil = $('#default_yil').val();
        
        if (confirm(`${yil} yılı için varsayılan sosyal yardımları yüklemek istediğinize emin misiniz? Bu işlem mevcut sosyal yardımları silecektir.`)) {
            // AJAX ile varsayılan sosyal yardımları yükle
            $.ajax({
                url: matas_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'matas_load_default_sosyal_yardimlar',
                    nonce: matas_ajax.nonce,
                    yil: yil
                },
                beforeSend: function() {
                    // Buton yükleniyor durumuna getir
                    $('#matas-yardim-yukle').prop('disabled', true).text('Yükleniyor...');
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
                    $('#matas-yardim-yukle').prop('disabled', false).text('Varsayılan Sosyal Yardımları Yükle');
                }
            });
        }
    });
});
</script> 

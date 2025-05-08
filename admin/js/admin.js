(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Mesaj gösterme fonksiyonu
        window.showMessage = function(message, type) {
            // Var olan mesajları kaldır
            $('.matas-message').remove();
            
            // Yeni mesaj oluştur
            const $message = $('<div class="matas-message ' + type + '">' + message + '</div>');
            
            // Mesajı sayfaya ekle
            $('.matas-container').prepend($message);
            
            // 5 saniye sonra mesajı kaldır
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        };
        
        // Katsayılar sayfası
        if ($('#matas-katsayi-form').length) {
            $('#matas-katsayi-form').on('submit', function(e) {
                e.preventDefault();
                
                const donem = $('#donem').val();
                const aylikKatsayi = parseFloat($('#aylik_katsayi').val());
                const tabanKatsayi = parseFloat($('#taban_katsayi').val());
                const yanOdemeKatsayi = parseFloat($('#yan_odeme_katsayi').val());
                
                // Form doğrulama
                if (!donem || isNaN(aylikKatsayi) || isNaN(tabanKatsayi) || isNaN(yanOdemeKatsayi)) {
                    showMessage('Lütfen tüm alanları doldurunuz!', 'error');
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
                        aylik_katsayi: aylikKatsayi,
                        taban_katsayi: tabanKatsayi,
                        yan_odeme_katsayi: yanOdemeKatsayi
                    },
                    beforeSend: function() {
                        // Buton yükleniyor durumuna getir
                        $('#matas-katsayi-form button[type="submit"]').prop('disabled', true).text('Kaydediliyor...');
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, 'success');
                            // Sayfayı yenile
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showMessage(response.data.message || 'Bir hata oluştu!', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Sunucu ile iletişim kurulamadı!', 'error');
                    },
                    complete: function() {
                        // Buton normal durumuna getir
                        $('#matas-katsayi-form button[type="submit"]').prop('disabled', false).text('Katsayıları Kaydet');
                    }
                });
            });
        }
        
        // Ünvanlar sayfası
        if ($('#matas-unvan-form').length) {
            // Unvan formu gönderildiğinde
            $('#matas-unvan-form').on('submit', function(e) {
                e.preventDefault();
                
                const unvanId = $('#unvan_id').val();
                const unvanKodu = $('#unvan_kodu').val();
                const unvanAdi = $('#unvan_adi').val();
                const ekgosterge = parseInt($('#ekgosterge').val());
                const ozelHizmet = parseInt($('#ozel_hizmet').val());
                const yanOdeme = parseInt($('#yan_odeme').val());
                const isGuclugu = parseInt($('#is_guclugu').val());
                const makamTazminat = parseInt($('#makam_tazminat').val());
                const egitimTazminat = parseFloat($('#egitim_tazminat').val());
                
                // Form doğrulama
                if (!unvanKodu || !unvanAdi || isNaN(ekgosterge) || isNaN(ozelHizmet) || 
                    isNaN(yanOdeme) || isNaN(isGuclugu) || isNaN(makamTazminat) || isNaN(egitimTazminat)) {
                    showMessage('Lütfen tüm alanları doldurunuz!', 'error');
                    return;
                }
                
                // AJAX isteği gönder
                $.ajax({
                    url: matas_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'matas_save_unvan',
                        nonce: matas_ajax.nonce,
                        unvan_id: unvanId,
                        unvan_kodu: unvanKodu,
                        unvan_adi: unvanAdi,
                        ekgosterge: ekgosterge,
                        ozel_hizmet: ozelHizmet,
                        yan_odeme: yanOdeme,
                        is_guclugu: isGuclugu,
                        makam_tazminat: makamTazminat,
                        egitim_tazminat: egitimTazminat
                    },
                    beforeSend: function() {
                        // Buton yükleniyor durumuna getir
                        $('#matas-unvan-form button[type="submit"]').prop('disabled', true).text('Kaydediliyor...');
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, 'success');
                            // Sayfayı yenile
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showMessage(response.data.message || 'Bir hata oluştu!', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Sunucu ile iletişim kurulamadı!', 'error');
                    },
                    complete: function() {
                        // Buton normal durumuna getir
                        $('#matas-unvan-form button[type="submit"]').prop('disabled', false).text('Ünvanı Kaydet');
                    }
                });
            });
            
            // Ünvan Düzenle Butonu
            $(document).on('click', '.unvan-edit', function() {
                const unvanId = $(this).data('id');
                
                // AJAX ile ünvan bilgilerini çek
                $.ajax({
                    url: matas_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'matas_get_unvan',
                        nonce: matas_ajax.nonce,
                        unvan_id: unvanId
                    },
                    success: function(response) {
                        if (response.success) {
                            // Form alanlarını doldur
                            const unvan = response.data.unvan;
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
                            showMessage(response.data.message || 'Bir hata oluştu!', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Sunucu ile iletişim kurulamadı!', 'error');
                    }
                });
            });
            
            // Ünvan Silme Butonu
            $(document).on('click', '.unvan-delete', function() {
                if (confirm('Bu ünvanı silmek istediğinize emin misiniz?')) {
                    const unvanId = $(this).data('id');
                    
                    // AJAX ile ünvanı sil
                    $.ajax({
                        url: matas_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'matas_delete_unvan',
                            nonce: matas_ajax.nonce,
                            unvan_id: unvanId
                        },
                        success: function(response) {
                            if (response.success) {
                                showMessage(response.data.message, 'success');
                                // Sayfayı yenile
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                showMessage(response.data.message || 'Bir hata oluştu!', 'error');
                            }
                        },
                        error: function() {
                            showMessage('Sunucu ile iletişim kurulamadı!', 'error');
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
        }
        
        // Gösterge Puanları sayfası
        if ($('#matas-gosterge-form').length) {
            // Gösterge formu gönderildiğinde
            $('#matas-gosterge-form').on('submit', function(e) {
                e.preventDefault();
                
                const gostergeId = $('#gosterge_id').val();
                const derece = parseInt($('#derece').val());
                const kademe = parseInt($('#kademe').val());
                const gostergePuani = parseInt($('#gosterge_puani').val());
                
                // Form doğrulama
                if (isNaN(derece) || isNaN(kademe) || isNaN(gostergePuani)) {
                    showMessage('Lütfen tüm alanları doldurunuz!', 'error');
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
                            showMessage(response.data.message, 'success');
                            // Sayfayı yenile
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showMessage(response.data.message || 'Bir hata oluştu!', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Sunucu ile iletişim kurulamadı!', 'error');
                    },
                    complete: function() {
                        // Buton normal durumuna getir
                        $('#matas-gosterge-form button[type="submit"]').prop('disabled', false).text('Gösterge Puanını Kaydet');
                    }
                });
            });
        }
        
        // Vergi Dilimleri sayfası
        if ($('#matas-vergi-form').length) {
            // Vergi formu gönderildiğinde
            $('#matas-vergi-form').on('submit', function(e) {
                e.preventDefault();
                
                const vergiId = $('#vergi_id').val();
                const yil = parseInt($('#yil').val());
                const dilim = parseInt($('#dilim').val());
                const altLimit = parseFloat($('#alt_limit').val());
                const ustLimit = parseFloat($('#ust_limit').val());
                const oran = parseFloat($('#oran').val());
                
                // Form doğrulama
                if (isNaN(yil) || isNaN(dilim) || isNaN(altLimit) || isNaN(oran)) {
                    showMessage('Lütfen tüm alanları doldurunuz!', 'error');
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
                        ust_limit: ustLimit,
                        oran: oran
                    },
                    beforeSend: function() {
                        // Buton yükleniyor durumuna getir
                        $('#matas-vergi-form button[type="submit"]').prop('disabled', true).text('Kaydediliyor...');
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, 'success');
                            // Sayfayı yenile
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showMessage(response.data.message || 'Bir hata oluştu!', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Sunucu ile iletişim kurulamadı!', 'error');
                    },
                    complete: function() {
                        // Buton normal durumuna getir
                        $('#matas-vergi-form button[type="submit"]').prop('disabled', false).text('Vergi Dilimini Kaydet');
                    }
                });
            });
        }
        
        // Sosyal Yardımlar sayfası
        if ($('#matas-yardim-form').length) {
            // Yardım formu gönderildiğinde
            $('#matas-yardim-form').on('submit', function(e) {
                e.preventDefault();
                
                const yardimId = $('#yardim_id').val();
                const yil = parseInt($('#yil').val());
                const tip = $('#tip').val();
                const adi = $('#adi').val();
                const tutar = parseFloat($('#tutar').val());
                
                // Form doğrulama
                if (isNaN(yil) || !tip || !adi || isNaN(tutar)) {
                    showMessage('Lütfen tüm alanları doldurunuz!', 'error');
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
                            showMessage(response.data.message, 'success');
                            // Sayfayı yenile
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showMessage(response.data.message || 'Bir hata oluştu!', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Sunucu ile iletişim kurulamadı!', 'error');
                    },
                    complete: function() {
                        // Buton normal durumuna getir
                        $('#matas-yardim-form button[type="submit"]').prop('disabled', false).text('Sosyal Yardımı Kaydet');
                    }
                });
            });
        }
    });
})(jQuery); 

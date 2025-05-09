/**
 * MATAS - Maaş Takip Sistemi
 * Frontend JavaScript
 */
(function($) {
    'use strict';
    
    // DOM yüklendikten sonra çalışacak fonksiyonlar
    $(document).ready(function() {
        initializeMatcas();
    });
    
    /**
     * MATAS formunu başlat
     */
    function initializeMatcas() {
        // Tooltip'leri başlat
        initTooltips();
        
        // Tab değişimini yönet
        $('.matas-tab').on('click', function() {
            const tabId = $(this).data('tab');
            openTab(tabId);
        });
        
        // Bölüm başlıklarını açıp kapatma
        $('.matas-form-section-header').on('click', function() {
            const content = $(this).next('.matas-form-section-content');
            const button = $(this).find('.matas-form-section-toggle');
            
            if (content.is(':visible')) {
                content.slideUp(200);
                button.text('+');
            } else {
                content.slideDown(200);
                button.text('-');
            }
        });
        
        // Ünvan değiştiğinde diğer alanları güncelle
        $('#unvan').on('change', function() {
            unvanChanged();
        });
        
        // Derece değiştiğinde kademe seçeneklerini güncelle
        $('#derece').on('change', function() {
            kademeOptionlariGuncelle();
        });
        
        // Eş durum alanlarını güncelle
        $('#medeni_hal').on('change', function() {
            esDurumGuncelle();
        });
        
        // Çocuk durumu alanlarını güncelle
        $('#cocuk_sayisi').on('change', function() {
            cocukDurumuGuncelle();
        });
        
        // Dil seviyesi değiştiğinde kullanım alanını göster/gizle
        $('#dil_seviyesi').on('change', function() {
            dilSeviyesiGuncelle();
        });
        
        // Hesaplama butonuna basıldığında
        $('#matas-hesapla-btn').on('click', function() {
            hesaplaMaas();
        });
        
        // Formu temizle butonuna basıldığında
        $('#matas-temizle-btn').on('click', function() {
            formuTemizle();
        });
        
        // Yedekleme işlemleri
        $('#matas-backup-btn').on('click', function() {
            saveFormData();
        });
        
        $('#matas-restore-btn').on('click', function() {
            $('#matas-restore-file').click();
        });
        
        $('#matas-restore-file').on('change', function(e) {
            loadFormData(e);
        });
        
        // Sayfa ilk yüklendiğinde gerekli fonksiyonları çalıştır
        kademeOptionlariGuncelle();
        esDurumGuncelle();
        cocukDurumuGuncelle();
        dilSeviyesiGuncelle();
        loadBackupList();
    }
    
    /**
     * Tooltip'leri başlat
     */
    function initTooltips() {
        try {
            $('.matas-tooltip').hover(
                function() {
                    const tooltipText = $(this).data('tip');
                    $('<div class="matas-tooltip-popup">' + tooltipText + '</div>').appendTo('body')
                        .css({
                            top: $(this).offset().top - 40,
                            left: $(this).offset().left - 100,
                            position: 'absolute'
                        })
                        .fadeIn('fast');
                },
                function() {
                    $('.matas-tooltip-popup').remove();
                }
            );
        } catch (e) {
            console.error('Tooltip başlatma hatası:', e);
        }
    }
    
    /**
     * Sekmeler arasında geçiş yapmak için fonksiyon
     * 
     * @param {string} tabName Tab adı
     */
    function openTab(tabName) {
        try {
            // Tüm sekme içeriklerini gizle
            $('.matas-tab-content').hide();
            
            // Tüm sekmelerin aktiflik sınıfını kaldır
            $('.matas-tab').removeClass('active');
            
            // Seçilen sekme içeriğini göster
            $('#matas-' + tabName).show();
            
            // Seçilen sekmeyi aktif yap
            $('.matas-tab[data-tab="' + tabName + '"]').addClass('active');
        } catch (e) {
            console.error('Tab açma hatası:', e);
        }
    }
    
    /**
     * Ünvan değiştiğinde bu fonksiyon çalışır
     */
    function unvanChanged() {
        try {
            const selectedOption = $('#unvan option:selected');
            if (selectedOption.val()) {
                // Ünvan bilgilerini form alanlarına yansıt
                $('#ekgosterge').val(selectedOption.data('ekgosterge'));
                $('#ozel_hizmet').val(selectedOption.data('ozelhizmet'));
                $('#yan_odeme').val(selectedOption.data('yanodeme'));
                $('#is_guclugu').val(selectedOption.data('isguclugu'));
            }
        } catch (e) {
            console.error('Ünvan değiştirme hatası:', e);
        }
    }
    
    /**
     * Derece değiştiğinde kademe seçeneklerini güncelle
     */
    function kademeOptionlariGuncelle() {
        try {
            const derece = parseInt($('#derece').val());
            
            if (!derece) return;
            
            // Kademe sayısını belirle
            let maxKademe = 9;
            
            // 1. ve 2. dereceler için kademe sayısı 8 olarak belirlenir
            if (derece === 1 || derece === 2) {
                maxKademe = 8;
            }
            
            // Kademe selectbox'ını güncelle
            const $kademe = $('#kademe');
            const currentVal = $kademe.val();
            
            // Kademe seçeneklerini temizle
            $kademe.empty();
            
            // Varsayılan seçenek ekle
            $kademe.append('<option value="" disabled' + (!currentVal ? ' selected' : '') + '>Seçiniz</option>');
            
            // Kademe seçeneklerini ekle
            for (let i = 1; i <= maxKademe; i++) {
                $kademe.append('<option value="' + i + '"' + (currentVal == i ? ' selected' : '') + '>' + i + '. Kademe</option>');
            }
        } catch (e) {
            console.error('Kademe güncelleme hatası:', e);
        }
    }
    
    /**
     * Medeni hal değiştiğinde eş durumu alanını güncelle
     */
    function esDurumGuncelle() {
        try {
            const medeniHal = $('#medeni_hal').val();
            
            if (medeniHal === 'evli') {
                $('#es_durum_group').show();
            } else {
                $('#es_durum_group').hide();
                $('#es_calisiyor').val('evet');
            }
        } catch (e) {
            console.error('Eş durumu güncelleme hatası:', e);
        }
    }
    
    /**
     * Çocuk durumu alanlarını güncelle
     */
    function cocukDurumuGuncelle() {
        try {
            const cocukSayisi = parseInt($('#cocuk_sayisi').val()) || 0;
            
            if (cocukSayisi > 0) {
                $('#cocuk_06_group, #engelli_cocuk_group, #ogrenim_cocuk_group').show();
                
                // Çocuk sayısı kontrolü
                const cocuk06 = parseInt($('#cocuk_06').val()) || 0;
                const engelliCocuk = parseInt($('#engelli_cocuk').val()) || 0;
                const ogrenimCocuk = parseInt($('#ogrenim_cocuk').val()) || 0;
                
                // Toplam çocuk sayısını kontrol et
                if (cocuk06 + engelliCocuk + ogrenimCocuk > cocukSayisi) {
                    // Değerleri sıfırla
                    $('#cocuk_06, #engelli_cocuk, #ogrenim_cocuk').val(0);
                    alert('Belirtilen özel durumlu çocuk sayısı toplam çocuk sayısından fazla olamaz!');
                }
            } else {
                $('#cocuk_06_group, #engelli_cocuk_group, #ogrenim_cocuk_group').hide();
                $('#cocuk_06, #engelli_cocuk, #ogrenim_cocuk').val(0);
            }
        } catch (e) {
            console.error('Çocuk durumu güncelleme hatası:', e);
        }
    }
    
    /**
     * Dil seviyesi değiştiğinde kullanım alanını güncelle
     */
    function dilSeviyesiGuncelle() {
        try {
            const dilSeviyesi = $('#dil_seviyesi').val();
            
            if (dilSeviyesi !== 'yok') {
                $('#dil_kullanimi_group').show();
            } else {
                $('#dil_kullanimi_group').hide();
                $('#dil_kullanimi').val('hayir');
            }
        } catch (e) {
            console.error('Dil seviyesi güncelleme hatası:', e);
        }
    }
    
    /**
     * Maaş hesaplama fonksiyonu
     */
    function hesaplaMaas() {
        try {
            // Form kontrolü
            if (!validateForm()) {
                return;
            }
            
            // Loading göster
            $('#matas-hesapla-btn').prop('disabled', true).html('<span class="spinner"></span> Hesaplanıyor...');
// Form verilerini topla
            const formData = new FormData(document.getElementById('matas-hesaplama-form'));
            formData.append('action', 'matas_hesapla');
            formData.append('nonce', matas_ajax.nonce);
            
            // Checkbox değerlerini kontrol et ve ekle
            formData.set('gorev_tazminati', $('#gorev_tazminati').is(':checked') ? 1 : 0);
            formData.set('gelistirme_odenegi', $('#gelistirme_odenegi').is(':checked') ? 1 : 0);
            formData.set('asgari_gecim_indirimi', $('#asgari_gecim_indirimi').is(':checked') ? 1 : 0);
            formData.set('kira_yardimi', $('#kira_yardimi').is(':checked') ? 1 : 0);
            formData.set('sendika_uyesi', $('#sendika_uyesi').is(':checked') ? 1 : 0);
            
            // AJAX isteği gönder
            $.ajax({
                url: matas_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                success: function(response) {
                    if (response.success) {
                        // Sonuçları göster
                        showResults(response.data);
                    } else {
                        alert(response.data.message || 'Hesaplama sırasında bir hata oluştu.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX hatası:", error);
                    alert('Sunucu ile iletişim kurulurken bir hata oluştu: ' + error);
                },
                complete: function() {
                    // Loading kaldır
                    $('#matas-hesapla-btn').prop('disabled', false).html(`
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg> Maaş Hesapla
                    `);
                }
            });
        } catch (e) {
            console.error('Hesaplama hatası:', e);
            alert('Hesaplama sırasında bir hata oluştu: ' + e.message);
            
            // Loading kaldır
            $('#matas-hesapla-btn').prop('disabled', false).html(`
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg> Maaş Hesapla
            `);
        }
    }
    
    /**
     * Form doğrulama fonksiyonu
     * 
     * @returns {boolean} Form geçerli mi
     */
    function validateForm() {
        try {
            let isValid = true;
            let errorMessages = [];
            
            // Zorunlu alanları kontrol et
            $('.matas-form-control[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('has-error');
                    isValid = false;
                    
                    // Hata mesajını ekle
                    const labelText = $(this).closest('.matas-form-group').find('label').text().replace(':', '').trim();
                    errorMessages.push(labelText + ' alanı boş olamaz');
                } else {
                    $(this).removeClass('has-error');
                }
            });
            
            // Çocuk sayısı kontrolü
            const cocukSayisi = parseInt($('#cocuk_sayisi').val()) || 0;
            if (cocukSayisi > 0) {
                const cocuk06 = parseInt($('#cocuk_06').val()) || 0;
                const engelliCocuk = parseInt($('#engelli_cocuk').val()) || 0;
                const ogrenimCocuk = parseInt($('#ogrenim_cocuk').val()) || 0;
                
                if (cocuk06 + engelliCocuk + ogrenimCocuk > cocukSayisi) {
                    isValid = false;
                    errorMessages.push('Belirtilen özel durumlu çocuk sayısı toplam çocuk sayısından fazla olamaz');
                }
            }
            
            // Hata mesajlarını göster
            if (!isValid) {
                alert('Lütfen formu kontrol ediniz:\n' + errorMessages.join('\n'));
            }
            
            return isValid;
        } catch (e) {
            console.error('Form doğrulama hatası:', e);
            alert('Form doğrulama sırasında bir hata oluştu: ' + e.message);
            return false;
        }
    }
    
    /**
     * Formu temizle
     */
    function formuTemizle() {
        try {
            $('#matas-hesaplama-form')[0].reset();
            $('.matas-form-control').removeClass('has-error');
            $('#matas-sonuclar').slideUp();
            
            // Select elementlerini varsayılan değerlere döndür
            $('#unvan, #derece, #kademe').val('').trigger('change');
            
            // Form alanlarını güncelle
            esDurumGuncelle();
            cocukDurumuGuncelle();
            dilSeviyesiGuncelle();
        } catch (e) {
            console.error('Form temizleme hatası:', e);
        }
    }
    
    /**
     * Sonuçları gösterme fonksiyonu
     * 
     * @param {Object} data Hesaplama sonuçları
     */
    function showResults(data) {
        try {
            // Sayısal değerleri formatlı göster
            const formatParaBirimi = function(deger) {
                return parseFloat(deger).toLocaleString('tr-TR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' TL';
            };
            
            // Sonuç HTML'ini oluştur
            let html = `
                <div class="matas-result-header">
                    <h2>${data.donem} Dönemi Maaş Bilgileri</h2>
                    <p>${data.unvanAdi} - ${$('#derece').val()}/${$('#kademe').val()}</p>
                </div>
                <div class="matas-result-content">
                    <div class="matas-result-group">Kazançlar</div>
                    <div class="matas-result-row">
                        <div>Taban Aylığı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.tabanAyligi)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Ek Gösterge Aylığı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.ekGostergeTutari)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Kıdem Aylığı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.kidemAyligi)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Yan Ödeme</div>
                        <div class="matas-result-value">${formatParaBirimi(data.yanOdeme)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Özel Hizmet Tazminatı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.ozelHizmetTazminati)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>İş Güçlüğü Zammı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.isGucluguzammi)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Yabancı Dil Tazminatı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.dilTazminati)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Ek Ödeme (666 KHK)</div>
                        <div class="matas-result-value">${formatParaBirimi(data.ekOdeme)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Eğitim-Öğretim Tazminatı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.egitimTazminati)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Geliştirme Ödeneği</div>
                        <div class="matas-result-value">${formatParaBirimi(data.gelistirmeOdenegiTutari)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Makam/Görev/Temsil Tazminatı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.makamTazminati)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Lisansüstü Eğitim Tazminatı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.lisansustuTazminat)}</div>
                    </div>
                    
                    <div class="matas-result-group">Sosyal Yardımlar</div>
                    <div class="matas-result-row">
                        <div>Aile Yardımı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.aileYardimi)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Çocuk Yardımı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.cocukYardimi)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Kira Yardımı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.kiraYardimiTutari)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Sendika Yardımı</div>
                        <div class="matas-result-value">${formatParaBirimi(data.sendikaYardimi)}</div>
                    </div>
                    
                    <div class="matas-result-group">Kesintiler</div>
                    <div class="matas-result-row">
                        <div>Emekli Keseneği (%16)</div>
                        <div class="matas-result-value">${formatParaBirimi(data.emekliKesenegi)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Gelir Vergisi</div>
                        <div class="matas-result-value">${formatParaBirimi(data.gelirVergisi)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Damga Vergisi</div>
                        <div class="matas-result-value">${formatParaBirimi(data.damgaVergisi)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Genel Sağlık Sigortası</div>
                        <div class="matas-result-value">${formatParaBirimi(data.gssPrimi)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Sendika Kesintisi</div>
                        <div class="matas-result-value">${formatParaBirimi(data.sendikaKesintisi)}</div>
                    </div>
                    <div class="matas-result-row">
                        <div>Kefalet/İcra Kesintisi</div>
                        <div class="matas-result-value">${formatParaBirimi(data.kefalet)}</div>
                    </div>
                    
                    <div class="matas-result-total">
                        <div>Toplam Brüt Maaş: <span class="matas-total-value">${formatParaBirimi(data.brutMaas)}</span></div>
                        <div>Toplam Kesintiler: <span class="matas-total-value">${formatParaBirimi(data.toplamKesintiler)}</span></div>
                        <div>Toplam Net Maaş: <span class="matas-total-value">${formatParaBirimi(data.netMaas)}</span></div>
                    </div>
                    
                    <button type="button" class="matas-detail-toggle" onclick="toggleDetail()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12" y2="8"></line>
                        </svg>
                        Hesaplama Detaylarını Göster
                    </button>
                    
                    <div id="matas-detail-content" class="matas-detail-content">
                        <h3>Hesaplamada Kullanılan Katsayılar</h3>
                        <p>Aylık Katsayı: ${data.aylikKatsayi}</p>
                        <p>Taban Aylık Katsayısı: ${data.tabanKatsayi}</p>
                        <p>Yan Ödeme Katsayısı: ${data.yanOdemeKatsayi}</p>
                        <p>Gösterge Puanı: ${data.gostergePuani}</p>
                    </div>
                    
                    <div class="matas-note">
                        <div class="matas-note-title">Önemli Not:</div>
                        <p>Bu hesaplama ${data.donem} dönemi için yaklaşık değerlerdir ve resmi hesaplamalarla farklılık gösterebilir. Kesin bilgi için lütfen kurumunuzun özlük birimine başvurunuz.</p>
                    </div>
                </div>
            `;
            
            // Sonuçları göster
            $('#matas-sonuclar').html(html).slideDown(400);
            
            // Sayfayı sonuçlara kaydır
            $('html, body').animate({
                scrollTop: $('#matas-sonuclar').offset().top - 50
            }, 500);
        } catch (e) {
            console.error('Sonuç gösterme hatası:', e);
            alert('Sonuçlar gösterilirken bir hata oluştu: ' + e.message);
        }
    }
    
    /**
     * Detayları göster/gizle
     */
    window.toggleDetail = function() {
        try {
            const detailContent = $('#matas-detail-content');
            const detailToggle = $('.matas-detail-toggle');
            
            if (detailContent.is(':visible')) {
                detailContent.slideUp();
                detailToggle.html(`
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="8"></line>
                    </svg>
                    Hesaplama Detaylarını Göster
                `);
            } else {
                detailContent.slideDown();
                detailToggle.html(`
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12" y2="16"></line>
                    </svg>
                    Hesaplama Detaylarını Gizle
                `);
            }
        } catch (e) {
            console.error('Detay gösterme hatası:', e);
        }
    };
    
    /**
     * Form verilerini yerel depolamaya kaydet
     */
    function saveFormData() {
        try {
            const formData = {};
            $('#matas-hesaplama-form').serializeArray().forEach(function(field) {
                formData[field.name] = field.value;
            });
            
            // Checkbox değerlerini ekle
            formData.gorev_tazminati = $('#gorev_tazminati').is(':checked');
            formData.gelistirme_odenegi = $('#gelistirme_odenegi').is(':checked');
            formData.asgari_gecim_indirimi = $('#asgari_gecim_indirimi').is(':checked');
            formData.kira_yardimi = $('#kira_yardimi').is(':checked');
            formData.sendika_uyesi = $('#sendika_uyesi').is(':checked');
            
            // Tarih ve isim ekle
            formData.tarih = new Date().toLocaleString('tr-TR');
            formData.isim = 'Yedek ' + new Date().toLocaleString('tr-TR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Yerel depolamadan mevcut yedekleri al
            let backups = [];
            try {
                const savedBackups = localStorage.getItem('matas_backups');
                if (savedBackups) {
                    backups = JSON.parse(savedBackups);
                }
            } catch (e) {
                console.error('Kayıtlı yedekler yüklenirken hata:', e);
                backups = [];
            }
            
            // Yeni yedeği ekle
            backups.push(formData);
            
            // Yerel depolamaya kaydet
            localStorage.setItem('matas_backups', JSON.stringify(backups));
            
            // Kullanıcıya bilgi ver
            alert('Form verileri başarıyla yedeklendi.');
            
            // Yedek listesini güncelle
            loadBackupList();
        } catch (e) {
            console.error('Form verilerini kaydetme hatası:', e);
            alert('Form verileri kaydedilirken bir hata oluştu: ' + e.message);
        }
    }
    
    /**
     * Form verilerini dosyadan yükle
     * 
     * @param {Event} e Dosya seçme olayı
     */
    function loadFormData(e) {
        try {
            const file = e.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(event) {
                try {
                    const formData = JSON.parse(event.target.result);
                    
                    // Form verilerini yükle
                    for (const key in formData) {
                        if (key === 'isim' || key === 'tarih') continue;
                        
                        if (['gorev_tazminati', 'gelistirme_odenegi', 'asgari_gecim_indirimi', 'kira_yardimi', 'sendika_uyesi'].includes(key)) {
                            $('#' + key).prop('checked', formData[key]);
                        } else {
                            $('#' + key).val(formData[key]);
                        }
                    }
                    
                    // Select elementlerini güncelle
                    $('#derece, #medeni_hal, #cocuk_sayisi, #dil_seviyesi').trigger('change');
                    
                    // Kullanıcıya bilgi ver
                    alert('Form verileri başarıyla yüklendi.');
                } catch (error) {
                    console.error('Dosya içeriği hatası:', error);
                    alert('Dosya yüklenirken bir hata oluştu. Lütfen geçerli bir yedek dosyası seçin.');
                }
            };
            
            reader.onerror = function(error) {
                console.error('Dosya okuma hatası:', error);
                alert('Dosya okunurken bir hata oluştu.');
            };
            
            reader.readAsText(file);
            
            // Dosya seçim alanını temizle
            $('#matas-restore-file').val('');
        } catch (e) {
            console.error('Dosyadan veri yükleme hatası:', e);
            alert('Dosyadan veri yüklenirken bir hata oluştu: ' + e.message);
        }
    }
    
    /**
     * Yerel depolamadaki yedekleri listele
     */
    function loadBackupList() {
        try {
            let backups = [];
            try {
                const savedBackups = localStorage.getItem('matas_backups');
                if (savedBackups) {
                    backups = JSON.parse(savedBackups);
                }
            } catch (e) {
                console.error('Kayıtlı yedekler yüklenirken hata:', e);
                backups = [];
            }
            
            if (!backups || backups.length === 0) {
                $('#matas-backup-list').html('<p>Henüz yedek bulunmuyor.</p>');
                return;
            }
            
            let html = '<ul class="matas-backup-list">';
            
            backups.forEach(function(backup, index) {
                html += `
                    <li class="matas-backup-item">
                        <span>${backup.isim || 'İsimsiz Yedek'}</span>
                        <div class="matas-backup-actions">
                            <button type="button" class="matas-backup-restore" data-index="${index}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Geri Yükle
                            </button>
                            <button type="button" class="matas-backup-delete" data-index="${index}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                                Sil
                            </button>
                        </div>
                    </li>
                `;
            });
            
            html += '</ul>';
            
            $('#matas-backup-list').html(html);
            
            // Geri yükleme butonlarına olay dinleyicisi ekle
            $('.matas-backup-restore').on('click', function() {
                restoreBackup($(this).data('index'));
            });
            
            // Silme butonlarına olay dinleyicisi ekle
            $('.matas-backup-delete').on('click', function() {
                deleteBackup($(this).data('index'));
            });
        } catch (e) {
            console.error('Yedek listesi yükleme hatası:', e);
            $('#matas-backup-list').html('<p>Yedekler yüklenirken bir hata oluştu.</p>');
        }
    }
    
    /**
     * Yedeği geri yükle
     * 
     * @param {number} index Yedek indeksi
     */
    function restoreBackup(index) {
        try {
            let backups = [];
            try {
                const savedBackups = localStorage.getItem('matas_backups');
                if (savedBackups) {
                    backups = JSON.parse(savedBackups);
                }
            } catch (e) {
                console.error('Kayıtlı yedekler yüklenirken hata:', e);
                backups = [];
            }
            
            if (index >= 0 && index < backups.length) {
                const formData = backups[index];
                
                // Form verilerini yükle
                for (const key in formData) {
                    if (key === 'isim' || key === 'tarih') continue;
                    
                    if (['gorev_tazminati', 'gelistirme_odenegi', 'asgari_gecim_indirimi', 'kira_yardimi', 'sendika_uyesi'].includes(key)) {
                        $('#' + key).prop('checked', formData[key]);
                    } else {
                        $('#' + key).val(formData[key]);
                    }
                }
                
                // Select elementlerini güncelle
                $('#derece, #medeni_hal, #cocuk_sayisi, #dil_seviyesi').trigger('change');
                
                // Hesaplama sekmesine geç
                openTab('hesaplama');
                
                // Kullanıcıya bilgi ver
                alert('Yedek başarıyla geri yüklendi.');
            }
        } catch (e) {
            console.error('Yedek geri yükleme hatası:', e);
            alert('Yedek geri yüklenirken bir hata oluştu: ' + e.message);
        }
    }
    
    /**
     * Yedeği sil
     * 
     * @param {number} index Yedek indeksi
     */
    function deleteBackup(index) {
        try {
            if (confirm('Bu yedeği silmek istediğinize emin misiniz?')) {
                let backups = [];
                try {
                    const savedBackups = localStorage.getItem('matas_backups');
                    if (savedBackups) {
                        backups = JSON.parse(savedBackups);
                    }
                } catch (e) {
                    console.error('Kayıtlı yedekler yüklenirken hata:', e);
                    backups = [];
                }
                
                if (index >= 0 && index < backups.length) {
                    backups.splice(index, 1);
                    localStorage.setItem('matas_backups', JSON.stringify(backups));
                    
                    // Listeyi güncelle
                    loadBackupList();
                    
                    // Kullanıcıya bilgi ver
                    alert('Yedek başarıyla silindi.');
                }
            }
        } catch (e) {
            console.error('Yedek silme hatası:', e);
            alert('Yedek silinirken bir hata oluştu: ' + e.message);
        }
    }
})(jQuery);

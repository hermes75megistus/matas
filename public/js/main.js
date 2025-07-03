// MATAS - Ana JavaScript Dosyası
document.addEventListener('DOMContentLoaded', function() {
    'use strict';
    
    // Sayfa yüklendiğinde çalışacak ana fonksiyon
    function initMATAS() {
        console.log('MATAS sistem başlatılıyor...');
        
        // Form kontrollerini başlat
        initFormControls();
        
        // Tab sistemini başlat
        initTabSystem();
        
        // Backup/Restore sistemini başlat
        initBackupSystem();
        
        // Form validasyonunu başlat
        initFormValidation();
        
        // Tooltip sistemini başlat
        initTooltips();
        
        // Kısayol tuşlarını başlat
        initKeyboardShortcuts();
        
        console.log('MATAS sistem başarıyla başlatıldı.');
    }
    
    // Form kontrolleri
    function initFormControls() {
        // Seksiyon daralt/genişlet
        const sectionToggles = document.querySelectorAll('.matas-form-section-toggle');
        sectionToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const section = this.closest('.matas-form-section');
                const content = section.querySelector('.matas-form-section-content');
                
                if (content.style.display === 'none') {
                    content.style.display = 'block';
                    this.textContent = '-';
                    section.classList.remove('collapsed');
                } else {
                    content.style.display = 'none';
                    this.textContent = '+';
                    section.classList.add('collapsed');
                }
            });
        });
        
        // Medeni hal değişikliği
        const medeniHal = document.getElementById('medeni_hal');
        const esDurumGroup = document.getElementById('es_durum_group');
        
        if (medeniHal && esDurumGroup) {
            medeniHal.addEventListener('change', function() {
                if (this.value === 'evli') {
                    esDurumGroup.style.display = 'block';
                } else {
                    esDurumGroup.style.display = 'none';
                    document.getElementById('es_calisiyor').value = 'evet';
                }
            });
            
            // İlk yükleme
            medeniHal.dispatchEvent(new Event('change'));
        }
        
        // Çocuk sayısı değişikliği
        const cocukSayisi = document.getElementById('cocuk_sayisi');
        const cocukGroups = [
            document.getElementById('cocuk_06_group'),
            document.getElementById('engelli_cocuk_group'),
            document.getElementById('ogrenim_cocuk_group')
        ];
        
        if (cocukSayisi) {
            cocukSayisi.addEventListener('change', function() {
                const sayi = parseInt(this.value);
                
                cocukGroups.forEach(group => {
                    if (group) {
                        if (sayi > 0) {
                            group.style.display = 'block';
                        } else {
                            group.style.display = 'none';
                            const select = group.querySelector('select');
                            if (select) select.value = '0';
                        }
                    }
                });
            });
            
            // İlk yükleme
            cocukSayisi.dispatchEvent(new Event('change'));
        }
        
        // Dil seviyesi değişikliği
        const dilSeviyesi = document.getElementById('dil_seviyesi');
        const dilKullanimiGroup = document.getElementById('dil_kullanimi_group');
        
        if (dilSeviyesi && dilKullanimiGroup) {
            dilSeviyesi.addEventListener('change', function() {
                if (this.value === 'yok') {
                    dilKullanimiGroup.style.display = 'none';
                    document.getElementById('dil_kullanimi').value = 'hayir';
                } else {
                    dilKullanimiGroup.style.display = 'block';
                }
            });
            
            // İlk yükleme
            dilSeviyesi.dispatchEvent(new Event('change'));
        }
        
        // Hesaplama butonu
        const hesaplaBtn = document.getElementById('matas-hesapla-btn');
        if (hesaplaBtn) {
            hesaplaBtn.addEventListener('click', function() {
                hesaplaMaas();
            });
        }
        
        // Temizle butonu
        const temizleBtn = document.getElementById('matas-temizle-btn');
        if (temizleBtn) {
            temizleBtn.addEventListener('click', function() {
                if (confirm('Formu temizlemek istediğinize emin misiniz?')) {
                    document.getElementById('matas-hesaplama-form').reset();
                    document.getElementById('matas-sonuclar').style.display = 'none';
                    
                    // Bağımlı alanları da sıfırla
                    medeniHal.dispatchEvent(new Event('change'));
                    cocukSayisi.dispatchEvent(new Event('change'));
                    dilSeviyesi.dispatchEvent(new Event('change'));
                }
            });
        }
    }
    
    // Tab sistemi
    function initTabSystem() {
        const tabs = document.querySelectorAll('.matas-tab');
        const tabContents = document.querySelectorAll('.matas-tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.dataset.tab;
                
                // Tüm tabları pasif yap
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(tc => tc.classList.remove('active'));
                
                // Seçili tabı aktif yap
                this.classList.add('active');
                document.getElementById('matas-' + targetTab).classList.add('active');
            });
        });
    }
    
    // Backup/Restore sistemi
    function initBackupSystem() {
        const backupBtn = document.getElementById('matas-backup-btn');
        const restoreBtn = document.getElementById('matas-restore-btn');
        const restoreFile = document.getElementById('matas-restore-file');
        
        if (backupBtn) {
            backupBtn.addEventListener('click', function() {
                saveFormData();
            });
        }
        
        if (restoreBtn) {
            restoreBtn.addEventListener('click', function() {
                restoreFile.click();
            });
        }
        
        if (restoreFile) {
            restoreFile.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    loadFormData(file);
                }
            });
        }
        
        // Mevcut yedekleri listele
        displayBackupList();
    }
    
    // Form validasyonu
    function initFormValidation() {
        const form = document.getElementById('matas-hesaplama-form');
        if (!form) return;
        
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });
            
            field.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    }
    
    // Tooltip sistemi
    function initTooltips() {
        const tooltips = document.querySelectorAll('.matas-tooltip');
        
        tooltips.forEach(tooltip => {
            tooltip.addEventListener('mouseenter', function() {
                showTooltip(this);
            });
            
            tooltip.addEventListener('mouseleave', function() {
                hideTooltip();
            });
        });
    }
    
    // Kısayol tuşları
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl+H - Hesapla
            if (e.ctrlKey && e.key === 'h') {
                e.preventDefault();
                hesaplaMaas();
            }
            
            // Ctrl+R - Reset (Ctrl+R'ı engelle ve kendi reset'imizi çalıştır)
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                if (confirm('Formu temizlemek istediğinize emin misiniz?')) {
                    document.getElementById('matas-hesaplama-form').reset();
                }
            }
            
            // F1 - Yardım
            if (e.key === 'F1') {
                e.preventDefault();
                showHelp();
            }
        });
    }
    
    // Ana hesaplama fonksiyonu
    function hesaplaMaas() {
        const form = document.getElementById('matas-hesaplama-form');
        const hesaplaBtn = document.getElementById('matas-hesapla-btn');
        
        if (!form || !hesaplaBtn) return;
        
        // Form validasyonu
        if (!validateForm(form)) {
            showError('Lütfen tüm zorunlu alanları doldurunuz.');
            return;
        }
        
        // Buton loading durumuna getir
        hesaplaBtn.disabled = true;
        hesaplaBtn.innerHTML = '<div class="loading-spinner"></div> Hesaplanıyor...';
        hesaplaBtn.classList.add('loading');
        
        // Form verilerini topla
        const formData = new FormData(form);
        formData.append('action', 'matas_hesapla');
        formData.append('nonce', matas_ajax.nonce);
        
        // AJAX isteği
        fetch(matas_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResults(data.data);
                showSuccess('Maaş hesaplaması başarıyla tamamlandı!');
            } else {
                showError(data.data?.message || 'Hesaplama sırasında bir hata oluştu.');
            }
        })
        .catch(error => {
            console.error('Hesaplama hatası:', error);
            showError('Sunucu ile iletişim kurulamadı. Lütfen tekrar deneyin.');
        })
        .finally(() => {
            // Buton normal durumuna getir
            hesaplaBtn.disabled = false;
            hesaplaBtn.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
                Maaş Hesapla
            `;
            hesaplaBtn.classList.remove('loading');
        });
    }
    
    // Sonuçları göster
    function displayResults(data) {
        const sonuclarDiv = document.getElementById('matas-sonuclar');
        if (!sonuclarDiv) return;
        
        sonuclarDiv.innerHTML = `
            <div class="matas-result-header">
                <h2>📊 Maaş Hesaplama Sonuçları</h2>
                <p>Dönem: ${data.donem || 'Geçerli Dönem'}</p>
                <p>Ünvan: ${data.unvanAdi || 'Belirtilmemiş'}</p>
            </div>
            
            <div class="matas-result-content">
                <div class="matas-result-group">💰 Temel Maaş Bileşenleri</div>
                <div class="matas-result-row">
                    <span>Taban Aylığı:</span>
                    <span class="matas-result-value">${formatCurrency(data.tabanAyligi)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Ek Gösterge Tutarı:</span>
                    <span class="matas-result-value">${formatCurrency(data.ekGostergeTutari)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Kıdem Aylığı:</span>
                    <span class="matas-result-value">${formatCurrency(data.kidemAyligi)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Yan Ödeme:</span>
                    <span class="matas-result-value">${formatCurrency(data.yanOdeme)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Özel Hizmet Tazminatı:</span>
                    <span class="matas-result-value">${formatCurrency(data.ozelHizmetTazminati)}</span>
                </div>
                
                <div class="matas-result-group">💼 Ek Ödemeler ve Tazminatlar</div>
                <div class="matas-result-row">
                    <span>İş Güçlüğü Zammı:</span>
                    <span class="matas-result-value">${formatCurrency(data.isGucluguzammi)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Dil Tazminatı:</span>
                    <span class="matas-result-value">${formatCurrency(data.dilTazminati)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Ek Ödeme (666 KHK):</span>
                    <span class="matas-result-value">${formatCurrency(data.ekOdeme)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Eğitim-Öğretim Tazminatı:</span>
                    <span class="matas-result-value">${formatCurrency(data.egitimTazminati)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Geliştirme Ödeneği:</span>
                    <span class="matas-result-value">${formatCurrency(data.gelistirmeOdenegiTutari)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Makam Tazminatı:</span>
                    <span class="matas-result-value">${formatCurrency(data.makamTazminati)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Lisansüstü Tazminat:</span>
                    <span class="matas-result-value">${formatCurrency(data.lisansustuTazminat)}</span>
                </div>
                
                <div class="matas-result-group">🤝 Sosyal Yardımlar</div>
                <div class="matas-result-row">
                    <span>Aile Yardımı:</span>
                    <span class="matas-result-value">${formatCurrency(data.aileYardimi)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Çocuk Yardımı:</span>
                    <span class="matas-result-value">${formatCurrency(data.cocukYardimi)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Kira Yardımı:</span>
                    <span class="matas-result-value">${formatCurrency(data.kiraYardimiTutari)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Sendika Yardımı:</span>
                    <span class="matas-result-value">${formatCurrency(data.sendikaYardimi)}</span>
                </div>
                
                <div class="matas-result-group">📉 Kesintiler</div>
                <div class="matas-result-row">
                    <span>Emekli Keseneği (%16):</span>
                    <span class="matas-result-value">${formatCurrency(data.emekliKesenegi)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Genel Sağlık Sigortası (%5):</span>
                    <span class="matas-result-value">${formatCurrency(data.gssPrimi)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Gelir Vergisi:</span>
                    <span class="matas-result-value">${formatCurrency(data.gelirVergisi)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Damga Vergisi:</span>
                    <span class="matas-result-value">${formatCurrency(data.damgaVergisi)}</span>
                </div>
                <div class="matas-result-row">
                    <span>Sendika Kesintisi:</span>
                    <span class="matas-result-value">${formatCurrency(data.sendikaKesintisi)}</span>
                </div>
                
                <div class="matas-result-total">
                    <div style="margin-bottom: 10px;">
                        <strong>Brüt Maaş: <span class="matas-total-value">${formatCurrency(data.brutMaas)}</span></strong>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <strong>Toplam Kesintiler: <span class="matas-total-value" style="color: #dc3545;">${formatCurrency(data.toplamKesintiler)}</span></strong>
                    </div>
                    <div style="font-size: 1.2em; padding: 15px; background: linear-gradient(135deg, #28a745, #20c997); color: white; border-radius: 8px;">
                        <strong>NET MAAŞ: <span class="matas-total-value">${formatCurrency(data.netMaas)}</span></strong>
                    </div>
                </div>
                
                <button class="matas-detail-toggle" onclick="toggleDetails()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                    Detayları Göster
                </button>
                
                <div id="matas-details" class="matas-detail-content" style="display: none;">
                    <h4>Hesaplama Detayları</h4>
                    <p><strong>Gösterge Puanı:</strong> ${data.gostergePuani}</p>
                    <p><strong>Aylık Katsayı:</strong> ${data.aylikKatsayi}</p>
                    <p><strong>Taban Katsayı:</strong> ${data.tabanKatsayi}</p>
                    <p><strong>Yan Ödeme Katsayısı:</strong> ${data.yanOdemeKatsayi}</p>
                </div>
                
                <div class="matas-note">
                    <div class="matas-note-title">⚠️ Önemli Uyarı</div>
                    <p>Bu hesaplama sonuçları tahmini değerlerdir. Kesin bilgi için kurumunuzun özlük birimine başvurunuz. Hesaplama ${new Date().toLocaleDateString('tr-TR')} tarihinde yapılmıştır.</p>
                </div>
            </div>
        `;
        
        sonuclarDiv.style.display = 'block';
        sonuclarDiv.scrollIntoView({ behavior: 'smooth' });
    }
    
    // Para formatı
    function formatCurrency(amount) {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
            minimumFractionDigits: 2
        }).format(amount || 0);
    }
    
    // Form validasyonu
    function validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    // Tek alan validasyonu
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        // Boş kontrol
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'Bu alan zorunludur.';
        }
        
        // Tip kontrolleri
        if (value && field.type === 'number') {
            const numValue = parseFloat(value);
            const min = parseFloat(field.getAttribute('min'));
            const max = parseFloat(field.getAttribute('max'));
            
            if (isNaN(numValue)) {
                isValid = false;
                errorMessage = 'Geçerli bir sayı giriniz.';
            } else if (!isNaN(min) && numValue < min) {
                isValid = false;
                errorMessage = `Minimum değer: ${min}`;
            } else if (!isNaN(max) && numValue > max) {
                isValid = false;
                errorMessage = `Maksimum değer: ${max}`;
            }
        }
        
        // Hata göster/gizle
        if (isValid) {
            clearFieldError(field);
        } else {
            showFieldError(field, errorMessage);
        }
        
        return isValid;
    }
    
    // Alan hatası göster
    function showFieldError(field, message) {
        clearFieldError(field);
        
        field.classList.add('has-error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'matas-error-message';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }
    
    // Alan hatasını temizle
    function clearFieldError(field) {
        field.classList.remove('has-error');
        
        const existingError = field.parentNode.querySelector('.matas-error-message');
        if (existingError) {
            existingError.remove();
        }
    }
    
    // Tooltip göster
    function showTooltip(element) {
        const tip = element.getAttribute('data-tip');
        if (!tip) return;
        
        const tooltipDiv = document.createElement('div');
        tooltipDiv.className = 'matas-tooltip-content';
        tooltipDiv.textContent = tip;
        
        document.body.appendChild(tooltipDiv);
        
        const rect = element.getBoundingClientRect();
        tooltipDiv.style.position = 'absolute';
        tooltipDiv.style.left = rect.left + 'px';
        tooltipDiv.style.top = (rect.bottom + 5) + 'px';
        tooltipDiv.style.zIndex = '9999';
        tooltipDiv.style.background = '#333';
        tooltipDiv.style.color = 'white';
        tooltipDiv.style.padding = '8px 12px';
        tooltipDiv.style.borderRadius = '4px';
        tooltipDiv.style.fontSize = '14px';
        tooltipDiv.style.maxWidth = '300px';
        tooltipDiv.style.boxShadow = '0 2px 8px rgba(0,0,0,0.3)';
    }
    
    // Tooltip gizle
    function hideTooltip() {
        const tooltip = document.querySelector('.matas-tooltip-content');
        if (tooltip) {
            tooltip.remove();
        }
    }
    
    // Form verilerini kaydet
    function saveFormData() {
        const form = document.getElementById('matas-hesaplama-form');
        if (!form) return;
        
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Timestamp ekle
        data.timestamp = new Date().toISOString();
        data.name = prompt('Bu yedek için bir isim giriniz:') || 'Yedek-' + new Date().toLocaleDateString('tr-TR');
        
        // localStorage'a kaydet
        const backups = JSON.parse(localStorage.getItem('matas_backups') || '[]');
        backups.unshift(data);
        
        // Maksimum 10 yedek tut
        if (backups.length > 10) {
            backups.splice(10);
        }
        
        localStorage.setItem('matas_backups', JSON.stringify(backups));
        
        // JSON dosyası olarak indir
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `matas-yedek-${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        URL.revokeObjectURL(url);
        
        displayBackupList();
        showSuccess('Form verileri başarıyla kaydedildi!');
    }
    
    // Form verilerini yükle
    function loadFormData(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = JSON.parse(e.target.result);
                const form = document.getElementById('matas-hesaplama-form');
                
                if (!form) return;
                
                // Form alanlarını doldur
                Object.keys(data).forEach(key => {
                    const field = form.querySelector(`[name="${key}"]`);
                    if (field) {
                        if (field.type === 'checkbox') {
                            field.checked = data[key] === '1' || data[key] === true;
                        } else {
                            field.value = data[key];
                        }
                    }
                });
                
                // Bağımlı alanları güncelle
                const medeniHal = document.getElementById('medeni_hal');
                const cocukSayisi = document.getElementById('cocuk_sayisi');
                const dilSeviyesi = document.getElementById('dil_seviyesi');
                
                if (medeniHal) medeniHal.dispatchEvent(new Event('change'));
                if (cocukSayisi) cocukSayisi.dispatchEvent(new Event('change'));
                if (dilSeviyesi) dilSeviyesi.dispatchEvent(new Event('change'));
                
                showSuccess('Form verileri başarıyla yüklendi!');
                
            } catch (error) {
                showError('Dosya formatı geçersiz!');
            }
        };
        reader.readAsText(file);
    }
    
    // Yedek listesini göster
    function displayBackupList() {
        const backupList = document.getElementById('matas-backup-list');
        if (!backupList) return;
        
        const backups = JSON.parse(localStorage.getItem('matas_backups') || '[]');
        
        if (backups.length === 0) {
            backupList.innerHTML = '<p>Henüz yedek bulunmuyor.</p>';
            return;
        }
        
        backupList.innerHTML = backups.map((backup, index) => `
            <div class="matas-backup-item">
                <div>
                    <strong>${backup.name}</strong><br>
                    <small>${new Date(backup.timestamp).toLocaleString('tr-TR')}</small>
                </div>
                <div class="matas-backup-actions">
                    <button class="matas-backup-restore" onclick="restoreBackup(${index})">
                        📁 Geri Yükle
                    </button>
                    <button class="matas-backup-delete" onclick="deleteBackup(${index})">
                        🗑️ Sil
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    // Mesaj gösterme fonksiyonları
    function showSuccess(message) {
        showMessage(message, 'success');
    }
    
    function showError(message) {
        showMessage(message, 'error');
    }
    
    function showMessage(message, type) {
        // Mevcut mesajları kaldır
        const existingMessages = document.querySelectorAll('.matas-message');
        existingMessages.forEach(msg => msg.remove());
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `matas-message ${type}`;
        messageDiv.textContent = message;
        
        // Sayfanın en üstüne ekle
        const container = document.querySelector('.matas-container');
        if (container) {
            container.insertBefore(messageDiv, container.firstChild);
        }
        
        // 5 saniye sonra kaldır
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
    
    // Yardım göster
    function showHelp() {
        alert(`MATAS - Maaş Takip Sistemi Yardım

Kısayol Tuşları:
• Ctrl+H: Maaş hesapla
• Ctrl+R: Formu temizle
• F1: Bu yardımı göster

Özellikler:
• Detaylı maaş hesaplama
• Form verilerini kaydetme/yükleme
• Responsive tasarım
• Erişilebilirlik desteği`);
    }
    
    // Global fonksiyonlar (onclick eventler için)
    window.toggleDetails = function() {
        const details = document.getElementById('matas-details');
        const button = document.querySelector('.matas-detail-toggle');
        
        if (details.style.display === 'none') {
            details.style.display = 'block';
            button.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="18 15 12 9 6 15"></polyline>
                </svg>
                Detayları Gizle
            `;
        } else {
            details.style.display = 'none';
            button.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
                Detayları Göster
            `;
        }
    };
    
    window.restoreBackup = function(index) {
        const backups = JSON.parse(localStorage.getItem('matas_backups') || '[]');
        const backup = backups[index];
        
        if (!backup) return;
        
        if (confirm(`"${backup.name}" yedeğini geri yüklemek istediğinize emin misiniz?`)) {
            const form = document.getElementById('matas-hesaplama-form');
            
            Object.keys(backup).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    if (field.type === 'checkbox') {
                        field.checked = backup[key] === '1' || backup[key] === true;
                    } else {
                        field.value = backup[key];
                    }
                }
            });
            
            // Bağımlı alanları güncelle
            const medeniHal = document.getElementById('medeni_hal');
            const cocukSayisi = document.getElementById('cocuk_sayisi');
            const dilSeviyesi = document.getElementById('dil_seviyesi');
            
            if (medeniHal) medeniHal.dispatchEvent(new Event('change'));
            if (cocukSayisi) cocukSayisi.dispatchEvent(new Event('change'));
            if (dilSeviyesi) dilSeviyesi.dispatchEvent(new Event('change'));
            
            showSuccess('Yedek başarıyla geri yüklendi!');
        }
    };
    
    window.deleteBackup = function(index) {
        const backups = JSON.parse(localStorage.getItem('matas_backups') || '[]');
        const backup = backups[index];
        
        if (!backup) return;
        
        if (confirm(`"${backup.name}" yedeğini silmek istediğinize emin misiniz?`)) {
            backups.splice(index, 1);
            localStorage.setItem('matas_backups', JSON.stringify(backups));
            displayBackupList();
            showSuccess('Yedek başarıyla silindi!');
        }
    };
    
    // Sistemi başlat
    initMATAS();
});

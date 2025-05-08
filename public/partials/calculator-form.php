<?php
// Aktif katsayıları al
global $wpdb;
$katsayilar = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}matas_katsayilar WHERE aktif = 1 ORDER BY id DESC LIMIT 1", ARRAY_A);
$donem = $katsayilar ? $katsayilar['donem'] : 'Geçerli Dönem';
?>

<div class="matas-container">
    <header>
        <h1><?php echo esc_html($atts['baslik']); ?> (<?php echo esc_html($donem); ?>)</h1>
        <div class="last-update">Son Güncelleme: <?php echo date('d F Y'); ?></div>
    </header>
    
    <div class="matas-tabs">
        <div class="matas-tab active" data-tab="hesaplama">
            <span class="tab-icon">📊</span>Maaş Hesaplama
        </div>
        <div class="matas-tab" data-tab="yedekleme">
            <span class="tab-icon">💾</span>Yedekleme/Geri Yükleme
        </div>
        <div class="matas-tab" data-tab="bilgi">
            <span class="tab-icon">ℹ️</span>Bilgilendirme
        </div>
    </div>
    
    <!-- Hesaplama Sekmesi -->
    <div id="matas-hesaplama" class="matas-tab-content active">
        <form id="matas-hesaplama-form">
            <!-- Genel Bilgiler Bölümü -->
            <div class="matas-form-section">
                <div class="matas-form-section-header">
                    <span>Genel Bilgiler</span>
                    <button type="button" class="matas-form-section-toggle">-</button>
                </div>
                <div class="matas-form-section-content">
                    <div class="matas-form-row">
                        <div class="matas-form-group">
                            <label for="unvan" class="required">Ünvan:</label>
                            <select id="unvan" name="unvan" class="matas-form-control" required>
                                <option value="" disabled selected>Seçiniz</option>
                                <?php
                                $unvanlar = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}matas_unvan_bilgileri ORDER BY unvan_adi ASC", ARRAY_A);
                                $unvan_gruplari = array();
                                
                                // Ünvanları gruplara ayır
                                foreach ($unvanlar as $unvan) {
                                    $grup = explode('_', $unvan['unvan_kodu'])[0];
                                    $grup = ucfirst($grup);
                                    
                                    if (!isset($unvan_gruplari[$grup])) {
                                        $unvan_gruplari[$grup] = array();
                                    }
                                    
                                    $unvan_gruplari[$grup][] = $unvan;
                                }
                                
                                // Grupları ve ünvanları listele
                                foreach ($unvan_gruplari as $grup => $grup_unvanlar) {
                                    echo '<optgroup label="' . esc_attr($grup) . '">';
                                    
                                    foreach ($grup_unvanlar as $unvan) {
                                        echo '<option value="' . esc_attr($unvan['unvan_kodu']) . '" data-ekgosterge="' . esc_attr($unvan['ekgosterge']) . '" data-ozelhizmet="' . esc_attr($unvan['ozel_hizmet']) . '" data-yanodeme="' . esc_attr($unvan['yan_odeme']) . '" data-isguclugu="' . esc_attr($unvan['is_guclugu']) . '">' . esc_html($unvan['unvan_adi']) . '</option>';
                                    }
                                    
                                    echo '</optgroup>';
                                }
                                ?>
                            </select>
                            <span class="matas-tooltip" data-tip="Çalıştığınız kurumda sahip olduğunuz ünvanı seçiniz.">?</span>
                        </div>
                        
                        <div class="matas-form-group">
                            <label for="derece" class="required">Derece:</label>
                            <select id="derece" name="derece" class="matas-form-control" required>
                                <option value="" disabled selected>Seçiniz</option>
                                <?php for ($i = 1; $i <= 15; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?>. Derece</option>
                                <?php endfor; ?>
                            </select>
                            <span class="matas-tooltip" data-tip="Maaş bordronuzda yazan derece bilgisini seçiniz. Eğer bilmiyorsanız özlük biriminizden öğrenebilirsiniz.">?</span>
                        </div>
                        
                        <div class="matas-form-group">
                            <label for="kademe" class="required">Kademe:</label>
                            <select id="kademe" name="kademe" class="matas-form-control" required>
                                <option value="" disabled selected>Seçiniz</option>
                                <?php for ($i = 1; $i <= 9; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?>. Kademe</option>
                                <?php endfor; ?>
                            </select>
                            <span class="matas-tooltip" data-tip="Maaş bordronuzda yazan kademe bilgisini seçiniz. Her derecede 1'den 9'a kadar kademe bulunur.">?</span>
                        </div>
                    </div>
                    
                    <div class="matas-form-row">
                        <div class="matas-form-group">
                            <label for="hizmet_yili" class="required">Hizmet Yılı:</label>
                            <select id="hizmet_yili" name="hizmet_yili" class="matas-form-control" required>
                                <option value="" disabled selected>Seçiniz</option>
                                <?php for ($i = 0; $i <= 40; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> Yıl</option>
                                <?php endfor; ?>
                            </select>
                            <span class="matas-tooltip" data-tip="Devlet memurluğunda geçirdiğiniz toplam hizmet süresini seçiniz.">?</span>
                        </div>
                        
                        <div class="matas-form-group">
                            <label for="medeni_hal">Medeni Hal:</label>
                            <select id="medeni_hal" name="medeni_hal" class="matas-form-control">
                                <option value="evli">Evli</option>
                                <option value="bekar">Bekar</option>
                            </select>
                            <span class="matas-tooltip" data-tip="Aile yardımı hesaplaması için medeni halinizi seçiniz.">?</span>
                        </div>
                        
                        <div class="matas-form-group" id="es_durum_group">
                            <label for="es_calisiyor">Eş Çalışma Durumu:</label>
                            <select id="es_calisiyor" name="es_calisiyor" class="matas-form-control">
                                <option value="hayir">Çalışmıyor</option>
                                <option value="evet">Çalışıyor</option>
                            </select>
                            <span class="matas-tooltip" data-tip="Eşinizin çalışma durumunu seçiniz. Eşiniz çalışmıyorsa aile yardımı alırsınız.">?</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Çocuk Bilgileri Bölümü -->
            <div class="matas-form-section">
                <div class="matas-form-section-header">
                    <span>Çocuk Bilgileri</span>
                    <button type="button" class="matas-form-section-toggle">-</button>
                </div>
                <div class="matas-form-section-content">
                    <div class="matas-form-row">
                        <div class="matas-form-group">
                            <label for="cocuk_sayisi">Toplam Çocuk Sayısı:</label>
                            <select id="cocuk_sayisi" name="cocuk_sayisi" class="matas-form-control">
                                <option value="0">0</option>
                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="matas-tooltip" data-tip="Çocuk yardımı hesaplaması için toplam çocuk sayınızı seçiniz.">?</span>
                        </div>
                        
                        <div class="matas-form-group" id="cocuk_06_group" style="display:none;">
                            <label for="cocuk_06">0-6 Yaş Arası Çocuk:</label>
                            <select id="cocuk_06" name="cocuk_06" class="matas-form-control">
                                <option value="0">0</option>
                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="matas-tooltip" data-tip="0-6 yaş arası çocuklarınız için daha yüksek çocuk yardımı alırsınız.">?</span>
                        </div>
                        
                        <div class="matas-form-group" id="engelli_cocuk_group" style="display:none;">
                            <label for="engelli_cocuk">Engelli Çocuk:</label>
                            <select id="engelli_cocuk" name="engelli_cocuk" class="matas-form-control">
                                <option value="0">0</option>
                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="matas-tooltip" data-tip="Engelli çocuklarınız için daha yüksek çocuk yardımı alırsınız.">?</span>
                        </div>
                        
                        <div class="matas-form-group" id="ogrenim_cocuk_group" style="display:none;">
                            <label for="ogrenim_cocuk">Öğrenim Gören Çocuk:</label>
                            <select id="ogrenim_cocuk" name="ogrenim_cocuk" class="matas-form-control">
                                <option value="0">0</option>
                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="matas-tooltip" data-tip="Öğrenim gören çocuklarınız için daha yüksek çocuk yardımı alırsınız.">?</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Eğitim ve Dil Bilgileri Bölümü -->
            <div class="matas-form-section">
                <div class="matas-form-section-header">
                    <span>Eğitim ve Dil Bilgileri</span>
                    <button type="button" class="matas-form-section-toggle">-</button>
                </div>
                <div class="matas-form-section-content">
                    <div class="matas-form-row">
                        <div class="matas-form-group">
                            <label for="egitim_durumu">Eğitim Durumu:</label>
                            <select id="egitim_durumu" name="egitim_durumu" class="matas-form-control">
                                <option value="lisans">Lisans</option>
                                <option value="yuksek_lisans">Yüksek Lisans</option>
                                <option value="doktora">Doktora</option>
                            </select>
                            <span class="matas-tooltip" data-tip="Yüksek lisans veya doktora mezunları ek tazminat alırlar.">?</span>
                        </div>
                        
                        <div class="matas-form-group">
                            <label for="dil_seviyesi">Yabancı Dil Seviyesi:</label>
                            <select id="dil_seviyesi" name="dil_seviyesi" class="matas-form-control">
                                <option value="yok">Yok</option>
                                <option value="a">A Seviyesi (90-100)</option>
                                <option value="b">B Seviyesi (80-89)</option>
                                <option value="c">C Seviyesi (70-79)</option>
                            </select>
                            <span class="matas-tooltip" data-tip="Yabancı dil sınav sonucunuz varsa, seviyesini seçiniz. Dil tazminatı almak için YDS veya dengi bir sınavdan geçerli puanınız olmalıdır.">?</span>
                        </div>
                        
                        <div class="matas-form-group" id="dil_kullanimi_group" style="display:none;">
                            <label for="dil_kullanimi">Dil Bilgisi Kullanımı:</label>
                            <select id="dil_kullanimi" name="dil_kullanimi" class="matas-form-control">
                                <option value="hayir">Kullanmıyorum</option>
                                <option value="evet">Kullanıyorum</option>
                            </select>
                            <span class="matas-tooltip" data-tip="Görevinizde yabancı dil bilgisini kullanıyorsanız daha yüksek dil tazminatı alırsınız.">?</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ek Ödemeler ve Tazminatlar Bölümü -->
            <div class="matas-form-section">
                <div class="matas-form-section-header">
                    <span>Ek Ödemeler ve Tazminatlar</span>
                    <button type="button" class="matas-form-section-toggle">-</button>
                </div>
                <div class="matas-form-section-content">
                    <div class="matas-form-row">
                        <div class="matas-form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="gorev_tazminati" name="gorev_tazminati" value="1">
                                Eğitim-Öğretim Tazminatı
                            </label>
                            <span class="matas-tooltip" data-tip="Eğitim-öğretim kurumlarında çalışanlar için ek tazminat.">?</span>
                        </div>
                        
                        <div class="matas-form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="gelistirme_odenegi" name="gelistirme_odenegi" value="1">
                                Geliştirme Ödeneği
                            </label>
                            <span class="matas-tooltip" data-tip="Gelişmekte olan bölgelerdeki kurumlar için ek ödenek.">?</span>
                        </div>
                        
                        <div class="matas-form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="asgari_gecim_indirimi" name="asgari_gecim_indirimi" value="1" checked>
                                Asgari Geçim İndirimi
                            </label>
                            <span class="matas-tooltip" data-tip="Asgari geçim indirimi, gelir vergisi matrahından düşülen bir indirimdir.">?</span>
                        </div>
                    </div>
                    
                    <div class="matas-form-row">
                        <div class="matas-form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="kira_yardimi" name="kira_yardimi" value="1">
                                Kira Yardımı
                            </label>
                            <span class="matas-tooltip" data-tip="Lojmanda oturmayan ve belirli şartları sağlayan memurlar için kira yardımı.">?</span>
                        </div>
                        
                        <div class="matas-form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="sendika_uyesi" name="sendika_uyesi" value="1">
                                Sendika Üyesi
                            </label>
                            <span class="matas-tooltip" data-tip="Sendika üyesi iseniz toplu sözleşme ikramiyesi alırsınız.">?</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="matas-actions">
                <button type="button" id="matas-hesapla-btn" class="matas-btn matas-btn-primary matas-btn-large">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    Maaş Hesapla
                </button>
                
                <button type="button" id="matas-temizle-btn" class="matas-btn matas-btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                    Formu Temizle
                </button>
            </div>
        </form>
        
        <div id="matas-sonuclar" class="matas-result-section" style="display:none;">
            <!-- Hesaplama sonuçları burada gösterilecek -->
        </div>
    </div>
    
    <!-- Yedekleme Sekmesi -->
    <div id="matas-yedekleme" class="matas-tab-content">
        <div class="matas-card">
            <h2>Form Verilerini Kaydet</h2>
            <p>Doldurduğunuz form verilerini yedekleyebilir ve daha sonra geri yükleyebilirsiniz.</p>
            
            <div class="matas-form-row">
                <div class="matas-form-group">
                    <button type="button" id="matas-backup-btn" class="matas-btn matas-btn-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Formu Kaydet
                    </button>
                    
                    <button type="button" id="matas-restore-btn" class="matas-btn matas-btn-secondary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 15v4c0 1.1.9 2 2 2h14a2 2 0 0 0 2-2v-4M17 8l-5-5-5 5M12 3v12"></path>
                        </svg>
                        Dosyadan Yükle
                    </button>
                    <input type="file" id="matas-restore-file" style="display:none;" accept=".json">
                </div>
            </div>
        </div>
        
        <div class="matas-card">
            <h2>Kaydedilmiş Form Verileri</h2>
            <div id="matas-backup-list">
                <!-- Yedekler burada listelenecek -->
                <p>Henüz yedek bulunmuyor.</p>
            </div>
        </div>
    </div>
    
    <!-- Bilgilendirme Sekmesi -->
    <div id="matas-bilgi" class="matas-tab-content">
        <div class="matas-card">
            <h2>Maaş Hesaplama Hakkında</h2>
            <p>MATAS - Maaş Takip Sistemi, Türkiye'deki memurların maaşlarını hesaplamak için geliştirilmiş kapsamlı bir hesaplama aracıdır. Bu hesaplayıcı, en güncel katsayılar ve değerler kullanılarak aşağıdaki bileşenleri hesaplar:</p>
            
            <h3>Temel Aylık Bileşenleri</h3>
            <ul>
                <li><strong>Taban Aylık:</strong> Derece ve kademeye göre belirlenen gösterge puanı ile aylık katsayının çarpımı.</li>
                <li><strong>Ek Gösterge:</strong> Unvana göre belirlenen ek gösterge ile aylık katsayının çarpımı.</li>
                <li><strong>Kıdem Aylığı:</strong> Hizmet yılı (en fazla 25 yıl) × 25 × aylık katsayı.</li>
                <li><strong>Yan Ödeme:</strong> Unvana göre belirlenen yan ödeme puanı × yan ödeme katsayısı.</li>
                <li><strong>Özel Hizmet Tazminatı:</strong> (Gösterge puanı + Ek gösterge) × Aylık katsayı × (Özel hizmet oranı / 100).</li>
            </ul>
            
            <h3>Ek Ödemeler ve Tazminatlar</h3>
            <ul>
                <li><strong>Ek Ödeme (666 KHK):</strong> (Taban aylık + Ek gösterge) × 0.20</li>
                <li><strong>Eğitim-Öğretim Tazminatı:</strong> Eğitim kurumlarında çalışanlar için.</li>
                <li><strong>Yabancı Dil Tazminatı:</strong> Yabancı dil sınavından alınan puana göre.</li>
                <li><strong>Lisansüstü Eğitim Tazminatı:</strong> Yüksek lisans için %5, doktora için %15.</li>
                <li><strong>Makam/Görev/Temsil Tazminatı:</strong> Belirli üst düzey görevliler için.</li>
            </ul>
            
            <h3>Sosyal Yardımlar</h3>
            <ul>
                <li><strong>Aile Yardımı:</strong> Eşin çalışmaması durumunda ödenir.</li>
                <li><strong>Çocuk Yardımı:</strong> Her bir çocuk için ödenir, 0-6 yaş, engelli veya öğrenim durumuna göre farklılık gösterir.</li>
                <li><strong>Kira Yardımı:</strong> Belirli şartları sağlayanlar için.</li>
                <li><strong>Sendika Yardımı:</strong> Sendika üyeleri için.</li>
            </ul>
            
            <h3>Kesintiler</h3>
            <ul>
                <li><strong>Emekli Keseneği:</strong> %16</li>
                <li><strong>Genel Sağlık Sigortası:</strong> %5</li>
                <li><strong>Gelir Vergisi:</strong> Gelir vergisi matrahına göre kademeli olarak hesaplanır.</li>
                <li><strong>Damga Vergisi:</strong> Brüt maaş × 0.00759</li>
            </ul>
        </div>
    </div>
    
    <footer>
        <p>MATAS - Maaş Takip Sistemi &copy; <?php echo date('Y'); ?></p>
    </footer>
</div>

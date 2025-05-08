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
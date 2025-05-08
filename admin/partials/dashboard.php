<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <div class="matas-dashboard">
        <div class="matas-card">
            <h2>MATAS - Maaş Takip Sistemi</h2>
            <p>Memur maaşlarını hesaplama ve takip etmek için geliştirilmiş WordPress eklentisi.</p>
            <p>Uygulamayı kullanmak için <code>[matas_hesaplama]</code> kısa kodunu herhangi bir sayfa veya yazıya ekleyin.</p>
        </div>
        
        <div class="matas-card">
            <h2>Hızlı İstatistikler</h2>
            <?php
            global $wpdb;
            $katsayi_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_katsayilar");
            $unvan_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_unvan_bilgileri");
            $gosterge_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_gosterge_puanlari");
            $vergi_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_vergiler WHERE yil = " . date('Y'));
            $yardim_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matas_sosyal_yardimlar WHERE yil = " . date('Y'));
            ?>
            <ul>
                <li><strong>Kayıtlı Dönem Sayısı:</strong> <?php echo $katsayi_count; ?></li>
                <li><strong>Kayıtlı Ünvan Sayısı:</strong> <?php echo $unvan_count; ?></li>
                <li><strong>Tanımlı Gösterge Puanı:</strong> <?php echo $gosterge_count; ?></li>
                <li><strong><?php echo date('Y'); ?> Vergi Dilimi Sayısı:</strong> <?php echo $vergi_count; ?></li>
                <li><strong><?php echo date('Y'); ?> Sosyal Yardım Sayısı:</strong> <?php echo $yardim_count; ?></li>
            </ul>
        </div>
        
        <div class="matas-card">
            <h2>Aktif Katsayılar</h2>
            <?php
            $aktif_katsayi = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}matas_katsayilar WHERE aktif = 1 ORDER BY id DESC LIMIT 1", ARRAY_A);
            if ($aktif_katsayi): ?>
                <table class="matas-table">
                    <tr>
                        <th>Dönem</th>
                        <td><?php echo esc_html($aktif_katsayi['donem']); ?></td>
                    </tr>
                    <tr>
                        <th>Aylık Katsayı</th>
                        <td><?php echo number_format($aktif_katsayi['aylik_katsayi'], 6); ?></td>
                    </tr>
                    <tr>
                        <th>Taban Aylık Katsayısı</th>
                        <td><?php echo number_format($aktif_katsayi['taban_katsayi'], 6); ?></td>
                    </tr>
                    <tr>
                        <th>Yan Ödeme Katsayısı</th>
                        <td><?php echo number_format($aktif_katsayi['yan_odeme_katsayi'], 6); ?></td>
                    </tr>
                </table>
            <?php else: ?>
                <p>Henüz aktif katsayı tanımlanmamış.</p>
            <?php endif; ?>
        </div>
        
        <div class="matas-card">
            <h2>Hızlı Bağlantılar</h2>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=matas-katsayilar'); ?>">Katsayıları Yönet</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=matas-unvanlar'); ?>">Ünvanları Yönet</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=matas-gostergeler'); ?>">Gösterge Puanlarını Yönet</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=matas-vergiler'); ?>">Vergi Dilimlerini Yönet</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=matas-sosyal-yardimlar'); ?>">Sosyal Yardımları Yönet</a></li>
            </ul>
        </div>
        
        <div class="matas-card">
            <h2>Kısa Kod Kullanımı</h2>
            <p>Maaş hesaplama formunu bir sayfada göstermek için aşağıdaki kısa kodu kullanabilirsiniz:</p>
            <code>[matas_hesaplama]</code>
            
            <p>Başlık ve stil seçenekleriyle özelleştirmek isterseniz:</p>
            <code>[matas_hesaplama baslik="2025 Memur Maaş Hesaplama" stil="modern"]</code>
        </div>
    </div>
</div> 

<?php
/**
 * MATAS Admin sınıfına eklenecek eksik AJAX işleyicileri
 * admin/admin.php dosyasına eklenecek metodlar
 */

// Admin sınıfına eklenecek eksik metodlar:

/**
 * Vergi dilimi kaydetme AJAX işleyicisi
 */
public function save_vergi_dilimi() {
    try {
        if (!$this->verify_security()) return;
        $this->check_rate_limit('save');
        
        $data = array(
            'id' => $this->sanitize_input($_POST['vergi_id'] ?? 0, 'int'),
            'yil' => $this->sanitize_input($_POST['yil'] ?? date('Y'), 'int'),
            'dilim' => $this->sanitize_input($_POST['dilim'] ?? 1, 'int'),
            'alt_limit' => $this->sanitize_input($_POST['alt_limit'] ?? 0, 'float'),
            'ust_limit' => $this->sanitize_input($_POST['ust_limit'] ?? 0, 'float'),
            'oran' => $this->sanitize_input($_POST['oran'] ?? 0, 'float')
        );
        
        // Veri doğrulama
        $validation = $this->validate_vergi_data($data);
        if (!$validation['valid']) {
            wp_send_json_error(array('message' => $validation['message']));
            return;
        }
        
        $result = $this->execute_db_operation(function() use ($data) {
            global $wpdb;
            
            // Aynı yıl ve dilim kontrolü
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}matas_vergiler WHERE yil = %d AND dilim = %d AND id != %d",
                $data['yil'], $data['dilim'], $data['id']
            ));
            
            if ($existing) {
                throw new Exception(__('Bu yıl ve dilim için zaten bir kayıt var.', 'matas'));
            }
            
            $db_data = $data;
            unset($db_data['id']);
            
            if ($data['id'] > 0) {
                return $wpdb->update(
                    $wpdb->prefix . 'matas_vergiler',
                    $db_data,
                    array('id' => $data['id']),
                    array('%d', '%d', '%f', '%f', '%f'),
                    array('%d')
                );
            } else {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_vergiler',
                    $db_data,
                    array('%d', '%d', '%f', '%f', '%f')
                );
                return $result ? $wpdb->insert_id : false;
            }
        });
        
        $this->clear_related_cache('vergiler');
        $this->log_admin_action('vergi_saved', $data);
        
        $message = $data['id'] > 0 ? 
            __('Vergi dilimi başarıyla güncellendi.', 'matas') : 
            __('Vergi dilimi başarıyla kaydedildi.', 'matas');
        
        wp_send_json_success(array(
            'message' => $message,
            'vergi_id' => $data['id'] > 0 ? $data['id'] : $result
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage(),
            'error_code' => 'SAVE_FAILED'
        ));
    }
}

/**
 * Vergi dilimi detaylarını getirme
 */
public function get_vergi_dilimi() {
    try {
        if (!$this->verify_security()) return;
        
        $vergi_id = $this->sanitize_input($_POST['vergi_id'] ?? 0, 'int');
        
        if ($vergi_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz vergi ID!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        $vergi = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}matas_vergiler WHERE id = %d",
                $vergi_id
            ),
            ARRAY_A
        );
        
        if (!$vergi) {
            wp_send_json_error(array('message' => __('Vergi dilimi bulunamadı!', 'matas')));
            return;
        }
        
        wp_send_json_success(array('vergi' => $vergi));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => __('Vergi dilimi bilgileri alınırken hata oluştu.', 'matas'),
            'error_code' => 'FETCH_FAILED'
        ));
    }
}

/**
 * Vergi dilimi silme
 */
public function delete_vergi_dilimi() {
    try {
        if (!$this->verify_security()) return;
        $this->check_rate_limit('delete');
        
        $vergi_id = $this->sanitize_input($_POST['vergi_id'] ?? 0, 'int');
        
        if ($vergi_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz vergi ID!', 'matas')));
            return;
        }
        
        $this->execute_db_operation(function() use ($vergi_id) {
            global $wpdb;
            
            $result = $wpdb->delete(
                $wpdb->prefix . 'matas_vergiler',
                array('id' => $vergi_id),
                array('%d')
            );
            
            if ($result === false) {
                throw new Exception('Delete operation failed');
            }
            
            return $result;
        });
        
        $this->clear_related_cache('vergiler');
        $this->log_admin_action('vergi_deleted', array('id' => $vergi_id));
        
        wp_send_json_success(array('message' => __('Vergi dilimi başarıyla silindi.', 'matas')));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => __('Vergi dilimi silinirken bir hata oluştu.', 'matas'),
            'error_code' => 'DELETE_FAILED'
        ));
    }
}

/**
 * Varsayılan vergi dilimlerini yükleme
 */
public function load_default_vergiler() {
    try {
        if (!$this->verify_security()) return;
        $this->check_rate_limit('save');
        
        $yil = $this->sanitize_input($_POST['yil'] ?? date('Y'), 'int');
        $default_vergiler = $this->get_default_vergi_data($yil);
        
        $success_count = $this->execute_db_operation(function() use ($default_vergiler, $yil) {
            global $wpdb;
            
            // Mevcut vergi dilimlerini sil (sadece belirtilen yıl için)
            $wpdb->delete(
                $wpdb->prefix . 'matas_vergiler',
                array('yil' => $yil),
                array('%d')
            );
            
            $success_count = 0;
            foreach ($default_vergiler as $vergi) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_vergiler',
                    $vergi,
                    array('%d', '%d', '%f', '%f', '%f')
                );
                
                if ($result) {
                    $success_count++;
                }
            }
            
            return $success_count;
        });
        
        $this->clear_related_cache('vergiler');
        $this->log_admin_action('default_vergiler_loaded', array('yil' => $yil, 'count' => $success_count));
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d yılı için varsayılan vergi dilimleri başarıyla yüklendi. Toplam %d dilim eklendi.', 'matas'), $yil, $success_count)
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => __('Varsayılan vergi dilimleri yüklenirken hata oluştu.', 'matas'),
            'error_code' => 'LOAD_FAILED'
        ));
    }
}

/**
 * Sosyal yardım kaydetme AJAX işleyicisi
 */
public function save_sosyal_yardim() {
    try {
        if (!$this->verify_security()) return;
        $this->check_rate_limit('save');
        
        $data = array(
            'id' => $this->sanitize_input($_POST['yardim_id'] ?? 0, 'int'),
            'yil' => $this->sanitize_input($_POST['yil'] ?? date('Y'), 'int'),
            'tip' => $this->sanitize_input($_POST['tip'] ?? ''),
            'adi' => $this->sanitize_input($_POST['adi'] ?? ''),
            'tutar' => $this->sanitize_input($_POST['tutar'] ?? 0, 'float')
        );
        
        // Veri doğrulama
        $validation = $this->validate_sosyal_yardim_data($data);
        if (!$validation['valid']) {
            wp_send_json_error(array('message' => $validation['message']));
            return;
        }
        
        $result = $this->execute_db_operation(function() use ($data) {
            global $wpdb;
            
            // Aynı yıl ve tip kontrolü
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}matas_sosyal_yardimlar WHERE yil = %d AND tip = %s AND id != %d",
                $data['yil'], $data['tip'], $data['id']
            ));
            
            if ($existing) {
                throw new Exception(__('Bu yıl ve tip için zaten bir kayıt var.', 'matas'));
            }
            
            $db_data = $data;
            unset($db_data['id']);
            
            if ($data['id'] > 0) {
                return $wpdb->update(
                    $wpdb->prefix . 'matas_sosyal_yardimlar',
                    $db_data,
                    array('id' => $data['id']),
                    array('%d', '%s', '%s', '%f'),
                    array('%d')
                );
            } else {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_sosyal_yardimlar',
                    $db_data,
                    array('%d', '%s', '%s', '%f')
                );
                return $result ? $wpdb->insert_id : false;
            }
        });
        
        $this->clear_related_cache('sosyal_yardimlar');
        $this->log_admin_action('sosyal_yardim_saved', $data);
        
        $message = $data['id'] > 0 ? 
            __('Sosyal yardım başarıyla güncellendi.', 'matas') : 
            __('Sosyal yardım başarıyla kaydedildi.', 'matas');
        
        wp_send_json_success(array(
            'message' => $message,
            'yardim_id' => $data['id'] > 0 ? $data['id'] : $result
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage(),
            'error_code' => 'SAVE_FAILED'
        ));
    }
}

/**
 * Sosyal yardım detaylarını getirme
 */
public function get_sosyal_yardim() {
    try {
        if (!$this->verify_security()) return;
        
        $yardim_id = $this->sanitize_input($_POST['yardim_id'] ?? 0, 'int');
        
        if ($yardim_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz yardım ID!', 'matas')));
            return;
        }
        
        global $wpdb;
        
        $yardim = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}matas_sosyal_yardimlar WHERE id = %d",
                $yardim_id
            ),
            ARRAY_A
        );
        
        if (!$yardim) {
            wp_send_json_error(array('message' => __('Sosyal yardım bulunamadı!', 'matas')));
            return;
        }
        
        wp_send_json_success(array('yardim' => $yardim));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => __('Sosyal yardım bilgileri alınırken hata oluştu.', 'matas'),
            'error_code' => 'FETCH_FAILED'
        ));
    }
}

/**
 * Sosyal yardım silme
 */
public function delete_sosyal_yardim() {
    try {
        if (!$this->verify_security()) return;
        $this->check_rate_limit('delete');
        
        $yardim_id = $this->sanitize_input($_POST['yardim_id'] ?? 0, 'int');
        
        if ($yardim_id <= 0) {
            wp_send_json_error(array('message' => __('Geçersiz yardım ID!', 'matas')));
            return;
        }
        
        $this->execute_db_operation(function() use ($yardim_id) {
            global $wpdb;
            
            $result = $wpdb->delete(
                $wpdb->prefix . 'matas_sosyal_yardimlar',
                array('id' => $yardim_id),
                array('%d')
            );
            
            if ($result === false) {
                throw new Exception('Delete operation failed');
            }
            
            return $result;
        });
        
        $this->clear_related_cache('sosyal_yardimlar');
        $this->log_admin_action('sosyal_yardim_deleted', array('id' => $yardim_id));
        
        wp_send_json_success(array('message' => __('Sosyal yardım başarıyla silindi.', 'matas')));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => __('Sosyal yardım silinirken bir hata oluştu.', 'matas'),
            'error_code' => 'DELETE_FAILED'
        ));
    }
}

/**
 * Varsayılan sosyal yardımları yükleme
 */
public function load_default_sosyal_yardimlar() {
    try {
        if (!$this->verify_security()) return;
        $this->check_rate_limit('save');
        
        $yil = $this->sanitize_input($_POST['yil'] ?? date('Y'), 'int');
        $default_yardimlar = $this->get_default_sosyal_yardim_data($yil);
        
        $success_count = $this->execute_db_operation(function() use ($default_yardimlar, $yil) {
            global $wpdb;
            
            // Mevcut sosyal yardımları sil (sadece belirtilen yıl için)
            $wpdb->delete(
                $wpdb->prefix . 'matas_sosyal_yardimlar',
                array('yil' => $yil),
                array('%d')
            );
            
            $success_count = 0;
            foreach ($default_yardimlar as $yardim) {
                $result = $wpdb->insert(
                    $wpdb->prefix . 'matas_sosyal_yardimlar',
                    $yardim,
                    array('%d', '%s', '%s', '%f')
                );
                
                if ($result) {
                    $success_count++;
                }
            }
            
            return $success_count;
        });
        
        $this->clear_related_cache('sosyal_yardimlar');
        $this->log_admin_action('default_sosyal_yardimlar_loaded', array('yil' => $yil, 'count' => $success_count));
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d yılı için varsayılan sosyal yardımlar başarıyla yüklendi. Toplam %d yardım eklendi.', 'matas'), $yil, $success_count)
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => __('Varsayılan sosyal yardımlar yüklenirken hata oluştu.', 'matas'),
            'error_code' => 'LOAD_FAILED'
        ));
    }
}

/**
 * Vergi verisi doğrulama
 */
private function validate_vergi_data($data) {
    if (empty($data['yil']) || $data['yil'] < 2020 || $data['yil'] > 2030) {
        return array('valid' => false, 'message' => __('Geçerli bir yıl seçiniz (2020-2030).', 'matas'));
    }

    if (empty($data['dilim']) || $data['dilim'] < 1 || $data['dilim'] > 10) {
        return array('valid' => false, 'message' => __('Vergi dilimi 1-10 arasında olmalıdır.', 'matas'));
    }

    if ($data['alt_limit'] < 0) {
        return array('valid' => false, 'message' => __('Alt limit 0\'dan küçük olamaz.', 'matas'));
    }

    if ($data['ust_limit'] < 0) {
        return array('valid' => false, 'message' => __('Üst limit 0\'dan küçük olamaz.', 'matas'));
    }

    if ($data['ust_limit'] > 0 && $data['ust_limit'] <= $data['alt_limit']) {
        return array('valid' => false, 'message' => __('Üst limit alt limitten büyük olmalıdır.', 'matas'));
    }

    if ($data['oran'] < 0 || $data['oran'] > 100) {
        return array('valid' => false, 'message' => __('Vergi oranı 0-100 arasında olmalıdır.', 'matas'));
    }

    return array('valid' => true);
}

/**
 * Sosyal yardım verisi doğrulama
 */
private function validate_sosyal_yardim_data($data) {
    if (empty($data['yil']) || $data['yil'] < 2020 || $data['yil'] > 2030) {
        return array('valid' => false, 'message' => __('Geçerli bir yıl seçiniz (2020-2030).', 'matas'));
    }

    if (empty($data['tip'])) {
        return array('valid' => false, 'message' => __('Yardım tipi seçiniz.', 'matas'));
    }

    if (empty($data['adi'])) {
        return array('valid' => false, 'message' => __('Yardım adı giriniz.', 'matas'));
    }

    if ($data['tutar'] < 0) {
        return array('valid' => false, 'message' => __('Tutar 0\'dan küçük olamaz.', 'matas'));
    }

    return array('valid' => true);
}

/**
 * Varsayılan vergi dilimi verilerini döndür
 */
private function get_default_vergi_data($yil) {
    return array(
        array('yil' => $yil, 'dilim' => 1, 'alt_limit' => 0, 'ust_limit' => 70000, 'oran' => 15),
        array('yil' => $yil, 'dilim' => 2, 'alt_limit' => 70000, 'ust_limit' => 150000, 'oran' => 20),
        array('yil' => $yil, 'dilim' => 3, 'alt_limit' => 150000, 'ust_limit' => 550000, 'oran' => 27),
        array('yil' => $yil, 'dilim' => 4, 'alt_limit' => 550000, 'ust_limit' => 1900000, 'oran' => 35),
        array('yil' => $yil, 'dilim' => 5, 'alt_limit' => 1900000, 'ust_limit' => 0, 'oran' => 40),
    );
}

/**
 * Varsayılan sosyal yardım verilerini döndür
 */
private function get_default_sosyal_yardim_data($yil) {
    return array(
        array('yil' => $yil, 'tip' => 'aile_yardimi', 'adi' => 'Aile Yardımı', 'tutar' => 1200),
        array('yil' => $yil, 'tip' => 'cocuk_normal', 'adi' => 'Çocuk Yardımı', 'tutar' => 150),
        array('yil' => $yil, 'tip' => 'cocuk_0_6', 'adi' => '0-6 Yaş Çocuk Yardımı', 'tutar' => 300),
        array('yil' => $yil, 'tip' => 'cocuk_engelli', 'adi' => 'Engelli Çocuk Yardımı', 'tutar' => 600),
        array('yil' => $yil, 'tip' => 'cocuk_ogrenim', 'adi' => 'Öğrenim Çocuk Yardımı', 'tutar' => 250),
        array('yil' => $yil, 'tip' => 'kira_yardimi', 'adi' => 'Kira Yardımı', 'tutar' => 2000),
        array('yil' => $yil, 'tip' => 'sendika_yardimi', 'adi' => 'Sendika Yardımı', 'tutar' => 500),
        array('yil' => $yil, 'tip' => 'yemek_yardimi', 'adi' => 'Yemek Yardımı', 'tutar' => 1200),
        array('yil' => $yil, 'tip' => 'giyecek_yardimi', 'adi' => 'Giyecek Yardımı', 'tutar' => 800),
        array('yil' => $yil, 'tip' => 'yakacak_yardimi', 'adi' => 'Yakacak Yardımı', 'tutar' => 1100),
    );
}
?>

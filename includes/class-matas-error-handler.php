<?php
/**
 * MATAS Error Handler - Gelişmiş Hata Yönetimi
 * 
 * @package MATAS
 * @since 1.1.0
 */

class Matas_Error_Handler {
    
    /**
     * Error codes and their user-friendly messages
     *
     * @var array
     */
    private static $error_messages = array(
        // Calculation Errors
        'CALCULATION_ERROR' => 'Maaş hesaplaması sırasında bir hata oluştu. Lütfen girdiğiniz değerleri kontrol edin.',
        'INVALID_PARAMETERS' => 'Girdiğiniz bilgilerde hata var. Lütfen tüm zorunlu alanları doğru şekilde doldurun.',
        'MISSING_REQUIRED_FIELD' => 'Zorunlu alanlar eksik. Lütfen tüm gerekli bilgileri girin.',
        'INVALID_RANGE' => 'Girdiğiniz değer geçerli aralıkta değil. Lütfen doğru değer girin.',
        'UNVAN_NOT_FOUND' => 'Seçtiğiniz ünvan bulunamadı. Lütfen geçerli bir ünvan seçin.',
        'GOSTERGE_NOT_FOUND' => 'Belirtilen derece ve kademe için gösterge puanı bulunamadı.',
        
        // Database Errors
        'DATABASE_ERROR' => 'Veritabanı bağlantısında sorun oluştu. Lütfen daha sonra tekrar deneyin.',
        'DATABASE_CONNECTION_FAILED' => 'Veritabanına bağlanılamadı. Lütfen sistem yöneticisine başvurun.',
        'QUERY_FAILED' => 'Veri sorgusu başarısız oldu. Lütfen tekrar deneyin.',
        'DATA_NOT_FOUND' => 'Aradığınız veri bulunamadı.',
        'DUPLICATE_ENTRY' => 'Bu kayıt zaten mevcut. Lütfen farklı değerler deneyin.',
        
        // Security Errors
        'SECURITY_ERROR' => 'Güvenlik doğrulaması başarısız oldu. Lütfen sayfayı yenileyin.',
        'NONCE_FAILED' => 'Güvenlik kontrolü başarısız. Lütfen sayfayı yenileyin ve tekrar deneyin.',
        'INSUFFICIENT_PERMISSIONS' => 'Bu işlemi gerçekleştirmek için yetkiniz bulunmuyor.',
        'INVALID_REQUEST' => 'Geçersiz istek. Lütfen sayfayı yenileyin.',
        'RATE_LIMIT_EXCEEDED' => 'Çok fazla istek gönderdiniz. Lütfen bir süre bekleyin.',
        'IP_BLOCKED' => 'IP adresiniz geçici olarak engellenmiştir.',
        
        // Validation Errors
        'VALIDATION_ERROR' => 'Girdiğiniz veriler geçerli değil.',
        'INVALID_EMAIL' => 'Geçerli bir e-posta adresi girin.',
        'INVALID_NUMBER' => 'Geçerli bir sayı girin.',
        'VALUE_TOO_SMALL' => 'Girdiğiniz değer çok küçük.',
        'VALUE_TOO_LARGE' => 'Girdiğiniz değer çok büyük.',
        'INVALID_FORMAT' => 'Girdiğiniz veri formatı geçerli değil.',
        
        // File Errors
        'FILE_ERROR' => 'Dosya işlemi sırasında hata oluştu.',
        'FILE_NOT_FOUND' => 'Dosya bulunamadı.',
        'FILE_UPLOAD_ERROR' => 'Dosya yükleme hatası. Lütfen tekrar deneyin.',
        'FILE_SIZE_EXCEEDED' => 'Dosya boyutu çok büyük.',
        'INVALID_FILE_TYPE' => 'Geçersiz dosya türü.',
        
        // System Errors
        'SYSTEM_ERROR' => 'Sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.',
        'MAINTENANCE_MODE' => 'Sistem bakımda. Lütfen daha sonra tekrar deneyin.',
        'SERVICE_UNAVAILABLE' => 'Hizmet şu anda kullanılamıyor.',
        'TIMEOUT_ERROR' => 'İşlem zaman aşımına uğradı. Lütfen tekrar deneyin.',
        'MEMORY_LIMIT_EXCEEDED' => 'Sistem kaynak sınırına ulaştı.',
        
        // Generic
        'UNKNOWN_ERROR' => 'Bilinmeyen bir hata oluştu. Lütfen tekrar deneyin.',
        'CONTACT_ADMIN' => 'Sorun devam ederse sistem yöneticisine başvurun.',
    );
    
    /**
     * Error severity levels
     *
     * @var array
     */
    private static $severity_levels = array(
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'critical' => 4
    );
    
    /**
     * Error log file path
     *
     * @var string
     */
    private static $log_file = null;
    
    /**
     * Initialize error handler
     */
    public static function init() {
        // Set custom error handler
        set_error_handler(array(__CLASS__, 'handle_php_error'));
        set_exception_handler(array(__CLASS__, 'handle_exception'));
        
        // Set log file path
        self::$log_file = MATAS_PLUGIN_DIR . 'logs/error.log';
        
        // Create logs directory if it doesn't exist
        $log_dir = dirname(self::$log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
    }
    
    /**
     * Get user-friendly error message
     *
     * @param string $error_code Error code
     * @param array $context Additional context
     * @return string User-friendly error message
     */
    public static function get_user_message($error_code, $context = array()) {
        $message = isset(self::$error_messages[$error_code]) 
            ? self::$error_messages[$error_code] 
            : self::$error_messages['UNKNOWN_ERROR'];
        
        // Add context-specific information
        if (!empty($context)) {
            $message = self::add_context_to_message($message, $context);
        }
        
        return $message;
    }
    
    /**
     * Add context to error message
     *
     * @param string $message Base message
     * @param array $context Context data
     * @return string Enhanced message
     */
    private static function add_context_to_message($message, $context) {
        // Field-specific error messages
        if (isset($context['field'])) {
            $field_names = array(
                'derece' => 'Derece',
                'kademe' => 'Kademe',
                'hizmet_yili' => 'Hizmet Yılı',
                'unvan' => 'Ünvan',
                'cocuk_sayisi' => 'Çocuk Sayısı'
            );
            
            if (isset($field_names[$context['field']])) {
                $message = $field_names[$context['field']] . ' alanında hata: ' . $message;
            }
        }
        
        // Range-specific messages
        if (isset($context['min'], $context['max'])) {
            $message .= " (Geçerli aralık: {$context['min']}-{$context['max']})";
        }
        
        // Value-specific messages
        if (isset($context['value'])) {
            $message .= " (Girilen değer: {$context['value']})";
        }
        
        return $message;
    }
    
    /**
     * Log error with details
     *
     * @param string $error_code Error code
     * @param string $message Error message
     * @param array $context Error context
     * @param string $severity Error severity
     */
    public static function log_error($error_code, $message, $context = array(), $severity = 'medium') {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'error_code' => $error_code,
            'message' => $message,
            'severity' => $severity,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'ip_address' => self::get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'stack_trace' => self::get_stack_trace()
        );
        
        // Log to file
        self::write_to_log_file($log_entry);
        
        // Log to WordPress error log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MATAS Error: ' . json_encode($log_entry));
        }
        
        // Send email for critical errors
        if ($severity === 'critical') {
            self::send_error_notification($log_entry);
        }
        
        // Store in database if table exists
        self::store_in_database($log_entry);
    }
    
    /**
     * Handle PHP errors
     *
     * @param int $severity Error severity
     * @param string $message Error message
     * @param string $file File where error occurred
     * @param int $line Line number
     * @return bool
     */
    public static function handle_php_error($severity, $message, $file, $line) {
        // Don't handle errors if error reporting is off
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $error_types = array(
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        );
        
        $error_type = isset($error_types[$severity]) ? $error_types[$severity] : 'Unknown Error';
        
        $context = array(
            'file' => $file,
            'line' => $line,
            'type' => $error_type,
            'severity_level' => $severity
        );
        
        $log_severity = ($severity & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) ? 'critical' : 'medium';
        
        self::log_error('PHP_ERROR', $message, $context, $log_severity);
        
        // Don't execute PHP's default error handler
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     *
     * @param Exception $exception The uncaught exception
     */
    public static function handle_exception($exception) {
        $context = array(
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString()
        );
        
        self::log_error('UNCAUGHT_EXCEPTION', $exception->getMessage(), $context, 'critical');
        
        // Display user-friendly error page
        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            self::display_error_page('Sistem hatası oluştu. Lütfen daha sonra tekrar deneyin.');
        }
    }
    
    /**
     * Create standardized error response
     *
     * @param string $error_code Error code
     * @param array $context Additional context
     * @param mixed $data Additional data
     * @return array Error response
     */
    public static function create_error_response($error_code, $context = array(), $data = null) {
        return array(
            'success' => false,
            'error_code' => $error_code,
            'message' => self::get_user_message($error_code, $context),
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * Create standardized success response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @return array Success response
     */
    public static function create_success_response($data = null, $message = '') {
        return array(
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * Validate and sanitize calculation parameters
     *
     * @param array $params Raw parameters
     * @return array Validation result
     */
    public static function validate_calculation_params($params) {
        $errors = array();
        $sanitized = array();
        
        // Required fields
        $required_fields = array('unvan', 'derece', 'kademe', 'hizmet_yili');
        
        foreach ($required_fields as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                $errors[] = self::create_error_response('MISSING_REQUIRED_FIELD', array('field' => $field));
            }
        }
        
        // Validate derece (1-15)
        if (isset($params['derece'])) {
            $derece = intval($params['derece']);
            if ($derece < 1 || $derece > 15) {
                $errors[] = self::create_error_response('INVALID_RANGE', array(
                    'field' => 'derece',
                    'value' => $derece,
                    'min' => 1,
                    'max' => 15
                ));
            } else {
                $sanitized['derece'] = $derece;
            }
        }
        
        // Validate kademe (1-9)
        if (isset($params['kademe'])) {
            $kademe = intval($params['kademe']);
            if ($kademe < 1 || $kademe > 9) {
                $errors[] = self::create_error_response('INVALID_RANGE', array(
                    'field' => 'kademe',
                    'value' => $kademe,
                    'min' => 1,
                    'max' => 9
                ));
            } else {
                $sanitized['kademe'] = $kademe;
            }
        }
        
        // Validate hizmet_yili (0-50)
        if (isset($params['hizmet_yili'])) {
            $hizmet_yili = intval($params['hizmet_yili']);
            if ($hizmet_yili < 0 || $hizmet_yili > 50) {
                $errors[] = self::create_error_response('INVALID_RANGE', array(
                    'field' => 'hizmet_yili',
                    'value' => $hizmet_yili,
                    'min' => 0,
                    'max' => 50
                ));
            } else {
                $sanitized['hizmet_yili'] = $hizmet_yili;
            }
        }
        
        // Validate unvan
        if (isset($params['unvan'])) {
            $unvan = sanitize_text_field($params['unvan']);
            if (empty($unvan)) {
                $errors[] = self::create_error_response('INVALID_PARAMETERS', array('field' => 'unvan'));
            } else {
                $sanitized['unvan'] = $unvan;
            }
        }
        
        // Validate cocuk_sayisi (0-10)
        if (isset($params['cocuk_sayisi'])) {
            $cocuk_sayisi = intval($params['cocuk_sayisi']);
            if ($cocuk_sayisi < 0 || $cocuk_sayisi > 10) {
                $errors[] = self::create_error_response('INVALID_RANGE', array(
                    'field' => 'cocuk_sayisi',
                    'value' => $cocuk_sayisi,
                    'min' => 0,
                    'max' => 10
                ));
            } else {
                $sanitized['cocuk_sayisi'] = $cocuk_sayisi;
            }
        }
        
        // Sanitize optional text fields
        $text_fields = array('medeni_hal', 'es_calisiyor', 'egitim_durumu', 'dil_seviyesi', 'dil_kullanimi');
        foreach ($text_fields as $field) {
            if (isset($params[$field])) {
                $sanitized[$field] = sanitize_text_field($params[$field]);
            }
        }
        
        // Sanitize numeric fields
        $numeric_fields = array('cocuk_06', 'engelli_cocuk', 'ogrenim_cocuk');
        foreach ($numeric_fields as $field) {
            if (isset($params[$field])) {
                $value = intval($params[$field]);
                if ($value < 0 || $value > 10) {
                    $errors[] = self::create_error_response('INVALID_RANGE', array(
                        'field' => $field,
                        'value' => $value,
                        'min' => 0,
                        'max' => 10
                    ));
                } else {
                    $sanitized[$field] = $value;
                }
            }
        }
        
        // Sanitize boolean fields
        $boolean_fields = array('gorev_tazminati', 'gelistirme_odenegi', 'asgari_gecim_indirimi', 'kira_yardimi', 'sendika_uyesi');
        foreach ($boolean_fields as $field) {
            if (isset($params[$field])) {
                $sanitized[$field] = !empty($params[$field]) ? 1 : 0;
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $sanitized
        );
    }
    
    /**
     * Get user IP address
     *
     * @return string User IP address
     */
    private static function get_user_ip() {
        $ip_fields = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_fields as $field) {
            if (!empty($_SERVER[$field])) {
                $ip = trim(explode(',', $_SERVER[$field])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get simplified stack trace
     *
     * @return string Stack trace
     */
    private static function get_stack_trace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $formatted_trace = array();
        
        foreach ($trace as $frame) {
            if (isset($frame['file'], $frame['line'])) {
                $file = basename($frame['file']);
                $formatted_trace[] = "{$file}:{$frame['line']}";
            }
        }
        
        return implode(' -> ', $formatted_trace);
    }
    
    /**
     * Write error to log file
     *
     * @param array $log_entry Log entry data
     */
    private static function write_to_log_file($log_entry) {
        if (!self::$log_file) {
            return;
        }
        
        $log_line = sprintf(
            "[%s] %s (%s) - %s - IP: %s - User: %d\n",
            $log_entry['timestamp'],
            $log_entry['error_code'],
            $log_entry['severity'],
            $log_entry['message'],
            $log_entry['ip_address'],
            $log_entry['user_id']
        );
        
        // Append to log file
        file_put_contents(self::$log_file, $log_line, FILE_APPEND | LOCK_EX);
        
        // Rotate log file if it gets too large (> 10MB)
        if (file_exists(self::$log_file) && filesize(self::$log_file) > 10 * 1024 * 1024) {
            self::rotate_log_file();
        }
    }
    
    /**
     * Rotate log file
     */
    private static function rotate_log_file() {
        if (!self::$log_file || !file_exists(self::$log_file)) {
            return;
        }
        
        $backup_file = self::$log_file . '.' . date('Y-m-d-H-i-s');
        rename(self::$log_file, $backup_file);
        
        // Keep only last 5 backup files
        $log_dir = dirname(self::$log_file);
        $backup_files = glob($log_dir . '/error.log.*');
        
        if (count($backup_files) > 5) {
            sort($backup_files);
            $files_to_delete = array_slice($backup_files, 0, -5);
            foreach ($files_to_delete as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Send error notification email
     *
     * @param array $log_entry Log entry data
     */
    private static function send_error_notification($log_entry) {
        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return;
        }
        
        $site_name = get_bloginfo('name');
        $subject = "[{$site_name}] MATAS Kritik Hata";
        
        $message = "MATAS eklentisinde kritik bir hata oluştu:\n\n";
        $message .= "Hata Kodu: {$log_entry['error_code']}\n";
        $message .= "Mesaj: {$log_entry['message']}\n";
        $message .= "Zaman: {$log_entry['timestamp']}\n";
        $message .= "Kullanıcı: {$log_entry['user_id']}\n";
        $message .= "IP: {$log_entry['ip_address']}\n";
        $message .= "URL: {$log_entry['request_uri']}\n";
        $message .= "Stack Trace: {$log_entry['stack_trace']}\n";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Store error in database
     *
     * @param array $log_entry Log entry data
     */
    private static function store_in_database($log_entry) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'matas_error_logs';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return;
        }
        
        $wpdb->insert(
            $table_name,
            array(
                'timestamp' => $log_entry['timestamp'],
                'error_code' => $log_entry['error_code'],
                'message' => $log_entry['message'],
                'severity' => $log_entry['severity'],
                'context' => json_encode($log_entry['context']),
                'user_id' => $log_entry['user_id'],
                'ip_address' => $log_entry['ip_address'],
                'user_agent' => $log_entry['user_agent'],
                'request_uri' => $log_entry['request_uri']
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Display user-friendly error page
     *
     * @param string $message Error message
     */
    private static function display_error_page($message) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Sistem Hatası - MATAS</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 50px; }
                .error-container { 
                    max-width: 600px; margin: 0 auto; background: white; 
                    padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
                    text-align: center;
                }
                .error-icon { font-size: 64px; color: #e74c3c; margin-bottom: 20px; }
                .error-title { color: #333; font-size: 24px; margin-bottom: 15px; }
                .error-message { color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 30px; }
                .error-actions { margin-top: 30px; }
                .btn { 
                    display: inline-block; padding: 12px 24px; background: #3498db; 
                    color: white; text-decoration: none; border-radius: 5px; margin: 0 10px;
                }
                .btn:hover { background: #2980b9; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <h1 class="error-title">Sistem Hatası</h1>
                <p class="error-message">' . esc_html($message) . '</p>
                <div class="error-actions">
                    <a href="javascript:history.back()" class="btn">Geri Dön</a>
                    <a href="' . home_url() . '" class="btn">Ana Sayfa</a>
                </div>
            </div>
        </body>
        </html>';
        
        echo $html;
        exit;
    }
    
    /**
     * Get error statistics
     *
     * @return array Error statistics
     */
    public static function get_error_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'matas_error_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return array();
        }
        
        $stats = array();
        
        // Total errors today
        $stats['today'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE DATE(timestamp) = CURDATE()"
        );
        
        // Total errors this week
        $stats['week'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        // Errors by severity
        $severity_stats = $wpdb->get_results(
            "SELECT severity, COUNT(*) as count FROM {$table_name} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
             GROUP BY severity",
            ARRAY_A
        );
        
        $stats['by_severity'] = array();
        foreach ($severity_stats as $row) {
            $stats['by_severity'][$row['severity']] = $row['count'];
        }
        
        // Most common errors
        $stats['common_errors'] = $wpdb->get_results(
            "SELECT error_code, COUNT(*) as count FROM {$table_name} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
             GROUP BY error_code ORDER BY count DESC LIMIT 5",
            ARRAY_A
        );
        
        return $stats;
    }
    
    /**
     * Clean old error logs
     *
     * @param int $days_to_keep Number of days to keep logs
     */
    public static function clean_old_logs($days_to_keep = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'matas_error_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return;
        }
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days_to_keep
        ));
        
        // Also clean old log files
        if (self::$log_file) {
            $log_dir = dirname(self::$log_file);
            $old_files = glob($log_dir . '/error.log.*');
            
            foreach ($old_files as $file) {
                if (filemtime($file) < strtotime("-{$days_to_keep} days")) {
                    unlink($file);
                }
            }
        }
    }
}

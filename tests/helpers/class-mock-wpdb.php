<?php
/**
 * Mock WPDB class for testing
 *
 * @package MATAS
 * @subpackage Tests
 */

class Mock_Wpdb {
    
    public $prefix = 'wp_';
    public $insert_id = 1;
    public $last_query = '';
    public $num_queries = 0;
    
    private $mock_data = array();
    private $mock_results = array();
    
    public function __construct() {
        $this->setup_mock_data();
    }
    
    /**
     * Setup mock data for testing
     */
    private function setup_mock_data() {
        // Mock katsayılar data
        $this->mock_data['matas_katsayilar'] = array(
            array(
                'id' => 1,
                'donem' => '2025 Ocak-Haziran',
                'aylik_katsayi' => 0.354507,
                'taban_katsayi' => 7.715,
                'yan_odeme_katsayi' => 0.0354507,
                'aktif' => 1,
                'olusturma_tarihi' => '2025-01-01 00:00:00'
            )
        );
        
        // Mock unvan bilgileri
        $this->mock_data['matas_unvan_bilgileri'] = array(
            array(
                'id' => 1,
                'unvan_kodu' => 'ogretmen_sinif',
                'unvan_adi' => 'Sınıf Öğretmeni',
                'ekgosterge' => 2200,
                'ozel_hizmet' => 80,
                'yan_odeme' => 800,
                'is_guclugu' => 300,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0.20
            ),
            array(
                'id' => 2,
                'unvan_kodu' => 'memur_genel',
                'unvan_adi' => 'Memur',
                'ekgosterge' => 1300,
                'ozel_hizmet' => 50,
                'yan_odeme' => 600,
                'is_guclugu' => 250,
                'makam_tazminat' => 0,
                'egitim_tazminat' => 0
            )
        );
        
        // Mock gösterge puanları
        $this->mock_data['matas_gosterge_puanlari'] = array(
            array('id' => 1, 'derece' => 8, 'kademe' => 5, 'gosterge_puani' => 720),
            array('id' => 2, 'derece' => 10, 'kademe' => 1, 'gosterge_puani' => 590),
        );
        
        // Mock sosyal yardımlar
        $this->mock_data['matas_sosyal_yardimlar'] = array(
            array('id' => 1, 'yil' => 2025, 'tip' => 'aile_yardimi', 'adi' => 'Aile Yardımı', 'tutar' => 1200),
            array('id' => 2, 'yil' => 2025, 'tip' => 'cocuk_normal', 'adi' => 'Çocuk Yardımı', 'tutar' => 150),
            array('id' => 3, 'yil' => 2025, 'tip' => 'cocuk_0_6', 'adi' => '0-6 Yaş Çocuk Yardımı', 'tutar' => 300),
        );
        
        // Mock vergi dilimleri
        $this->mock_data['matas_vergiler'] = array(
            array('id' => 1, 'yil' => 2025, 'dilim' => 1, 'alt_limit' => 0, 'ust_limit' => 70000, 'oran' => 15),
            array('id' => 2, 'yil' => 2025, 'dilim' => 2, 'alt_limit' => 70000, 'ust_limit' => 150000, 'oran' => 20),
        );
    }
    
    /**
     * Mock get_row method
     */
    public function get_row($query, $output = OBJECT, $y = 0) {
        $this->last_query = $query;
        $this->num_queries++;
        
        // Parse table name from query
        $table = $this->extract_table_from_query($query);
        
        if (!isset($this->mock_data[$table]) || empty($this->mock_data[$table])) {
            return null;
        }
        
        $result = $this->mock_data[$table][0];
        
        if ($output === ARRAY_A) {
            return $result;
        } elseif ($output === OBJECT) {
            return (object) $result;
        }
        
        return $result;
    }
    
    /**
     * Mock get_results method
     */
    public function get_results($query, $output = OBJECT) {
        $this->last_query = $query;
        $this->num_queries++;
        
        $table = $this->extract_table_from_query($query);
        
        if (!isset($this->mock_data[$table])) {
            return array();
        }
        
        $results = $this->mock_data[$table];
        
        if ($output === ARRAY_A) {
            return $results;
        } elseif ($output === OBJECT) {
            return array_map(function($row) {
                return (object) $row;
            }, $results);
        }
        
        return $results;
    }
    
    /**
     * Mock get_var method
     */
    public function get_var($query, $x = 0, $y = 0) {
        $this->last_query = $query;
        $this->num_queries++;
        
        // Return mock count for COUNT queries
        if (strpos(strtoupper($query), 'COUNT') !== false) {
            $table = $this->extract_table_from_query($query);
            return isset($this->mock_data[$table]) ? count($this->mock_data[$table]) : 0;
        }
        
        return 1;
    }
    
    /**
     * Mock insert method
     */
    public function insert($table, $data, $format = null) {
        $this->last_query = "INSERT INTO {$table}";
        $this->num_queries++;
        
        $clean_table = str_replace($this->prefix, '', $table);
        
        if (!isset($this->mock_data[$clean_table])) {
            $this->mock_data[$clean_table] = array();
        }
        
        $data['id'] = ++$this->insert_id;
        $this->mock_data[$clean_table][] = $data;
        
        return 1; // Success
    }
    
    /**
     * Mock update method
     */
    public function update($table, $data, $where, $format = null, $where_format = null) {
        $this->last_query = "UPDATE {$table}";
        $this->num_queries++;
        
        return 1; // Success
    }
    
    /**
     * Mock delete method
     */
    public function delete($table, $where, $where_format = null) {
        $this->last_query = "DELETE FROM {$table}";
        $this->num_queries++;
        
        return 1; // Success
    }
    
    /**
     * Mock prepare method
     */
    public function prepare($query) {
        $args = func_get_args();
        array_shift($args); // Remove query
        
        $this->last_query = $query;
        
        // Simple prepare simulation - just return the query
        return vsprintf(str_replace('%s', "'%s'", $query), $args);
    }
    
    /**
     * Mock query method
     */
    public function query($query) {
        $this->last_query = $query;
        $this->num_queries++;
        
        // Return success for most queries
        if (stripos($query, 'START TRANSACTION') !== false ||
            stripos($query, 'COMMIT') !== false ||
            stripos($query, 'ROLLBACK') !== false ||
            stripos($query, 'OPTIMIZE TABLE') !== false ||
            stripos($query, 'TRUNCATE TABLE') !== false) {
            return true;
        }
        
        return 1;
    }
    
    /**
     * Extract table name from SQL query
     */
    private function extract_table_from_query($query) {
        // Remove prefix and extract table name
        $pattern = '/' . preg_quote($this->prefix, '/') . '([a-zA-Z_]+)/';
        if (preg_match($pattern, $query, $matches)) {
            return $matches[1];
        }
        
        return 'unknown_table';
    }
    
    /**
     * Get charset collate for table creation
     */
    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';
    }
    
    /**
     * Add mock data for testing
     */
    public function add_mock_data($table, $data) {
        if (!isset($this->mock_data[$table])) {
            $this->mock_data[$table] = array();
        }
        
        $this->mock_data[$table][] = $data;
    }
    
    /**
     * Clear mock data
     */
    public function clear_mock_data($table = null) {
        if ($table) {
            unset($this->mock_data[$table]);
        } else {
            $this->mock_data = array();
        }
    }
    
    /**
     * Get mock data for assertions
     */
    public function get_mock_data($table = null) {
        if ($table) {
            return isset($this->mock_data[$table]) ? $this->mock_data[$table] : array();
        }
        
        return $this->mock_data;
    }
}

<?php
/**
 * Kancaları ve kısa kodları yükleme sınıfı
 * 
 * @package MATAS
 * @since 1.0.0
 */

class Matas_Loader {
    /**
     * Actions kancaları
     *
     * @var array
     */
    protected $actions;
    
    /**
     * Filters kancaları
     *
     * @var array
     */
    protected $filters;
    
    /**
     * Shortcodes kısa kodları
     *
     * @var array
     */
    protected $shortcodes;

    /**
     * Sınıfı başlat
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
    }
    
    /**
     * Action kancası ekle
     *
     * @param string $hook Kanca adı
     * @param object $component Bileşen nesnesi
     * @param string $callback Çağrılacak fonksiyon
     * @param int $priority Öncelik
     * @param int $accepted_args Kabul edilen argüman sayısı
     * @return void
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Filter kancası ekle
     *
     * @param string $hook Kanca adı
     * @param object $component Bileşen nesnesi
     * @param string $callback Çağrılacak fonksiyon
     * @param int $priority Öncelik
     * @param int $accepted_args Kabul edilen argüman sayısı
     * @return void
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Kısa kod ekle
     *
     * @param string $tag Kısa kod etiketi
     * @param object $component Bileşen nesnesi
     * @param string $callback Çağrılacak fonksiyon
     * @return void
     */
    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes = $this->add_sc($this->shortcodes, $tag, $component, $callback);
    }
    
    /**
     * Kanca ekle
     *
     * @param array $hooks Mevcut kancalar
     * @param string $hook Kanca adı
     * @param object $component Bileşen nesnesi
     * @param string $callback Çağrılacak fonksiyon
     * @param int $priority Öncelik
     * @param int $accepted_args Kabul edilen argüman sayısı
     * @return array
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        
        return $hooks;
    }
    
    /**
     * Kısa kod ekle
     *
     * @param array $shortcodes Mevcut kısa kodlar
     * @param string $tag Kısa kod etiketi
     * @param object $component Bileşen nesnesi
     * @param string $callback Çağrılacak fonksiyon
     * @return array
     */
    private function add_sc($shortcodes, $tag, $component, $callback) {
        $shortcodes[] = array(
            'tag'       => $tag,
            'component' => $component,
            'callback'  => $callback
        );
        
        return $shortcodes;
    }
    
    /**
     * Tüm kancaları ve kısa kodları çalıştır
     */
    public function run() {
        // Filter kancalarını ekle
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'], 
                array($hook['component'], $hook['callback']), 
                $hook['priority'], 
                $hook['accepted_args']
            );
        }
        
        // Action kancalarını ekle
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'], 
                array($hook['component'], $hook['callback']), 
                $hook['priority'], 
                $hook['accepted_args']
            );
        }
        
        // Kısa kodları ekle
        foreach ($this->shortcodes as $shortcode) {
            add_shortcode(
                $shortcode['tag'], 
                array($shortcode['component'], $shortcode['callback'])
            );
        }
    }
}

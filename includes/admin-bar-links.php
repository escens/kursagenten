<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * Klasse for å håndtere admin-bar-lenker og admin-markeringer for system-sidene
 */
class KA_Admin_Bar_Links {
    private static $instance = null;
    private $system_pages;
    
    private function __construct() {
        $this->init_system_pages();
        add_action('admin_bar_menu', [$this, 'add_admin_bar_links'], 100);
        add_filter('display_post_states', [$this, 'add_post_state'], 10, 2);
    }
    
    private function init_system_pages() {
        $this->system_pages = [
            'instruktorer' => [
                'taxonomy' => 'instructors',
                'option_key' => 'ka_page_instruktorer',
                'label' => 'Rediger instruktører'
            ],
            'kurskategorier' => [
                'taxonomy' => 'coursecategory',
                'option_key' => 'ka_page_kurskategorier',
                'label' => 'Rediger kurskategorier'
            ],
            'kurssteder' => [
                'taxonomy' => 'course_location',
                'option_key' => 'ka_page_kurssteder',
                'label' => 'Rediger kurssteder'
            ],
            'kurs' => [
                'option_key' => 'ka_page_kurs',
                'label' => 'Se importerte kurs',
                'admin_url' => 'edit.php?post_type=course'
            ],
            'betaling' => [
                'option_key' => 'ka_page_betaling',
                'label' => 'Betaling'
            ]
        ];
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Legger til "Kursagenten" som post state for systemsider
     */
    public function add_post_state($post_states, $post) {
        foreach ($this->system_pages as $page_data) {
            $page_id = get_option($page_data['option_key']);
            if ($page_id && $post->ID == $page_id) {
                $post_states['kursagenten'] = 'Kursagenten';
                break;
            }
        }
        return $post_states;
    }
    
    /**
     * Legger til lenker i admin-baren basert på systemsider
     */
    public function add_admin_bar_links($wp_admin_bar) {
        // Sjekk om vi er på en systemside
        $current_page_id = get_the_ID();
        if (!$current_page_id) {
            return;
        }
        
        // Sjekk om gjeldende side er en systemside ved å sammenligne page ID
        foreach ($this->system_pages as $page_key => $page_data) {
            $page_id = get_option($page_data['option_key']);
            
            if ($page_id && $current_page_id == $page_id) {
                // Sjekk om brukeren har tilgang til å redigere
                if (!current_user_can('manage_categories')) {
                    return;
                }
                
                // Legg til lenke i admin-baren
                $wp_admin_bar->add_node([
                    'id' => 'ka-edit-' . $page_key,
                    'title' => $page_data['label'],
                    'href' => admin_url(isset($page_data['admin_url']) 
                        ? $page_data['admin_url'] 
                        : 'edit-tags.php?taxonomy=' . $page_data['taxonomy']),
                    'parent' => 'edit'
                ]);
                break;
            }
        }
    }
}

// Initialiser klassen
KA_Admin_Bar_Links::get_instance(); 
<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/admin
 */

class SEO_Advisor_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version, 'all' );
        
        // Add Select2 CSS
        wp_enqueue_style( $this->plugin_name . '-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0', 'all' );
        
        // Add Chart.js CSS if needed
        wp_enqueue_style( $this->plugin_name . '-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css', array(), '3.9.1', 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), $this->version, false );
        
        // Add Select2 JS
        wp_enqueue_script( $this->plugin_name . '-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );
        
        // Add Chart.js
        wp_enqueue_script( $this->plugin_name . '-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true );
        
        // Localize the script with data
        wp_localize_script( $this->plugin_name, 'seo_advisor', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'seo_advisor_nonce' ),
            'analyzing_text' => __( 'Analyzing...', 'seo-advisor-woo' ),
            'analyze_complete' => __( 'Analysis Complete', 'seo-advisor-woo' ),
            'analyze_error' => __( 'Analysis Error', 'seo-advisor-woo' ),
        ));
    }

    /**
     * Add plugin admin menu items.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Main menu item
        add_menu_page(
            __( 'SEO Advisor', 'seo-advisor-woo' ),
            __( 'SEO Advisor', 'seo-advisor-woo' ),
            'manage_options',
            'seo-advisor',
            array( $this, 'display_dashboard_page' ),
            'dashicons-chart-line',
            25
        );
        
        // Dashboard submenu
        add_submenu_page(
            'seo-advisor',
            __( 'Dashboard', 'seo-advisor-woo' ),
            __( 'Dashboard', 'seo-advisor-woo' ),
            'manage_options',
            'seo-advisor',
            array( $this, 'display_dashboard_page' )
        );
        
        // Settings submenu
        add_submenu_page(
            'seo-advisor',
            __( 'Settings', 'seo-advisor-woo' ),
            __( 'Settings', 'seo-advisor-woo' ),
            'manage_options',
            'seo-advisor-settings',
            array( $this, 'display_settings_page' )
        );
        
        // Content Generator submenu (for Phase 2)
        add_submenu_page(
            'seo-advisor',
            __( 'Content Generator', 'seo-advisor-woo' ),
            __( 'Content Generator', 'seo-advisor-woo' ) . ' <span class="awaiting-mod">Pro</span>',
            'manage_options',
            'seo-advisor-content-generator',
            array( $this, 'display_content_generator_page' )
        );
    }

    /**
     * Display the SEO dashboard page.
     *
     * @since    1.0.0
     */
    public function display_dashboard_page() {
        include_once( 'partials/admin-display.php' );
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        include_once( 'settings/settings-page.php' );
    }
    
    /**
     * Display the content generator page (Phase 2).
     *
     * @since    1.0.0
     */
    public function display_content_generator_page() {
        // Currently shows a "Coming Soon" message
        echo '<div class="wrap">';
        echo '<h1>' . __( 'Content Generator', 'seo-advisor-woo' ) . '</h1>';
        echo '<div class="notice notice-info">';
        echo '<p>' . __( 'The Content Generator feature will be available in the upcoming update. Stay tuned!', 'seo-advisor-woo' ) . '</p>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Add WooCommerce product data fields.
     *
     * @since    1.0.0
     */
    public function add_woocommerce_product_fields() {
        global $post;
        
        echo '<div class="options_group">';
        
        // SEO Focus Keyword field
        woocommerce_wp_text_input( 
            array( 
                'id'          => '_seo_advisor_focus_keyword', 
                'label'       => __( 'SEO Focus Keyword', 'seo-advisor-woo' ), 
                'desc_tip'    => 'true',
                'description' => __( 'Enter the main keyword you want to rank for with this product.', 'seo-advisor-woo' ),
                'value'       => get_post_meta( $post->ID, '_seo_advisor_focus_keyword', true )
            )
        );
        
        // Secondary Keywords field
        woocommerce_wp_textarea_input( 
            array( 
                'id'          => '_seo_advisor_secondary_keywords', 
                'label'       => __( 'SEO Secondary Keywords', 'seo-advisor-woo' ), 
                'desc_tip'    => 'true',
                'description' => __( 'Enter secondary keywords separated by commas.', 'seo-advisor-woo' ),
                'value'       => get_post_meta( $post->ID, '_seo_advisor_secondary_keywords', true )
            )
        );
        
        echo '</div>';
    }
    
    /**
     * Save WooCommerce product fields.
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the post being saved.
     */
    public function save_woocommerce_product_fields( $post_id ) {
        // Save focus keyword
        if ( isset( $_POST['_seo_advisor_focus_keyword'] ) ) {
            update_post_meta( $post_id, '_seo_advisor_focus_keyword', sanitize_text_field( $_POST['_seo_advisor_focus_keyword'] ) );
        }
        
        // Save secondary keywords
        if ( isset( $_POST['_seo_advisor_secondary_keywords'] ) ) {
            update_post_meta( $post_id, '_seo_advisor_secondary_keywords', sanitize_textarea_field( $_POST['_seo_advisor_secondary_keywords'] ) );
        }
        
        // Trigger SEO analysis
        $this->analyze_product( $post_id );
    }
    
    /**
     * AJAX handler for analyzing a post.
     *
     * @since    1.0.0
     */
    public function ajax_analyze_post() {
        check_ajax_referer( 'seo_advisor_nonce', 'nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Permission denied' );
        }
        
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        
        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID' );
        }
        
        $post_analyzer = new SEO_Advisor_Post_Analyzer();
        $analysis = $post_analyzer->analyze_post( $post_id );
        
        if ( is_wp_error( $analysis ) ) {
            wp_send_json_error( $analysis->get_error_message() );
        }
        
        wp_send_json_success( $analysis );
    }
    
    /**
     * AJAX handler for analyzing a product.
     *
     * @since    1.0.0
     */
    public function ajax_analyze_product() {
        check_ajax_referer( 'seo_advisor_nonce', 'nonce' );
        
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Permission denied' );
        }
        
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        
        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID' );
        }
        
        $analysis = $this->analyze_product( $post_id );
        
        if ( is_wp_error( $analysis ) ) {
            wp_send_json_error( $analysis->get_error_message() );
        }
        
        wp_send_json_success( $analysis );
    }
    
    /**
     * Analyze a WooCommerce product.
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the product to analyze.
     * @return   array|WP_Error     Analysis results or error.
     */
    private function analyze_product( $post_id ) {
        if ( ! seo_advisor_is_woocommerce_active() ) {
            return new WP_Error( 'woocommerce_inactive', __( 'WooCommerce is not active', 'seo-advisor-woo' ) );
        }
        
        $product_analyzer = new SEO_Advisor_Product_Analyzer();
        return $product_analyzer->analyze_product( $post_id );
    }
}
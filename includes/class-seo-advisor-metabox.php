<?php
/**
 * Handles the display and functionality of SEO metaboxes.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/includes
 */

class SEO_Advisor_Metabox {

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
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the metaboxes.
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        // Get post types to analyze
        $general_settings = get_option('seo_advisor_general_settings', array());
        $post_types = isset($general_settings['post_types']) ? $general_settings['post_types'] : array('post', 'page');
        
        // Add metabox to each post type
        foreach ($post_types as $post_type) {
            add_meta_box(
                'seo_advisor_metabox',
                __('SEO Advisor Analysis', 'seo-advisor-woo'),
                array($this, 'render_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
        
        // Add metabox to WooCommerce products if integration is enabled
        if (seo_advisor_is_woocommerce_active() && 
            (isset($general_settings['woocommerce_integration']) && $general_settings['woocommerce_integration'] === 'yes')) {
            add_meta_box(
                'seo_advisor_product_metabox',
                __('SEO Advisor Product Analysis', 'seo-advisor-woo'),
                array($this, 'render_product_meta_box'),
                'product',
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Render the metabox for posts and pages.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('seo_advisor_meta_box', 'seo_advisor_meta_box_nonce');
        
        // Get saved values
        $focus_keyword = get_post_meta($post->ID, '_seo_advisor_focus_keyword', true);
        $secondary_keywords = get_post_meta($post->ID, '_seo_advisor_secondary_keywords', true);
        $seo_score = get_post_meta($post->ID, '_seo_advisor_seo_score', true);
        $last_updated = get_post_meta($post->ID, '_seo_advisor_last_updated', true);
        
        // Load the metabox template
        include(plugin_dir_path(dirname(__FILE__)) . 'admin/partials/metabox-post.php');
    }
    
    /**
     * Render the metabox for WooCommerce products.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_product_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('seo_advisor_product_meta_box', 'seo_advisor_product_meta_box_nonce');
        
        // Get saved values
        $focus_keyword = get_post_meta($post->ID, '_seo_advisor_focus_keyword', true);
        $secondary_keywords = get_post_meta($post->ID, '_seo_advisor_secondary_keywords', true);
        $seo_score = get_post_meta($post->ID, '_seo_advisor_seo_score', true);
        $last_updated = get_post_meta($post->ID, '_seo_advisor_last_updated', true);
        
        // Load the product metabox template
        include(plugin_dir_path(dirname(__FILE__)) . 'admin/partials/metabox-product.php');
    }
    
    /**
     * Save the metabox data.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    public function save_meta_box_data($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['seo_advisor_meta_box_nonce']) && !isset($_POST['seo_advisor_product_meta_box_nonce'])) {
            return;
        }
        
        // Verify the nonce
        if (isset($_POST['seo_advisor_meta_box_nonce'])) {
            if (!wp_verify_nonce($_POST['seo_advisor_meta_box_nonce'], 'seo_advisor_meta_box')) {
                return;
            }
        } elseif (isset($_POST['seo_advisor_product_meta_box_nonce'])) {
            if (!wp_verify_nonce($_POST['seo_advisor_product_meta_box_nonce'], 'seo_advisor_product_meta_box')) {
                return;
            }
        }
        
        // If this is an autosave, don't do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (isset($_POST['post_type'])) {
            if ($_POST['post_type'] === 'page') {
                if (!current_user_can('edit_page', $post_id)) {
                    return;
                }
            } else {
                if (!current_user_can('edit_post', $post_id)) {
                    return;
                }
            }
        }
        
        // Save focus keyword
        if (isset($_POST['seo_advisor_focus_keyword'])) {
            update_post_meta($post_id, '_seo_advisor_focus_keyword', sanitize_text_field($_POST['seo_advisor_focus_keyword']));
        }
        
        // Save secondary keywords
        if (isset($_POST['seo_advisor_secondary_keywords'])) {
            update_post_meta($post_id, '_seo_advisor_secondary_keywords', sanitize_textarea_field($_POST['seo_advisor_secondary_keywords']));
        }
        
        // Run analysis if auto-analyze is enabled
        $general_settings = get_option('seo_advisor_general_settings', array());
        $auto_analyze = isset($general_settings['auto_analyze']) ? $general_settings['auto_analyze'] : 'yes';
        
        if ($auto_analyze === 'yes') {
            $post_type = get_post_type($post_id);
            
            if ($post_type === 'product' && seo_advisor_is_woocommerce_active()) {
                // Analyze product
                $product_analyzer = new SEO_Advisor_Product_Analyzer();
                $product_analyzer->analyze_product($post_id);
            } else {
                // Analyze post
                $post_analyzer = new SEO_Advisor_Post_Analyzer();
                $post_analyzer->analyze_post($post_id);
            }
        }
    }
}
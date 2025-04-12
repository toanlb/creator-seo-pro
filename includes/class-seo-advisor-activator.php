<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/includes
 */

class SEO_Advisor_Activator {

    /**
     * Create necessary database tables and initialize settings on plugin activation.
     *
     * @since    1.0.0
     */
    public static function activate() {
        global $wpdb;
        
        // Set the current database version
        update_option('seo_advisor_db_version', SEO_ADVISOR_VERSION);
        
        // WordPress database character collate
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table names
        $settings_table = $wpdb->prefix . 'seo_advisor_settings';
        $analysis_table = $wpdb->prefix . 'seo_advisor_analysis';
        
        // SQL for creating settings table
        $settings_sql = "CREATE TABLE $settings_table (
            setting_id bigint(20) NOT NULL AUTO_INCREMENT,
            setting_name varchar(191) NOT NULL,
            setting_value longtext NOT NULL,
            autoload varchar(20) NOT NULL DEFAULT 'yes',
            PRIMARY KEY  (setting_id),
            UNIQUE KEY setting_name (setting_name)
        ) $charset_collate;";
        
        // SQL for creating analysis table
        $analysis_sql = "CREATE TABLE $analysis_table (
            analysis_id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_type varchar(20) NOT NULL,
            seo_score int(3) NOT NULL,
            analysis_data longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (analysis_id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        // Include the dbDelta function
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        // Create the tables
        dbDelta( $settings_sql );
        dbDelta( $analysis_sql );
        
        // Initialize default settings
        self::initialize_settings();
    }
    
    /**
     * Initialize default plugin settings
     *
     * @since    1.0.0
     */
    private static function initialize_settings() {
        // General settings
        if ( !get_option('seo_advisor_general_settings') ) {
            $general_settings = array(
                'post_types' => array('post', 'page'),
                'auto_analyze' => 'yes',
                'woocommerce_integration' => seo_advisor_is_woocommerce_active() ? 'yes' : 'no',
            );
            update_option('seo_advisor_general_settings', $general_settings);
        }
        
        // Analysis settings
        if ( !get_option('seo_advisor_analysis_settings') ) {
            $analysis_settings = array(
                'strictness' => 'medium',
                'analyze_meta' => 'yes',
                'analyze_content' => 'yes',
                'analyze_images' => 'yes',
                'analyze_technical' => 'yes',
                'default_keyword' => '',
            );
            update_option('seo_advisor_analysis_settings', $analysis_settings);
        }
        
        // Set plugin version
        update_option('seo_advisor_version', SEO_ADVISOR_VERSION);
    }
}
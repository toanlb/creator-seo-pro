<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Get plugin options
$remove_data = get_option('seo_advisor_general_settings');
$remove_data = (isset($remove_data['remove_data_on_uninstall']) && $remove_data['remove_data_on_uninstall'] === 'yes');

// If remove data option is enabled, delete all plugin data
if ($remove_data) {
    global $wpdb;
    
    // Drop tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}seo_advisor_settings");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}seo_advisor_analysis");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}seo_advisor_ai_history");
    
    // Delete options
    delete_option('seo_advisor_general_settings');
    delete_option('seo_advisor_analysis_settings');
    delete_option('seo_advisor_ai_settings');
    delete_option('seo_advisor_version');
    delete_option('seo_advisor_db_version');
    
    // Delete post meta
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_seo_advisor_%'");
}
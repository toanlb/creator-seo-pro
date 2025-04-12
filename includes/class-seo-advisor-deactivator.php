<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/includes
 */

class SEO_Advisor_Deactivator {

    /**
     * Perform cleanup tasks on plugin deactivation.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear any scheduled hooks
        wp_clear_scheduled_hook('seo_advisor_scheduled_analysis');
        
        // We don't delete tables or options here, as that's handled by uninstall.php
        // This ensures data is preserved if the plugin is temporarily deactivated
    }
}
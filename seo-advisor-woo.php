<?php
/**
 * Plugin Name:       SEO Advisor & Content Generator
 * Plugin URI:        https://zin100.vn/seo-advisor-woo
 * Description:       A comprehensive WordPress plugin designed to help website owners and WooCommerce administrators optimize their content according to the latest Google SEO standards.
 * Version:           1.0.0
 * Author:            toanlb
 * Author URI:        https://zin100.vn
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       seo-advisor-woo
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 */
define( 'SEO_ADVISOR_VERSION', '1.0.0' );

/**
 * Plugin base name
 */
define( 'SEO_ADVISOR_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Plugin path
 */
define( 'SEO_ADVISOR_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin URL
 */
define( 'SEO_ADVISOR_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-seo-advisor-activator.php
 */
function activate_seo_advisor() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-seo-advisor-activator.php';
    SEO_Advisor_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-seo-advisor-deactivator.php
 */
function deactivate_seo_advisor() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-seo-advisor-deactivator.php';
    SEO_Advisor_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_seo_advisor' );
register_deactivation_hook( __FILE__, 'deactivate_seo_advisor' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-seo-advisor.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_seo_advisor() {
    $plugin = new SEO_Advisor();
    $plugin->run();
}

/**
 * Check if WooCommerce is active
 */
function seo_advisor_is_woocommerce_active() {
    return in_array( 
        'woocommerce/woocommerce.php', 
        apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) 
    );
}

/**
 * Run the plugin
 */
run_seo_advisor();
<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/includes
 */

class SEO_Advisor {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      SEO_Advisor_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = SEO_ADVISOR_VERSION;
        $this->plugin_name = 'seo-advisor-woo';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - SEO_Advisor_Loader. Orchestrates the hooks of the plugin.
     * - SEO_Advisor_i18n. Defines internationalization functionality.
     * - SEO_Advisor_Admin. Defines all hooks for the admin area.
     * - SEO_Advisor_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seo-advisor-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seo-advisor-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-seo-advisor-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-seo-advisor-public.php';

        /**
         * The class responsible for analyzing post content.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seo-advisor-post-analyzer.php';

        /**
         * The class responsible for analyzing WooCommerce product content.
         */
        if ( seo_advisor_is_woocommerce_active() ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seo-advisor-product-analyzer.php';
        }

        /**
         * The class responsible for metabox display and handling.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seo-advisor-metabox.php';

        /**
         * The class responsible for settings management.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-seo-advisor-settings.php';

        $this->loader = new SEO_Advisor_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the SEO_Advisor_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new SEO_Advisor_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new SEO_Advisor_Admin( $this->get_plugin_name(), $this->get_version() );
        $plugin_metabox = new SEO_Advisor_Metabox( $this->get_plugin_name(), $this->get_version() );
        $plugin_settings = new SEO_Advisor_Settings( $this->get_plugin_name(), $this->get_version() );

        // Admin assets
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

        // Admin menu
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

        // Metabox
        $this->loader->add_action( 'add_meta_boxes', $plugin_metabox, 'add_meta_boxes' );
        $this->loader->add_action( 'save_post', $plugin_metabox, 'save_meta_box_data' );

        // Settings
        $this->loader->add_action( 'admin_init', $plugin_settings, 'register_settings' );

        // WooCommerce integration
        if ( seo_advisor_is_woocommerce_active() ) {
            $this->loader->add_action( 'woocommerce_product_options_general_product_data', $plugin_admin, 'add_woocommerce_product_fields' );
            $this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'save_woocommerce_product_fields' );
        }

        // Ajax handlers
        $this->loader->add_action( 'wp_ajax_seo_advisor_analyze_post', $plugin_admin, 'ajax_analyze_post' );
        $this->loader->add_action( 'wp_ajax_seo_advisor_analyze_product', $plugin_admin, 'ajax_analyze_product' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new SEO_Advisor_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    SEO_Advisor_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
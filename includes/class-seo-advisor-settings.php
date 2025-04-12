<?php
/**
 * Handles plugin settings.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/includes
 */

class SEO_Advisor_Settings {

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
     * Register all the settings fields.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register General Settings
        register_setting(
            'seo_advisor_general_settings',
            'seo_advisor_general_settings'
        );
        
        // Register Analysis Settings
        register_setting(
            'seo_advisor_analysis_settings',
            'seo_advisor_analysis_settings'
        );
        
        // Register AI Settings (for Phase 2)
        register_setting(
            'seo_advisor_ai_settings',
            'seo_advisor_ai_settings'
        );
        
        // General Settings section
        add_settings_section(
            'seo_advisor_general_section',
            __('General Settings', 'seo-advisor-woo'),
            array($this, 'general_section_callback'),
            'seo_advisor_general_settings'
        );
        
        // Analysis Settings section
        add_settings_section(
            'seo_advisor_analysis_section',
            __('Analysis Settings', 'seo-advisor-woo'),
            array($this, 'analysis_section_callback'),
            'seo_advisor_analysis_settings'
        );
        
        // AI Settings section (for Phase 2)
        add_settings_section(
            'seo_advisor_ai_section',
            __('AI Settings', 'seo-advisor-woo'),
            array($this, 'ai_section_callback'),
            'seo_advisor_ai_settings'
        );
        
        // Post Types field
        add_settings_field(
            'post_types',
            __('Post Types to Analyze', 'seo-advisor-woo'),
            array($this, 'post_types_callback'),
            'seo_advisor_general_settings',
            'seo_advisor_general_section'
        );
        
        // Auto Analyze field
        add_settings_field(
            'auto_analyze',
            __('Auto Analyze on Save', 'seo-advisor-woo'),
            array($this, 'auto_analyze_callback'),
            'seo_advisor_general_settings',
            'seo_advisor_general_section'
        );
        
        // WooCommerce Integration field
        if (seo_advisor_is_woocommerce_active()) {
            add_settings_field(
                'woocommerce_integration',
                __('WooCommerce Integration', 'seo-advisor-woo'),
                array($this, 'woocommerce_integration_callback'),
                'seo_advisor_general_settings',
                'seo_advisor_general_section'
            );
        }
        
        // Remove Data field
        add_settings_field(
            'remove_data_on_uninstall',
            __('Remove Data on Uninstall', 'seo-advisor-woo'),
            array($this, 'remove_data_callback'),
            'seo_advisor_general_settings',
            'seo_advisor_general_section'
        );
        
        // Strictness field
        add_settings_field(
            'strictness',
            __('Analysis Strictness', 'seo-advisor-woo'),
            array($this, 'strictness_callback'),
            'seo_advisor_analysis_settings',
            'seo_advisor_analysis_section'
        );
        
        // Analyze Meta field
        add_settings_field(
            'analyze_meta',
            __('Analyze Meta Tags', 'seo-advisor-woo'),
            array($this, 'analyze_meta_callback'),
            'seo_advisor_analysis_settings',
            'seo_advisor_analysis_section'
        );
        
        // Analyze Content field
        add_settings_field(
            'analyze_content',
            __('Analyze Content', 'seo-advisor-woo'),
            array($this, 'analyze_content_callback'),
            'seo_advisor_analysis_settings',
            'seo_advisor_analysis_section'
        );
        
        // Analyze Images field
        add_settings_field(
            'analyze_images',
            __('Analyze Images', 'seo-advisor-woo'),
            array($this, 'analyze_images_callback'),
            'seo_advisor_analysis_settings',
            'seo_advisor_analysis_section'
        );
        
        // Analyze Technical field
        add_settings_field(
            'analyze_technical',
            __('Analyze Technical SEO', 'seo-advisor-woo'),
            array($this, 'analyze_technical_callback'),
            'seo_advisor_analysis_settings',
            'seo_advisor_analysis_section'
        );
        
        // Default Keyword field
        add_settings_field(
            'default_keyword',
            __('Default Focus Keyword', 'seo-advisor-woo'),
            array($this, 'default_keyword_callback'),
            'seo_advisor_analysis_settings',
            'seo_advisor_analysis_section'
        );
        
        // AI API Key field (for Phase 2)
        add_settings_field(
            'ai_api_key',
            __('AI API Key', 'seo-advisor-woo'),
            array($this, 'ai_api_key_callback'),
            'seo_advisor_ai_settings',
            'seo_advisor_ai_section'
        );
        
        // AI Provider field (for Phase 2)
        add_settings_field(
            'ai_provider',
            __('AI Provider', 'seo-advisor-woo'),
            array($this, 'ai_provider_callback'),
            'seo_advisor_ai_settings',
            'seo_advisor_ai_section'
        );
    }
    
    /**
     * General Settings section callback.
     *
     * @since    1.0.0
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure the general settings for SEO Advisor.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * Analysis Settings section callback.
     *
     * @since    1.0.0
     */
    public function analysis_section_callback() {
        echo '<p>' . __('Configure how content is analyzed for SEO optimization.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * AI Settings section callback.
     *
     * @since    1.0.0
     */
    public function ai_section_callback() {
        echo '<p>' . __('Configure the AI settings for content generation (Coming in Phase 2).', 'seo-advisor-woo') . '</p>';
        echo '<div class="notice notice-info inline"><p>' . __('AI content generation features will be available in the upcoming update.', 'seo-advisor-woo') . '</p></div>';
    }
    
    /**
     * Post Types field callback.
     *
     * @since    1.0.0
     */
    public function post_types_callback() {
        $options = get_option('seo_advisor_general_settings');
        $post_types = isset($options['post_types']) ? $options['post_types'] : array('post', 'page');
        
        // Get all public post types
        $public_post_types = get_post_types(array('public' => true), 'objects');
        
        // Remove product if WooCommerce is active (it's handled separately)
        if (seo_advisor_is_woocommerce_active() && isset($public_post_types['product'])) {
            unset($public_post_types['product']);
        }
        
        // Remove attachment and other non-content post types
        $excluded_post_types = array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset');
        foreach ($excluded_post_types as $excluded) {
            if (isset($public_post_types[$excluded])) {
                unset($public_post_types[$excluded]);
            }
        }
        
        echo '<fieldset>';
        foreach ($public_post_types as $post_type) {
            echo '<label for="seo_advisor_post_type_' . esc_attr($post_type->name) . '">';
            echo '<input type="checkbox" id="seo_advisor_post_type_' . esc_attr($post_type->name) . '" name="seo_advisor_general_settings[post_types][]" value="' . esc_attr($post_type->name) . '" ' . checked(in_array($post_type->name, $post_types), true, false) . '>';
            echo esc_html($post_type->label);
            echo '</label><br>';
        }
        echo '</fieldset>';
        echo '<p class="description">' . __('Select which post types should be analyzed for SEO.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * Auto Analyze field callback.
     *
     * @since    1.0.0
     */
    public function auto_analyze_callback() {
        $options = get_option('seo_advisor_general_settings');
        $auto_analyze = isset($options['auto_analyze']) ? $options['auto_analyze'] : 'yes';
        
        echo '<select name="seo_advisor_general_settings[auto_analyze]" id="seo_advisor_auto_analyze">';
        echo '<option value="yes" ' . selected($auto_analyze, 'yes', false) . '>' . __('Yes', 'seo-advisor-woo') . '</option>';
        echo '<option value="no" ' . selected($auto_analyze, 'no', false) . '>' . __('No', 'seo-advisor-woo') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Automatically analyze content when saving.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * WooCommerce Integration field callback.
     *
     * @since    1.0.0
     */
    public function woocommerce_integration_callback() {
        $options = get_option('seo_advisor_general_settings');
        $woocommerce_integration = isset($options['woocommerce_integration']) ? $options['woocommerce_integration'] : 'yes';
        
        echo '<select name="seo_advisor_general_settings[woocommerce_integration]" id="seo_advisor_woocommerce_integration">';
        echo '<option value="yes" ' . selected($woocommerce_integration, 'yes', false) . '>' . __('Yes', 'seo-advisor-woo') . '</option>';
        echo '<option value="no" ' . selected($woocommerce_integration, 'no', false) . '>' . __('No', 'seo-advisor-woo') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Enable WooCommerce product analysis features.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * Remove Data field callback.
     *
     * @since    1.0.0
     */
    public function remove_data_callback() {
        $options = get_option('seo_advisor_general_settings');
        $remove_data = isset($options['remove_data_on_uninstall']) ? $options['remove_data_on_uninstall'] : 'no';
        
        echo '<select name="seo_advisor_general_settings[remove_data_on_uninstall]" id="seo_advisor_remove_data">';
        echo '<option value="yes" ' . selected($remove_data, 'yes', false) . '>' . __('Yes', 'seo-advisor-woo') . '</option>';
        echo '<option value="no" ' . selected($remove_data, 'no', false) . '>' . __('No', 'seo-advisor-woo') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Remove all plugin data when uninstalling.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * Strictness field callback.
     *
     * @since    1.0.0
     */
    public function strictness_callback() {
        $options = get_option('seo_advisor_analysis_settings');
        $strictness = isset($options['strictness']) ? $options['strictness'] : 'medium';
        
        echo '<select name="seo_advisor_analysis_settings[strictness]" id="seo_advisor_strictness">';
        echo '<option value="low" ' . selected($strictness, 'low', false) . '>' . __('Low', 'seo-advisor-woo') . '</option>';
        echo '<option value="medium" ' . selected($strictness, 'medium', false) . '>' . __('Medium', 'seo-advisor-woo') . '</option>';
        echo '<option value="high" ' . selected($strictness, 'high', false) . '>' . __('High', 'seo-advisor-woo') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Set how strict the analysis should be.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * Analyze Meta field callback.
     *
     * @since    1.0.0
     */
    public function analyze_meta_callback() {
        $options = get_option('seo_advisor_analysis_settings');
        $analyze_meta = isset($options['analyze_meta']) ? $options['analyze_meta'] : 'yes';
        
        echo '<select name="seo_advisor_analysis_settings[analyze_meta]" id="seo_advisor_analyze_meta">';
        echo '<option value="yes" ' . selected($analyze_meta, 'yes', false) . '>' . __('Yes', 'seo-advisor-woo') . '</option>';
        echo '<option value="no" ' . selected($analyze_meta, 'no', false) . '>' . __('No', 'seo-advisor-woo') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Analyze meta tags like title, meta description, and URL.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * Analyze Content field callback.
     *
     * @since    1.0.0
     */
    public function analyze_content_callback() {
        $options = get_option('seo_advisor_analysis_settings');
        $analyze_content = isset($options['analyze_content']) ? $options['analyze_content'] : 'yes';
        
        echo '<select name="seo_advisor_analysis_settings[analyze_content]" id="seo_advisor_analyze_content">';
        echo '<option value="yes" ' . selected($analyze_content, 'yes', false) . '>' . __('Yes', 'seo-advisor-woo') . '</option>';
        echo '<option value="no" ' . selected($analyze_content, 'no', false) . '>' . __('No', 'seo-advisor-woo') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Analyze content for length, readability, keyword usage, and structure.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * Analyze Images field callback.
     *
     * @since    1.0.0
     */
    public function analyze_images_callback() {
        $options = get_option('seo_advisor_analysis_settings');
        $analyze_images = isset($options['analyze_images']) ? $options['analyze_images'] : 'yes';
        
        echo '<select name="seo_advisor_analysis_settings[analyze_images]" id="seo_advisor_analyze_images">';
        echo '<option value="yes" ' . selected($analyze_images, 'yes', false) . '>' . __('Yes', 'seo-advisor-woo') . '</option>';
        echo '<option value="no" ' . selected($analyze_images, 'no', false) . '>' . __('No', 'seo-advisor-woo') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Analyze images for alt text, file names, and other SEO factors.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * Analyze Technical field callback.
     *
     * @since    1.0.0
     */
    public function analyze_technical_callback() {
        $options = get_option('seo_advisor_analysis_settings');
        $analyze_technical = isset($options['analyze_technical']) ? $options['analyze_technical'] : 'yes';
        
        echo '<select name="seo_advisor_analysis_settings[analyze_technical]" id="seo_advisor_analyze_technical">';
        echo '<option value="yes" ' . selected($analyze_technical, 'yes', false) . '>' . __('Yes', 'seo-advisor-woo') . '</option>';
        echo '<option value="no" ' . selected($analyze_technical, 'no', false) . '>' . __('No', 'seo-advisor-woo') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Analyze technical SEO factors like schema markup, canonicals, etc.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * Default Keyword field callback.
     *
     * @since    1.0.0
     */
    public function default_keyword_callback() {
        $options = get_option('seo_advisor_analysis_settings');
        $default_keyword = isset($options['default_keyword']) ? $options['default_keyword'] : '';
        
        echo '<input type="text" id="seo_advisor_default_keyword" name="seo_advisor_analysis_settings[default_keyword]" value="' . esc_attr($default_keyword) . '" class="regular-text">';
        echo '<p class="description">' . __('Default focus keyword to use when none is specified.', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * AI API Key field callback (for Phase 2).
     *
     * @since    1.0.0
     */
    public function ai_api_key_callback() {
        $options = get_option('seo_advisor_ai_settings');
        $api_key = isset($options['ai_api_key']) ? $options['ai_api_key'] : '';
        
        echo '<input type="password" id="seo_advisor_ai_api_key" name="seo_advisor_ai_settings[ai_api_key]" value="' . esc_attr($api_key) . '" class="regular-text" disabled>';
        echo '<p class="description">' . __('Enter your AI provider API key (Coming in Phase 2).', 'seo-advisor-woo') . '</p>';
    }
    
    /**
     * AI Provider field callback (for Phase 2).
     *
     * @since    1.0.0
     */
    public function ai_provider_callback() {
        $options = get_option('seo_advisor_ai_settings');
        $provider = isset($options['ai_provider']) ? $options['ai_provider'] : 'openai';
        
        echo '<select name="seo_advisor_ai_settings[ai_provider]" id="seo_advisor_ai_provider" disabled>';
        echo '<option value="openai" ' . selected($provider, 'openai', false) . '>' . __('OpenAI (GPT)', 'seo-advisor-woo') . '</option>';
        echo '<option value="anthropic" ' . selected($provider, 'anthropic', false) . '>' . __('Anthropic (Claude)', 'seo-advisor-woo') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Select your AI provider for content generation (Coming in Phase 2).', 'seo-advisor-woo') . '</p>';
    }
}
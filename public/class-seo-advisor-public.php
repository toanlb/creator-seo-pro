<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/public
 */

class SEO_Advisor_Public {

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
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/public.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/public.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Add schema markup to the site.
     * 
     * This is a basic implementation that will be expanded in future versions.
     *
     * @since    1.0.0
     */
    public function add_schema_markup() {
        // Get general settings
        $general_settings = get_option('seo_advisor_general_settings', array());
        
        // Only proceed if enabled
        if (isset($general_settings['add_schema_markup']) && $general_settings['add_schema_markup'] === 'yes') {
            if (is_singular()) {
                global $post;
                
                // Determine schema type based on post type
                $schema_type = 'Article';
                
                if ($post->post_type === 'product' && seo_advisor_is_woocommerce_active()) {
                    $schema_type = 'Product';
                } elseif ($post->post_type === 'page') {
                    $schema_type = 'WebPage';
                }
                
                // Generate basic schema
                $this->output_basic_schema($post, $schema_type);
            } elseif (is_home() || is_front_page()) {
                // Output website schema for homepage
                $this->output_website_schema();
            }
        }
    }
    
    /**
     * Output basic schema markup for a post.
     *
     * @since    1.0.0
     * @param    WP_Post    $post         The post object.
     * @param    string     $schema_type  The schema type.
     */
    private function output_basic_schema($post, $schema_type) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            'headline' => get_the_title($post->ID),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author)
            ),
            'description' => $this->get_post_description($post)
        );
        
        // Add image if available
        if (has_post_thumbnail($post->ID)) {
            $image_id = get_post_thumbnail_id($post->ID);
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            
            if ($image_url) {
                $schema['image'] = array(
                    '@type' => 'ImageObject',
                    'url' => $image_url,
                    'width' => 1200,
                    'height' => 630
                );
            }
        }
        
        // Add publisher info
        $schema['publisher'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => $this->get_site_logo_url(),
                'width' => 600,
                'height' => 60
            )
        );
        
        // Add product specific data
        if ($schema_type === 'Product' && seo_advisor_is_woocommerce_active()) {
            $product = wc_get_product($post->ID);
            
            if ($product) {
                // Add price
                $schema['offers'] = array(
                    '@type' => 'Offer',
                    'price' => $product->get_price(),
                    'priceCurrency' => get_woocommerce_currency(),
                    'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'url' => get_permalink($post->ID)
                );
                
                // Add review info if available
                if ($product->get_review_count() > 0) {
                    $schema['aggregateRating'] = array(
                        '@type' => 'AggregateRating',
                        'ratingValue' => $product->get_average_rating(),
                        'reviewCount' => $product->get_review_count()
                    );
                }
            }
        }
        
        // Output schema as JSON-LD
        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
    }
    
    /**
     * Output website schema for homepage.
     *
     * @since    1.0.0
     */
    private function output_website_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => home_url('/'),
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => home_url('/?s={search_term_string}'),
                'query-input' => 'required name=search_term_string'
            )
        );
        
        // Output schema as JSON-LD
        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
    }
    
    /**
     * Get post description for schema.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     * @return   string              The post description.
     */
    private function get_post_description($post) {
        // Check for meta description first
        $description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true); // Yoast SEO
        
        if (empty($description)) {
            $description = get_post_meta($post->ID, '_aioseop_description', true); // All in One SEO
        }
        
        // Use excerpt if available
        if (empty($description) && !empty($post->post_excerpt)) {
            $description = $post->post_excerpt;
        }
        
        // Generate from content as last resort
        if (empty($description)) {
            $description = wp_trim_words(strip_shortcodes($post->post_content), 30, '...');
        }
        
        return $description;
    }
    
    /**
     * Get site logo URL for schema.
     *
     * @since    1.0.0
     * @return   string    The site logo URL.
     */
    private function get_site_logo_url() {
        $logo_url = '';
        
        // Check for custom logo
        $custom_logo_id = get_theme_mod('custom_logo');
        
        if ($custom_logo_id) {
            $logo_image = wp_get_attachment_image_src($custom_logo_id, 'full');
            
            if ($logo_image) {
                $logo_url = $logo_image[0];
            }
        }
        
        // Fallback to site icon
        if (empty($logo_url)) {
            $site_icon_id = get_option('site_icon');
            
            if ($site_icon_id) {
                $icon_image = wp_get_attachment_image_src($site_icon_id, 'full');
                
                if ($icon_image) {
                    $logo_url = $icon_image[0];
                }
            }
        }
        
        // Return default image if no logo found
        if (empty($logo_url)) {
            $logo_url = plugin_dir_url(__FILE__) . '../assets/images/default-logo.png';
        }
        
        return $logo_url;
    }

    /**
     * Modify the page title for SEO.
     *
     * @since    1.0.0
     * @param    string    $title    The original title.
     * @return   string              The modified title.
     */
    public function modify_page_title($title) {
        // Get general settings
        $general_settings = get_option('seo_advisor_general_settings', array());
        
        // Only proceed if enabled and not on admin pages
        if (!is_admin() && isset($general_settings['modify_page_title']) && $general_settings['modify_page_title'] === 'yes') {
            // Only modify singular posts/pages
            if (is_singular()) {
                global $post;
                
                // Get focus keyword
                $focus_keyword = get_post_meta($post->ID, '_seo_advisor_focus_keyword', true);
                
                if (!empty($focus_keyword) && strpos($title, $focus_keyword) === false) {
                    // Add keyword to title if not already present
                    return $title . ' - ' . $focus_keyword;
                }
            }
        }
        
        return $title;
    }
    
    /**
     * Add canonical URL to head.
     *
     * @since    1.0.0
     */
    public function add_canonical_url() {
        // Get general settings
        $general_settings = get_option('seo_advisor_general_settings', array());
        
        // Only proceed if enabled
        if (isset($general_settings['add_canonical_url']) && $general_settings['add_canonical_url'] === 'yes') {
            // Don't output if another SEO plugin is active and might handle this
            if ($this->is_major_seo_plugin_active()) {
                return;
            }
            
            // Only add to singular posts/pages
            if (is_singular()) {
                global $post;
                
                // Check if a custom canonical URL is set
                $canonical_url = get_post_meta($post->ID, '_seo_advisor_canonical_url', true);
                
                if (empty($canonical_url)) {
                    // Use permalink as default
                    $canonical_url = get_permalink($post->ID);
                }
                
                echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
            } elseif (is_home() || is_front_page()) {
                echo '<link rel="canonical" href="' . esc_url(home_url('/')) . '" />' . "\n";
            } elseif (is_category() || is_tag() || is_tax()) {
                echo '<link rel="canonical" href="' . esc_url(get_term_link(get_queried_object_id())) . '" />' . "\n";
            } elseif (is_post_type_archive()) {
                echo '<link rel="canonical" href="' . esc_url(get_post_type_archive_link(get_query_var('post_type'))) . '" />' . "\n";
            }
        }
    }
    
    /**
     * Check if a major SEO plugin is active.
     *
     * @since    1.0.0
     * @return   boolean    True if a major SEO plugin is active.
     */
    private function is_major_seo_plugin_active() {
        return is_plugin_active('wordpress-seo/wp-seo.php') || // Yoast SEO
               is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || // All in One SEO
               is_plugin_active('seo-by-rank-math/rank-math.php'); // Rank Math
    }
}
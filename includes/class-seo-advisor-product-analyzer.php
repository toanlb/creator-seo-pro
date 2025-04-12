<?php
/**
 * WooCommerce product analyzer for SEO.
 *
 * This class handles the analysis of WooCommerce product content for SEO optimization.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/includes
 */

class SEO_Advisor_Product_Analyzer {

    /**
     * The analysis settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings    The analysis settings.
     */
    private $settings;
    
    /**
     * The post analyzer.
     *
     * @since    1.0.0
     * @access   private
     * @var      SEO_Advisor_Post_Analyzer    $post_analyzer    The post analyzer object.
     */
    private $post_analyzer;
    
    /**
     * Site URL for internal link detection.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $site_url    The site URL.
     */
    private $site_url;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Get the analysis settings
        $this->settings = get_option('seo_advisor_analysis_settings', array());
        
        // Set defaults if settings are empty
        if (empty($this->settings)) {
            $this->settings = array(
                'strictness' => 'medium',
                'analyze_meta' => 'yes',
                'analyze_content' => 'yes',
                'analyze_images' => 'yes',
                'analyze_technical' => 'yes',
                'default_keyword' => '',
            );
        }
        
        // Initialize post analyzer for base functionality
        $this->post_analyzer = new SEO_Advisor_Post_Analyzer();
        
        // Get site URL for internal link detection
        $this->site_url = get_site_url();
    }

    /**
     * Analyze a WooCommerce product for SEO optimization.
     *
     * @since    1.0.0
     * @param    int    $post_id    The product ID to analyze.
     * @return   array|WP_Error     The analysis results or error.
     */
    public function analyze_product($post_id) {
        // Get the product data
        $product = wc_get_product($post_id);
        
        if (!$product) {
            return new WP_Error('invalid_product', __('Invalid product ID', 'seo-advisor-woo'));
        }
        
        // Get the post object
        $post = get_post($post_id);
        
        // Use the post analyzer for base analysis
        $base_analysis = $this->post_analyzer->analyze_post($post_id);
        
        if (is_wp_error($base_analysis)) {
            return $base_analysis;
        }
        
        // Add product-specific analysis
        $product_analysis = $this->analyze_product_specific($product);
        
        // Merge results
        $base_analysis['analysis_groups']['product'] = $product_analysis;
        
        // Recalculate total score
        $score = 0;
        $total_checks = 0;
        
        foreach ($base_analysis['analysis_groups'] as $group) {
            foreach ($group as $check) {
                $total_checks++;
                $score += $check['score'];
                
                // Count issues by status
                if ($check['status'] === 'critical') {
                    $base_analysis['issues']['critical']++;
                } elseif ($check['status'] === 'warning') {
                    $base_analysis['issues']['warnings']++;
                } elseif ($check['status'] === 'good') {
                    $base_analysis['issues']['good']++;
                }
            }
        }
        
        // Calculate final score (0-100)
        $base_analysis['score'] = $total_checks > 0 ? round(($score / $total_checks) * 100) : 0;
        
        // Save the analysis results
        $this->save_product_analysis_results($post_id, $base_analysis);
        
        return $base_analysis;
    }
    
    /**
     * Analyze product-specific SEO factors.
     *
     * @since    1.0.0
     * @param    WC_Product    $product    The WooCommerce product object.
     * @return   array                     The product-specific analysis results.
     */
    private function analyze_product_specific($product) {
        $results = array();
        
        // Check short description
        $short_description = $product->get_short_description();
        $short_desc_length = mb_strlen(wp_strip_all_tags($short_description));
        
        if (empty($short_description)) {
            $results['short_description'] = array(
                'name' => __('Short Description', 'seo-advisor-woo'),
                'status' => 'critical',
                'message' => __('Your product does not have a short description. Add a concise, compelling short description to improve conversions.', 'seo-advisor-woo'),
                'score' => 0,
            );
        } elseif ($short_desc_length < 50) {
            $results['short_description'] = array(
                'name' => __('Short Description', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product short description is too brief. Try to make it at least 50 characters.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } else {
            $results['short_description'] = array(
                'name' => __('Short Description', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your product has a good short description.', 'seo-advisor-woo'),
                'score' => 1,
            );
        }
        
        // Check product attributes
        $attributes = $product->get_attributes();
        
        if (empty($attributes)) {
            $results['product_attributes'] = array(
                'name' => __('Product Attributes', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product does not have any attributes. Adding attributes can improve SEO and help customers find your product.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } else {
            $results['product_attributes'] = array(
                'name' => __('Product Attributes', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has %d attributes. Good job!', 'seo-advisor-woo'), count($attributes)),
                'score' => 1,
            );
        }
        
        // Check product categories
        $categories = $product->get_category_ids();
        
        if (empty($categories)) {
            $results['product_categories'] = array(
                'name' => __('Product Categories', 'seo-advisor-woo'),
                'status' => 'critical',
                'message' => __('Your product is not assigned to any category. Assign it to at least one relevant category.', 'seo-advisor-woo'),
                'score' => 0,
            );
        } elseif (count($categories) > 3) {
            $results['product_categories'] = array(
                'name' => __('Product Categories', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product is assigned to too many categories. Try to limit it to 1-3 most relevant categories.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } else {
            $results['product_categories'] = array(
                'name' => __('Product Categories', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product is assigned to %d categories. Good job!', 'seo-advisor-woo'), count($categories)),
                'score' => 1,
            );
        }
        
        // Check product tags
        $tags = $product->get_tag_ids();
        
        if (empty($tags)) {
            $results['product_tags'] = array(
                'name' => __('Product Tags', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product does not have any tags. Adding relevant tags can improve discoverability.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } elseif (count($tags) > 10) {
            $results['product_tags'] = array(
                'name' => __('Product Tags', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product has too many tags. Try to limit it to 5-10 most relevant tags.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } else {
            $results['product_tags'] = array(
                'name' => __('Product Tags', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has %d tags. Good job!', 'seo-advisor-woo'), count($tags)),
                'score' => 1,
            );
        }
        
        // Check product images
        $image_id = $product->get_image_id();
        $gallery_image_ids = $product->get_gallery_image_ids();
        
        if (empty($image_id)) {
            $results['product_images'] = array(
                'name' => __('Product Images', 'seo-advisor-woo'),
                'status' => 'critical',
                'message' => __('Your product does not have a main image. Add a high-quality main product image.', 'seo-advisor-woo'),
                'score' => 0,
            );
        } elseif (empty($gallery_image_ids)) {
            $results['product_images'] = array(
                'name' => __('Product Images', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product only has a main image but no gallery images. Add multiple product images to improve conversions.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } else {
            $results['product_images'] = array(
                'name' => __('Product Images', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has a main image and %d gallery images. Good job!', 'seo-advisor-woo'), count($gallery_image_ids)),
                'score' => 1,
            );
        }
        
        // Check for product reviews
        $review_count = $product->get_review_count();
        
        if ($review_count === 0) {
            $results['product_reviews'] = array(
                'name' => __('Product Reviews', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product does not have any reviews. Reviews can improve trust and SEO.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } else {
            $results['product_reviews'] = array(
                'name' => __('Product Reviews', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has %d reviews. Good job!', 'seo-advisor-woo'), $review_count),
                'score' => 1,
            );
        }
        
        // Check product schema
        $has_schema = $this->has_product_schema($product);
        
        if ($has_schema) {
            $results['product_schema'] = array(
                'name' => __('Product Schema', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your product has product schema markup. Good job!', 'seo-advisor-woo'),
                'score' => 1,
            );
        } else {
            $results['product_schema'] = array(
                'name' => __('Product Schema', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('No product schema detected. Consider adding structured data for rich snippets in search results.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        }
        
        // Check product price
        if ($product->get_price() === '') {
            $results['product_price'] = array(
                'name' => __('Product Price', 'seo-advisor-woo'),
                'status' => 'critical',
                'message' => __('Your product does not have a price set. Adding a price is essential for e-commerce SEO.', 'seo-advisor-woo'),
                'score' => 0,
            );
        } else {
            $results['product_price'] = array(
                'name' => __('Product Price', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your product has a price set. Good job!', 'seo-advisor-woo'),
                'score' => 1,
            );
        }
        
        // Check related products
        $related_products = wc_get_related_products($product->get_id(), 5);
        
        if (empty($related_products)) {
            $results['related_products'] = array(
                'name' => __('Related Products', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product does not have any related products. Having related products can improve internal linking and user experience.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } else {
            $results['related_products'] = array(
                'name' => __('Related Products', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has %d related products. Good job!', 'seo-advisor-woo'), count($related_products)),
                'score' => 1,
            );
        }
        
        return $results;
    }
    
    /**
     * Check if product has product schema markup.
     *
     * @since    1.0.0
     * @param    WC_Product    $product    The WooCommerce product object.
     * @return   boolean                   Whether the product has schema markup.
     */
    private function has_product_schema($product) {
        // WooCommerce adds product schema by default
        if (version_compare(WC_VERSION, '3.0.0', '>=')) {
            return true;
        }
        
        // Check for common schema plugins as fallback
        if (is_plugin_active('schema-pro/schema-pro.php') || 
            is_plugin_active('wp-schema-pro/wp-schema-pro.php') ||
            is_plugin_active('schema/schema.php') ||
            is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') ||
            is_plugin_active('wordpress-seo/wp-seo.php')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Save product analysis results to database.
     *
     * @since    1.0.0
     * @param    int     $product_id    The product ID.
     * @param    array   $results       The analysis results.
     */
    private function save_product_analysis_results($product_id, $results) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        $current_time = current_time('mysql');
        
        // Check if analysis already exists for this product
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT analysis_id FROM $table_name WHERE post_id = %d",
            $product_id
        ));
        
        $analysis_data = wp_json_encode($results);
        
        if ($existing) {
            // Update existing analysis
            $wpdb->update(
                $table_name,
                array(
                    'seo_score' => $results['score'],
                    'analysis_data' => $analysis_data,
                    'updated_at' => $current_time
                ),
                array('post_id' => $product_id),
                array('%d', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new analysis
            $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $product_id,
                    'post_type' => 'product',
                    'seo_score' => $results['score'],
                    'analysis_data' => $analysis_data,
                    'created_at' => $current_time,
                    'updated_at' => $current_time
                ),
                array('%d', '%s', '%d', '%s', '%s', '%s')
            );
        }
        
        // Update post meta
        update_post_meta($product_id, '_seo_advisor_seo_score', $results['score']);
        update_post_meta($product_id, '_seo_advisor_last_updated', $current_time);
    }
}
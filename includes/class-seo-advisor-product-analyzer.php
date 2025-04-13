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
     * @param    int       $post_id     The product ID to analyze.
     * @param    boolean   $fast_mode   Whether to perform a fast analysis
     * @return   array|WP_Error        The analysis results or error.
     */
    public function analyze_product($post_id, $fast_mode = false) {
        // Get the product data
        $product = wc_get_product($post_id);
        
        if (!$product) {
            return new WP_Error('invalid_product', __('Invalid product ID', 'seo-advisor-woo'));
        }
        
        // Get the post object
        $post = get_post($post_id);
        
        // Use the post analyzer for base analysis
        $base_analysis = $this->post_analyzer->analyze_post($post_id, $fast_mode);
        
        if (is_wp_error($base_analysis)) {
            return $base_analysis;
        }
        
        // Add product-specific analysis
        $product_analysis = $this->analyze_product_specific($product, $fast_mode);
        
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
        
        // Calculate per-category scores
        $base_analysis['category_scores'] = $this->calculate_category_scores($base_analysis);
        
        // Save the analysis results if not in fast mode
        if (!$fast_mode) {
            $this->save_product_analysis_results($post_id, $base_analysis);
        }
        
        return $base_analysis;
    }
    
    /**
     * Calculate scores for each category.
     *
     * @since    1.0.0
     * @param    array    $results    The analysis results.
     * @return   array                The category scores.
     */
    private function calculate_category_scores($results) {
        $category_scores = array();
        
        // Calculate score for each analysis group
        foreach ($results['analysis_groups'] as $group_name => $group) {
            $group_score = 0;
            $group_total = 0;
            
            foreach ($group as $check) {
                $group_score += $check['score'];
                $group_total++;
            }
            
            // Calculate percentage
            $category_scores[$group_name] = $group_total > 0 ? round(($group_score / $group_total) * 100) : 0;
        }
        
        return $category_scores;
    }
    
    /**
     * Analyze product-specific SEO factors.
     *
     * @since    1.0.0
     * @param    WC_Product    $product      The WooCommerce product object.
     * @param    boolean       $fast_mode    Whether to perform a fast analysis
     * @return   array                       The product-specific analysis results.
     */
    private function analyze_product_specific($product, $fast_mode = false) {
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
                'importance' => 'high',
                'recommended_action' => __('Add a concise, compelling short description (50-150 characters) highlighting the key benefits of your product.', 'seo-advisor-woo'),
            );
        } elseif ($short_desc_length < 50) {
            $results['short_description'] = array(
                'name' => __('Short Description', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('Your product short description is too brief (%d characters). Try to make it at least 50 characters.', 'seo-advisor-woo'), $short_desc_length),
                'score' => 0.5,
                'importance' => 'high',
                'recommended_action' => __('Expand your short description to at least 50 characters. Include key features and benefits.', 'seo-advisor-woo'),
            );
        } else {
            $results['short_description'] = array(
                'name' => __('Short Description', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has a good short description (%d characters).', 'seo-advisor-woo'), $short_desc_length),
                'score' => 1,
                'importance' => 'high',
                'recommended_action' => '',
            );
        }
        
        // Check if short description contains focus keyword
        $focus_keyword = get_post_meta($product->get_id(), '_seo_advisor_focus_keyword', true);
        if (!empty($focus_keyword) && !empty($short_description)) {
            if (stripos($short_description, $focus_keyword) !== false) {
                $results['keyword_in_short_desc'] = array(
                    'name' => __('Keyword in Short Description', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => __('Your short description contains the focus keyword. Good job!', 'seo-advisor-woo'),
                    'score' => 1,
                    'importance' => 'medium',
                    'recommended_action' => '',
                );
            } else {
                $results['keyword_in_short_desc'] = array(
                    'name' => __('Keyword in Short Description', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('Your short description does not contain the focus keyword.', 'seo-advisor-woo'),
                    'score' => 0.75,
                    'importance' => 'medium',
                    'recommended_action' => sprintf(__('Add your focus keyword "%s" to the short description if it fits naturally.', 'seo-advisor-woo'), $focus_keyword),
                );
            }
        }
        
        // Check product attributes
        $attributes = $product->get_attributes();
        
        if (empty($attributes)) {
            $results['product_attributes'] = array(
                'name' => __('Product Attributes', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product does not have any attributes. Adding attributes can improve SEO and help customers find your product.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'medium',
                'recommended_action' => __('Add relevant attributes to your product. Attributes help customers filter products and improve SEO for specific searches.', 'seo-advisor-woo'),
            );
        } else {
            $results['product_attributes'] = array(
                'name' => __('Product Attributes', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has %d attributes. Good job!', 'seo-advisor-woo'), count($attributes)),
                'score' => 1,
                'importance' => 'medium',
                'recommended_action' => '',
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
                'importance' => 'high',
                'recommended_action' => __('Assign your product to at least one relevant category. Categories help customers find your products and improve SEO.', 'seo-advisor-woo'),
            );
        } elseif (count($categories) > 3) {
            $results['product_categories'] = array(
                'name' => __('Product Categories', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('Your product is assigned to %d categories. Try to limit it to 1-3 most relevant categories.', 'seo-advisor-woo'), count($categories)),
                'score' => 0.5,
                'importance' => 'high',
                'recommended_action' => __('Reduce the number of categories to 1-3 most relevant ones. Too many categories can dilute your SEO focus.', 'seo-advisor-woo'),
            );
        } else {
            $results['product_categories'] = array(
                'name' => __('Product Categories', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product is assigned to %d categories. Good job!', 'seo-advisor-woo'), count($categories)),
                'score' => 1,
                'importance' => 'high',
                'recommended_action' => '',
            );
        }
        
        // Check category descriptions
        if (!empty($categories)) {
            $categories_without_desc = 0;
            
            foreach ($categories as $category_id) {
                $term = get_term($category_id, 'product_cat');
                if ($term && empty($term->description)) {
                    $categories_without_desc++;
                }
            }
            
            if ($categories_without_desc > 0) {
                $results['category_descriptions'] = array(
                    'name' => __('Category Descriptions', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => sprintf(__('%d of your product categories do not have descriptions. Add descriptions to improve SEO.', 'seo-advisor-woo'), $categories_without_desc),
                    'score' => 0.75,
                    'importance' => 'medium',
                    'recommended_action' => __('Add descriptive content to your product categories. Category descriptions help with SEO and provide context for customers.', 'seo-advisor-woo'),
                );
            } else {
                $results['category_descriptions'] = array(
                    'name' => __('Category Descriptions', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => __('All your product categories have descriptions. Good job!', 'seo-advisor-woo'),
                    'score' => 1,
                    'importance' => 'medium',
                    'recommended_action' => '',
                );
            }
        }
        
        // Check product tags
        $tags = $product->get_tag_ids();
        
        if (empty($tags)) {
            $results['product_tags'] = array(
                'name' => __('Product Tags', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product does not have any tags. Adding relevant tags can improve discoverability.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'medium',
                'recommended_action' => __('Add 5-10 relevant tags to your product. Tags help with internal linking and can improve SEO for long-tail keywords.', 'seo-advisor-woo'),
            );
        } elseif (count($tags) > 15) {
            $results['product_tags'] = array(
                'name' => __('Product Tags', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('Your product has %d tags, which may be excessive. Try to limit it to 5-15 most relevant tags.', 'seo-advisor-woo'), count($tags)),
                'score' => 0.5,
                'importance' => 'medium',
                'recommended_action' => __('Reduce the number of tags to 5-15 most relevant ones. Too many tags can dilute your SEO focus and create too many archive pages.', 'seo-advisor-woo'),
            );
        } else {
            $results['product_tags'] = array(
                'name' => __('Product Tags', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has %d tags. Good job!', 'seo-advisor-woo'), count($tags)),
                'score' => 1,
                'importance' => 'medium',
                'recommended_action' => '',
            );
        }
        
        // Check if tags contain focus keyword
        if (!empty($focus_keyword) && !empty($tags)) {
            $keyword_in_tags = false;
            
            foreach ($tags as $tag_id) {
                $term = get_term($tag_id, 'product_tag');
                if ($term && stripos($term->name, $focus_keyword) !== false) {
                    $keyword_in_tags = true;
                    break;
                }
            }
            
            if ($keyword_in_tags) {
                $results['keyword_in_tags'] = array(
                    'name' => __('Keyword in Tags', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => __('At least one of your product tags contains the focus keyword. Good job!', 'seo-advisor-woo'),
                    'score' => 1,
                    'importance' => 'low',
                    'recommended_action' => '',
                );
            } else {
                $results['keyword_in_tags'] = array(
                    'name' => __('Keyword in Tags', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('None of your product tags contain the focus keyword.', 'seo-advisor-woo'),
                    'score' => 0.75,
                    'importance' => 'low',
                    'recommended_action' => sprintf(__('Add a tag that contains your focus keyword "%s" or a close variant.', 'seo-advisor-woo'), $focus_keyword),
                );
            }
        }
        
        // Stop here if in fast mode
        if ($fast_mode) {
            return $results;
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
                'importance' => 'high',
                'recommended_action' => __('Add a high-quality main product image. Product images are essential for conversions and SEO.', 'seo-advisor-woo'),
            );
        } elseif (empty($gallery_image_ids)) {
            $results['product_images'] = array(
                'name' => __('Product Images', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product only has a main image but no gallery images. Add multiple product images to improve conversions.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'high',
                'recommended_action' => __('Add multiple gallery images showing different angles or features of your product. Multiple images improve conversions.', 'seo-advisor-woo'),
            );
        } else {
            $results['product_images'] = array(
                'name' => __('Product Images', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has a main image and %d gallery images. Good job!', 'seo-advisor-woo'), count($gallery_image_ids)),
                'score' => 1,
                'importance' => 'high',
                'recommended_action' => '',
            );
        }
        
        // Check image quality (size)
        if (!empty($image_id)) {
            $image_data = wp_get_attachment_image_src($image_id, 'full');
            
            if ($image_data) {
                $image_width = $image_data[1];
                $image_height = $image_data[2];
                
                if ($image_width < 800 || $image_height < 800) {
                    $results['image_quality'] = array(
                        'name' => __('Image Quality', 'seo-advisor-woo'),
                        'status' => 'warning',
                        'message' => sprintf(__('Your main product image is only %dx%d pixels. For best results, use images at least 800x800 pixels.', 'seo-advisor-woo'), $image_width, $image_height),
                        'score' => 0.5,
                        'importance' => 'medium',
                        'recommended_action' => __('Replace your main product image with a higher resolution version (at least 800x800 pixels).', 'seo-advisor-woo'),
                    );
                } else {
                    $results['image_quality'] = array(
                        'name' => __('Image Quality', 'seo-advisor-woo'),
                        'status' => 'good',
                        'message' => sprintf(__('Your main product image has good resolution (%dx%d pixels).', 'seo-advisor-woo'), $image_width, $image_height),
                        'score' => 1,
                        'importance' => 'medium',
                        'recommended_action' => '',
                    );
                }
            }
            
            // Check image alt text
            $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            
            if (empty($image_alt)) {
                $results['image_alt_text'] = array(
                    'name' => __('Image Alt Text', 'seo-advisor-woo'),
                    'status' => 'critical',
                    'message' => __('Your main product image does not have alt text. Add descriptive alt text for better accessibility and SEO.', 'seo-advisor-woo'),
                    'score' => 0,
                    'importance' => 'high',
                    'recommended_action' => __('Add descriptive alt text to your main product image. Include your product name and key features.', 'seo-advisor-woo'),
                );
            } elseif (!empty($focus_keyword) && stripos($image_alt, $focus_keyword) === false) {
                $results['image_alt_text'] = array(
                    'name' => __('Image Alt Text', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('Your main product image alt text does not contain the focus keyword.', 'seo-advisor-woo'),
                    'score' => 0.75,
                    'importance' => 'high',
                    'recommended_action' => sprintf(__('Update your main image alt text to include your focus keyword "%s" if it fits naturally.', 'seo-advisor-woo'), $focus_keyword),
                );
            } else {
                $results['image_alt_text'] = array(
                    'name' => __('Image Alt Text', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => __('Your main product image has good alt text.', 'seo-advisor-woo'),
                    'score' => 1,
                    'importance' => 'high',
                    'recommended_action' => '',
                );
            }
        }
        
        // Check for product reviews
        $review_count = $product->get_review_count();
        
        if ($review_count === 0) {
            $results['product_reviews'] = array(
                'name' => __('Product Reviews', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product does not have any reviews. Reviews can improve trust and SEO.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'medium',
                'recommended_action' => __('Encourage customers to leave reviews. Consider reaching out to past customers or offering incentives for honest reviews.', 'seo-advisor-woo'),
            );
        } elseif ($review_count < 5) {
            $results['product_reviews'] = array(
                'name' => __('Product Reviews', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('Your product has only %d reviews. More reviews can improve trust and SEO.', 'seo-advisor-woo'), $review_count),
                'score' => 0.75,
                'importance' => 'medium',
                'recommended_action' => __('Try to get at least 5 reviews for your product. More reviews provide social proof and improve rankings.', 'seo-advisor-woo'),
            );
        } else {
            $results['product_reviews'] = array(
                'name' => __('Product Reviews', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has %d reviews. Good job!', 'seo-advisor-woo'), $review_count),
                'score' => 1,
                'importance' => 'medium',
                'recommended_action' => '',
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
                'importance' => 'high',
                'recommended_action' => '',
            );
        } else {
            $results['product_schema'] = array(
                'name' => __('Product Schema', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('No product schema detected. Consider adding structured data for rich snippets in search results.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'high',
                'recommended_action' => __('Add product schema markup to your product page. This can be done using a schema plugin or through your theme settings.', 'seo-advisor-woo'),
            );
        }
        
        // Check product price
        if ($product->get_price() === '') {
            $results['product_price'] = array(
                'name' => __('Product Price', 'seo-advisor-woo'),
                'status' => 'critical',
                'message' => __('Your product does not have a price set. Adding a price is essential for e-commerce SEO.', 'seo-advisor-woo'),
                'score' => 0,
                'importance' => 'high',
                'recommended_action' => __('Set a price for your product. Price information is essential for product schema and customer decision-making.', 'seo-advisor-woo'),
            );
        } else {
            $results['product_price'] = array(
                'name' => __('Product Price', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your product has a price set. Good job!', 'seo-advisor-woo'),
                'score' => 1,
                'importance' => 'high',
                'recommended_action' => '',
            );
        }
        
        // Check for SKU
        $sku = $product->get_sku();
        
        if (empty($sku)) {
            $results['product_sku'] = array(
                'name' => __('Product SKU', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product does not have an SKU. Adding an SKU can improve inventory management and SEO.', 'seo-advisor-woo'),
                'score' => 0.75,
                'importance' => 'medium',
                'recommended_action' => __('Add a unique SKU to your product. SKUs help with inventory management and can appear in structured data.', 'seo-advisor-woo'),
            );
        } else {
            $results['product_sku'] = array(
                'name' => __('Product SKU', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your product has an SKU set. Good job!', 'seo-advisor-woo'),
                'score' => 1,
                'importance' => 'medium',
                'recommended_action' => '',
            );
        }
        
        // Check product variations if it's a variable product
        if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();
            
            if (empty($variations)) {
                $results['product_variations'] = array(
                    'name' => __('Product Variations', 'seo-advisor-woo'),
                    'status' => 'critical',
                    'message' => __('Your variable product does not have any available variations. Add variations for customers to choose from.', 'seo-advisor-woo'),
                    'score' => 0,
                    'importance' => 'high',
                    'recommended_action' => __('Add variations to your variable product. Each variation should have its own price, SKU, and stock status.', 'seo-advisor-woo'),
                );
            } else {
                $variations_without_desc = 0;
                
                foreach ($variations as $variation) {
                    $variation_obj = wc_get_product($variation['variation_id']);
                    if ($variation_obj && empty($variation_obj->get_description())) {
                        $variations_without_desc++;
                    }
                }
                
                if ($variations_without_desc > 0) {
                    $results['variation_descriptions'] = array(
                        'name' => __('Variation Descriptions', 'seo-advisor-woo'),
                        'status' => 'warning',
                        'message' => sprintf(__('%d of %d variations do not have descriptions. Adding descriptions can improve user experience.', 'seo-advisor-woo'), $variations_without_desc, count($variations)),
                        'score' => 0.75,
                        'importance' => 'medium',
                        'recommended_action' => __('Add descriptions to your product variations. Variation descriptions help customers understand the differences between options.', 'seo-advisor-woo'),
                    );
                } else {
                    $results['variation_descriptions'] = array(
                        'name' => __('Variation Descriptions', 'seo-advisor-woo'),
                        'status' => 'good',
                        'message' => __('All your product variations have descriptions. Good job!', 'seo-advisor-woo'),
                        'score' => 1,
                        'importance' => 'medium',
                        'recommended_action' => '',
                    );
                }
                
                $results['product_variations'] = array(
                    'name' => __('Product Variations', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => sprintf(__('Your product has %d variations. Good job!', 'seo-advisor-woo'), count($variations)),
                    'score' => 1,
                    'importance' => 'high',
                    'recommended_action' => '',
                );
            }
        }
        
        // Check related products
        $related_products = wc_get_related_products($product->get_id(), 5);
        
        if (empty($related_products)) {
            $results['related_products'] = array(
                'name' => __('Related Products', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your product does not have any related products. Having related products can improve internal linking and user experience.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'low',
                'recommended_action' => __('Add related products by assigning similar categories or tags to your products. Related products improve internal linking and keep customers browsing your store.', 'seo-advisor-woo'),
            );
        } else {
            $results['related_products'] = array(
                'name' => __('Related Products', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your product has %d related products. Good job!', 'seo-advisor-woo'), count($related_products)),
                'score' => 1,
                'importance' => 'low',
                'recommended_action' => '',
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
        // WooCommerce adds product schema by default in newer versions
        if (version_compare(WC_VERSION, '3.0.0', '>=')) {
            return true;
        }
        
        // Check for common schema plugins as fallback
        if (is_plugin_active('schema-pro/schema-pro.php') || 
            is_plugin_active('wp-schema-pro/wp-schema-pro.php') ||
            is_plugin_active('schema/schema.php') ||
            is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') ||
            is_plugin_active('wordpress-seo/wp-seo.php') ||
            is_plugin_active('seo-by-rank-math/rank-math.php')) {
            return true;
        }
        
        // Check for schema in product content
        $content = $product->get_description();
        
        if (strpos($content, 'application/ld+json') !== false || preg_match('/itemtype=[\'"]http(s)?:\/\/schema\.org/i', $content)) {
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
    
    /**
     * Get product analysis results from database.
     *
     * @since    1.0.0
     * @param    int       $product_id    The product ID.
     * @return   array|false              The analysis results or false if not found.
     */
    public function get_analysis_results($product_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        
        $analysis_data = $wpdb->get_var($wpdb->prepare(
            "SELECT analysis_data FROM $table_name WHERE post_id = %d",
            $product_id
        ));
        
        if ($analysis_data) {
            return json_decode($analysis_data, true);
        }
        
        return false;
    }
    
    /**
     * Get the average SEO score for products.
     *
     * @since    1.0.0
     * @return   int       The average SEO score.
     */
    public function get_average_product_score() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        
        $avg_score = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(seo_score) FROM $table_name WHERE post_type = %s",
            'product'
        ));
        
        return $avg_score ? round($avg_score) : 0;
    }
    
    /**
     * Get product categories with the best and worst SEO scores.
     *
     * @since    1.0.0
     * @param    string    $type     The type of categories to return (best, worst).
     * @param    int       $limit    The maximum number of categories to return.
     * @return   array               The product categories with their scores.
     */
    public function get_categories_by_score($type = 'best', $limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        
        // Get product IDs with their SEO scores
        $products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, seo_score FROM $table_name WHERE post_type = %s",
                'product'
            )
        );
        
        // Get categories with scores
        $categories = array();
        
        foreach ($products as $product) {
            $product_categories = wp_get_post_terms($product->post_id, 'product_cat', array('fields' => 'ids'));
            
            foreach ($product_categories as $category_id) {
                if (!isset($categories[$category_id])) {
                    $categories[$category_id] = array(
                        'scores' => array(),
                        'count' => 0,
                    );
                }
                
                $categories[$category_id]['scores'][] = $product->seo_score;
                $categories[$category_id]['count']++;
            }
        }
        
        // Calculate average scores
        $category_scores = array();
        
        foreach ($categories as $category_id => $data) {
            if ($data['count'] >= 3) { // Only include categories with at least 3 products
                $avg_score = array_sum($data['scores']) / $data['count'];
                
                $term = get_term($category_id, 'product_cat');
                if ($term) {
                    $category_scores[] = array(
                        'id' => $category_id,
                        'name' => $term->name,
                        'score' => round($avg_score),
                        'product_count' => $data['count'],
                        'url' => get_term_link($term),
                    );
                }
            }
        }
        
        // Sort categories
        if ($type === 'best') {
            usort($category_scores, function($a, $b) {
                return $b['score'] - $a['score'];
            });
        } else {
            usort($category_scores, function($a, $b) {
                return $a['score'] - $b['score'];
            });
        }
        
        // Return limited number of categories
        return array_slice($category_scores, 0, $limit);
    }
    
    /**
     * Get improvement suggestions for products.
     *
     * @since    1.0.0
     * @param    int       $limit    The maximum number of suggestions to return.
     * @return   array               The improvement suggestions.
     */
    public function get_product_improvement_suggestions($limit = 5) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        
        // Get the most recent product analyses
        $analyses = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_id, analysis_data 
                 FROM $table_name 
                 WHERE post_type = %s
                 ORDER BY updated_at DESC 
                 LIMIT 50",
                'product'
            )
        );
        
        $all_issues = array();
        
        // Process each analysis
        foreach ($analyses as $analysis) {
            $data = json_decode($analysis->analysis_data, true);
            
            if (!$data || !isset($data['analysis_groups']['product'])) {
                continue;
            }
            
            // Process product-specific issues
            foreach ($data['analysis_groups']['product'] as $check_key => $check) {
                // Skip if not a critical or warning issue
                if ($check['status'] !== 'critical' && $check['status'] !== 'warning') {
                    continue;
                }
                
                // Create issue key for counting
                $issue_key = 'product_' . $check_key;
                
                // Add to issues array or increment count
                if (!isset($all_issues[$issue_key])) {
                    $all_issues[$issue_key] = array(
                        'name' => $check['name'],
                        'status' => $check['status'],
                        'count' => 1,
                        'importance' => isset($check['importance']) ? $check['importance'] : 'medium',
                        'recommended_action' => isset($check['recommended_action']) ? $check['recommended_action'] : '',
                    );
                } else {
                    $all_issues[$issue_key]['count']++;
                }
            }
        }
        
        // Sort issues by importance, status, and count
        usort($all_issues, function($a, $b) {
            // First sort by importance
            $importance_order = array('high' => 1, 'medium' => 2, 'low' => 3);
            $a_importance = isset($importance_order[$a['importance']]) ? $importance_order[$a['importance']] : 2;
            $b_importance = isset($importance_order[$b['importance']]) ? $importance_order[$b['importance']] : 2;
            
            if ($a_importance !== $b_importance) {
                return $a_importance - $b_importance;
            }
            
            // Then sort by status
            $status_order = array('critical' => 1, 'warning' => 2);
            $a_status = isset($status_order[$a['status']]) ? $status_order[$a['status']] : 2;
            $b_status = isset($status_order[$b['status']]) ? $status_order[$b['status']] : 2;
            
            if ($a_status !== $b_status) {
                return $a_status - $b_status;
            }
            
            // Finally sort by count (descending)
            return $b['count'] - $a['count'];
        });
        
        // Return limited number of issues
        return array_slice($all_issues, 0, $limit);
    }
}
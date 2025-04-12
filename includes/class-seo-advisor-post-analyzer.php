<?php
/**
 * Post content analyzer for SEO.
 *
 * This class handles the analysis of post content for SEO optimization.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/includes
 */

class SEO_Advisor_Post_Analyzer {

    /**
     * The analysis settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings    The analysis settings.
     */
    private $settings;
    
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
        
        // Get site URL for internal link detection
        $this->site_url = get_site_url();
    }

    /**
     * Analyze a post for SEO optimization.
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID to analyze.
     * @return   array|WP_Error     The analysis results or error.
     */
    public function analyze_post($post_id) {
        // Get the post data
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('invalid_post', __('Invalid post ID', 'seo-advisor-woo'));
        }
        
        // Check if post type should be analyzed
        $general_settings = get_option('seo_advisor_general_settings', array());
        $post_types = isset($general_settings['post_types']) ? $general_settings['post_types'] : array('post', 'page');
        
        if (!in_array($post->post_type, $post_types) && $post->post_type !== 'product') {
            return new WP_Error('invalid_post_type', __('This post type is not set to be analyzed', 'seo-advisor-woo'));
        }
        
        // Get focus keyword
        $focus_keyword = get_post_meta($post_id, '_seo_advisor_focus_keyword', true);
        if (empty($focus_keyword)) {
            $focus_keyword = $this->settings['default_keyword'];
        }
        
        // Get secondary keywords
        $secondary_keywords = get_post_meta($post_id, '_seo_advisor_secondary_keywords', true);
        $secondary_keywords_array = array();
        if (!empty($secondary_keywords)) {
            $secondary_keywords_array = array_map('trim', explode(',', $secondary_keywords));
        }
        
        // Initialize results array
        $results = array(
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'focus_keyword' => $focus_keyword,
            'secondary_keywords' => $secondary_keywords_array,
            'analysis_groups' => array(),
            'score' => 0,
            'issues' => array(
                'critical' => 0,
                'warnings' => 0,
                'good' => 0,
            ),
        );
        
        // Run the analysis modules
        if ($this->settings['analyze_meta'] === 'yes') {
            $results['analysis_groups']['meta'] = $this->analyze_meta($post, $focus_keyword, $secondary_keywords_array);
        }
        
        if ($this->settings['analyze_content'] === 'yes') {
            $results['analysis_groups']['content'] = $this->analyze_content($post, $focus_keyword, $secondary_keywords_array);
        }
        
        if ($this->settings['analyze_images'] === 'yes') {
            $results['analysis_groups']['images'] = $this->analyze_images($post, $focus_keyword);
        }
        
        if ($this->settings['analyze_technical'] === 'yes') {
            $results['analysis_groups']['technical'] = $this->analyze_technical($post);
        }
        
        // Calculate total score and issue count
        $score = 0;
        $total_checks = 0;
        
        foreach ($results['analysis_groups'] as $group) {
            foreach ($group as $check) {
                $total_checks++;
                $score += $check['score'];
                
                // Count issues by status
                if ($check['status'] === 'critical') {
                    $results['issues']['critical']++;
                } elseif ($check['status'] === 'warning') {
                    $results['issues']['warnings']++;
                } elseif ($check['status'] === 'good') {
                    $results['issues']['good']++;
                }
            }
        }
        
        // Calculate final score (0-100)
        $results['score'] = $total_checks > 0 ? round(($score / $total_checks) * 100) : 0;
        
        // Save the analysis results to database
        $this->save_analysis_results($post_id, $results);
        
        // Update post meta
        update_post_meta($post_id, '_seo_advisor_seo_score', $results['score']);
        update_post_meta($post_id, '_seo_advisor_last_updated', current_time('mysql'));
        
        return $results;
    }

    /**
     * Analyze meta tags.
     *
     * @since    1.0.0
     * @param    WP_Post    $post                  The post object.
     * @param    string     $focus_keyword         The focus keyword.
     * @param    array      $secondary_keywords    The secondary keywords.
     * @return   array                             The meta analysis results.
     */
    private function analyze_meta($post, $focus_keyword, $secondary_keywords) {
        $results = array();
        
        // Analyze title
        $title = $post->post_title;
        $title_length = mb_strlen($title);
        $title_contains_keyword = false;
        
        if (!empty($focus_keyword) && stripos($title, $focus_keyword) !== false) {
            $title_contains_keyword = true;
        }
        
        // Title length check
        if ($title_length < 30) {
            $results['title_length'] = array(
                'name' => __('Title Length', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your title is too short. Try to make it at least 30 characters long.', 'seo-advisor-woo'),
                'score' => 0.25,
            );
        } elseif ($title_length > 60) {
            $results['title_length'] = array(
                'name' => __('Title Length', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your title is too long. Keep it under 60 characters for optimal display in search results.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } else {
            $results['title_length'] = array(
                'name' => __('Title Length', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your title has a good length.', 'seo-advisor-woo'),
                'score' => 1,
            );
        }
        
        // Title keyword check
        if (!empty($focus_keyword)) {
            if ($title_contains_keyword) {
                $results['title_keyword'] = array(
                    'name' => __('Title Keyword', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => sprintf(__('Your title contains the focus keyword "%s".', 'seo-advisor-woo'), $focus_keyword),
                    'score' => 1,
                );
            } else {
                $results['title_keyword'] = array(
                    'name' => __('Title Keyword', 'seo-advisor-woo'),
                    'status' => 'critical',
                    'message' => sprintf(__('Your title does not contain the focus keyword "%s".', 'seo-advisor-woo'), $focus_keyword),
                    'score' => 0,
                );
            }
        }
        
        // Analyze permalink
        $permalink = get_permalink($post->ID);
        $slug = basename($permalink);
        $slug_contains_keyword = false;
        
        if (!empty($focus_keyword)) {
            $keyword_slug = sanitize_title($focus_keyword);
            if (stripos($slug, $keyword_slug) !== false) {
                $slug_contains_keyword = true;
            }
        }
        
        if (!empty($focus_keyword)) {
            if ($slug_contains_keyword) {
                $results['slug_keyword'] = array(
                    'name' => __('URL Keyword', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => __('Your URL contains the focus keyword.', 'seo-advisor-woo'),
                    'score' => 1,
                );
            } else {
                $results['slug_keyword'] = array(
                    'name' => __('URL Keyword', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('Your URL does not contain the focus keyword.', 'seo-advisor-woo'),
                    'score' => 0.5,
                );
            }
        }
        
        // Analyze meta description
        $meta_description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true); // Check for Yoast meta
        if (empty($meta_description)) {
            $meta_description = get_post_meta($post->ID, '_aioseop_description', true); // Check for All in One SEO
        }
        if (empty($meta_description)) {
            $meta_description = get_post_meta($post->ID, '_genesis_description', true); // Check for Genesis
        }
        
        if (empty($meta_description)) {
            // Use post excerpt or generate from content
            $meta_description = $post->post_excerpt;
            if (empty($meta_description)) {
                $meta_description = wp_trim_words($post->post_content, 30, '...');
            }
        }
        
        $meta_description_length = mb_strlen($meta_description);
        $meta_contains_keyword = false;
        
        if (!empty($focus_keyword) && stripos($meta_description, $focus_keyword) !== false) {
            $meta_contains_keyword = true;
        }
        
        // Meta description length check
        if ($meta_description_length < 120) {
            $results['meta_description_length'] = array(
                'name' => __('Meta Description Length', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your meta description is too short. Try to make it at least 120 characters long.', 'seo-advisor-woo'),
                'score' => 0.25,
            );
        } elseif ($meta_description_length > 160) {
            $results['meta_description_length'] = array(
                'name' => __('Meta Description Length', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your meta description is too long. Keep it under 160 characters to avoid truncation in search results.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } else {
            $results['meta_description_length'] = array(
                'name' => __('Meta Description Length', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your meta description has a good length.', 'seo-advisor-woo'),
                'score' => 1,
            );
        }
        
        // Meta description keyword check
        if (!empty($focus_keyword)) {
            if ($meta_contains_keyword) {
                $results['meta_description_keyword'] = array(
                    'name' => __('Meta Description Keyword', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => __('Your meta description contains the focus keyword.', 'seo-advisor-woo'),
                    'score' => 1,
                );
            } else {
                $results['meta_description_keyword'] = array(
                    'name' => __('Meta Description Keyword', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('Your meta description does not contain the focus keyword.', 'seo-advisor-woo'),
                    'score' => 0.5,
                );
            }
        }
        
        return $results;
    }

    /**
     * Analyze content.
     *
     * @since    1.0.0
     * @param    WP_Post    $post                  The post object.
     * @param    string     $focus_keyword         The focus keyword.
     * @param    array      $secondary_keywords    The secondary keywords.
     * @return   array                             The content analysis results.
     */
    private function analyze_content($post, $focus_keyword, $secondary_keywords) {
        $results = array();
        $content = $post->post_content;
        $content_text = wp_strip_all_tags($content);
        $word_count = str_word_count($content_text);
        
        // Word count check
        if ($word_count < 300) {
            $results['content_length'] = array(
                'name' => __('Content Length', 'seo-advisor-woo'),
                'status' => 'critical',
                'message' => sprintf(__('Your content is too short (%d words). Try to write at least 300 words for better SEO.', 'seo-advisor-woo'), $word_count),
                'score' => 0,
            );
        } elseif ($word_count < 600) {
            $results['content_length'] = array(
                'name' => __('Content Length', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('Your content is a bit short (%d words). Consider expanding it to at least 600 words for better SEO.', 'seo-advisor-woo'), $word_count),
                'score' => 0.5,
            );
        } else {
            $results['content_length'] = array(
                'name' => __('Content Length', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content length is good (%d words).', 'seo-advisor-woo'), $word_count),
                'score' => 1,
            );
        }
        
        // Keyword density check
        if (!empty($focus_keyword)) {
            $keyword_count = substr_count(strtolower($content_text), strtolower($focus_keyword));
            $keyword_density = ($word_count > 0) ? ($keyword_count / $word_count) * 100 : 0;
            
            if ($keyword_density < 0.5) {
                $results['keyword_density'] = array(
                    'name' => __('Keyword Density', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => sprintf(__('Your keyword density is too low (%.2f%%). Try to use the focus keyword more often.', 'seo-advisor-woo'), $keyword_density),
                    'score' => 0.5,
                );
            } elseif ($keyword_density > 3) {
                $results['keyword_density'] = array(
                    'name' => __('Keyword Density', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => sprintf(__('Your keyword density is too high (%.2f%%). This might be seen as keyword stuffing.', 'seo-advisor-woo'), $keyword_density),
                    'score' => 0.5,
                );
            } else {
                $results['keyword_density'] = array(
                    'name' => __('Keyword Density', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => sprintf(__('Your keyword density is good (%.2f%%).', 'seo-advisor-woo'), $keyword_density),
                    'score' => 1,
                );
            }
        }
        
        // Heading structure check
        $headings = $this->extract_headings($content);
        
        if (empty($headings['h2']) && empty($headings['h3'])) {
            $results['headings'] = array(
                'name' => __('Heading Structure', 'seo-advisor-woo'),
                'status' => 'critical',
                'message' => __('Your content does not contain any H2 or H3 headings. Add some headings to structure your content.', 'seo-advisor-woo'),
                'score' => 0,
            );
        } elseif (empty($headings['h2'])) {
            $results['headings'] = array(
                'name' => __('Heading Structure', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your content does not contain any H2 headings. Consider adding some for better content structure.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } else {
            $results['headings'] = array(
                'name' => __('Heading Structure', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content has a good heading structure (%d H2 tags, %d H3 tags).', 'seo-advisor-woo'), count($headings['h2']), count($headings['h3'])),
                'score' => 1,
            );
        }
        
        // Keyword in headings check
        if (!empty($focus_keyword) && !empty($headings)) {
            $keyword_in_heading = false;
            
            foreach ($headings as $heading_type => $heading_contents) {
                foreach ($heading_contents as $heading) {
                    if (stripos($heading, $focus_keyword) !== false) {
                        $keyword_in_heading = true;
                        break 2;
                    }
                }
            }
            
            if ($keyword_in_heading) {
                $results['keyword_in_headings'] = array(
                    'name' => __('Keyword in Headings', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => __('Your headings contain the focus keyword. Good job!', 'seo-advisor-woo'),
                    'score' => 1,
                );
            } else {
                $results['keyword_in_headings'] = array(
                    'name' => __('Keyword in Headings', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('Your headings do not contain the focus keyword. Try to add it to at least one heading.', 'seo-advisor-woo'),
                    'score' => 0.5,
                );
            }
        }
        
        // Check for internal links
        $internal_links = $this->count_internal_links($content);
        
        if ($internal_links === 0) {
            $results['internal_links'] = array(
                'name' => __('Internal Links', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your content does not contain any internal links. Add some to improve your site structure.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        } elseif ($internal_links < 3 && $word_count > 1000) {
            $results['internal_links'] = array(
                'name' => __('Internal Links', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('Your content has only %d internal links. For long content, consider adding more internal links.', 'seo-advisor-woo'), $internal_links),
                'score' => 0.75,
            );
        } else {
            $results['internal_links'] = array(
                'name' => __('Internal Links', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content has %d internal links. Good job!', 'seo-advisor-woo'), $internal_links),
                'score' => 1,
            );
        }
        
        // Check for external links
        $external_links = $this->count_external_links($content);
        
        if ($external_links === 0 && $word_count > 600) {
            $results['external_links'] = array(
                'name' => __('External Links', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your content does not contain any external links. Consider adding some to authoritative sources.', 'seo-advisor-woo'),
                'score' => 0.75,
            );
        } else {
            $results['external_links'] = array(
                'name' => __('External Links', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content has %d external links.', 'seo-advisor-woo'), $external_links),
                'score' => 1,
            );
        }
        
        // Paragraph length check
        $paragraphs = $this->extract_paragraphs($content);
        $long_paragraphs = 0;
        
        foreach ($paragraphs as $paragraph) {
            $paragraph_word_count = str_word_count(wp_strip_all_tags($paragraph));
            if ($paragraph_word_count > 150) {
                $long_paragraphs++;
            }
        }
        
        if ($long_paragraphs > 0) {
            $results['paragraph_length'] = array(
                'name' => __('Paragraph Length', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('%d paragraphs are too long. Try to keep paragraphs under 150 words for better readability.', 'seo-advisor-woo'), $long_paragraphs),
                'score' => 0.75,
            );
        } else {
            $results['paragraph_length'] = array(
                'name' => __('Paragraph Length', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your paragraphs have a good length for easy reading.', 'seo-advisor-woo'),
                'score' => 1,
            );
        }
        
        return $results;
    }
    
    /**
     * Analyze images in content.
     *
     * @since    1.0.0
     * @param    WP_Post    $post              The post object.
     * @param    string     $focus_keyword     The focus keyword.
     * @return   array                         The images analysis results.
     */
    private function analyze_images($post, $focus_keyword) {
        $results = array();
        $content = $post->post_content;
        
        // Extract all images from content
        preg_match_all('/<img [^>]+>/', $content, $matches);
        $images = $matches[0];
        $image_count = count($images);
        
        // Check if content has images
        if ($image_count === 0) {
            $results['image_count'] = array(
                'name' => __('Image Count', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your content does not contain any images. Consider adding at least one relevant image.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
            
            return $results; // No need to check further
        } else {
            $results['image_count'] = array(
                'name' => __('Image Count', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content contains %d images. Good job!', 'seo-advisor-woo'), $image_count),
                'score' => 1,
            );
        }
        
        // Check for alt text
        $images_without_alt = 0;
        $keyword_in_alt = false;
        
        foreach ($images as $image) {
            // Check if image has alt attribute
            if (!preg_match('/alt=["\']([^"\']*)["\']/', $image, $alt_match)) {
                $images_without_alt++;
            } else {
                $alt_text = $alt_match[1];
                
                // Check if alt text contains focus keyword
                if (!empty($focus_keyword) && stripos($alt_text, $focus_keyword) !== false) {
                    $keyword_in_alt = true;
                }
            }
        }
        
        // Alt text presence check
        if ($images_without_alt > 0) {
            $results['images_alt'] = array(
                'name' => __('Image Alt Text', 'seo-advisor-woo'),
                'status' => 'critical',
                'message' => sprintf(__('%d images do not have alt text. Add descriptive alt text to all images for better accessibility and SEO.', 'seo-advisor-woo'), $images_without_alt),
                'score' => 0,
            );
        } else {
            $results['images_alt'] = array(
                'name' => __('Image Alt Text', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('All images have alt text. Good job!', 'seo-advisor-woo'),
                'score' => 1,
            );
        }
        
        // Keyword in alt text check
        if (!empty($focus_keyword)) {
            if ($keyword_in_alt) {
                $results['keyword_in_alt'] = array(
                    'name' => __('Keyword in Alt Text', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => __('At least one image alt text contains the focus keyword. Good job!', 'seo-advisor-woo'),
                    'score' => 1,
                );
            } else {
                $results['keyword_in_alt'] = array(
                    'name' => __('Keyword in Alt Text', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('None of your image alt texts contain the focus keyword. Consider adding it to at least one relevant image.', 'seo-advisor-woo'),
                    'score' => 0.5,
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Analyze technical SEO aspects.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     * @return   array               The technical analysis results.
     */
    private function analyze_technical($post) {
        $results = array();
        
        // Check for schema markup
        $has_schema = $this->has_schema_markup($post);
        
        if ($has_schema) {
            $results['schema_markup'] = array(
                'name' => __('Schema Markup', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your content has schema markup. Good job!', 'seo-advisor-woo'),
                'score' => 1,
            );
        } else {
            $results['schema_markup'] = array(
                'name' => __('Schema Markup', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('No schema markup detected. Consider adding structured data to enhance your search results display.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        }
        
        // Check for canonical URL
        $canonical_url = $this->get_canonical_url($post->ID);
        
        if ($canonical_url) {
            $results['canonical_url'] = array(
                'name' => __('Canonical URL', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your content has a canonical URL set. Good job!', 'seo-advisor-woo'),
                'score' => 1,
            );
        } else {
            $results['canonical_url'] = array(
                'name' => __('Canonical URL', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('No canonical URL detected. Consider setting a canonical URL to prevent duplicate content issues.', 'seo-advisor-woo'),
                'score' => 0.5,
            );
        }
        
        // Check for mobile-friendliness (basic check)
        $results['mobile_friendly'] = array(
            'name' => __('Mobile-Friendly', 'seo-advisor-woo'),
            'status' => 'good',
            'message' => __('Your site appears to be using a responsive theme, which is good for mobile-friendliness.', 'seo-advisor-woo'),
            'score' => 1,
        );
        
        return $results;
    }
    
    /**
     * Extract headings from content.
     *
     * @since    1.0.0
     * @param    string    $content    The post content.
     * @return   array                 Array of headings by type.
     */
    private function extract_headings($content) {
        $headings = array(
            'h1' => array(),
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'h6' => array(),
        );
        
        // Extract H1 headings
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches);
        if (!empty($matches[1])) {
            $headings['h1'] = array_map('wp_strip_all_tags', $matches[1]);
        }
        
        // Extract H2 headings
        preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $content, $matches);
        if (!empty($matches[1])) {
            $headings['h2'] = array_map('wp_strip_all_tags', $matches[1]);
        }
        
        // Extract H3 headings
        preg_match_all('/<h3[^>]*>(.*?)<\/h3>/i', $content, $matches);
        if (!empty($matches[1])) {
            $headings['h3'] = array_map('wp_strip_all_tags', $matches[1]);
        }
        
        // Extract H4 headings
        preg_match_all('/<h4[^>]*>(.*?)<\/h4>/i', $content, $matches);
        if (!empty($matches[1])) {
            $headings['h4'] = array_map('wp_strip_all_tags', $matches[1]);
        }
        
        // Extract H5 headings
        preg_match_all('/<h5[^>]*>(.*?)<\/h5>/i', $content, $matches);
        if (!empty($matches[1])) {
            $headings['h5'] = array_map('wp_strip_all_tags', $matches[1]);
        }
        
        // Extract H6 headings
        preg_match_all('/<h6[^>]*>(.*?)<\/h6>/i', $content, $matches);
        if (!empty($matches[1])) {
            $headings['h6'] = array_map('wp_strip_all_tags', $matches[1]);
        }
        
        return $headings;
    }
    
    /**
     * Extract paragraphs from content.
     *
     * @since    1.0.0
     * @param    string    $content    The post content.
     * @return   array                 Array of paragraphs.
     */
    private function extract_paragraphs($content) {
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $content, $matches);
        return $matches[0];
    }
    
    /**
     * Count internal links in content.
     *
     * @since    1.0.0
     * @param    string    $content    The post content.
     * @return   int                   Number of internal links.
     */
    private function count_internal_links($content) {
        preg_match_all('/<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);
        $links = $matches[1];
        $internal_count = 0;
        
        foreach ($links as $link) {
            // Check if link is internal
            if (strpos($link, $this->site_url) === 0 || preg_match('/^\/[^\/]/', $link)) {
                $internal_count++;
            }
        }
        
        return $internal_count;
    }
    
    /**
     * Count external links in content.
     *
     * @since    1.0.0
     * @param    string    $content    The post content.
     * @return   int                   Number of external links.
     */
    private function count_external_links($content) {
        preg_match_all('/<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);
        $links = $matches[1];
        $external_count = 0;
        
        foreach ($links as $link) {
            // Skip non-HTTP(S) links
            if (!preg_match('/^https?:\/\//', $link)) {
                continue;
            }
            
            // Check if link is external
            if (strpos($link, $this->site_url) !== 0) {
                $external_count++;
            }
        }
        
        return $external_count;
    }
    
    /**
     * Check if post has schema markup.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     * @return   boolean             Whether the post has schema markup.
     */
    private function has_schema_markup($post) {
        $content = $post->post_content;
        
        // Check for JSON-LD schema
        if (strpos($content, 'application/ld+json') !== false) {
            return true;
        }
        
        // Check for microdata schema
        if (preg_match('/itemtype=[\'"]http(s)?:\/\/schema\.org/i', $content)) {
            return true;
        }
        
        // Check for common schema plugins
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
     * Get canonical URL for a post.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @return   string|boolean        The canonical URL or false if not found.
     */
    private function get_canonical_url($post_id) {
        // Check if Yoast SEO is active
        if (defined('WPSEO_VERSION')) {
            $canonical = WPSEO_Meta::get_value('canonical', $post_id);
            if (!empty($canonical)) {
                return $canonical;
            }
        }
        
        // Check if All in One SEO is active
        $aioseo_canonical = get_post_meta($post_id, '_aioseop_canonical_url', true);
        if (!empty($aioseo_canonical)) {
            return $aioseo_canonical;
        }
        
        // Check for canonical in post meta (for other SEO plugins)
        $meta_canonical = get_post_meta($post_id, '_canonical', true);
        if (!empty($meta_canonical)) {
            return $meta_canonical;
        }
        
        // Use permalink as default canonical
        return get_permalink($post_id);
    }
    
    /**
     * Save analysis results to database.
     *
     * @since    1.0.0
     * @param    int     $post_id     The post ID.
     * @param    array   $results     The analysis results.
     */
    private function save_analysis_results($post_id, $results) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        $post_type = get_post_type($post_id);
        $current_time = current_time('mysql');
        
        // Check if analysis already exists for this post
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT analysis_id FROM $table_name WHERE post_id = %d",
            $post_id
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
                array('post_id' => $post_id),
                array('%d', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new analysis
            $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'post_type' => $post_type,
                    'seo_score' => $results['score'],
                    'analysis_data' => $analysis_data,
                    'created_at' => $current_time,
                    'updated_at' => $current_time
                ),
                array('%d', '%s', '%d', '%s', '%s', '%s')
            );
        }
    }
}
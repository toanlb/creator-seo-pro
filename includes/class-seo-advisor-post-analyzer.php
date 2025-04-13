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
     * @param    int       $post_id    The post ID to analyze.
     * @param    boolean   $fast_mode  Whether to perform a fast analysis without heavy processing
     * @return   array|WP_Error       The analysis results or error.
     */
    public function analyze_post($post_id, $fast_mode = false) {
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
            'timestamp' => current_time('timestamp'),
        );
        
        // Run the analysis modules
        if ($this->settings['analyze_meta'] === 'yes') {
            $results['analysis_groups']['meta'] = $this->analyze_meta($post, $focus_keyword, $secondary_keywords_array);
        }
        
        if ($this->settings['analyze_content'] === 'yes') {
            $results['analysis_groups']['content'] = $this->analyze_content($post, $focus_keyword, $secondary_keywords_array, $fast_mode);
        }
        
        if ($this->settings['analyze_images'] === 'yes' && !$fast_mode) {
            $results['analysis_groups']['images'] = $this->analyze_images($post, $focus_keyword);
        }
        
        if ($this->settings['analyze_technical'] === 'yes' && !$fast_mode) {
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
        
        // Get per-category scores
        $results['category_scores'] = $this->calculate_category_scores($results);
        
        // Save the analysis results to database if not in fast mode
        if (!$fast_mode) {
            $this->save_analysis_results($post_id, $results);
            
            // Update post meta
            update_post_meta($post_id, '_seo_advisor_seo_score', $results['score']);
            update_post_meta($post_id, '_seo_advisor_last_updated', current_time('mysql'));
        }
        
        return $results;
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
                'importance' => 'high',
                'recommended_action' => __('Expand your title to at least 30 characters while keeping it under 60 characters. Include your focus keyword near the beginning.', 'seo-advisor-woo'),
            );
        } elseif ($title_length > 60) {
            $results['title_length'] = array(
                'name' => __('Title Length', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your title is too long. Keep it under 60 characters for optimal display in search results.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'high',
                'recommended_action' => __('Shorten your title to under 60 characters to prevent truncation in search results. Keep your focus keyword and maintain clarity.', 'seo-advisor-woo'),
            );
        } else {
            $results['title_length'] = array(
                'name' => __('Title Length', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your title has a good length.', 'seo-advisor-woo'),
                'score' => 1,
                'importance' => 'high',
                'recommended_action' => '',
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
                    'importance' => 'high',
                    'recommended_action' => '',
                );
            } else {
                $results['title_keyword'] = array(
                    'name' => __('Title Keyword', 'seo-advisor-woo'),
                    'status' => 'critical',
                    'message' => sprintf(__('Your title does not contain the focus keyword "%s".', 'seo-advisor-woo'), $focus_keyword),
                    'score' => 0,
                    'importance' => 'high',
                    'recommended_action' => sprintf(__('Add your focus keyword "%s" to the title, preferably near the beginning.', 'seo-advisor-woo'), $focus_keyword),
                );
            }
            
            // Check for keyword at beginning of title
            if ($title_contains_keyword && stripos($title, $focus_keyword) > 10) {
                $results['title_keyword_position'] = array(
                    'name' => __('Keyword Position in Title', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('Your focus keyword is not at the beginning of the title.', 'seo-advisor-woo'),
                    'score' => 0.75,
                    'importance' => 'medium',
                    'recommended_action' => __('Try to place your focus keyword closer to the beginning of the title for better SEO.', 'seo-advisor-woo'),
                );
            } elseif ($title_contains_keyword) {
                $results['title_keyword_position'] = array(
                    'name' => __('Keyword Position in Title', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => __('Your focus keyword is at the beginning of the title.', 'seo-advisor-woo'),
                    'score' => 1,
                    'importance' => 'medium',
                    'recommended_action' => '',
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
                    'importance' => 'high',
                    'recommended_action' => '',
                );
            } else {
                $results['slug_keyword'] = array(
                    'name' => __('URL Keyword', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('Your URL does not contain the focus keyword.', 'seo-advisor-woo'),
                    'score' => 0.5,
                    'importance' => 'high',
                    'recommended_action' => sprintf(__('Consider updating your permalink to include the focus keyword "%s".', 'seo-advisor-woo'), $focus_keyword),
                );
            }
        }
        
        // Analyze meta description
        $meta_description = $this->get_meta_description($post);
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
                'message' => sprintf(__('Your meta description is too short (%d characters). Try to make it at least 120 characters long.', 'seo-advisor-woo'), $meta_description_length),
                'score' => 0.25,
                'importance' => 'high',
                'recommended_action' => __('Expand your meta description to between 120-160 characters. Include your focus keyword and a call to action.', 'seo-advisor-woo'),
            );
        } elseif ($meta_description_length > 160) {
            $results['meta_description_length'] = array(
                'name' => __('Meta Description Length', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('Your meta description is too long (%d characters). Keep it under 160 characters to avoid truncation in search results.', 'seo-advisor-woo'), $meta_description_length),
                'score' => 0.5,
                'importance' => 'high',
                'recommended_action' => __('Shorten your meta description to under 160 characters to prevent truncation in search results.', 'seo-advisor-woo'),
            );
        } else {
            $results['meta_description_length'] = array(
                'name' => __('Meta Description Length', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your meta description has a good length (%d characters).', 'seo-advisor-woo'), $meta_description_length),
                'score' => 1,
                'importance' => 'high',
                'recommended_action' => '',
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
                    'importance' => 'high',
                    'recommended_action' => '',
                );
            } else {
                $results['meta_description_keyword'] = array(
                    'name' => __('Meta Description Keyword', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('Your meta description does not contain the focus keyword.', 'seo-advisor-woo'),
                    'score' => 0.5,
                    'importance' => 'high',
                    'recommended_action' => sprintf(__('Add your focus keyword "%s" to the meta description.', 'seo-advisor-woo'), $focus_keyword),
                );
            }
        }
        
        // Meta description secondary keywords check
        if (!empty($secondary_keywords_array)) {
            $secondary_in_meta = 0;
            foreach ($secondary_keywords_array as $keyword) {
                if (stripos($meta_description, $keyword) !== false) {
                    $secondary_in_meta++;
                }
            }
            
            if ($secondary_in_meta > 0) {
                $results['meta_description_secondary'] = array(
                    'name' => __('Secondary Keywords in Meta', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => sprintf(__('Your meta description contains %d of your secondary keywords.', 'seo-advisor-woo'), $secondary_in_meta),
                    'score' => 1,
                    'importance' => 'medium',
                    'recommended_action' => '',
                );
            } else {
                $results['meta_description_secondary'] = array(
                    'name' => __('Secondary Keywords in Meta', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('Your meta description does not contain any of your secondary keywords.', 'seo-advisor-woo'),
                    'score' => 0.75,
                    'importance' => 'medium',
                    'recommended_action' => __('Consider adding some of your secondary keywords to the meta description if they fit naturally.', 'seo-advisor-woo'),
                );
            }
        }
        
        return $results;
    }

    /**
     * Get meta description from various sources.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     * @return   string              The meta description.
     */
    private function get_meta_description($post) {
        // Check for Yoast SEO meta
        $meta_description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
        
        // Check for All in One SEO
        if (empty($meta_description)) {
            $meta_description = get_post_meta($post->ID, '_aioseop_description', true);
        }
        
        // Check for Rank Math
        if (empty($meta_description)) {
            $meta_description = get_post_meta($post->ID, 'rank_math_description', true);
        }
        
        // Check for Genesis
        if (empty($meta_description)) {
            $meta_description = get_post_meta($post->ID, '_genesis_description', true);
        }
        
        // Use post excerpt or generate from content
        if (empty($meta_description)) {
            $meta_description = $post->post_excerpt;
            if (empty($meta_description)) {
                $meta_description = wp_trim_words(strip_shortcodes($post->post_content), 30, '...');
            }
        }
        
        return $meta_description;
    }

    /**
     * Analyze content.
     *
     * @since    1.0.0
     * @param    WP_Post    $post                  The post object.
     * @param    string     $focus_keyword         The focus keyword.
     * @param    array      $secondary_keywords    The secondary keywords.
     * @param    boolean    $fast_mode             Whether to perform a fast analysis
     * @return   array                             The content analysis results.
     */
    private function analyze_content($post, $focus_keyword, $secondary_keywords, $fast_mode = false) {
        $results = array();
        $content = $post->post_content;
        $content_text = wp_strip_all_tags(strip_shortcodes($content));
        $word_count = str_word_count($content_text);
        
        // Word count check
        if ($word_count < 300) {
            $results['content_length'] = array(
                'name' => __('Content Length', 'seo-advisor-woo'),
                'status' => 'critical',
                'message' => sprintf(__('Your content is too short (%d words). Try to write at least 300 words for better SEO.', 'seo-advisor-woo'), $word_count),
                'score' => 0,
                'importance' => 'high',
                'recommended_action' => __('Expand your content to at least 300 words, preferably 600+ for better SEO performance.', 'seo-advisor-woo'),
            );
        } elseif ($word_count < 600) {
            $results['content_length'] = array(
                'name' => __('Content Length', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('Your content is a bit short (%d words). Consider expanding it to at least 600 words for better SEO.', 'seo-advisor-woo'), $word_count),
                'score' => 0.5,
                'importance' => 'high',
                'recommended_action' => __('Aim for at least 600 words for competitive topics. Research shows longer content tends to rank better.', 'seo-advisor-woo'),
            );
        } else {
            $results['content_length'] = array(
                'name' => __('Content Length', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content length is good (%d words).', 'seo-advisor-woo'), $word_count),
                'score' => 1,
                'importance' => 'high',
                'recommended_action' => '',
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
                    'importance' => 'high',
                    'recommended_action' => sprintf(__('Increase your focus keyword "%s" usage to reach a density between 0.5%% and 2.5%%.', 'seo-advisor-woo'), $focus_keyword),
                );
            } elseif ($keyword_density > 3) {
                $results['keyword_density'] = array(
                    'name' => __('Keyword Density', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => sprintf(__('Your keyword density is too high (%.2f%%). This might be seen as keyword stuffing.', 'seo-advisor-woo'), $keyword_density),
                    'score' => 0.5,
                    'importance' => 'high',
                    'recommended_action' => __('Reduce your focus keyword usage to avoid keyword stuffing. Aim for a natural flow in your content.', 'seo-advisor-woo'),
                );
            } else {
                $results['keyword_density'] = array(
                    'name' => __('Keyword Density', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => sprintf(__('Your keyword density is good (%.2f%%).', 'seo-advisor-woo'), $keyword_density),
                    'score' => 1,
                    'importance' => 'high',
                    'recommended_action' => '',
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
                'importance' => 'high',
                'recommended_action' => __('Add at least one H2 heading and several H3 headings to structure your content. This improves readability and SEO.', 'seo-advisor-woo'),
            );
        } elseif (empty($headings['h2'])) {
            $results['headings'] = array(
                'name' => __('Heading Structure', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your content does not contain any H2 headings. Consider adding some for better content structure.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'high',
                'recommended_action' => __('Add at least one H2 heading to clearly define the main sections of your content.', 'seo-advisor-woo'),
            );
        } else {
            $results['headings'] = array(
                'name' => __('Heading Structure', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content has a good heading structure (%d H2 tags, %d H3 tags).', 'seo-advisor-woo'), count($headings['h2']), count($headings['h3'])),
                'score' => 1,
                'importance' => 'high',
                'recommended_action' => '',
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
                    'importance' => 'high',
                    'recommended_action' => '',
                );
            } else {
                $results['keyword_in_headings'] = array(
                    'name' => __('Keyword in Headings', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('Your headings do not contain the focus keyword. Try to add it to at least one heading.', 'seo-advisor-woo'),
                    'score' => 0.5,
                    'importance' => 'high',
                    'recommended_action' => sprintf(__('Add your focus keyword "%s" to at least one H2 heading.', 'seo-advisor-woo'), $focus_keyword),
                );
            }
        }
        
        // Stop here if in fast mode
        if ($fast_mode) {
            return $results;
        }
        
        // Check for internal links
        $internal_links = $this->count_internal_links($content);
        
        if ($internal_links === 0) {
            $results['internal_links'] = array(
                'name' => __('Internal Links', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('Your content does not contain any internal links. Add some to improve your site structure.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'medium',
                'recommended_action' => __('Add at least 1-3 internal links to related content on your site to improve navigation and SEO.', 'seo-advisor-woo'),
            );
        } elseif ($internal_links < 3 && $word_count > 1000) {
            $results['internal_links'] = array(
                'name' => __('Internal Links', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('Your content has only %d internal links. For long content, consider adding more internal links.', 'seo-advisor-woo'), $internal_links),
                'score' => 0.75,
                'importance' => 'medium',
                'recommended_action' => __('For content over 1000 words, aim for at least 3-5 internal links to related content.', 'seo-advisor-woo'),
            );
        } else {
            $results['internal_links'] = array(
                'name' => __('Internal Links', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content has %d internal links. Good job!', 'seo-advisor-woo'), $internal_links),
                'score' => 1,
                'importance' => 'medium',
                'recommended_action' => '',
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
                'importance' => 'low',
                'recommended_action' => __('Add 1-2 external links to authoritative sources to increase the credibility of your content.', 'seo-advisor-woo'),
            );
        } else {
            $results['external_links'] = array(
                'name' => __('External Links', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content has %d external links.', 'seo-advisor-woo'), $external_links),
                'score' => 1,
                'importance' => 'low',
                'recommended_action' => '',
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
                'importance' => 'medium',
                'recommended_action' => __('Break up long paragraphs into smaller ones for better readability, especially on mobile devices.', 'seo-advisor-woo'),
            );
        } else {
            $results['paragraph_length'] = array(
                'name' => __('Paragraph Length', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your paragraphs have a good length for easy reading.', 'seo-advisor-woo'),
                'score' => 1,
                'importance' => 'medium',
                'recommended_action' => '',
            );
        }
        
        // Calculate readability (simplified Flesch Reading Ease)
        $readability_score = $this->calculate_readability($content_text);
        
        if ($readability_score < 50) {
            $results['readability'] = array(
                'name' => __('Readability', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('Your content is difficult to read (score: %d). Try to simplify your language.', 'seo-advisor-woo'), $readability_score),
                'score' => 0.5,
                'importance' => 'medium',
                'recommended_action' => __('Use shorter sentences, simpler words, and active voice to improve readability.', 'seo-advisor-woo'),
            );
        } elseif ($readability_score < 70) {
            $results['readability'] = array(
                'name' => __('Readability', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content has a decent readability (score: %d).', 'seo-advisor-woo'), $readability_score),
                'score' => 0.75,
                'importance' => 'medium',
                'recommended_action' => __('Your content is readable, but you could still simplify some sentences for a broader audience.', 'seo-advisor-woo'),
            );
        } else {
            $results['readability'] = array(
                'name' => __('Readability', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content is easy to read (score: %d). Great job!', 'seo-advisor-woo'), $readability_score),
                'score' => 1,
                'importance' => 'medium',
                'recommended_action' => '',
            );
        }
        
        return $results;
    }
    
    /**
     * Calculate a simplified readability score.
     *
     * @since    1.0.0
     * @param    string    $text    The text to analyze.
     * @return   int                The readability score.
     */
    private function calculate_readability($text) {
        // Count sentences (simplified)
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        
        // Count words
        $word_count = str_word_count($text);
        
        // Count syllables (simplified)
        $syllable_count = 0;
        $words = str_word_count($text, 1);
        
        foreach ($words as $word) {
            // A very basic syllable counter
            $word = strtolower($word);
            $word = preg_replace('/[^a-z]/', '', $word);
            $syllables = 0;
            
            // Count vowel groups as syllables
            $syllables = preg_match_all('/[aeiouy]+/', $word, $matches);
            
            // Adjust for common patterns
            if ($syllables === 0) {
                $syllables = 1;
            }
            
            $syllable_count += $syllables;
        }
        
        // Prevent division by zero
        if ($sentence_count === 0 || $word_count === 0) {
            return 50; // Default mid-range score
        }
        
        // Calculate simplified Flesch Reading Ease score
        $avg_words_per_sentence = $word_count / $sentence_count;
        $avg_syllables_per_word = $syllable_count / $word_count;
        
        $readability = 206.835 - (1.015 * $avg_words_per_sentence) - (84.6 * $avg_syllables_per_word);
        
        // Clamp the score between 0 and 100
        $readability = max(0, min(100, $readability));
        
        return round($readability);
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
                'importance' => 'medium',
                'recommended_action' => __('Add at least one relevant, high-quality image to make your content more engaging and SEO-friendly.', 'seo-advisor-woo'),
            );
            
            return $results; // No need to check further
        } else {
            $results['image_count'] = array(
                'name' => __('Image Count', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => sprintf(__('Your content contains %d images. Good job!', 'seo-advisor-woo'), $image_count),
                'score' => 1,
                'importance' => 'medium',
                'recommended_action' => '',
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
                'importance' => 'high',
                'recommended_action' => __('Add descriptive alt text to all images. This is essential for accessibility and SEO.', 'seo-advisor-woo'),
            );
        } else {
            $results['images_alt'] = array(
                'name' => __('Image Alt Text', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('All images have alt text. Good job!', 'seo-advisor-woo'),
                'score' => 1,
                'importance' => 'high',
                'recommended_action' => '',
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
                    'importance' => 'medium',
                    'recommended_action' => '',
                );
            } else {
                $results['keyword_in_alt'] = array(
                    'name' => __('Keyword in Alt Text', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('None of your image alt texts contain the focus keyword. Consider adding it to at least one relevant image.', 'seo-advisor-woo'),
                    'score' => 0.5,
                    'importance' => 'medium',
                    'recommended_action' => sprintf(__('Add your focus keyword "%s" to the alt text of at least one relevant image.', 'seo-advisor-woo'), $focus_keyword),
                );
            }
        }
        
        // Check image filenames
        $descriptive_filenames = 0;
        $keyword_in_filename = false;
        
        foreach ($images as $image) {
            if (preg_match('/src=["\']([^"\']*)["\']/', $image, $src_match)) {
                $src = $src_match[1];
                $filename = basename($src);
                
                // Check if filename is descriptive (more than just numbers or default names)
                if (!preg_match('/^(image|img|photo|pic|dsc|untitled|screenshot)[0-9_-]*\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
                    $descriptive_filenames++;
                }
                
                // Check if filename contains focus keyword
                if (!empty($focus_keyword) && stripos($filename, str_replace(' ', '-', strtolower($focus_keyword))) !== false) {
                    $keyword_in_filename = true;
                }
            }
        }
        
        // Descriptive filenames check
        if ($descriptive_filenames < $image_count) {
            $results['image_filenames'] = array(
                'name' => __('Image Filenames', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => sprintf(__('%d of %d images do not have descriptive filenames.', 'seo-advisor-woo'), ($image_count - $descriptive_filenames), $image_count),
                'score' => 0.75,
                'importance' => 'low',
                'recommended_action' => __('Rename your image files to be more descriptive before uploading. Use hyphens between words.', 'seo-advisor-woo'),
            );
        } else {
            $results['image_filenames'] = array(
                'name' => __('Image Filenames', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('All images have descriptive filenames. Good job!', 'seo-advisor-woo'),
                'score' => 1,
                'importance' => 'low',
                'recommended_action' => '',
            );
        }
        
        // Keyword in filename check
        if (!empty($focus_keyword)) {
            if ($keyword_in_filename) {
                $results['keyword_in_filename'] = array(
                    'name' => __('Keyword in Image Filename', 'seo-advisor-woo'),
                    'status' => 'good',
                    'message' => __('At least one image filename contains the focus keyword. Good job!', 'seo-advisor-woo'),
                    'score' => 1,
                    'importance' => 'low',
                    'recommended_action' => '',
                );
            } else {
                $results['keyword_in_filename'] = array(
                    'name' => __('Keyword in Image Filename', 'seo-advisor-woo'),
                    'status' => 'warning',
                    'message' => __('None of your image filenames contain the focus keyword.', 'seo-advisor-woo'),
                    'score' => 0.75,
                    'importance' => 'low',
                    'recommended_action' => sprintf(__('Consider renaming your image files to include your focus keyword "%s" before uploading them.', 'seo-advisor-woo'), $focus_keyword),
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
                'importance' => 'medium',
                'recommended_action' => '',
            );
        } else {
            $results['schema_markup'] = array(
                'name' => __('Schema Markup', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('No schema markup detected. Consider adding structured data to enhance your search results display.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'medium',
                'recommended_action' => __('Add schema markup to your content. Consider using a schema plugin or adding it manually.', 'seo-advisor-woo'),
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
                'importance' => 'medium',
                'recommended_action' => '',
            );
        } else {
            $results['canonical_url'] = array(
                'name' => __('Canonical URL', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('No canonical URL detected. Consider setting a canonical URL to prevent duplicate content issues.', 'seo-advisor-woo'),
                'score' => 0.5,
                'importance' => 'medium',
                'recommended_action' => __('Add a canonical URL to prevent duplicate content issues, especially if you have similar content on multiple pages.', 'seo-advisor-woo'),
            );
        }
        
        // Check for mobile-friendliness (basic check)
        $results['mobile_friendly'] = array(
            'name' => __('Mobile-Friendly Check', 'seo-advisor-woo'),
            'status' => 'good',
            'message' => __('Your site appears to be using a responsive theme, which is good for mobile-friendliness.', 'seo-advisor-woo'),
            'score' => 1,
            'importance' => 'high',
            'recommended_action' => '',
        );
        
        // Check for SSL
        if (is_ssl()) {
            $results['ssl'] = array(
                'name' => __('HTTPS/SSL', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your site is using HTTPS, which is good for SEO and security.', 'seo-advisor-woo'),
                'score' => 1,
                'importance' => 'high',
                'recommended_action' => '',
            );
        } else {
            $results['ssl'] = array(
                'name' => __('HTTPS/SSL', 'seo-advisor-woo'),
                'status' => 'critical',
                'message' => __('Your site is not using HTTPS. This is bad for SEO and security.', 'seo-advisor-woo'),
                'score' => 0,
                'importance' => 'high',
                'recommended_action' => __('Switch to HTTPS by installing an SSL certificate. This is essential for SEO and user trust.', 'seo-advisor-woo'),
            );
        }
        
        // Check for social meta tags
        $has_social_meta = $this->has_social_meta($post->ID);
        
        if ($has_social_meta) {
            $results['social_meta'] = array(
                'name' => __('Social Meta Tags', 'seo-advisor-woo'),
                'status' => 'good',
                'message' => __('Your content has social meta tags (Open Graph and/or Twitter Card).', 'seo-advisor-woo'),
                'score' => 1,
                'importance' => 'low',
                'recommended_action' => '',
            );
        } else {
            $results['social_meta'] = array(
                'name' => __('Social Meta Tags', 'seo-advisor-woo'),
                'status' => 'warning',
                'message' => __('No social meta tags detected. Consider adding Open Graph and Twitter Card tags.', 'seo-advisor-woo'),
                'score' => 0.75,
                'importance' => 'low',
                'recommended_action' => __('Add Open Graph and Twitter Card meta tags to improve how your content appears when shared on social media.', 'seo-advisor-woo'),
            );
        }
        
        return $results;
    }
    
    /**
     * Check if post has social meta tags.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @return   boolean               Whether the post has social meta tags.
     */
    private function has_social_meta($post_id) {
        // Check for Yoast SEO social meta
        if (defined('WPSEO_VERSION')) {
            $og_title = WPSEO_Meta::get_value('opengraph-title', $post_id);
            $twitter_title = WPSEO_Meta::get_value('twitter-title', $post_id);
            
            if (!empty($og_title) || !empty($twitter_title)) {
                return true;
            }
        }
        
        // Check for All in One SEO social meta
        $aioseo_og_title = get_post_meta($post_id, '_aioseop_opengraph_settings', true);
        if (!empty($aioseo_og_title)) {
            return true;
        }
        
        // Check for Rank Math SEO social meta
        $rank_math_fb_title = get_post_meta($post_id, 'rank_math_facebook_title', true);
        $rank_math_tw_title = get_post_meta($post_id, 'rank_math_twitter_title', true);
        
        if (!empty($rank_math_fb_title) || !empty($rank_math_tw_title)) {
            return true;
        }
        
        // Check for common theme/plugin social meta in post content
        $content = get_post_field('post_content', $post_id);
        if (strpos($content, 'og:') !== false || strpos($content, 'twitter:card') !== false) {
            return true;
        }
        
        return false;
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
        
        // Check for Gutenberg block headings
        if (function_exists('parse_blocks') && strpos($content, '<!-- wp:heading') !== false) {
            $blocks = parse_blocks($content);
            
            foreach ($blocks as $block) {
                if ($block['blockName'] === 'core/heading') {
                    $level = isset($block['attrs']['level']) ? $block['attrs']['level'] : 2;
                    $text = wp_strip_all_tags($block['innerHTML']);
                    
                    if (!empty($text)) {
                        $key = 'h' . $level;
                        if (!in_array($text, $headings[$key])) {
                            $headings[$key][] = $text;
                        }
                    }
                }
            }
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
            is_plugin_active('wordpress-seo/wp-seo.php') ||
            is_plugin_active('seo-by-rank-math/rank-math.php')) {
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
        
        // Check if Rank Math is active
        $rank_math_canonical = get_post_meta($post_id, 'rank_math_canonical_url', true);
        if (!empty($rank_math_canonical)) {
            return $rank_math_canonical;
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
    
    /**
     * Get analysis results from database.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @return   array|false           The analysis results or false if not found.
     */
    public function get_analysis_results($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        
        $analysis_data = $wpdb->get_var($wpdb->prepare(
            "SELECT analysis_data FROM $table_name WHERE post_id = %d",
            $post_id
        ));
        
        if ($analysis_data) {
            return json_decode($analysis_data, true);
        }
        
        return false;
    }
    
    /**
     * Get the average SEO score for a post type.
     *
     * @since    1.0.0
     * @param    string    $post_type    The post type.
     * @return   int                     The average SEO score.
     */
    public function get_average_score($post_type = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        
        if (!empty($post_type)) {
            $avg_score = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(seo_score) FROM $table_name WHERE post_type = %s",
                $post_type
            ));
        } else {
            $avg_score = $wpdb->get_var("SELECT AVG(seo_score) FROM $table_name");
        }
        
        return $avg_score ? round($avg_score) : 0;
    }
    
    /**
     * Get the top SEO issues across all analyzed content.
     *
     * @since    1.0.0
     * @param    int       $limit       The maximum number of issues to return.
     * @param    string    $status      The status of issues to return (critical, warning, all).
     * @return   array                  The top SEO issues.
     */
    public function get_top_issues($limit = 5, $status = 'all') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        
        // Get the most recent analyses
        $analyses = $wpdb->get_results(
            "SELECT post_id, post_type, analysis_data 
             FROM $table_name 
             ORDER BY updated_at DESC 
             LIMIT 50"
        );
        
        $all_issues = array();
        
        // Process each analysis
        foreach ($analyses as $analysis) {
            $data = json_decode($analysis->analysis_data, true);
            
            if (!$data || !isset($data['analysis_groups'])) {
                continue;
            }
            
            // Process each analysis group
            foreach ($data['analysis_groups'] as $group_name => $group) {
                foreach ($group as $check_key => $check) {
                    // Skip if not matching the requested status
                    if ($status !== 'all' && $check['status'] !== $status) {
                        continue;
                    }
                    
                    // Skip good status if we're not looking for all statuses
                    if ($status !== 'all' && $check['status'] === 'good') {
                        continue;
                    }
                    
                    // Create issue key for counting
                    $issue_key = $group_name . '_' . $check_key;
                    
                    // Add to issues array or increment count
                    if (!isset($all_issues[$issue_key])) {
                        $all_issues[$issue_key] = array(
                            'name' => $check['name'],
                            'group' => $group_name,
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
        }
        
        // Sort issues by importance and count
        usort($all_issues, function($a, $b) {
            // First sort by importance
            $importance_order = array('high' => 1, 'medium' => 2, 'low' => 3);
            $a_importance = isset($importance_order[$a['importance']]) ? $importance_order[$a['importance']] : 2;
            $b_importance = isset($importance_order[$b['importance']]) ? $importance_order[$b['importance']] : 2;
            
            if ($a_importance !== $b_importance) {
                return $a_importance - $b_importance;
            }
            
            // Then sort by status
            $status_order = array('critical' => 1, 'warning' => 2, 'good' => 3);
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
    
    /**
     * Get content with the best and worst SEO scores.
     *
     * @since    1.0.0
     * @param    string    $type        The type of content to return (best, worst).
     * @param    int       $limit       The maximum number of items to return.
     * @param    string    $post_type   The post type to filter by (optional).
     * @return   array                  The content items with their scores.
     */
    public function get_content_by_score($type = 'best', $limit = 5, $post_type = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        
        $post_type_filter = '';
        if (!empty($post_type)) {
            $post_type_filter = $wpdb->prepare("AND a.post_type = %s", $post_type);
        }
        
        $order = ($type === 'best') ? 'DESC' : 'ASC';
        
        $results = $wpdb->get_results(
            "SELECT a.post_id, a.post_type, a.seo_score, p.post_title 
             FROM $table_name AS a 
             JOIN {$wpdb->posts} AS p ON a.post_id = p.ID 
             WHERE p.post_status = 'publish' $post_type_filter
             ORDER BY a.seo_score $order 
             LIMIT $limit"
        );
        
        $content_items = array();
        
        foreach ($results as $item) {
            $content_items[] = array(
                'post_id' => $item->post_id,
                'post_type' => $item->post_type,
                'title' => $item->post_title,
                'score' => $item->seo_score,
                'edit_url' => get_edit_post_link($item->post_id),
                'view_url' => get_permalink($item->post_id)
            );
        }
        
        return $content_items;
    }
    
    /**
     * Get SEO score distribution across all content.
     *
     * @since    1.0.0
     * @return   array    The score distribution.
     */
    public function get_score_distribution() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'seo_advisor_analysis';
        
        $distribution = array(
            'poor' => 0,       // 0-49
            'average' => 0,    // 50-69
            'good' => 0,       // 70-89
            'excellent' => 0   // 90-100
        );
        
        $results = $wpdb->get_results(
            "SELECT 
                COUNT(CASE WHEN seo_score < 50 THEN 1 END) AS poor,
                COUNT(CASE WHEN seo_score >= 50 AND seo_score < 70 THEN 1 END) AS average,
                COUNT(CASE WHEN seo_score >= 70 AND seo_score < 90 THEN 1 END) AS good,
                COUNT(CASE WHEN seo_score >= 90 THEN 1 END) AS excellent
             FROM $table_name"
        );
        
        if (!empty($results)) {
            $distribution['poor'] = (int) $results[0]->poor;
            $distribution['average'] = (int) $results[0]->average;
            $distribution['good'] = (int) $results[0]->good;
            $distribution['excellent'] = (int) $results[0]->excellent;
        }
        
        return $distribution;
    }
    
    /**
     * Generate improvement suggestions based on analysis results.
     *
     * @since    1.0.0
     * @param    array     $results    The analysis results.
     * @param    int       $limit      The maximum number of suggestions to return.
     * @return   array                 The improvement suggestions.
     */
    public function get_improvement_suggestions($results, $limit = 5) {
        $suggestions = array();
        
        // Process each analysis group
        foreach ($results['analysis_groups'] as $group_name => $group) {
            foreach ($group as $check_key => $check) {
                // Only include critical and warning issues
                if ($check['status'] === 'critical' || $check['status'] === 'warning') {
                    // Skip if no recommended action is provided
                    if (empty($check['recommended_action'])) {
                        continue;
                    }
                    
                    // Add to suggestions array
                    $suggestions[] = array(
                        'name' => $check['name'],
                        'group' => $group_name,
                        'status' => $check['status'],
                        'message' => $check['message'],
                        'recommended_action' => $check['recommended_action'],
                        'importance' => isset($check['importance']) ? $check['importance'] : 'medium',
                    );
                }
            }
        }
        
        // Sort suggestions by importance and status
        usort($suggestions, function($a, $b) {
            // First sort by importance
            $importance_order = array('high' => 1, 'medium' => 2, 'low' => 3);
            $a_importance = isset($importance_order[$a['importance']]) ? $importance_order[$a['importance']] : 2;
            $b_importance = isset($importance_order[$b['importance']]) ? $importance_order[$b['importance']] : 2;
            
            if ($a_importance !== $b_importance) {
                return $a_importance - $b_importance;
            }
            
            // Then sort by status
            $status_order = array('critical' => 1, 'warning' => 2);
            $a_status = isset($status_order[$a['status']]) ? $status_order[$a['status']] : 1;
            $b_status = isset($status_order[$b['status']]) ? $status_order[$b['status']] : 1;
            
            return $a_status - $b_status;
        });
        
        // Return limited number of suggestions
        return array_slice($suggestions, 0, $limit);
    }
    
    /**
     * Get SEO score history for a post.
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @param    int       $limit      The maximum number of history entries to return.
     * @return   array                 The SEO score history.
     */
    public function get_score_history($post_id, $limit = 10) {
        // This method would typically require a separate table to store historical data
        // For now, we'll return a placeholder implementation
        
        return array(
            array(
                'date' => current_time('mysql'),
                'score' => get_post_meta($post_id, '_seo_advisor_seo_score', true),
            )
        );
    }
}
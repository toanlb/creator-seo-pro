<?php
/**
 * Metabox display for posts and pages.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/admin/partials
 */
?>

<div class="seo-advisor-metabox">
    <div class="seo-advisor-tabs">
        <div class="seo-advisor-tab-nav">
            <a href="#" class="seo-advisor-tab-link active" data-tab="keywords"><?php echo esc_html__('Keywords', 'seo-advisor-woo'); ?></a>
            <a href="#" class="seo-advisor-tab-link" data-tab="analysis"><?php echo esc_html__('Analysis', 'seo-advisor-woo'); ?></a>
            <a href="#" class="seo-advisor-tab-link" data-tab="content"><?php echo esc_html__('Content', 'seo-advisor-woo'); ?></a>
            <a href="#" class="seo-advisor-tab-link" data-tab="technical"><?php echo esc_html__('Technical', 'seo-advisor-woo'); ?></a>
            
            <?php 
            $score_class = 'poor';
            $score_text = __('Poor', 'seo-advisor-woo');
            
            if (!empty($seo_score)) {
                if ($seo_score >= 70 && $seo_score < 90) {
                    $score_class = 'good';
                    $score_text = __('Good', 'seo-advisor-woo');
                } elseif ($seo_score >= 90) {
                    $score_class = 'excellent';
                    $score_text = __('Excellent', 'seo-advisor-woo');
                }
            ?>
                <div class="seo-advisor-score seo-advisor-score-<?php echo esc_attr($score_class); ?>">
                    <?php echo esc_html($seo_score); ?>
                    <span class="seo-advisor-score-text"><?php echo esc_html($score_text); ?></span>
                </div>
            <?php } ?>
        </div>
        
        <div class="seo-advisor-tab-content">
            <!-- Keywords Tab -->
            <div class="seo-advisor-tab-pane active" id="seo-advisor-tab-keywords">
                <div class="seo-advisor-field">
                    <label for="seo_advisor_focus_keyword">
                        <?php echo esc_html__('Focus Keyword', 'seo-advisor-woo'); ?>
                        <span class="seo-advisor-tooltip" data-tooltip="<?php echo esc_attr__('This is the main keyword you want your content to rank for in search engines. Choose it carefully!', 'seo-advisor-woo'); ?>">?</span>
                    </label>
                    <input type="text" id="seo_advisor_focus_keyword" name="seo_advisor_focus_keyword" value="<?php echo esc_attr($focus_keyword); ?>" class="widefat" placeholder="<?php echo esc_attr__('e.g., wordpress seo plugin', 'seo-advisor-woo'); ?>">
                    <p class="description"><?php echo esc_html__('Enter the main keyword you want to rank for with this content.', 'seo-advisor-woo'); ?></p>
                </div>
                
                <div class="seo-advisor-field">
                    <label for="seo_advisor_secondary_keywords">
                        <?php echo esc_html__('Secondary Keywords', 'seo-advisor-woo'); ?>
                        <span class="seo-advisor-tooltip" data-tooltip="<?php echo esc_attr__('These are related keywords that support your focus keyword. Separate them with commas.', 'seo-advisor-woo'); ?>">?</span>
                    </label>
                    <textarea id="seo_advisor_secondary_keywords" name="seo_advisor_secondary_keywords" class="widefat" rows="3" placeholder="<?php echo esc_attr__('e.g., SEO tips, search engine optimization, WordPress optimization', 'seo-advisor-woo'); ?>"><?php echo esc_textarea($secondary_keywords); ?></textarea>
                    <p class="description"><?php echo esc_html__('Enter secondary keywords separated by commas.', 'seo-advisor-woo'); ?></p>
                </div>
                
                <div class="seo-advisor-actions">
                    <button type="button" class="button button-primary" id="seo_advisor_analyze_button">
                        <span class="dashicons dashicons-search"></span>
                        <?php echo esc_html__('Analyze Content', 'seo-advisor-woo'); ?>
                    </button>
                    <span class="spinner"></span>
                    
                    <?php if (!empty($last_updated)) : ?>
                        <div class="seo-advisor-last-updated">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html__('Last analyzed:', 'seo-advisor-woo'); ?>
                            <?php echo esc_html(human_time_diff(strtotime($last_updated), current_time('timestamp'))); ?>
                            <?php echo esc_html__('ago', 'seo-advisor-woo'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Analysis Tab -->
            <div class="seo-advisor-tab-pane" id="seo-advisor-tab-analysis">
                <div class="seo-advisor-overall-score">
                    <?php if (!empty($seo_score)) : ?>
                        <div class="seo-advisor-score-circle seo-advisor-score-<?php echo esc_attr($score_class); ?>">
                            <span class="seo-advisor-score-number"><?php echo esc_html($seo_score); ?></span>
                        </div>
                        <div class="seo-advisor-score-info">
                            <h3><?php echo esc_html($score_text); ?> <?php echo esc_html__('SEO Score', 'seo-advisor-woo'); ?></h3>
                            <?php
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'seo_advisor_analysis';
                            
                            $analysis_data = $wpdb->get_var($wpdb->prepare(
                                "SELECT analysis_data FROM $table_name WHERE post_id = %d",
                                $post->ID
                            ));
                            
                            if ($analysis_data) {
                                $analysis = json_decode($analysis_data, true);
                                if (isset($analysis['issues'])) {
                                    echo '<p>';
                                    echo sprintf(
                                        esc_html__('Your content has %d critical issues, %d warnings, and %d good aspects.', 'seo-advisor-woo'),
                                        $analysis['issues']['critical'],
                                        $analysis['issues']['warnings'],
                                        $analysis['issues']['good']
                                    );
                                    echo '</p>';
                                }
                            }
                            ?>
                        </div>
                    <?php else : ?>
                        <div class="seo-advisor-score-placeholder">
                            <p><?php echo esc_html__('Click "Analyze Content" to generate an SEO score.', 'seo-advisor-woo'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($seo_score) && isset($analysis['category_scores'])) : ?>
                <div class="seo-advisor-category-scores">
                    <h3><?php echo esc_html__('Category Scores', 'seo-advisor-woo'); ?></h3>
                    <div class="seo-advisor-category-grid">
                        <?php foreach ($analysis['category_scores'] as $category => $cat_score) : 
                            $cat_score_class = 'poor';
                            if ($cat_score >= 70 && $cat_score < 90) {
                                $cat_score_class = 'good';
                            } elseif ($cat_score >= 90) {
                                $cat_score_class = 'excellent';
                            }
                            
                            // Get category name
                            $category_name = ucfirst($category);
                            if ($category === 'meta') {
                                $category_name = __('Meta Tags', 'seo-advisor-woo');
                            } elseif ($category === 'content') {
                                $category_name = __('Content', 'seo-advisor-woo');
                            } elseif ($category === 'images') {
                                $category_name = __('Images', 'seo-advisor-woo');
                            } elseif ($category === 'technical') {
                                $category_name = __('Technical', 'seo-advisor-woo');
                            }
                        ?>
                            <div class="seo-advisor-category-item">
                                <div class="seo-advisor-category-score seo-advisor-score-<?php echo esc_attr($cat_score_class); ?>">
                                    <?php echo esc_html($cat_score); ?>
                                </div>
                                <div class="seo-advisor-category-name">
                                    <?php echo esc_html($category_name); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="seo-advisor-analysis-results">
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'seo_advisor_analysis';
                    
                    $analysis_data = $wpdb->get_var($wpdb->prepare(
                        "SELECT analysis_data FROM $table_name WHERE post_id = %d",
                        $post->ID
                    ));
                    
                    if ($analysis_data) {
                        $analysis = json_decode($analysis_data, true);
                        
                        if (isset($analysis['analysis_groups'])) {
                            // Create improvements list
                            if (!empty($post_analyzer)) {
                                $suggestions = $post_analyzer->get_improvement_suggestions($analysis, 5);
                                
                                if (!empty($suggestions)) {
                                    echo '<div class="seo-advisor-improvements">';
                                    echo '<h3>' . esc_html__('Top Improvements', 'seo-advisor-woo') . '</h3>';
                                    echo '<ul class="seo-advisor-improvements-list">';
                                    
                                    foreach ($suggestions as $suggestion) {
                                        $status_class = ($suggestion['status'] === 'critical') ? 'critical' : 'warning';
                                        
                                        echo '<li class="seo-advisor-improvement seo-advisor-' . esc_attr($status_class) . '">';
                                        echo '<div class="seo-advisor-improvement-header">';
                                        echo '<span class="seo-advisor-improvement-icon dashicons ' . ($suggestion['status'] === 'critical' ? 'dashicons-warning' : 'dashicons-flag') . '"></span>';
                                        echo '<h4>' . esc_html($suggestion['name']) . '</h4>';
                                        echo '</div>';
                                        echo '<div class="seo-advisor-improvement-content">';
                                        echo '<p class="seo-advisor-improvement-message">' . esc_html($suggestion['message']) . '</p>';
                                        if (!empty($suggestion['recommended_action'])) {
                                            echo '<p class="seo-advisor-improvement-action">';
                                            echo '<strong>' . esc_html__('Action:', 'seo-advisor-woo') . '</strong> ';
                                            echo esc_html($suggestion['recommended_action']);
                                            echo '</p>';
                                        }
                                        echo '</div>';
                                        echo '</li>';
                                    }
                                    
                                    echo '</ul>';
                                    echo '</div>';
                                }
                            }
                            
                            // Display critical issues first
                            $has_issues = false;
                            echo '<div class="seo-advisor-issues-section">';
                            echo '<h3 class="seo-advisor-issues-header seo-advisor-issues-critical">';
                            echo '<span class="dashicons dashicons-warning"></span>';
                            echo esc_html__('Critical Issues', 'seo-advisor-woo');
                            echo '</h3>';
                            echo '<div class="seo-advisor-issues-content">';
                            echo '<ul class="seo-advisor-issues seo-advisor-issues-critical">';
                            
                            foreach ($analysis['analysis_groups'] as $group_name => $group) {
                                foreach ($group as $check_key => $check) {
                                    if ($check['status'] === 'critical') {
                                        $has_issues = true;
                                        echo '<li class="seo-advisor-issue">';
                                        echo '<div class="seo-advisor-issue-title">';
                                        echo '<span class="seo-advisor-issue-icon dashicons dashicons-warning"></span>';
                                        echo '<strong>' . esc_html($check['name']) . '</strong>';
                                        echo '</div>';
                                        echo '<div class="seo-advisor-issue-message">' . esc_html($check['message']) . '</div>';
                                        if (!empty($check['recommended_action'])) {
                                            echo '<div class="seo-advisor-issue-action">';
                                            echo '<strong>' . esc_html__('Recommended Action:', 'seo-advisor-woo') . '</strong> ';
                                            echo esc_html($check['recommended_action']);
                                            echo '</div>';
                                        }
                                        echo '</li>';
                                    }
                                }
                            }
                            
                            if (!$has_issues) {
                                echo '<li class="seo-advisor-no-issues">';
                                echo '<span class="dashicons dashicons-yes-alt"></span> ';
                                echo esc_html__('No critical issues found!', 'seo-advisor-woo');
                                echo '</li>';
                            }
                            
                            echo '</ul>';
                            echo '</div>';
                            echo '</div>';
                            
                            // Display warnings
                            $has_warnings = false;
                            echo '<div class="seo-advisor-issues-section">';
                            echo '<h3 class="seo-advisor-issues-header seo-advisor-issues-warnings">';
                            echo '<span class="dashicons dashicons-flag"></span>';
                            echo esc_html__('Improvements', 'seo-advisor-woo');
                            echo '</h3>';
                            echo '<div class="seo-advisor-issues-content">';
                            echo '<ul class="seo-advisor-issues seo-advisor-issues-warnings">';
                            
                            foreach ($analysis['analysis_groups'] as $group_name => $group) {
                                foreach ($group as $check_key => $check) {
                                    if ($check['status'] === 'warning') {
                                        $has_warnings = true;
                                        echo '<li class="seo-advisor-issue">';
                                        echo '<div class="seo-advisor-issue-title">';
                                        echo '<span class="seo-advisor-issue-icon dashicons dashicons-flag"></span>';
                                        echo '<strong>' . esc_html($check['name']) . '</strong>';
                                        echo '</div>';
                                        echo '<div class="seo-advisor-issue-message">' . esc_html($check['message']) . '</div>';
                                        if (!empty($check['recommended_action'])) {
                                            echo '<div class="seo-advisor-issue-action">';
                                            echo '<strong>' . esc_html__('Recommended Action:', 'seo-advisor-woo') . '</strong> ';
                                            echo esc_html($check['recommended_action']);
                                            echo '</div>';
                                        }
                                        echo '</li>';
                                    }
                                }
                            }
                            
                            if (!$has_warnings) {
                                echo '<li class="seo-advisor-no-issues">';
                                echo '<span class="dashicons dashicons-yes-alt"></span> ';
                                echo esc_html__('No improvements needed!', 'seo-advisor-woo');
                                echo '</li>';
                            }
                            
                            echo '</ul>';
                            echo '</div>';
                            echo '</div>';
                            
                            // Display good aspects
                            $has_good = false;
                            echo '<div class="seo-advisor-issues-section">';
                            echo '<h3 class="seo-advisor-issues-header seo-advisor-issues-good">';
                            echo '<span class="dashicons dashicons-yes-alt"></span>';
                            echo esc_html__('Good Aspects', 'seo-advisor-woo');
                            echo '</h3>';
                            echo '<div class="seo-advisor-issues-content">';
                            echo '<ul class="seo-advisor-issues seo-advisor-issues-good">';
                            
                            foreach ($analysis['analysis_groups'] as $group_name => $group) {
                                foreach ($group as $check_key => $check) {
                                    if ($check['status'] === 'good') {
                                        $has_good = true;
                                        echo '<li class="seo-advisor-issue">';
                                        echo '<div class="seo-advisor-issue-title">';
                                        echo '<span class="seo-advisor-issue-icon dashicons dashicons-yes-alt"></span>';
                                        echo '<strong>' . esc_html($check['name']) . '</strong>';
                                        echo '</div>';
                                        echo '<div class="seo-advisor-issue-message">' . esc_html($check['message']) . '</div>';
                                        echo '</li>';
                                    }
                                }
                            }
                            
                            if (!$has_good) {
                                echo '<li class="seo-advisor-no-issues">';
                                echo esc_html__('No good aspects found yet. Improve your content to see positive results.', 'seo-advisor-woo');
                                echo '</li>';
                            }
                            
                            echo '</ul>';
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo '<div class="seo-advisor-no-data">';
                            echo '<p>' . esc_html__('No analysis data available. Click "Analyze Content" to generate SEO recommendations.', 'seo-advisor-woo') . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="seo-advisor-no-data">';
                        echo '<p>' . esc_html__('No analysis data available. Click "Analyze Content" to generate SEO recommendations.', 'seo-advisor-woo') . '</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Content Tab -->
            <div class="seo-advisor-tab-pane" id="seo-advisor-tab-content">
                <?php
                if ($analysis_data) {
                    $analysis = json_decode($analysis_data, true);
                    
                    if (isset($analysis['analysis_groups']['content'])) {
                        $content_analysis = $analysis['analysis_groups']['content'];
                        
                        echo '<div class="seo-advisor-content-analysis">';
                        
                        // Content length
                        if (isset($content_analysis['content_length'])) {
                            $check = $content_analysis['content_length'];
                            echo '<div class="seo-advisor-content-section">';
                            echo '<h3>' . esc_html__('Content Length', 'seo-advisor-woo') . '</h3>';
                            
                            // Extract word count from message
                            $word_count = 0;
                            if (preg_match('/\((\d+) words\)/', $check['message'], $matches)) {
                                $word_count = intval($matches[1]);
                            }
                            
                            echo '<div class="seo-advisor-word-count-meter">';
                            
                            // Word count bar
                            $percentage = min(100, ($word_count / 1000) * 100);
                            echo '<div class="seo-advisor-word-count-bar">';
                            echo '<div class="seo-advisor-word-count-progress seo-advisor-score-' . esc_attr($check['status']) . '" style="width: ' . esc_attr($percentage) . '%"></div>';
                            echo '</div>';
                            
                            // Word count markers
                            echo '<div class="seo-advisor-word-count-markers">';
                            echo '<span>0</span>';
                            echo '<span>300</span>';
                            echo '<span>600</span>';
                            echo '<span>1000+</span>';
                            echo '</div>';
                            
                            echo '</div>';
                            
                            echo '<div class="seo-advisor-content-status seo-advisor-status-' . esc_attr($check['status']) . '">';
                            echo '<span class="dashicons ' . ($check['status'] === 'good' ? 'dashicons-yes-alt' : 'dashicons-warning') . '"></span>';
                            echo esc_html($check['message']);
                            echo '</div>';
                            
                            if (!empty($check['recommended_action'])) {
                                echo '<div class="seo-advisor-content-recommendation">';
                                echo esc_html($check['recommended_action']);
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        
                        // Keyword density
                        if (isset($content_analysis['keyword_density'])) {
                            $check = $content_analysis['keyword_density'];
                            echo '<div class="seo-advisor-content-section">';
                            echo '<h3>' . esc_html__('Keyword Density', 'seo-advisor-woo') . '</h3>';
                            
                            // Extract density from message
                            $density = 0;
                            if (preg_match('/\(([\d.]+)%\)/', $check['message'], $matches)) {
                                $density = floatval($matches[1]);
                            }
                            
                            echo '<div class="seo-advisor-density-meter">';
                            echo '<div class="seo-advisor-density-bar">';
                            
                            // Mark ideal range (0.5% - 2.5%)
                            echo '<div class="seo-advisor-density-ideal"></div>';
                            
                            // Current position
                            $position = min(100, ($density / 5) * 100);
                            echo '<div class="seo-advisor-density-marker" style="left: ' . esc_attr($position) . '%"></div>';
                            
                            echo '</div>';
                            
                            // Density markers
                            echo '<div class="seo-advisor-density-markers">';
                            echo '<span>0%</span>';
                            echo '<span>0.5%</span>';
                            echo '<span>2.5%</span>';
                            echo '<span>5%</span>';
                            echo '</div>';
                            
                            echo '</div>';
                            
                            echo '<div class="seo-advisor-content-status seo-advisor-status-' . esc_attr($check['status']) . '">';
                            echo '<span class="dashicons ' . ($check['status'] === 'good' ? 'dashicons-yes-alt' : 'dashicons-warning') . '"></span>';
                            echo esc_html($check['message']);
                            echo '</div>';
                            
                            if (!empty($check['recommended_action'])) {
                                echo '<div class="seo-advisor-content-recommendation">';
                                echo esc_html($check['recommended_action']);
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        
                        // Headings structure
                        if (isset($content_analysis['headings'])) {
                            $check = $content_analysis['headings'];
                            echo '<div class="seo-advisor-content-section">';
                            echo '<h3>' . esc_html__('Heading Structure', 'seo-advisor-woo') . '</h3>';
                            
                            echo '<div class="seo-advisor-content-status seo-advisor-status-' . esc_attr($check['status']) . '">';
                            echo '<span class="dashicons ' . ($check['status'] === 'good' ? 'dashicons-yes-alt' : 'dashicons-warning') . '"></span>';
                            echo esc_html($check['message']);
                            echo '</div>';
                            
                            if (!empty($check['recommended_action'])) {
                                echo '<div class="seo-advisor-content-recommendation">';
                                echo esc_html($check['recommended_action']);
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        
                        // Readability
                        if (isset($content_analysis['readability'])) {
                            $check = $content_analysis['readability'];
                            echo '<div class="seo-advisor-content-section">';
                            echo '<h3>' . esc_html__('Readability', 'seo-advisor-woo') . '</h3>';
                            
                            // Extract readability score from message
                            $readability_score = 0;
                            if (preg_match('/score: (\d+)/', $check['message'], $matches)) {
                                $readability_score = intval($matches[1]);
                            }
                            
                            echo '<div class="seo-advisor-readability-meter">';
                            echo '<div class="seo-advisor-readability-score seo-advisor-status-' . esc_attr($check['status']) . '">' . esc_html($readability_score) . '</div>';
                            
                            echo '<div class="seo-advisor-readability-scale">';
                            echo '<div class="seo-advisor-readability-range seo-advisor-readability-difficult">0-50: ' . esc_html__('Difficult', 'seo-advisor-woo') . '</div>';
                            echo '<div class="seo-advisor-readability-range seo-advisor-readability-ok">51-70: ' . esc_html__('OK', 'seo-advisor-woo') . '</div>';
                            echo '<div class="seo-advisor-readability-range seo-advisor-readability-easy">71-100: ' . esc_html__('Easy', 'seo-advisor-woo') . '</div>';
                            echo '</div>';
                            
                            echo '</div>';
                            
                            echo '<div class="seo-advisor-content-status seo-advisor-status-' . esc_attr($check['status']) . '">';
                            echo '<span class="dashicons ' . ($check['status'] === 'good' ? 'dashicons-yes-alt' : 'dashicons-warning') . '"></span>';
                            echo esc_html($check['message']);
                            echo '</div>';
                            
                            if (!empty($check['recommended_action'])) {
                                echo '<div class="seo-advisor-content-recommendation">';
                                echo esc_html($check['recommended_action']);
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        
                        // Paragraph length
                        if (isset($content_analysis['paragraph_length'])) {
                            $check = $content_analysis['paragraph_length'];
                            echo '<div class="seo-advisor-content-section">';
                            echo '<h3>' . esc_html__('Paragraph Length', 'seo-advisor-woo') . '</h3>';
                            
                            echo '<div class="seo-advisor-content-status seo-advisor-status-' . esc_attr($check['status']) . '">';
                            echo '<span class="dashicons ' . ($check['status'] === 'good' ? 'dashicons-yes-alt' : 'dashicons-warning') . '"></span>';
                            echo esc_html($check['message']);
                            echo '</div>';
                            
                            if (!empty($check['recommended_action'])) {
                                echo '<div class="seo-advisor-content-recommendation">';
                                echo esc_html($check['recommended_action']);
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        
                        // Links analysis
                        if (isset($content_analysis['internal_links']) || isset($content_analysis['external_links'])) {
                            echo '<div class="seo-advisor-content-section">';
                            echo '<h3>' . esc_html__('Links Analysis', 'seo-advisor-woo') . '</h3>';
                            
                            echo '<div class="seo-advisor-links-container">';
                            
                            // Internal links
                            if (isset($content_analysis['internal_links'])) {
                                $check = $content_analysis['internal_links'];
                                
                                // Extract link count
                                $internal_count = 0;
                                if (preg_match('/has (\d+) internal links/', $check['message'], $matches)) {
                                    $internal_count = intval($matches[1]);
                                }
                                
                                echo '<div class="seo-advisor-links-item">';
                                echo '<div class="seo-advisor-links-count">' . esc_html($internal_count) . '</div>';
                                echo '<div class="seo-advisor-links-label">' . esc_html__('Internal Links', 'seo-advisor-woo') . '</div>';
                                echo '<div class="seo-advisor-links-status seo-advisor-status-' . esc_attr($check['status']) . '">';
                                echo '<span class="dashicons ' . ($check['status'] === 'good' ? 'dashicons-yes-alt' : 'dashicons-warning') . '"></span>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            // External links
                            if (isset($content_analysis['external_links'])) {
                                $check = $content_analysis['external_links'];
                                
                                // Extract link count
                                $external_count = 0;
                                if (preg_match('/has (\d+) external links/', $check['message'], $matches)) {
                                    $external_count = intval($matches[1]);
                                }
                                
                                echo '<div class="seo-advisor-links-item">';
                                echo '<div class="seo-advisor-links-count">' . esc_html($external_count) . '</div>';
                                echo '<div class="seo-advisor-links-label">' . esc_html__('External Links', 'seo-advisor-woo') . '</div>';
                                echo '<div class="seo-advisor-links-status seo-advisor-status-' . esc_attr($check['status']) . '">';
                                echo '<span class="dashicons ' . ($check['status'] === 'good' ? 'dashicons-yes-alt' : 'dashicons-warning') . '"></span>';
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                            
                            if (isset($content_analysis['internal_links']) && !empty($content_analysis['internal_links']['recommended_action'])) {
                                echo '<div class="seo-advisor-content-recommendation">';
                                echo esc_html($content_analysis['internal_links']['recommended_action']);
                                echo '</div>';
                            }
                            
                            if (isset($content_analysis['external_links']) && !empty($content_analysis['external_links']['recommended_action'])) {
                                echo '<div class="seo-advisor-content-recommendation">';
                                echo esc_html($content_analysis['external_links']['recommended_action']);
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        
                        echo '</div>';
                    } else {
                        echo '<div class="seo-advisor-no-data">';
                        echo '<p>' . esc_html__('No content analysis data available. Click "Analyze Content" to generate recommendations.', 'seo-advisor-woo') . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="seo-advisor-no-data">';
                    echo '<p>' . esc_html__('No analysis data available. Click "Analyze Content" to generate recommendations.', 'seo-advisor-woo') . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- Technical Tab -->
            <div class="seo-advisor-tab-pane" id="seo-advisor-tab-technical">
                <?php
                if ($analysis_data) {
                    $analysis = json_decode($analysis_data, true);
                    
                    if (isset($analysis['analysis_groups']['technical']) || isset($analysis['analysis_groups']['images'])) {
                        echo '<div class="seo-advisor-technical-analysis">';
                        
                        // Technical SEO section
                        if (isset($analysis['analysis_groups']['technical'])) {
                            $technical_analysis = $analysis['analysis_groups']['technical'];
                            
                            echo '<div class="seo-advisor-tech-section">';
                            echo '<h3>' . esc_html__('Technical SEO', 'seo-advisor-woo') . '</h3>';
                            
                            foreach ($technical_analysis as $check_key => $check) {
                                echo '<div class="seo-advisor-tech-item">';
                                echo '<div class="seo-advisor-tech-header">';
                                echo '<h4>' . esc_html($check['name']) . '</h4>';
                                echo '<div class="seo-advisor-tech-status seo-advisor-status-' . esc_attr($check['status']) . '">';
                                
                                if ($check['status'] === 'good') {
                                    echo '<span class="dashicons dashicons-yes-alt"></span>';
                                } elseif ($check['status'] === 'warning') {
                                    echo '<span class="dashicons dashicons-flag"></span>';
                                } else {
                                    echo '<span class="dashicons dashicons-warning"></span>';
                                }
                                
                                echo '</div>';
                                echo '</div>';
                                
                                echo '<div class="seo-advisor-tech-content">';
                                echo '<p>' . esc_html($check['message']) . '</p>';
                                
                                if (!empty($check['recommended_action'])) {
                                    echo '<div class="seo-advisor-tech-recommendation">';
                                    echo '<strong>' . esc_html__('Recommended Action:', 'seo-advisor-woo') . '</strong> ';
                                    echo esc_html($check['recommended_action']);
                                    echo '</div>';
                                }
                                
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        
                        // Images section
                        if (isset($analysis['analysis_groups']['images'])) {
                            $image_analysis = $analysis['analysis_groups']['images'];
                            
                            echo '<div class="seo-advisor-tech-section">';
                            echo '<h3>' . esc_html__('Images', 'seo-advisor-woo') . '</h3>';
                            
                            foreach ($image_analysis as $check_key => $check) {
                                echo '<div class="seo-advisor-tech-item">';
                                echo '<div class="seo-advisor-tech-header">';
                                echo '<h4>' . esc_html($check['name']) . '</h4>';
                                echo '<div class="seo-advisor-tech-status seo-advisor-status-' . esc_attr($check['status']) . '">';
                                
                                if ($check['status'] === 'good') {
                                    echo '<span class="dashicons dashicons-yes-alt"></span>';
                                } elseif ($check['status'] === 'warning') {
                                    echo '<span class="dashicons dashicons-flag"></span>';
                                } else {
                                    echo '<span class="dashicons dashicons-warning"></span>';
                                }
                                
                                echo '</div>';
                                echo '</div>';
                                
                                echo '<div class="seo-advisor-tech-content">';
                                echo '<p>' . esc_html($check['message']) . '</p>';
                                
                                if (!empty($check['recommended_action'])) {
                                    echo '<div class="seo-advisor-tech-recommendation">';
                                    echo '<strong>' . esc_html__('Recommended Action:', 'seo-advisor-woo') . '</strong> ';
                                    echo esc_html($check['recommended_action']);
                                    echo '</div>';
                                }
                                
                                echo '</div>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        
                        echo '</div>';
                    } else {
                        echo '<div class="seo-advisor-no-data">';
                        echo '<p>' . esc_html__('No technical analysis data available. Click "Analyze Content" to generate recommendations.', 'seo-advisor-woo') . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="seo-advisor-no-data">';
                    echo '<p>' . esc_html__('No analysis data available. Click "Analyze Content" to generate recommendations.', 'seo-advisor-woo') . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="seo-advisor-coming-soon">
        <h4>
            <span class="dashicons dashicons-admin-customizer"></span>
            <?php echo esc_html__('Coming Soon: AI Content Generator', 'seo-advisor-woo'); ?>
        </h4>
        <p><?php echo esc_html__('Generate SEO-optimized content with AI assistance. Our AI will suggest improvements to your content and help you rank higher.', 'seo-advisor-woo'); ?></p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.seo-advisor-tab-link').on('click', function(e) {
        e.preventDefault();
        
        // Get the tab ID
        var tabId = $(this).data('tab');
        
        // Update active tab link
        $('.seo-advisor-tab-link').removeClass('active');
        $(this).addClass('active');
        
        // Show the selected tab content
        $('.seo-advisor-tab-pane').removeClass('active');
        $('#seo-advisor-tab-' + tabId).addClass('active');
    });
    
    // Tooltips
    $('.seo-advisor-tooltip').hover(
        function() {
            var tooltip = $(this);
            var tooltipText = tooltip.data('tooltip');
            
            // Create tooltip element if it doesn't exist
            if ($('#seo-advisor-tooltip-popup').length === 0) {
                $('body').append('<div id="seo-advisor-tooltip-popup"></div>');
            }
            
            // Position and show tooltip
            var tooltipPopup = $('#seo-advisor-tooltip-popup');
            tooltipPopup.html(tooltipText);
            
            var offset = tooltip.offset();
            tooltipPopup.css({
                top: offset.top - tooltipPopup.outerHeight() - 10,
                left: offset.left - (tooltipPopup.outerWidth() / 2) + (tooltip.outerWidth() / 2)
            }).show();
        },
        function() {
            // Hide tooltip
            $('#seo-advisor-tooltip-popup').hide();
        }
    );
    
    // Accordion for issues sections
    $('.seo-advisor-issues-header').on('click', function() {
        var content = $(this).next('.seo-advisor-issues-content');
        content.slideToggle(200);
        $(this).toggleClass('collapsed');
    });
    
    // Analyze button functionality
    $('#seo_advisor_analyze_button').on('click', function() {
        var button = $(this);
        var spinner = button.next('.spinner');
        
        // Show spinner
        spinner.css('visibility', 'visible');
        button.prop('disabled', true);
        
        // AJAX request for analysis
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'seo_advisor_analyze_post',
                post_id: <?php echo intval($post->ID); ?>,
                nonce: seo_advisor.nonce,
                focus_keyword: $('#seo_advisor_focus_keyword').val(),
                secondary_keywords: $('#seo_advisor_secondary_keywords').val()
            },
            success: function(response) {
                // Hide spinner
                spinner.css('visibility', 'hidden');
                button.prop('disabled', false);
                
                if (response.success) {
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>' + seo_advisor.analyze_complete + '</p></div>')
                        .insertAfter(button)
                        .delay(3000)
                        .fadeOut(400, function() {
                            $(this).remove();
                        });
                    
                    // Reload the page to show updated analysis
                    location.reload();
                } else {
                    // Show error message
                    $('<div class="notice notice-error is-dismissible"><p>' + (response.data || seo_advisor.analyze_error) + '</p></div>')
                        .insertAfter(button)
                        .delay(3000)
                        .fadeOut(400, function() {
                            $(this).remove();
                        });
                }
            },
            error: function() {
                // Hide spinner
                spinner.css('visibility', 'hidden');
                button.prop('disabled', false);
                
                // Show error message
                $('<div class="notice notice-error is-dismissible"><p>' + seo_advisor.analyze_error + '</p></div>')
                    .insertAfter(button)
                    .delay(3000)
                    .fadeOut(400, function() {
                        $(this).remove();
                    });
            }
        });
    });
});
</script>

<style>
/* Main metabox */
.seo-advisor-metabox {
    margin: -6px -12px -12px -12px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
}

/* Tabs navigation */
.seo-advisor-tab-nav {
    display: flex;
    background-color: #f0f0f1;
    border-bottom: 1px solid #c3c4c7;
    position: relative;
}

.seo-advisor-tab-link {
    padding: 12px 15px;
    text-decoration: none;
    color: #646970;
    font-weight: 500;
    position: relative;
    transition: all 0.2s ease;
}

.seo-advisor-tab-link:hover {
    color: #2271b1;
    background-color: rgba(255, 255, 255, 0.5);
}

.seo-advisor-tab-link.active {
    background-color: #fff;
    color: #2271b1;
    font-weight: 600;
}

.seo-advisor-tab-link.active:after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: #2271b1;
}

/* Score display in tabs */
.seo-advisor-score {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 4px;
    color: #fff;
    font-weight: bold;
}

.seo-advisor-score-text {
    font-size: 12px;
    margin-left: 5px;
    display: none;
}

.seo-advisor-score:hover .seo-advisor-score-text {
    display: inline;
}

.seo-advisor-score-poor {
    background-color: #d63638;
}

.seo-advisor-score-good {
    background-color: #2271b1;
}

.seo-advisor-score-excellent {
    background-color: #00a32a;
}

/* Tab content */
.seo-advisor-tab-content {
    padding: 20px;
    background: #fff;
}

.seo-advisor-tab-pane {
    display: none;
}

.seo-advisor-tab-pane.active {
    display: block;
}

/* Form fields */
.seo-advisor-field {
    margin-bottom: 20px;
}

.seo-advisor-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #1d2327;
}

.seo-advisor-tooltip {
    display: inline-block;
    width: 16px;
    height: 16px;
    background: #ddd;
    color: #444;
    border-radius: 50%;
    text-align: center;
    line-height: 16px;
    font-size: 12px;
    margin-left: 5px;
    cursor: help;
}

#seo-advisor-tooltip-popup {
    position: absolute;
    background: #333;
    color: #fff;
    padding: 10px;
    border-radius: 3px;
    font-size: 12px;
    max-width: 250px;
    z-index: 9999;
    display: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

#seo-advisor-tooltip-popup:after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 5px solid #333;
}

/* Action buttons */
.seo-advisor-actions {
    margin-top: 20px;
    display: flex;
    align-items: center;
}

.seo-advisor-actions .button {
    display: flex;
    align-items: center;
    padding: 6px 12px;
}

.seo-advisor-actions .button .dashicons {
    margin-right: 5px;
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.seo-advisor-actions .spinner {
    float: none;
    margin-top: 0;
    margin-left: 10px;
}

.seo-advisor-last-updated {
    margin-left: auto;
    font-style: italic;
    color: #646970;
    font-size: 12px;
    display: flex;
    align-items: center;
}

.seo-advisor-last-updated .dashicons {
    margin-right: 5px;
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Overall score */
.seo-advisor-overall-score {
    display: flex;
    margin-bottom: 25px;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 5px;
    align-items: center;
}

.seo-advisor-score-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
    color: #fff;
    font-weight: bold;
    font-size: 28px;
    box-shadow: 0 3px 5px rgba(0,0,0,0.1);
}

.seo-advisor-score-info {
    flex: 1;
}

.seo-advisor-score-info h3 {
    margin-top: 0;
    margin-bottom: 5px;
}

.seo-advisor-score-info p {
    margin: 0;
    color: #646970;
}

.seo-advisor-score-placeholder {
    text-align: center;
    padding: 30px;
    background: #f9f9f9;
    border-radius: 5px;
}

/* Category scores */
.seo-advisor-category-scores {
    margin-bottom: 25px;
}

.seo-advisor-category-scores h3 {
    margin-top: 0;
    margin-bottom: 15px;
}

.seo-advisor-category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
}

.seo-advisor-category-item {
    text-align: center;
}

.seo-advisor-category-score {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    margin: 0 auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: bold;
    font-size: 20px;
}

.seo-advisor-category-name {
    font-size: 13px;
    font-weight: 500;
}

/* Improvements */
.seo-advisor-improvements {
    margin-bottom: 25px;
}

.seo-advisor-improvements h3 {
    margin-top: 0;
    margin-bottom: 15px;
}

.seo-advisor-improvements-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.seo-advisor-improvement {
    margin-bottom: 10px;
    padding: 15px;
    border-radius: 4px;
    background-color: #fff;
    border: 1px solid #ddd;
}

.seo-advisor-improvement.seo-advisor-critical {
    border-left: 4px solid #d63638;
}

.seo-advisor-improvement.seo-advisor-warning {
    border-left: 4px solid #dba617;
}

.seo-advisor-improvement-header {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.seo-advisor-improvement-icon {
    margin-right: 8px;
    color: #d63638;
}

.seo-advisor-improvement.seo-advisor-warning .seo-advisor-improvement-icon {
    color: #dba617;
}

.seo-advisor-improvement-header h4 {
    margin: 0;
    font-size: 14px;
}

.seo-advisor-improvement-content p {
    margin: 0 0 8px;
    color: #646970;
}

.seo-advisor-improvement-action {
    font-size: 13px;
    color: #1d2327;
    background-color: #f0f0f0;
    padding: 8px;
    border-radius: 3px;
    margin-top: 8px;
}

/* Issues sections */
.seo-advisor-issues-section {
    margin-bottom: 20px;
}

.seo-advisor-issues-header {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    background-color: #f0f0f1;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    position: relative;
}

.seo-advisor-issues-header:after {
    content: '\f140';
    font-family: dashicons;
    position: absolute;
    right: 15px;
    transition: transform 0.2s ease;
}

.seo-advisor-issues-header.collapsed:after {
    transform: rotate(-90deg);
}

.seo-advisor-issues-header .dashicons {
    margin-right: 8px;
}

.seo-advisor-issues-critical {
    color: #d63638;
}

.seo-advisor-issues-warnings {
    color: #dba617;
}

.seo-advisor-issues-good {
    color: #00a32a;
}

.seo-advisor-issues {
    margin: 0;
    padding: 0 0 0 10px;
    list-style: none;
}

.seo-advisor-issue {
    margin-top: 10px;
    padding: 15px;
    border-radius: 4px;
    background-color: #f9f9f9;
}

.seo-advisor-issue-title {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.seo-advisor-issue-icon {
    margin-right: 5px;
}

.seo-advisor-issues-critical .seo-advisor-issue-icon {
    color: #d63638;
}

.seo-advisor-issues-warnings .seo-advisor-issue-icon {
    color: #dba617;
}

.seo-advisor-issues-good .seo-advisor-issue-icon {
    color: #00a32a;
}

.seo-advisor-issue-message {
    color: #646970;
    font-size: 13px;
    margin-bottom: 5px;
}

.seo-advisor-issue-action {
    font-size: 12px;
    background-color: #f0f0f0;
    padding: 8px;
    border-radius: 3px;
    margin-top: 5px;
}

.seo-advisor-no-issues {
    padding: 10px 15px;
    background-color: #f9f9f9;
    border-radius: 4px;
    color: #646970;
    font-style: italic;
    margin-top: 10px;
}

.seo-advisor-no-data {
    padding: 30px;
    text-align: center;
    background-color: #f9f9f9;
    border-radius: 4px;
    color: #646970;
}

/* Content Tab */
.seo-advisor-content-section {
    margin-bottom: 25px;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 4px;
}

.seo-advisor-content-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 15px;
}

.seo-advisor-content-status {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.seo-advisor-content-status .dashicons {
    margin-right: 8px;
}

.seo-advisor-status-good {
    color: #00a32a;
}

.seo-advisor-status-warning {
    color: #dba617;
}

.seo-advisor-status-critical {
    color: #d63638;
}

.seo-advisor-content-recommendation {
    background-color: #f0f0f0;
    padding: 10px;
    border-radius: 3px;
    font-size: 13px;
    margin-top: 10px;
}

/* Word count meter */
.seo-advisor-word-count-meter {
    margin: 15px 0;
}

.seo-advisor-word-count-bar {
    height: 10px;
    background-color: #f0f0f0;
    border-radius: 5px;
    margin-bottom: 5px;
    overflow: hidden;
}

.seo-advisor-word-count-progress {
    height: 100%;
}

.seo-advisor-word-count-markers {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #646970;
}

/* Density meter */
.seo-advisor-density-meter {
    margin: 15px 0;
    position: relative;
}

.seo-advisor-density-bar {
    height: 10px;
    background-color: #f0f0f0;
    border-radius: 5px;
    margin-bottom: 5px;
    position: relative;
}

.seo-advisor-density-ideal {
    position: absolute;
    left: 10%;
    width: 40%;
    height: 100%;
    background-color: rgba(0, 163, 42, 0.2);
    border-radius: 5px;
}

.seo-advisor-density-marker {
    position: absolute;
    width: 10px;
    height: 10px;
    background-color: #2271b1;
    border-radius: 50%;
    top: 0;
    transform: translateX(-50%);
}

.seo-advisor-density-markers {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #646970;
}

/* Readability meter */
.seo-advisor-readability-meter {
    margin: 15px 0;
    text-align: center;
}

.seo-advisor-readability-score {
    display: inline-block;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #2271b1;
    color: #fff;
    font-weight: bold;
    font-size: 20px;
    line-height: 60px;
    margin-bottom: 10px;
}

.seo-advisor-readability-scale {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    font-size: 12px;
}

.seo-advisor-readability-range {
    padding: 3px 8px;
    border-radius: 3px;
    background-color: #f0f0f0;
}

.seo-advisor-readability-difficult {
    color: #d63638;
}

.seo-advisor-readability-ok {
    color: #dba617;
}

.seo-advisor-readability-easy {
    color: #00a32a;
}

/* Links analysis */
.seo-advisor-links-container {
    display: flex;
    gap: 20px;
    margin-bottom: 10px;
}

.seo-advisor-links-item {
    flex: 1;
    text-align: center;
    background-color: #fff;
    padding: 15px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    position: relative;
}

.seo-advisor-links-count {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.seo-advisor-links-label {
    font-size: 13px;
    color: #646970;
}

.seo-advisor-links-status {
    position: absolute;
    top: 5px;
    right: 5px;
}

/* Technical tab */
.seo-advisor-tech-section {
    margin-bottom: 25px;
}

.seo-advisor-tech-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
}

.seo-advisor-tech-item {
    margin-bottom: 15px;
    background-color: #f9f9f9;
    border-radius: 4px;
    padding: 15px;
}

.seo-advisor-tech-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.seo-advisor-tech-header h4 {
    margin: 0;
    font-size: 14px;
}

.seo-advisor-tech-content p {
    margin-top: 0;
    color: #646970;
}

.seo-advisor-tech-recommendation {
    background-color: #f0f0f0;
    padding: 10px;
    border-radius: 3px;
    font-size: 13px;
    margin-top: 10px;
}

/* Coming soon promo */
.seo-advisor-coming-soon {
    margin-top: 20px;
    padding: 15px;
    background-color: #f0f6fc;
    border-left: 4px solid #72aee6;
    color: #2c3338;
    display: flex;
    flex-direction: column;
}

.seo-advisor-coming-soon h4 {
    margin: 0 0 5px 0;
    font-size: 15px;
    display: flex;
    align-items: center;
}

.seo-advisor-coming-soon h4 .dashicons {
    margin-right: 8px;
    color: #72aee6;
}

.seo-advisor-coming-soon p {
    margin: 0;
    color: #646970;
}
</style>
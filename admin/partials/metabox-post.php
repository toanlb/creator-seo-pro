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
            <?php if (!empty($seo_score)) : ?>
                <?php 
                $score_class = 'poor';
                if ($seo_score >= 70 && $seo_score < 90) {
                    $score_class = 'good';
                } elseif ($seo_score >= 90) {
                    $score_class = 'excellent';
                }
                ?>
                <div class="seo-advisor-score seo-advisor-score-<?php echo esc_attr($score_class); ?>">
                    <?php echo esc_html($seo_score); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="seo-advisor-tab-content">
            <div class="seo-advisor-tab-pane active" id="seo-advisor-tab-keywords">
                <div class="seo-advisor-field">
                    <label for="seo_advisor_focus_keyword"><?php echo esc_html__('Focus Keyword', 'seo-advisor-woo'); ?></label>
                    <input type="text" id="seo_advisor_focus_keyword" name="seo_advisor_focus_keyword" value="<?php echo esc_attr($focus_keyword); ?>" class="widefat">
                    <p class="description"><?php echo esc_html__('Enter the main keyword you want to rank for with this content.', 'seo-advisor-woo'); ?></p>
                </div>
                
                <div class="seo-advisor-field">
                    <label for="seo_advisor_secondary_keywords"><?php echo esc_html__('Secondary Keywords', 'seo-advisor-woo'); ?></label>
                    <textarea id="seo_advisor_secondary_keywords" name="seo_advisor_secondary_keywords" class="widefat" rows="3"><?php echo esc_textarea($secondary_keywords); ?></textarea>
                    <p class="description"><?php echo esc_html__('Enter secondary keywords separated by commas.', 'seo-advisor-woo'); ?></p>
                </div>
                
                <div class="seo-advisor-actions">
                    <button type="button" class="button button-primary" id="seo_advisor_analyze_button"><?php echo esc_html__('Analyze Content', 'seo-advisor-woo'); ?></button>
                    <span class="spinner"></span>
                </div>
                
                <?php if (!empty($last_updated)) : ?>
                    <div class="seo-advisor-last-updated">
                        <?php echo esc_html__('Last analyzed:', 'seo-advisor-woo'); ?>
                        <?php echo esc_html(human_time_diff(strtotime($last_updated), current_time('timestamp'))); ?>
                        <?php echo esc_html__('ago', 'seo-advisor-woo'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="seo-advisor-tab-pane" id="seo-advisor-tab-analysis">
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
                            // Display critical issues first
                            $has_issues = false;
                            echo '<div class="seo-advisor-issues-section">';
                            echo '<h4>' . esc_html__('Critical Issues', 'seo-advisor-woo') . '</h4>';
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
                                        echo '</li>';
                                    }
                                }
                            }
                            
                            if (!$has_issues) {
                                echo '<li class="seo-advisor-no-issues">' . esc_html__('No critical issues found.', 'seo-advisor-woo') . '</li>';
                            }
                            
                            echo '</ul>';
                            echo '</div>';
                            
                            // Display warnings
                            $has_warnings = false;
                            echo '<div class="seo-advisor-issues-section">';
                            echo '<h4>' . esc_html__('Improvements', 'seo-advisor-woo') . '</h4>';
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
                                        echo '</li>';
                                    }
                                }
                            }
                            
                            if (!$has_warnings) {
                                echo '<li class="seo-advisor-no-issues">' . esc_html__('No improvements needed.', 'seo-advisor-woo') . '</li>';
                            }
                            
                            echo '</ul>';
                            echo '</div>';
                            
                            // Display good aspects
                            $has_good = false;
                            echo '<div class="seo-advisor-issues-section">';
                            echo '<h4>' . esc_html__('Good Aspects', 'seo-advisor-woo') . '</h4>';
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
                                echo '<li class="seo-advisor-no-issues">' . esc_html__('No good aspects found yet. Improve your content to see positive results.', 'seo-advisor-woo') . '</li>';
                            }
                            
                            echo '</ul>';
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
        </div>
    </div>
    
    <div class="seo-advisor-coming-soon">
        <h4><?php echo esc_html__('Coming Soon: AI Content Generator', 'seo-advisor-woo'); ?></h4>
        <p><?php echo esc_html__('Generate SEO-optimized content with AI assistance.', 'seo-advisor-woo'); ?></p>
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
                    // Reload the page to show updated analysis
                    location.reload();
                } else {
                    alert(response.data || 'Error analyzing content.');
                }
            },
            error: function() {
                // Hide spinner
                spinner.css('visibility', 'hidden');
                button.prop('disabled', false);
                
                alert('Error connecting to the server.');
            }
        });
    });
});
</script>

<style>
.seo-advisor-metabox {
    margin: -6px -12px -12px -12px;
}

.seo-advisor-tab-nav {
    display: flex;
    background-color: #f0f0f1;
    border-bottom: 1px solid #c3c4c7;
    position: relative;
}

.seo-advisor-tab-link {
    padding: 10px 15px;
    text-decoration: none;
    color: #646970;
    font-weight: 500;
}

.seo-advisor-tab-link.active {
    background-color: #fff;
    color: #2271b1;
    border-bottom: 2px solid #2271b1;
    margin-bottom: -1px;
}

.seo-advisor-tab-content {
    padding: 15px;
}

.seo-advisor-tab-pane {
    display: none;
}

.seo-advisor-tab-pane.active {
    display: block;
}

.seo-advisor-field {
    margin-bottom: 15px;
}

.seo-advisor-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.seo-advisor-actions {
    margin-top: 15px;
    display: flex;
    align-items: center;
}

.seo-advisor-actions .spinner {
    float: none;
    margin-top: 0;
    margin-left: 5px;
}

.seo-advisor-last-updated {
    margin-top: 10px;
    font-style: italic;
    color: #646970;
    font-size: 12px;
}

.seo-advisor-issues-section {
    margin-bottom: 20px;
}

.seo-advisor-issues {
    margin: 0;
    padding: 0;
    list-style: none;
}

.seo-advisor-issue {
    padding: 10px;
    border-radius: 3px;
    margin-bottom: 5px;
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
}

.seo-advisor-no-data {
    padding: 20px;
    text-align: center;
    background-color: #f9f9f9;
    border-radius: 3px;
}

.seo-advisor-no-issues {
    padding: 10px;
    color: #646970;
    font-style: italic;
}

.seo-advisor-score {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    color: #fff;
    font-weight: bold;
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

.seo-advisor-coming-soon {
    margin-top: 20px;
    padding: 10px 15px;
    background-color: #f0f6fc;
    border-left: 4px solid #72aee6;
    color: #2c3338;
}

.seo-advisor-coming-soon h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.seo-advisor-coming-soon p {
    margin: 0;
    color: #646970;
}
</style>
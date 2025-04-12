<?php
/**
 * Admin dashboard display.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/admin/partials
 */
?>

<div class="wrap seo-advisor-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    // Get settings
    $general_settings = get_option('seo_advisor_general_settings', array());
    $post_types = isset($general_settings['post_types']) ? $general_settings['post_types'] : array('post', 'page');
    
    // Add product to post types if WooCommerce integration is enabled
    if (seo_advisor_is_woocommerce_active() && 
        (isset($general_settings['woocommerce_integration']) && $general_settings['woocommerce_integration'] === 'yes')) {
        $post_types[] = 'product';
    }
    
    // Get stats
    global $wpdb;
    $table_name = $wpdb->prefix . 'seo_advisor_analysis';
    
    // Count analyzed content
    $analyzed_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    
    // Average SEO score
    $avg_score = $wpdb->get_var("SELECT AVG(seo_score) FROM $table_name");
    $avg_score = $avg_score ? round($avg_score) : 0;
    
    // Content with issues
    $issues_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE seo_score < 70");
    
    // Recent content
    $recent_items = $wpdb->get_results(
        "SELECT a.post_id, a.post_type, a.seo_score, a.updated_at, p.post_title 
         FROM $table_name AS a 
         JOIN {$wpdb->posts} AS p ON a.post_id = p.ID 
         WHERE p.post_status = 'publish' 
         ORDER BY a.updated_at DESC 
         LIMIT 10"
    );
    ?>
    
    <div class="seo-advisor-dashboard-header">
        <div class="seo-advisor-version">
            <span><?php echo esc_html__('Version:', 'seo-advisor-woo'); ?> <?php echo SEO_ADVISOR_VERSION; ?></span>
        </div>
    </div>
    
    <div class="seo-advisor-dashboard-widgets">
        <!-- Stats Overview -->
        <div class="seo-advisor-widget seo-advisor-stats">
            <h2><?php echo esc_html__('SEO Overview', 'seo-advisor-woo'); ?></h2>
            <div class="seo-advisor-stats-grid">
                <div class="seo-advisor-stat-box">
                    <div class="seo-advisor-stat-icon">
                        <span class="dashicons dashicons-analytics"></span>
                    </div>
                    <div class="seo-advisor-stat-content">
                        <h3><?php echo esc_html__('Analyzed Content', 'seo-advisor-woo'); ?></h3>
                        <div class="seo-advisor-stat-value"><?php echo esc_html($analyzed_count); ?></div>
                    </div>
                </div>
                
                <div class="seo-advisor-stat-box">
                    <div class="seo-advisor-stat-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="seo-advisor-stat-content">
                        <h3><?php echo esc_html__('Average SEO Score', 'seo-advisor-woo'); ?></h3>
                        <div class="seo-advisor-stat-value">
                            <?php 
                            $score_class = 'poor';
                            if ($avg_score >= 70 && $avg_score < 90) {
                                $score_class = 'good';
                            } elseif ($avg_score >= 90) {
                                $score_class = 'excellent';
                            }
                            ?>
                            <span class="seo-advisor-score seo-advisor-score-<?php echo esc_attr($score_class); ?>">
                                <?php echo esc_html($avg_score); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="seo-advisor-stat-box">
                    <div class="seo-advisor-stat-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="seo-advisor-stat-content">
                        <h3><?php echo esc_html__('Content with Issues', 'seo-advisor-woo'); ?></h3>
                        <div class="seo-advisor-stat-value"><?php echo esc_html($issues_count); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Content -->
        <div class="seo-advisor-widget seo-advisor-recent-content">
            <h2><?php echo esc_html__('Recent Content', 'seo-advisor-woo'); ?></h2>
            <?php if (!empty($recent_items)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Title', 'seo-advisor-woo'); ?></th>
                            <th><?php echo esc_html__('Type', 'seo-advisor-woo'); ?></th>
                            <th><?php echo esc_html__('SEO Score', 'seo-advisor-woo'); ?></th>
                            <th><?php echo esc_html__('Last Updated', 'seo-advisor-woo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_items as $item) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($item->post_id)); ?>">
                                        <?php echo esc_html($item->post_title); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(ucfirst($item->post_type)); ?></td>
                                <td>
                                    <?php 
                                    $item_score_class = 'poor';
                                    if ($item->seo_score >= 70 && $item->seo_score < 90) {
                                        $item_score_class = 'good';
                                    } elseif ($item->seo_score >= 90) {
                                        $item_score_class = 'excellent';
                                    }
                                    ?>
                                    <span class="seo-advisor-score seo-advisor-score-<?php echo esc_attr($item_score_class); ?>">
                                        <?php echo esc_html($item->seo_score); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html(human_time_diff(strtotime($item->updated_at), current_time('timestamp'))); ?>
                                    <?php echo esc_html__('ago', 'seo-advisor-woo'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="seo-advisor-no-data">
                    <p><?php echo esc_html__('No content has been analyzed yet.', 'seo-advisor-woo'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- SEO Tips -->
        <div class="seo-advisor-widget seo-advisor-tips">
            <h2><?php echo esc_html__('SEO Tips', 'seo-advisor-woo'); ?></h2>
            <div class="seo-advisor-tips-content">
                <div class="seo-advisor-tip">
                    <div class="seo-advisor-tip-icon">
                        <span class="dashicons dashicons-lightbulb"></span>
                    </div>
                    <div class="seo-advisor-tip-content">
                        <h3><?php echo esc_html__('Use Clear, Descriptive Titles', 'seo-advisor-woo'); ?></h3>
                        <p><?php echo esc_html__('Your page title is one of the most important on-page SEO elements. Make it descriptive, include your focus keyword near the beginning, and keep it under 60 characters.', 'seo-advisor-woo'); ?></p>
                    </div>
                </div>
                
                <div class="seo-advisor-tip">
                    <div class="seo-advisor-tip-icon">
                        <span class="dashicons dashicons-lightbulb"></span>
                    </div>
                    <div class="seo-advisor-tip-content">
                        <h3><?php echo esc_html__('Optimize Images', 'seo-advisor-woo'); ?></h3>
                        <p><?php echo esc_html__('Always add descriptive alt text to your images that includes your target keywords where appropriate. This helps both SEO and accessibility.', 'seo-advisor-woo'); ?></p>
                    </div>
                </div>
                
                <div class="seo-advisor-tip">
                    <div class="seo-advisor-tip-icon">
                        <span class="dashicons dashicons-lightbulb"></span>
                    </div>
                    <div class="seo-advisor-tip-content">
                        <h3><?php echo esc_html__('Use Proper Heading Structure', 'seo-advisor-woo'); ?></h3>
                        <p><?php echo esc_html__('Organize your content with a clear heading structure (H2, H3, etc.). Include keywords in your headings and ensure there\'s only one H1 tag per page.', 'seo-advisor-woo'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content Generator Promo -->
        <div class="seo-advisor-widget seo-advisor-promo">
            <h2><?php echo esc_html__('Content Generator', 'seo-advisor-woo'); ?></h2>
            <div class="seo-advisor-promo-content">
                <div class="seo-advisor-promo-icon">
                    <span class="dashicons dashicons-text-page"></span>
                </div>
                <div class="seo-advisor-promo-text">
                    <h3><?php echo esc_html__('Coming Soon: AI-Powered Content Generator', 'seo-advisor-woo'); ?></h3>
                    <p><?php echo esc_html__('In the upcoming update, you\'ll be able to generate SEO-optimized content with the help of AI. Create engaging titles, meta descriptions, and even full content with just a few clicks.', 'seo-advisor-woo'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.seo-advisor-dashboard-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(45%, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.seo-advisor-widget {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
    padding: 20px;
    border-radius: 5px;
}

.seo-advisor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.seo-advisor-stat-box {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
}

.seo-advisor-stat-icon {
    margin-right: 15px;
}

.seo-advisor-stat-icon .dashicons {
    font-size: 30px;
    width: 30px;
    height: 30px;
    color: #2271b1;
}

.seo-advisor-stat-value {
    font-size: 24px;
    font-weight: bold;
    margin-top: 5px;
}

.seo-advisor-score {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    color: #fff;
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

.seo-advisor-tips-content {
    margin-top: 15px;
}

.seo-advisor-tip {
    display: flex;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.seo-advisor-tip:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.seo-advisor-tip-icon {
    margin-right: 15px;
}

.seo-advisor-tip-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #2271b1;
}

.seo-advisor-promo-content {
    display: flex;
    align-items: center;
    padding: 20px;
    background: #f0f6fc;
    border-radius: 5px;
    margin-top: 15px;
}

.seo-advisor-promo-icon {
    margin-right: 20px;
}

.seo-advisor-promo-icon .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #2271b1;
}

@media screen and (max-width: 782px) {
    .seo-advisor-dashboard-widgets {
        grid-template-columns: 1fr;
    }
}
</style>
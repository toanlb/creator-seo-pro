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
    
    // Count by post type
    $post_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_type = %s", 'post'));
    $page_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_type = %s", 'page'));
    $product_count = 0;
    if (in_array('product', $post_types)) {
        $product_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_type = %s", 'product'));
    }
    
    // Average SEO score
    $avg_score = $wpdb->get_var("SELECT AVG(seo_score) FROM $table_name");
    $avg_score = $avg_score ? round($avg_score) : 0;
    
    // Average scores by post type
    $avg_post_score = $wpdb->get_var($wpdb->prepare("SELECT AVG(seo_score) FROM $table_name WHERE post_type = %s", 'post'));
    $avg_post_score = $avg_post_score ? round($avg_post_score) : 0;
    
    $avg_page_score = $wpdb->get_var($wpdb->prepare("SELECT AVG(seo_score) FROM $table_name WHERE post_type = %s", 'page'));
    $avg_page_score = $avg_page_score ? round($avg_page_score) : 0;
    
    $avg_product_score = 0;
    if (in_array('product', $post_types)) {
        $avg_product_score = $wpdb->get_var($wpdb->prepare("SELECT AVG(seo_score) FROM $table_name WHERE post_type = %s", 'product'));
        $avg_product_score = $avg_product_score ? round($avg_product_score) : 0;
    }
    
    // Score distribution
    $score_distribution = array(
        'poor' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE seo_score < 50"),
        'average' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE seo_score >= 50 AND seo_score < 70"),
        'good' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE seo_score >= 70 AND seo_score < 90"),
        'excellent' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE seo_score >= 90")
    );
    
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
    
    // Get top issues (you would need to implement this function)
    $post_analyzer = new SEO_Advisor_Post_Analyzer();
    $top_issues = $post_analyzer->get_top_issues(5, 'all');
    
    // Best and worst content
    $best_content = $post_analyzer->get_content_by_score('best', 5);
    $worst_content = $post_analyzer->get_content_by_score('worst', 5);
    
    // Calculate chart data
    $chart_labels = array();
    $chart_data = array();
    
    if (in_array('post', $post_types)) {
        $chart_labels[] = __('Posts', 'seo-advisor-woo');
        $chart_data[] = $avg_post_score;
    }
    
    if (in_array('page', $post_types)) {
        $chart_labels[] = __('Pages', 'seo-advisor-woo');
        $chart_data[] = $avg_page_score;
    }
    
    if (in_array('product', $post_types)) {
        $chart_labels[] = __('Products', 'seo-advisor-woo');
        $chart_data[] = $avg_product_score;
    }
    
    // Prepare data for JS
    $seo_advisor_data = array(
        'chart_labels' => $chart_labels,
        'chart_data' => $chart_data,
        'score_distribution' => array_values($score_distribution)
    );
    
    wp_localize_script('seo-advisor-admin', 'seo_advisor_data', $seo_advisor_data);
    ?>
    
    <div class="seo-advisor-dashboard-header">
        <div class="seo-advisor-version">
            <span><?php echo esc_html__('Version:', 'seo-advisor-woo'); ?> <?php echo SEO_ADVISOR_VERSION; ?></span>
        </div>
        
        <div class="seo-advisor-dashboard-actions">
            <button type="button" class="button button-primary" id="seo-advisor-refresh-stats">
                <span class="dashicons dashicons-update"></span>
                <?php echo esc_html__('Refresh Dashboard', 'seo-advisor-woo'); ?>
            </button>
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-advisor-settings')); ?>" class="button">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php echo esc_html__('Settings', 'seo-advisor-woo'); ?>
            </a>
        </div>
    </div>
    
    <!-- Overview Cards -->
    <div class="seo-advisor-dashboard-cards">
        <div class="seo-advisor-card">
            <div class="seo-advisor-card-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="seo-advisor-card-content">
                <div class="seo-advisor-card-value"><?php echo esc_html($analyzed_count); ?></div>
                <div class="seo-advisor-card-title"><?php echo esc_html__('Analyzed Content', 'seo-advisor-woo'); ?></div>
            </div>
        </div>
        
        <div class="seo-advisor-card">
            <div class="seo-advisor-card-icon">
                <span class="dashicons dashicons-performance"></span>
            </div>
            <div class="seo-advisor-card-content">
                <?php 
                    $score_class = 'poor';
                    if ($avg_score >= 70 && $avg_score < 90) {
                        $score_class = 'good';
                    } elseif ($avg_score >= 90) {
                        $score_class = 'excellent';
                    }
                ?>
                <div class="seo-advisor-card-value seo-advisor-score-<?php echo esc_attr($score_class); ?>"><?php echo esc_html($avg_score); ?></div>
                <div class="seo-advisor-card-title"><?php echo esc_html__('Average SEO Score', 'seo-advisor-woo'); ?></div>
            </div>
        </div>
        
        <div class="seo-advisor-card">
            <div class="seo-advisor-card-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="seo-advisor-card-content">
                <div class="seo-advisor-card-value"><?php echo esc_html($issues_count); ?></div>
                <div class="seo-advisor-card-title"><?php echo esc_html__('Content with Issues', 'seo-advisor-woo'); ?></div>
            </div>
        </div>
        
        <div class="seo-advisor-card">
            <div class="seo-advisor-card-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="seo-advisor-card-content">
                <div class="seo-advisor-card-value">
                    <?php
                    $latest_update = $wpdb->get_var("SELECT MAX(updated_at) FROM $table_name");
                    if ($latest_update) {
                        echo esc_html(human_time_diff(strtotime($latest_update), current_time('timestamp')));
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
                <div class="seo-advisor-card-title"><?php echo esc_html__('Since Last Update', 'seo-advisor-woo'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="seo-advisor-dashboard-widgets">
        <!-- Score Overview -->
        <div class="seo-advisor-widget seo-advisor-widget-score-overview">
            <div class="seo-advisor-widget-header">
                <h2><?php echo esc_html__('SEO Score Overview', 'seo-advisor-woo'); ?></h2>
            </div>
            <div class="seo-advisor-widget-content">
                <div class="seo-advisor-chart-container">
                    <canvas id="seo-advisor-score-chart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Score Distribution -->
        <div class="seo-advisor-widget seo-advisor-widget-score-distribution">
            <div class="seo-advisor-widget-header">
                <h2><?php echo esc_html__('Score Distribution', 'seo-advisor-woo'); ?></h2>
            </div>
            <div class="seo-advisor-widget-content">
                <div class="seo-advisor-chart-container">
                    <canvas id="seo-advisor-distribution-chart" width="400" height="300"></canvas>
                </div>
                <div class="seo-advisor-distribution-legend">
                    <div class="seo-advisor-legend-item">
                        <span class="seo-advisor-legend-color seo-advisor-color-poor"></span>
                        <span class="seo-advisor-legend-text"><?php echo esc_html__('Poor (0-49)', 'seo-advisor-woo'); ?>: <?php echo esc_html($score_distribution['poor']); ?></span>
                    </div>
                    <div class="seo-advisor-legend-item">
                        <span class="seo-advisor-legend-color seo-advisor-color-average"></span>
                        <span class="seo-advisor-legend-text"><?php echo esc_html__('Average (50-69)', 'seo-advisor-woo'); ?>: <?php echo esc_html($score_distribution['average']); ?></span>
                    </div>
                    <div class="seo-advisor-legend-item">
                        <span class="seo-advisor-legend-color seo-advisor-color-good"></span>
                        <span class="seo-advisor-legend-text"><?php echo esc_html__('Good (70-89)', 'seo-advisor-woo'); ?>: <?php echo esc_html($score_distribution['good']); ?></span>
                    </div>
                    <div class="seo-advisor-legend-item">
                        <span class="seo-advisor-legend-color seo-advisor-color-excellent"></span>
                        <span class="seo-advisor-legend-text"><?php echo esc_html__('Excellent (90-100)', 'seo-advisor-woo'); ?>: <?php echo esc_html($score_distribution['excellent']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Issues -->
        <div class="seo-advisor-widget seo-advisor-widget-top-issues">
            <div class="seo-advisor-widget-header">
                <h2><?php echo esc_html__('Top Issues', 'seo-advisor-woo'); ?></h2>
            </div>
            <div class="seo-advisor-widget-content">
                <?php if (!empty($top_issues)) : ?>
                    <ul class="seo-advisor-issue-list">
                        <?php foreach ($top_issues as $issue) : 
                            $status_class = 'warning';
                            if ($issue['status'] === 'critical') {
                                $status_class = 'critical';
                            }
                        ?>
                            <li class="seo-advisor-issue-item seo-advisor-issue-<?php echo esc_attr($status_class); ?>">
                                <div class="seo-advisor-issue-icon">
                                    <span class="dashicons <?php echo $issue['status'] === 'critical' ? 'dashicons-warning' : 'dashicons-flag'; ?>"></span>
                                </div>
                                <div class="seo-advisor-issue-content">
                                    <h4 class="seo-advisor-issue-title"><?php echo esc_html($issue['name']); ?></h4>
                                    <div class="seo-advisor-issue-count">
                                        <?php echo sprintf(
                                            esc_html__('Found in %d pieces of content', 'seo-advisor-woo'),
                                            $issue['count']
                                        ); ?>
                                    </div>
                                    <?php if (!empty($issue['recommended_action'])) : ?>
                                        <div class="seo-advisor-issue-action">
                                            <?php echo esc_html($issue['recommended_action']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div class="seo-advisor-no-issues">
                        <p><?php echo esc_html__('No significant issues found. Great job!', 'seo-advisor-woo'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Content -->
        <div class="seo-advisor-widget seo-advisor-widget-recent-content">
            <div class="seo-advisor-widget-header">
                <h2><?php echo esc_html__('Recently Analyzed Content', 'seo-advisor-woo'); ?></h2>
            </div>
            <div class="seo-advisor-widget-content">
                <?php if (!empty($recent_items)) : ?>
                    <table class="seo-advisor-table">
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
                                    <td>
                                        <span class="seo-advisor-post-type seo-advisor-post-type-<?php echo esc_attr($item->post_type); ?>">
                                            <?php echo esc_html(ucfirst($item->post_type)); ?>
                                        </span>
                                    </td>
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
                                        <span class="seo-advisor-time">
                                            <?php echo esc_html(human_time_diff(strtotime($item->updated_at), current_time('timestamp'))); ?>
                                            <?php echo esc_html__('ago', 'seo-advisor-woo'); ?>
                                        </span>
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
        </div>
        
        <!-- Best and Worst Content -->
        <div class="seo-advisor-widget seo-advisor-widget-best-worst">
            <div class="seo-advisor-widget-header">
                <h2><?php echo esc_html__('Best & Worst Content', 'seo-advisor-woo'); ?></h2>
            </div>
            <div class="seo-advisor-widget-content">
                <div class="seo-advisor-tabs">
                    <div class="seo-advisor-tabs-nav">
                        <a href="#" class="seo-advisor-tab-link active" data-tab="best"><?php echo esc_html__('Best Content', 'seo-advisor-woo'); ?></a>
                        <a href="#" class="seo-advisor-tab-link" data-tab="worst"><?php echo esc_html__('Needs Improvement', 'seo-advisor-woo'); ?></a>
                    </div>
                    
                    <div class="seo-advisor-tabs-content">
                        <div class="seo-advisor-tab-pane active" id="seo-advisor-tab-best">
                            <?php if (!empty($best_content)) : ?>
                                <ul class="seo-advisor-best-worst-list">
                                    <?php foreach ($best_content as $item) : ?>
                                        <li class="seo-advisor-best-worst-item">
                                            <div class="seo-advisor-best-worst-score seo-advisor-score-<?php echo $item['score'] >= 90 ? 'excellent' : 'good'; ?>">
                                                <?php echo esc_html($item['score']); ?>
                                            </div>
                                            <div class="seo-advisor-best-worst-details">
                                                <h4 class="seo-advisor-best-worst-title">
                                                    <a href="<?php echo esc_url($item['edit_url']); ?>">
                                                        <?php echo esc_html($item['title']); ?>
                                                    </a>
                                                </h4>
                                                <div class="seo-advisor-best-worst-meta">
                                                    <span class="seo-advisor-post-type seo-advisor-post-type-<?php echo esc_attr($item['post_type']); ?>">
                                                        <?php echo esc_html(ucfirst($item['post_type'])); ?>
                                                    </span>
                                                    <a href="<?php echo esc_url($item['view_url']); ?>" class="seo-advisor-view-link" target="_blank">
                                                        <span class="dashicons dashicons-visibility"></span>
                                                        <?php echo esc_html__('View', 'seo-advisor-woo'); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <div class="seo-advisor-no-data">
                                    <p><?php echo esc_html__('No content has been analyzed yet.', 'seo-advisor-woo'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="seo-advisor-tab-pane" id="seo-advisor-tab-worst">
                            <?php if (!empty($worst_content)) : ?>
                                <ul class="seo-advisor-best-worst-list">
                                    <?php foreach ($worst_content as $item) : ?>
                                        <li class="seo-advisor-best-worst-item">
                                            <div class="seo-advisor-best-worst-score seo-advisor-score-<?php echo $item['score'] >= 50 ? 'average' : 'poor'; ?>">
                                                <?php echo esc_html($item['score']); ?>
                                            </div>
                                            <div class="seo-advisor-best-worst-details">
                                                <h4 class="seo-advisor-best-worst-title">
                                                    <a href="<?php echo esc_url($item['edit_url']); ?>">
                                                        <?php echo esc_html($item['title']); ?>
                                                    </a>
                                                </h4>
                                                <div class="seo-advisor-best-worst-meta">
                                                    <span class="seo-advisor-post-type seo-advisor-post-type-<?php echo esc_attr($item['post_type']); ?>">
                                                        <?php echo esc_html(ucfirst($item['post_type'])); ?>
                                                    </span>
                                                    <a href="<?php echo esc_url($item['view_url']); ?>" class="seo-advisor-view-link" target="_blank">
                                                        <span class="dashicons dashicons-visibility"></span>
                                                        <?php echo esc_html__('View', 'seo-advisor-woo'); ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <div class="seo-advisor-no-data">
                                    <p><?php echo esc_html__('No content has been analyzed yet.', 'seo-advisor-woo'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SEO Tips -->
        <div class="seo-advisor-widget seo-advisor-widget-tips">
            <div class="seo-advisor-widget-header">
                <h2><?php echo esc_html__('SEO Tips', 'seo-advisor-woo'); ?></h2>
            </div>
            <div class="seo-advisor-widget-content">
                <div class="seo-advisor-tips-carousel">
                    <div class="seo-advisor-tip active">
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
                    
                    <div class="seo-advisor-tip">
                        <div class="seo-advisor-tip-icon">
                            <span class="dashicons dashicons-lightbulb"></span>
                        </div>
                        <div class="seo-advisor-tip-content">
                            <h3><?php echo esc_html__('Internal Linking', 'seo-advisor-woo'); ?></h3>
                            <p><?php echo esc_html__('Create a strong internal linking structure by linking to your own content. This helps search engines understand your site structure and distributes page authority.', 'seo-advisor-woo'); ?></p>
                        </div>
                    </div>
                    
                    <div class="seo-advisor-tip">
                        <div class="seo-advisor-tip-icon">
                            <span class="dashicons dashicons-lightbulb"></span>
                        </div>
                        <div class="seo-advisor-tip-content">
                            <h3><?php echo esc_html__('Mobile Optimization', 'seo-advisor-woo'); ?></h3>
                            <p><?php echo esc_html__('Ensure your content is mobile-friendly. Google predominantly uses mobile-first indexing, so your site must perform well on mobile devices.', 'seo-advisor-woo'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="seo-advisor-tips-nav">
                    <button type="button" class="seo-advisor-tip-prev">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </button>
                    <div class="seo-advisor-tip-dots">
                        <span class="seo-advisor-tip-dot active" data-index="0"></span>
                        <span class="seo-advisor-tip-dot" data-index="1"></span>
                        <span class="seo-advisor-tip-dot" data-index="2"></span>
                        <span class="seo-advisor-tip-dot" data-index="3"></span>
                        <span class="seo-advisor-tip-dot" data-index="4"></span>
                    </div>
                    <button type="button" class="seo-advisor-tip-next">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Content Generator Promo -->
        <div class="seo-advisor-widget seo-advisor-widget-promo">
            <div class="seo-advisor-widget-header">
                <h2><?php echo esc_html__('Content Generator', 'seo-advisor-woo'); ?></h2>
            </div>
            <div class="seo-advisor-widget-content">
                <div class="seo-advisor-promo-content">
                    <div class="seo-advisor-promo-icon">
                        <span class="dashicons dashicons-text-page"></span>
                    </div>
                    <div class="seo-advisor-promo-text">
                        <h3><?php echo esc_html__('Coming Soon: AI-Powered Content Generator', 'seo-advisor-woo'); ?></h3>
                        <p><?php echo esc_html__('In the upcoming update, you\'ll be able to generate SEO-optimized content with the help of AI. Create engaging titles, meta descriptions, and even full content with just a few clicks.', 'seo-advisor-woo'); ?></p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=seo-advisor-content-generator')); ?>" class="button button-primary seo-advisor-promo-button">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php echo esc_html__('Learn More', 'seo-advisor-woo'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize charts if Chart.js is loaded
    if (typeof Chart !== 'undefined') {
        // Score Chart
        var scoreCtx = document.getElementById('seo-advisor-score-chart');
        if (scoreCtx) {
            var scoreChart = new Chart(scoreCtx, {
                type: 'bar',
                data: {
                    labels: seo_advisor_data.chart_labels || ['Posts', 'Pages', 'Products'],
                    datasets: [{
                        label: '<?php echo esc_js(__('Average SEO Score', 'seo-advisor-woo')); ?>',
                        data: seo_advisor_data.chart_data || [0, 0, 0],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: '<?php echo esc_js(__('Score', 'seo-advisor-woo')); ?>'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw + ' points';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Distribution Chart
        var distributionCtx = document.getElementById('seo-advisor-distribution-chart');
        if (distributionCtx) {
            var distributionChart = new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: [
                        '<?php echo esc_js(__('Poor', 'seo-advisor-woo')); ?>',
                        '<?php echo esc_js(__('Average', 'seo-advisor-woo')); ?>',
                        '<?php echo esc_js(__('Good', 'seo-advisor-woo')); ?>',
                        '<?php echo esc_js(__('Excellent', 'seo-advisor-woo')); ?>'
                    ],
                    datasets: [{
                        data: seo_advisor_data.score_distribution || [0, 0, 0, 0],
                        backgroundColor: [
                            'rgba(214, 54, 56, 0.6)',    // Poor - red
                            'rgba(247, 181, 0, 0.6)',    // Average - yellow
                            'rgba(34, 113, 177, 0.6)',   // Good - blue
                            'rgba(0, 163, 42, 0.6)'      // Excellent - green
                        ],
                        borderColor: [
                            'rgba(214, 54, 56, 1)',
                            'rgba(247, 181, 0, 1)',
                            'rgba(34, 113, 177, 1)',
                            'rgba(0, 163, 42, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.raw || 0;
                                    var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    
    // Best/Worst content tabs
    $('.seo-advisor-tab-link').on('click', function(e) {
        e.preventDefault();
        
        var tabId = $(this).data('tab');
        
        // Update active tab link
        $('.seo-advisor-tab-link').removeClass('active');
        $(this).addClass('active');
        
        // Show the selected tab content
        $('.seo-advisor-tab-pane').removeClass('active');
        $('#seo-advisor-tab-' + tabId).addClass('active');
    });
    
    // Tips carousel
    var currentTip = 0;
    var tipCount = $('.seo-advisor-tip').length;
    
    $('.seo-advisor-tip-next').on('click', function() {
        showTip((currentTip + 1) % tipCount);
    });
    
    $('.seo-advisor-tip-prev').on('click', function() {
        showTip((currentTip - 1 + tipCount) % tipCount);
    });
    
    $('.seo-advisor-tip-dot').on('click', function() {
        var index = $(this).data('index');
        showTip(index);
    });
    
    function showTip(index) {
        $('.seo-advisor-tip').removeClass('active');
        $('.seo-advisor-tip').eq(index).addClass('active');
        
        $('.seo-advisor-tip-dot').removeClass('active');
        $('.seo-advisor-tip-dot[data-index="' + index + '"]').addClass('active');
        
        currentTip = index;
    }
    
    // Auto-advance tips every 10 seconds
    setInterval(function() {
        showTip((currentTip + 1) % tipCount);
    }, 10000);
    
    // Refresh dashboard
    $('#seo-advisor-refresh-stats').on('click', function() {
        var button = $(this);
        button.prop('disabled', true);
        button.find('.dashicons').addClass('seo-advisor-spin');
        
        // Reload the page
        location.reload();
    });
});
</script>

<style>
/* Dashboard */
.seo-advisor-dashboard {
    margin: 20px 0;
}

/* Dashboard header */
.seo-advisor-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.seo-advisor-version {
    color: #646970;
    font-size: 13px;
}

.seo-advisor-dashboard-actions {
    display: flex;
    gap: 10px;
}

.seo-advisor-dashboard-actions .button {
    display: flex;
    align-items: center;
}

.seo-advisor-dashboard-actions .button .dashicons {
    margin-right: 5px;
}

.seo-advisor-spin {
    animation: seo-advisor-spin 2s linear infinite;
}

@keyframes seo-advisor-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Overview cards */
.seo-advisor-dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.seo-advisor-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    padding: 20px;
    display: flex;
    align-items: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.seo-advisor-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.seo-advisor-card-icon {
    margin-right: 20px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: #f0f6fc;
    display: flex;
    align-items: center;
    justify-content: center;
}

.seo-advisor-card-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #2271b1;
}

.seo-advisor-card-content {
    flex: 1;
}

.seo-advisor-card-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.seo-advisor-card-title {
    color: #646970;
    font-size: 14px;
}

.seo-advisor-card-value.seo-advisor-score-poor {
    color: #d63638;
}

.seo-advisor-card-value.seo-advisor-score-average {
    color: #dba617;
}

.seo-advisor-card-value.seo-advisor-score-good {
    color: #2271b1;
}

.seo-advisor-card-value.seo-advisor-score-excellent {
    color: #00a32a;
}

/* Dashboard widgets */
.seo-advisor-dashboard-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(48%, 1fr));
    gap: 20px;
}

.seo-advisor-widget {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.seo-advisor-widget-header {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.seo-advisor-widget-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.seo-advisor-widget-content {
    padding: 20px;
}

/* Chart containers */
.seo-advisor-chart-container {
    height: 300px;
    position: relative;
}

/* Distribution legend */
.seo-advisor-distribution-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
    margin-top: 15px;
}

.seo-advisor-legend-item {
    display: flex;
    align-items: center;
}

.seo-advisor-legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
    margin-right: 5px;
}

.seo-advisor-color-poor {
    background-color: rgba(214, 54, 56, 0.6);
}

.seo-advisor-color-average {
    background-color: rgba(247, 181, 0, 0.6);
}

.seo-advisor-color-good {
    background-color: rgba(34, 113, 177, 0.6);
}

.seo-advisor-color-excellent {
    background-color: rgba(0, 163, 42, 0.6);
}

.seo-advisor-legend-text {
    font-size: 12px;
    color: #646970;
}

/* Issue list */
.seo-advisor-issue-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.seo-advisor-issue-item {
    padding: 15px;
    border-left: 4px solid #dba617;
    background-color: #f9f9f9;
    margin-bottom: 10px;
    display: flex;
    border-radius: 4px;
}

.seo-advisor-issue-item.seo-advisor-issue-critical {
    border-left-color: #d63638;
}

.seo-advisor-issue-icon {
    margin-right: 15px;
}

.seo-advisor-issue-icon .dashicons {
    color: #dba617;
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.seo-advisor-issue-critical .seo-advisor-issue-icon .dashicons {
    color: #d63638;
}

.seo-advisor-issue-content {
    flex: 1;
}

.seo-advisor-issue-title {
    margin: 0 0 5px;
    font-size: 14px;
}

.seo-advisor-issue-count {
    color: #646970;
    font-size: 12px;
    margin-bottom: 8px;
}

.seo-advisor-issue-action {
    background-color: #f0f0f0;
    padding: 8px;
    border-radius: 3px;
    font-size: 12px;
}

.seo-advisor-no-issues,
.seo-advisor-no-data {
    padding: 20px;
    text-align: center;
    background-color: #f9f9f9;
    border-radius: 4px;
    color: #646970;
}

/* Table styling */
.seo-advisor-table {
    width: 100%;
    border-collapse: collapse;
}

.seo-advisor-table th {
    text-align: left;
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
    color: #646970;
    font-weight: 600;
}

.seo-advisor-table td {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
}

.seo-advisor-table tr:last-child td {
    border-bottom: none;
}

.seo-advisor-table tr:hover {
    background-color: #f9f9f9;
}

.seo-advisor-post-type {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    background-color: #f0f0f0;
}

.seo-advisor-post-type-post {
    background-color: #e5f5fa;
    color: #0071a1;
}

.seo-advisor-post-type-page {
    background-color: #e5f5e5;
    color: #00a32a;
}

.seo-advisor-post-type-product {
    background-color: #f0e6f5;
    color: #8c2ebd;
}

.seo-advisor-score {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    color: #fff;
    font-weight: bold;
}

.seo-advisor-score-poor {
    background-color: #d63638;
}

.seo-advisor-score-average {
    background-color: #dba617;
}

.seo-advisor-score-good {
    background-color: #2271b1;
}

.seo-advisor-score-excellent {
    background-color: #00a32a;
}

.seo-advisor-time {
    color: #646970;
    font-size: 12px;
}

/* Tabs */
.seo-advisor-tabs-nav {
    display: flex;
    border-bottom: 1px solid #f0f0f0;
    margin-bottom: 15px;
}

.seo-advisor-tab-link {
    padding: 10px 15px;
    text-decoration: none;
    color: #646970;
    font-weight: 500;
    position: relative;
}

.seo-advisor-tab-link.active {
    color: #2271b1;
    font-weight: 600;
}

.seo-advisor-tab-link.active:after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: #2271b1;
}

.seo-advisor-tab-pane {
    display: none;
}

.seo-advisor-tab-pane.active {
    display: block;
}

/* Best & Worst content */
.seo-advisor-best-worst-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.seo-advisor-best-worst-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border-radius: 4px;
    background-color: #f9f9f9;
    margin-bottom: 10px;
}

.seo-advisor-best-worst-score {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: #fff;
    font-weight: bold;
}

.seo-advisor-best-worst-details {
    flex: 1;
}

.seo-advisor-best-worst-title {
    margin: 0 0 5px;
    font-size: 14px;
}

.seo-advisor-best-worst-meta {
    display: flex;
    align-items: center;
    gap: 10px;
}

.seo-advisor-view-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #2271b1;
    font-size: 12px;
}

.seo-advisor-view-link .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    margin-right: 3px;
}

/* Tips carousel */
.seo-advisor-tips-carousel {
    position: relative;
    min-height: 120px;
}

.seo-advisor-tip {
    display: none;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 4px;
}

.seo-advisor-tip.active {
    display: flex;
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

.seo-advisor-tip-content h3 {
    margin-top: 0;
    margin-bottom: 5px;
    font-size: 16px;
}

.seo-advisor-tip-content p {
    margin: 0;
    color: #646970;
}

.seo-advisor-tips-nav {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 15px;
}

.seo-advisor-tip-prev,
.seo-advisor-tip-next {
    background: none;
    border: none;
    cursor: pointer;
    color: #2271b1;
    padding: 5px;
}

.seo-advisor-tip-dots {
    display: flex;
    gap: 5px;
    margin: 0 10px;
}

.seo-advisor-tip-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #ddd;
    cursor: pointer;
}

.seo-advisor-tip-dot.active {
    background-color: #2271b1;
}

/* Promo content */
.seo-advisor-promo-content {
    display: flex;
    align-items: center;
    padding: 20px;
    background-color: #f0f6fc;
    border-radius: 4px;
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

.seo-advisor-promo-text h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
}

.seo-advisor-promo-text p {
    margin: 0 0 15px;
    color: #646970;
}

.seo-advisor-promo-button {
    display: inline-flex;
    align-items: center;
}

.seo-advisor-promo-button .dashicons {
    margin-right: 5px;
}

/* Responsive styles */
@media screen and (max-width: 782px) {
    .seo-advisor-dashboard-widgets {
        grid-template-columns: 1fr;
    }
    
    .seo-advisor-dashboard-cards {
        grid-template-columns: 1fr;
    }
    
    .seo-advisor-promo-content {
        flex-direction: column;
        text-align: center;
    }
    
    .seo-advisor-promo-icon {
        margin-right: 0;
        margin-bottom: 15px;
    }
}
</style>
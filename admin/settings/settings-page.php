<?php
/**
 * Settings page display.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 * @subpackage SEO_Advisor/admin/settings
 */
?>

<div class="wrap seo-advisor-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="seo-advisor-settings-tabs">
        <h2 class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active"><?php echo esc_html__('General', 'seo-advisor-woo'); ?></a>
            <a href="#analysis" class="nav-tab"><?php echo esc_html__('Analysis', 'seo-advisor-woo'); ?></a>
            <a href="#ai" class="nav-tab"><?php echo esc_html__('AI Settings', 'seo-advisor-woo'); ?> <span class="awaiting-mod">Pro</span></a>
        </h2>
        
        <div id="general" class="seo-advisor-tab-content active">
            <form method="post" action="options.php">
                <?php
                settings_fields('seo_advisor_general_settings');
                do_settings_sections('seo_advisor_general_settings');
                submit_button();
                ?>
            </form>
        </div>
        
        <div id="analysis" class="seo-advisor-tab-content">
            <form method="post" action="options.php">
                <?php
                settings_fields('seo_advisor_analysis_settings');
                do_settings_sections('seo_advisor_analysis_settings');
                submit_button();
                ?>
            </form>
        </div>
        
        <div id="ai" class="seo-advisor-tab-content">
            <form method="post" action="options.php">
                <?php
                settings_fields('seo_advisor_ai_settings');
                do_settings_sections('seo_advisor_ai_settings');
                submit_button(__('Save Settings', 'seo-advisor-woo'), 'primary', 'submit', true, array('disabled' => 'disabled'));
                ?>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.seo-advisor-settings-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Hide all tab contents
        $('.seo-advisor-tab-content').removeClass('active');
        
        // Show the selected tab content
        $($(this).attr('href')).addClass('active');
        
        // Update active tab
        $('.seo-advisor-settings-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
    });
    
    // Handle direct linking to tabs
    var hash = window.location.hash;
    if (hash) {
        $('.seo-advisor-settings-tabs .nav-tab[href="' + hash + '"]').trigger('click');
    }
});
</script>

<style>
.seo-advisor-tab-content {
    display: none;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.seo-advisor-tab-content.active {
    display: block;
}

.seo-advisor-settings .form-table th {
    width: 250px;
}

.awaiting-mod {
    display: inline-block;
    vertical-align: top;
    box-sizing: border-box;
    margin: 1px 0 -1px 2px;
    padding: 0 5px;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    background-color: #72aee6;
    color: #fff;
    font-size: 11px;
    line-height: 1.6;
    text-align: center;
    z-index: 26;
}
</style>
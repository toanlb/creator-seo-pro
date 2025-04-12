/**
 * Admin JavaScript for SEO Advisor & Content Generator.
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 */

(function($) {
    'use strict';

    /**
     * Dashboard charts and graphs.
     */
    function initDashboardCharts() {
        if ($('#seo-advisor-score-chart').length) {
            var ctx = document.getElementById('seo-advisor-score-chart').getContext('2d');
            
            var scoreChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: seo_advisor_data.chart_labels || ['Posts', 'Pages', 'Products'],
                    datasets: [{
                        label: 'Average SEO Score',
                        data: seo_advisor_data.chart_data || [65, 72, 80],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    }

    /**
     * Initialize Select2 for dropdown fields.
     */
    function initSelect2() {
        $('.seo-advisor-select2').select2({
            width: '100%',
            placeholder: $(this).data('placeholder')
        });
    }

    /**
     * Handle the settings tabs.
     */
    function initSettingsTabs() {
        $('.seo-advisor-settings-tabs .nav-tab').on('click', function(e) {
            e.preventDefault();
            
            // Hide all tab contents
            $('.seo-advisor-tab-content').removeClass('active');
            
            // Show the selected tab content
            $($(this).attr('href')).addClass('active');
            
            // Update active tab
            $('.seo-advisor-settings-tabs .nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Update URL hash
            window.location.hash = $(this).attr('href');
        });
        
        // Check for hash in URL and activate the corresponding tab
        var hash = window.location.hash;
        if (hash) {
            $('.seo-advisor-settings-tabs .nav-tab[href="' + hash + '"]').trigger('click');
        }
    }

    /**
     * Document ready handler.
     */
    $(document).ready(function() {
        initDashboardCharts();
        initSelect2();
        initSettingsTabs();
    });

})(jQuery);
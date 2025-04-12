/**
 * All of the public-facing JavaScript for the plugin
 *
 * @since      1.0.0
 * @package    SEO_Advisor
 */

(function($) {
    'use strict';

    /**
     * Initialize all public-facing functionality
     */
    function initPublicFunctions() {
        // Currently minimal public JavaScript is needed
        // This will be expanded for future public-facing features
    }

    /**
     * Initialize schema data toggle if implemented
     */
    function initSchemaViewer() {
        $('.seo-advisor-schema-toggle').on('click', function(e) {
            e.preventDefault();
            $(this).next('.seo-advisor-schema-data').slideToggle();
        });
    }

    /**
     * Document ready handler
     */
    $(document).ready(function() {
        initPublicFunctions();
        initSchemaViewer();
    });

})(jQuery);
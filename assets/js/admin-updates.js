/**
 * AQM Blog Post Feed Admin Updates JavaScript
 * 
 * Handles the "Check for Updates" functionality on the plugins page.
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Add click handler for the "Check for Updates" link
        $('.aqm-blog-check-updates').on('click', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var originalText = $link.text();
            
            // Show checking message
            $link.text(aqmBlogFeedData.checkingText);
            $link.css('cursor', 'wait');
            
            // Make the AJAX request
            $.ajax({
                url: aqmBlogFeedData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aqm_blog_post_feed_check_updates',
                    nonce: aqmBlogFeedData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $link.text(aqmBlogFeedData.successText);
                        
                        // Reload the page after a short delay to show any updates
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Show error message
                        $link.text(aqmBlogFeedData.errorText);
                        
                        // Reset to original text after a delay
                        setTimeout(function() {
                            $link.text(originalText);
                            $link.css('cursor', 'pointer');
                        }, 2000);
                    }
                },
                error: function() {
                    // Show error message
                    $link.text(aqmBlogFeedData.errorText);
                    
                    // Reset to original text after a delay
                    setTimeout(function() {
                        $link.text(originalText);
                        $link.css('cursor', 'pointer');
                    }, 2000);
                }
            });
        });
    });
})(jQuery);

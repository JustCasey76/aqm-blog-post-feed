(function($) {
    'use strict';

    $(function() { // Wait for document ready
        const $checkLink = $('#aqm-check-updates-link');
        const $statusSpan = $('#aqm-update-status');

        if ($checkLink.length) {
            $checkLink.on('click', function(e) {
                e.preventDefault(); // Prevent navigating to '#'

                // Show checking status
                $statusSpan.text(aqm_update_params.checking_text).css('color', ''); // Reset color
                $checkLink.css('pointer-events', 'none'); // Disable link during check

                // Perform AJAX request
                $.post(aqm_update_params.ajax_url, {
                    action: 'aqm_check_plugin_updates', // Matches the PHP action hook
                    nonce: aqm_update_params.nonce
                }, function(response) {
                    // Handle success
                    if (response.success) {
                        $statusSpan.text(aqm_update_params.success_text).css('color', 'green');
                    } else {
                        // Handle potential WP error response format
                        let errorMessage = response.data && response.data.message ? response.data.message : aqm_update_params.error_text;
                        $statusSpan.text(errorMessage).css('color', 'red');
                    }
                }).fail(function() {
                    // Handle AJAX failure (network error, etc.)
                    $statusSpan.text(aqm_update_params.error_text).css('color', 'red');
                }).always(function() {
                    // Re-enable link after a short delay
                    setTimeout(function() {
                        $checkLink.css('pointer-events', '');
                    }, 3000); // Re-enable after 3 seconds
                });
            });
        }
    });

})(jQuery);

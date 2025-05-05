<?php
/**
 * Plugin Update Checker Implementation
 * 
 * Uses the YahnisElsts/plugin-update-checker library to handle updates from GitHub.
 */

// Don't allow direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize the Plugin Update Checker
 */
function aqm_blog_post_feed_init_update_checker() {
    // Make sure we have the plugin-update-checker library
    if (!file_exists(plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php')) {
        error_log('[AQM BPF] Plugin Update Checker library not found');
        return;
    }

    // Include the library
    require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

    // Log initialization
    error_log('=========================================================');
    error_log('[AQM BPF] Initializing Plugin Update Checker');
    error_log('=========================================================');

    // Initialize the update checker
    $update_checker = Puc_v4_Factory::buildUpdateChecker(
        'https://github.com/JustCasey76/aqm-blog-post-feed/',
        AQM_BLOG_POST_FEED_FILE,
        'aqm-blog-post-feed'
    );

    // Set the branch to master
    $update_checker->setBranch('master');

    // Set debug mode
    $update_checker->setDebugMode(true);

    // Add hooks for plugin activation persistence
    add_filter('upgrader_pre_install', 'aqm_blog_post_feed_pre_install', 10, 2);
    add_action('upgrader_process_complete', 'aqm_blog_post_feed_post_install', 10, 2);
    add_action('admin_init', 'aqm_blog_post_feed_maybe_reactivate');

    error_log('[AQM BPF] Plugin Update Checker initialized successfully');
}

/**
 * Before installation, check if the plugin is active and set a transient
 * 
 * @param bool $return Whether to proceed with installation
 * @param array $hook_extra Extra data about the plugin being updated
 * @return bool Whether to proceed with installation
 */
function aqm_blog_post_feed_pre_install($return, $hook_extra) {
    error_log('=========================================================');
    error_log('[AQM BPF] ENTERING pre_install hook');
    error_log('=========================================================');

    // Check if this is our plugin
    if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== plugin_basename(AQM_BLOG_POST_FEED_FILE)) {
        return $return;
    }
    
    // Check if the plugin is active
    if (is_plugin_active(plugin_basename(AQM_BLOG_POST_FEED_FILE))) {
        // Set a transient to reactivate the plugin after update
        set_transient('aqm_blog_post_feed_was_active', true, 5 * MINUTE_IN_SECONDS);
        error_log('[AQM BPF] Plugin was active, setting transient');
    }
    
    return $return;
}

/**
 * After installation, check if we need to reactivate the plugin
 * 
 * @param WP_Upgrader $upgrader_object WP_Upgrader instance
 * @param array $options Array of bulk item update data
 */
function aqm_blog_post_feed_post_install($upgrader_object, $options) {
    error_log('=========================================================');
    error_log('[AQM BPF] ENTERING post_install hook');
    error_log('=========================================================');

    // Check if this is a plugin update
    if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
        return;
    }
    
    // Check if our plugin was updated
    if (!isset($options['plugins']) || !in_array(plugin_basename(AQM_BLOG_POST_FEED_FILE), $options['plugins'])) {
        return;
    }
    
    // Set a transient to reactivate on next admin page load
    // This is a fallback in case the plugin can't be activated immediately
    set_transient('aqm_blog_post_feed_reactivate', true, 5 * MINUTE_IN_SECONDS);
    
    error_log('[AQM BPF] Update complete, setting reactivation transient');
    
    // Try to reactivate the plugin now
    if (get_transient('aqm_blog_post_feed_was_active')) {
        // Delete the transient
        delete_transient('aqm_blog_post_feed_was_active');
        
        // Make sure plugin functions are loaded
        if (!function_exists('activate_plugin')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        // Reactivate the plugin
        $result = activate_plugin(plugin_basename(AQM_BLOG_POST_FEED_FILE));
        
        if (is_wp_error($result)) {
            error_log('[AQM BPF] Reactivation failed: ' . $result->get_error_message());
        } else {
            error_log('[AQM BPF] Plugin successfully reactivated');
            
            // Clear the reactivation transient since we successfully reactivated
            delete_transient('aqm_blog_post_feed_reactivate');
        }
        
        // Clear plugin cache
        wp_clean_plugins_cache(true);
    }
}

/**
 * Check if we need to reactivate the plugin on admin page load
 */
function aqm_blog_post_feed_maybe_reactivate() {
    error_log('=========================================================');
    error_log('[AQM BPF] ENTERING maybe_reactivate function');
    error_log('=========================================================');

    // Check if the reactivation transient exists
    if (get_transient('aqm_blog_post_feed_reactivate')) {
        // Delete the transient
        delete_transient('aqm_blog_post_feed_reactivate');
        
        error_log('[AQM BPF] Attempting reactivation on admin page load');
        
        // Make sure plugin functions are loaded
        if (!function_exists('activate_plugin')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        // Reactivate the plugin
        $result = activate_plugin(plugin_basename(AQM_BLOG_POST_FEED_FILE));
        
        if (is_wp_error($result)) {
            error_log('[AQM BPF] Reactivation failed: ' . $result->get_error_message());
        } else {
            error_log('[AQM BPF] Plugin successfully reactivated');
        }
        
        // Clear plugin cache
        wp_clean_plugins_cache(true);
    }
}

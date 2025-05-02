<?php
/*
Plugin Name: AQM Blog Post Feed
Plugin URI: https://aqmarketing.com/
Description: A custom Divi module to display blog posts in a customizable grid with Font Awesome icons, hover effects, and more.
Version: 3.1.9
Author: AQ Marketing
Author URI: https://aqmarketing.com/
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('AQM_BLOG_POST_FEED_FILE', __FILE__);
define('AQM_BLOG_POST_FEED_PATH', plugin_dir_path(__FILE__));
define('AQM_BLOG_POST_FEED_BASENAME', plugin_basename(__FILE__));
define('AQM_BLOG_POST_FEED_VERSION', '3.1.9');

// Include admin page
require_once AQM_BLOG_POST_FEED_PATH . 'includes/admin-page.php';

// Include the WP GitHub Plugin Updater
require_once AQM_BLOG_POST_FEED_PATH . 'includes/updater.php';

/**
 * Cleanup development files from existing installations
 * This will run once after updating to version 3.1.8 or higher
 */
function aqm_blog_post_feed_cleanup_dev_files() {
    // Check if cleanup has already been performed
    if (get_option('aqm_dev_files_cleaned_3_1_8') !== 'yes') {
        $plugin_dir = plugin_dir_path(__FILE__);
        
        // List of development directories/files to remove
        $dev_items = array(
            '.github',
            '.git',
            '.gitignore',
            'create-release.ps1',
            'create-clean-release.ps1'
        );
        
        foreach ($dev_items as $item) {
            $path = $plugin_dir . $item;
            if (file_exists($path)) {
                if (is_dir($path)) {
                    // Recursive directory deletion
                    aqm_blog_post_feed_delete_directory($path);
                } else {
                    // File deletion
                    @unlink($path);
                }
            }
        }
        
        // Mark cleanup as completed
        update_option('aqm_dev_files_cleaned_3_1_8', 'yes');
    }
}

/**
 * Helper function to recursively delete a directory
 */
function aqm_blog_post_feed_delete_directory($dir) {
    if (!file_exists($dir)) return;
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? aqm_blog_post_feed_delete_directory($path) : @unlink($path);
    }
    return @rmdir($dir);
}

// Run cleanup on plugin activation or update
add_action('admin_init', 'aqm_blog_post_feed_cleanup_dev_files');

// Plugin version history and update mechanism has been simplified
// The GitHub updater class now uses a minimal implementation to prevent errors

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'aqm_blog_post_feed_activate');
register_deactivation_hook(__FILE__, 'aqm_blog_post_feed_deactivate');

/**
 * Plugin activation function
 */
function aqm_blog_post_feed_activate() {
    // Store activation state in options table
    update_option('aqm_blog_post_feed_active', true);
}

/**
 * Plugin deactivation function
 */
function aqm_blog_post_feed_deactivate() {
    // Update activation state in options table
    update_option('aqm_blog_post_feed_active', false);
}

// The update system has been replaced with a more robust GitHub updater in includes/updater.php

// The old upgrader_process_complete action has been replaced by the GitHub updater in includes/updater.php

// The old upgrader_source_selection filter has been replaced by the GitHub updater in includes/updater.php

// Add function to reactivate plugin after update
function aqm_blog_post_feed_maybe_reactivate() {
    error_log('AQM Debug: aqm_blog_post_feed_maybe_reactivate running.');
    
    // Force activation on every admin page load for a short period after update
    // This is an aggressive approach but ensures the plugin gets reactivated
    $force_activation = false;
    
    // Only run in admin
    if (!is_admin()) {
        error_log('AQM Debug: Not admin, exiting.');
        return;
    }

    // Check if the transient was set by the updater hook
    if (get_transient('aqm_reactivate_after_update') === '1') {
        error_log('AQM Debug: Reactivation transient found.');
        $force_activation = true;
        
        // Don't delete the transient yet - keep it for multiple attempts
        // We'll reset the expiration time instead
        set_transient('aqm_reactivate_after_update', '1', 600); // Reset for another 10 minutes

        // Ensure the plugin file actually exists before trying to activate
        if (!file_exists(WP_PLUGIN_DIR . '/' . AQM_BLOG_POST_FEED_BASENAME)) {
            error_log('AQM Debug: Plugin file MISSING, cannot reactivate: ' . WP_PLUGIN_DIR . '/' . AQM_BLOG_POST_FEED_BASENAME);
            return; // Exit if the plugin file isn't there
        }
        
        // Check if already active
        if (is_plugin_active(AQM_BLOG_POST_FEED_BASENAME)) {
            error_log('AQM Debug: Plugin already active, deleting transient.');
            delete_transient('aqm_reactivate_after_update'); // Only delete when we confirm it's active
            return;
        }

        error_log('AQM Debug: Plugin needs reactivation based on transient. Activating...');
        $result = activate_plugin(AQM_BLOG_POST_FEED_BASENAME);
        if (is_wp_error($result)) {
            error_log('AQM Debug: Error reactivating plugin: ' . $result->get_error_message());
        } else {
            error_log('AQM Debug: Plugin reactivated successfully via transient.');
            wp_clean_plugins_cache(true);
            error_log('AQM Debug: Plugin cache cleaned after transient reactivation.');

            // Redirect logic might still be needed if activation happens but state isn't reflected immediately
            global $pagenow;
            if ($pagenow === 'plugins.php' && !isset($_GET['aqm_reactivated'])) {
                error_log('AQM Debug: On plugins.php after transient activation, redirecting...');
                wp_redirect(add_query_arg('aqm_reactivated', '1', $_SERVER['REQUEST_URI']));
                exit;
            }
        }
    } else {
        error_log('AQM Debug: Reactivation transient NOT found.');
    }

    // --- Keep the original logic as a fallback, but log it differently ---
    $should_be_active = get_option('aqm_blog_post_feed_active', false);
    $is_active = is_plugin_active(AQM_BLOG_POST_FEED_BASENAME);
    
    error_log('AQM Debug Fallback Check: Should be active? ' . ($should_be_active ? 'Yes' : 'No'));
    error_log('AQM Debug Fallback Check: Is currently active? ' . ($is_active ? 'Yes' : 'No'));

    // Check if we need to reactivate (fallback check)
    if (($should_be_active && !$is_active) || $force_activation) {
        error_log('AQM Debug Fallback: Plugin needs reactivation. Checking file existence...');
        // Ensure the plugin file actually exists before trying to activate
        if (!file_exists(WP_PLUGIN_DIR . '/' . AQM_BLOG_POST_FEED_BASENAME)) {
            error_log('AQM Debug Fallback: Plugin file MISSING, cannot reactivate: ' . WP_PLUGIN_DIR . '/' . AQM_BLOG_POST_FEED_BASENAME);
            return; // Exit if the plugin file isn't there
        }

        error_log('AQM Debug Fallback: Activating...');
        $result = activate_plugin(AQM_BLOG_POST_FEED_BASENAME);
        if (is_wp_error($result)) {
            error_log('AQM Debug Fallback: Error reactivating plugin: ' . $result->get_error_message());
        } else {
            error_log('AQM Debug Fallback: Plugin reactivated successfully.');
            // If we successfully reactivated, we can delete the transient
            delete_transient('aqm_reactivate_after_update');
        }
        
        wp_clean_plugins_cache(true);
        error_log('AQM Debug Fallback: Plugin cache cleaned.');
        
        global $pagenow;
        if ($pagenow === 'plugins.php' && !isset($_GET['aqm_reactivated'])) {
            error_log('AQM Debug Fallback: On plugins.php, redirecting...');
            wp_redirect(add_query_arg('aqm_reactivated', '1', $_SERVER['REQUEST_URI']));
            exit;
        } else {
             error_log('AQM Debug Fallback: Not redirecting (Page: ' . $pagenow . ', Reactivated Param: ' . (isset($_GET['aqm_reactivated']) ? 'Set' : 'Not Set') . ')');
        }
    } else {
        error_log('AQM Debug Fallback: No reactivation needed.');
    }
}
add_action('admin_init', 'aqm_blog_post_feed_maybe_reactivate', 1); // Priority 1 to run early

// Add a notice to show when the plugin has been reactivated
function aqm_blog_post_feed_reactivation_notice() {
    if (isset($_GET['aqm_reactivated']) && $_GET['aqm_reactivated'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>AQM Blog Post Feed plugin has been automatically reactivated.</p></div>';
    }
}
add_action('admin_notices', 'aqm_blog_post_feed_reactivation_notice');

/**
 * Add plugin action links
 *
 * @param array $links Default plugin action links
 * @param string $file Plugin file
 * @return array Modified plugin action links
 */
function aqm_blog_post_feed_plugin_action_links($links, $file) {
    if ($file === AQM_BLOG_POST_FEED_BASENAME) {
        // Add settings link
        $settings_link = '<a href="' . admin_url('options-general.php?page=aqm-blog-post-feed') . '">' . __('Settings', 'aqm-blog-post-feed') . '</a>';
        array_unshift($links, $settings_link);
        
        // Add check for updates link
        $check_updates_url = wp_nonce_url(
            add_query_arg(
                array(
                    'aqm_action' => 'check_updates',
                    'plugin' => AQM_BLOG_POST_FEED_BASENAME
                ),
                admin_url('plugins.php')
            ),
            'aqm_check_updates_action'
        );
        $check_updates_link = '<a href="' . esc_url($check_updates_url) . '">' . __('Check for Updates', 'aqm-blog-post-feed') . '</a>';
        $links[] = $check_updates_link;
    }
    return $links;
}
add_filter('plugin_action_links', 'aqm_blog_post_feed_plugin_action_links', 10, 2);

/**
 * Handle the check for updates action
 */
function aqm_blog_post_feed_handle_update_check() {
    if (isset($_GET['aqm_action']) && $_GET['aqm_action'] === 'check_updates' && 
        isset($_GET['plugin']) && $_GET['plugin'] === AQM_BLOG_POST_FEED_BASENAME && 
        isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'aqm_check_updates_action')) {
        
        // Force WordPress to check for updates
        delete_site_transient('update_plugins');
        wp_update_plugins();
        
        // Add a transient to show a notice
        set_transient('aqm_update_check_performed', true, 30);
        
        // Redirect back to plugins page
        wp_redirect(admin_url('plugins.php'));
        exit;
    }
}

/**
 * Display admin notice after checking for updates on the plugins page
 */
function aqm_blog_post_feed_update_check_plugins_notice() {
    // Only show on plugins page
    $screen = get_current_screen();
    if ($screen->id !== 'plugins') {
        return;
    }
    
    // Check if we just performed an update check
    if (get_transient('aqm_update_check_performed')) {
        delete_transient('aqm_update_check_performed');
        ?>
        <div class="notice notice-info is-dismissible">
            <p><?php _e('Checking for AQM Blog Post Feed updates. If an update is available, it will be shown in the list below.', 'aqm-blog-post-feed'); ?></p>
        </div>
        <?php
    }
}
add_action('admin_init', 'aqm_blog_post_feed_handle_update_check');
add_action('admin_notices', 'aqm_blog_post_feed_update_check_plugins_notice');

/**
 * Force plugin activation on admin page load if needed
 * This is a last-resort approach to ensure the plugin stays activated
 */
function aqm_blog_post_feed_force_activation() {
    // Only check once per page load
    static $checked = false;
    if ($checked) return;
    $checked = true;
    
    // Only run in admin
    if (!is_admin()) return;
    
    // Check if our plugin should be active
    if (get_option('aqm_blog_post_feed_active', false) || get_transient('aqm_reactivate_after_update') === '1') {
        // If it's not active, activate it
        if (!is_plugin_active(AQM_BLOG_POST_FEED_BASENAME)) {
            error_log('AQM Debug: Force activation hook - plugin is not active but should be. Activating...');
            activate_plugin(AQM_BLOG_POST_FEED_BASENAME);
            wp_clean_plugins_cache(true);
            error_log('AQM Debug: Force activation hook - activation attempt complete');
        }
    }
}
add_action('admin_init', 'aqm_blog_post_feed_force_activation', 1); // Run very early

function aqm_blog_post_feed_divi_module() {
    if (class_exists('ET_Builder_Module')) {
        $posts_limit = 10; // Set default limit for posts to display

        // Check if a limit is set via a query parameter
        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
            $posts_limit = (int) $_GET['limit'];
        }

        include_once(plugin_dir_path(__FILE__) . 'includes/AQM_Blog_Post_Feed_Module.php');

        if (!class_exists('AQM_Blog_Post_Feed_Module')) {
            class AQM_Blog_Post_Feed_Module extends ET_Builder_Module {
                public function init() {
                    $this->name = esc_html__('AQM Blog Post Feed', 'aqm-blog-post-feed');
                    $this->slug = 'aqm_blog_post_feed';
                    $this->whitelisted_fields = array('sort_order');
                    $this->fields = $this->get_fields();
                }

                public function get_fields() {
                    $fields = array(
                        'sort_order' => array(
                            'label' => esc_html__('Sort Order', 'aqm-blog-post-feed'),
                            'type' => 'select',
                            'options' => array(
                                'date_desc' => esc_html__('Date Descending (Most Recent First)', 'aqm-blog-post-feed'),
                                'date_asc' => esc_html__('Date Ascending (Oldest First)', 'aqm-blog-post-feed'),
                                'title_asc' => esc_html__('Title Ascending', 'aqm-blog-post-feed'),
                                'title_desc' => esc_html__('Title Descending', 'aqm-blog-post-feed'),
                            ),
                            'default' => 'date_desc',
                        ),
                        // Other fields...
                    );
                    return $fields;
                }

                public function render() {
                    $sort_order = $this->props['sort_order'];
                    // Determine the order by clause based on the selected sort order
                    switch ($sort_order) {
                        case 'date_asc':
                            $order_by = 'post_date ASC';
                            break;
                        case 'date_desc':
                            $order_by = 'post_date DESC';
                            break;
                        case 'title_asc':
                            $order_by = 'post_title ASC';
                            break;
                        case 'title_desc':
                            $order_by = 'post_title DESC';
                            break;
                        default:
                            $order_by = 'post_date DESC';
                    }
                    $query = "SELECT * FROM posts ORDER BY $order_by LIMIT $posts_limit"; // Updated query with sorting based on user selection
                }
            }
        }
    }
}
add_action('et_builder_ready', 'aqm_blog_post_feed_divi_module');

// AJAX handler for the Load More functionality
function aqm_load_more_posts_handler() {
    // Verify the nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aqm_load_more_nonce')) {
        wp_send_json_error('Invalid security token');
        die();
    }
    
    // Get the parameters from the AJAX request
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 3;
    $orderby = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date';
    $order = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';
    
    // Get styling parameters
    $columns = isset($_POST['columns']) ? intval($_POST['columns']) : 3;
    $spacing = isset($_POST['spacing']) ? intval($_POST['spacing']) : 20;
    $item_border_radius = isset($_POST['item_border_radius']) ? intval($_POST['item_border_radius']) : 10;
    $title_color = isset($_POST['title_color']) ? sanitize_text_field($_POST['title_color']) : '#ffffff';
    $title_font_size = isset($_POST['title_font_size']) ? intval($_POST['title_font_size']) : 18;
    $title_line_height = isset($_POST['title_line_height']) ? (float)$_POST['title_line_height'] : 1.4;
    $content_font_size = isset($_POST['content_font_size']) ? intval($_POST['content_font_size']) : 14;
    $content_color = isset($_POST['content_color']) ? sanitize_text_field($_POST['content_color']) : '#ffffff';
    $content_line_height = isset($_POST['content_line_height']) ? (float)$_POST['content_line_height'] : 1.6;
    $content_padding = isset($_POST['content_padding']) ? sanitize_text_field($_POST['content_padding']) : '20px 20px 20px 20px';
    $meta_font_size = isset($_POST['meta_font_size']) ? intval($_POST['meta_font_size']) : 12;
    $meta_line_height = isset($_POST['meta_line_height']) ? (float)$_POST['meta_line_height'] : 1.4;
    $meta_color = isset($_POST['meta_color']) ? sanitize_text_field($_POST['meta_color']) : '#ffffff';
    $read_more_padding = isset($_POST['read_more_padding']) ? sanitize_text_field($_POST['read_more_padding']) : '10px 20px 10px 20px';
    $read_more_border_radius = isset($_POST['read_more_border_radius']) ? intval($_POST['read_more_border_radius']) : 5;
    $read_more_color = isset($_POST['read_more_color']) ? sanitize_text_field($_POST['read_more_color']) : '#ffffff';
    $read_more_bg_color = isset($_POST['read_more_bg_color']) ? sanitize_text_field($_POST['read_more_bg_color']) : '#0073e6';
    $read_more_font_size = isset($_POST['read_more_font_size']) ? intval($_POST['read_more_font_size']) : 14;
    $read_more_hover_color = isset($_POST['read_more_hover_color']) ? sanitize_text_field($_POST['read_more_hover_color']) : '#ffffff';
    $read_more_hover_bg_color = isset($_POST['read_more_hover_bg_color']) ? sanitize_text_field($_POST['read_more_hover_bg_color']) : '#005bb5';
    $excerpt_limit = isset($_POST['excerpt_limit']) ? intval($_POST['excerpt_limit']) : 60;
    $read_more_text = isset($_POST['read_more_text']) ? sanitize_text_field($_POST['read_more_text']) : 'Read More';
    $read_more_uppercase = isset($_POST['read_more_uppercase']) ? sanitize_text_field($_POST['read_more_uppercase']) : 'off';
    $background_zoom = isset($_POST['background_zoom']) ? intval($_POST['background_zoom']) : 125;
    
    // Apply uppercase style based on the setting
    $uppercase_style = $read_more_uppercase === 'on' ? 'text-transform: uppercase;' : '';
    
    // Set up query arguments for fetching more posts
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => $posts_per_page,
        'orderby' => $orderby,
        'order' => $order,
        'paged' => $page,
    );
    
    // Run the query
    $posts = new WP_Query($args);
    
    $html = '';
    $has_more = false;
    
    if ($posts->have_posts()) {
        while ($posts->have_posts()) {
            $posts->the_post();
            $author = get_the_author();
            $date = get_the_date();
            $thumbnail_url = get_the_post_thumbnail_url(null, 'large');

            // Post item with background image
            $html .= '<div class="aqm-post-item" style="border-radius: ' . esc_attr($item_border_radius) . 'px; overflow: hidden; position: relative; min-height: 300px; background-image: url(' . esc_url($thumbnail_url) . '); background-size: 110%; background-position: center; transition: background-size 0.5s ease;">';
            
            // Full height overlay
            $html .= '<div class="aqm-post-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; transition: background-color 0.3s ease;"></div>';
            
            // Full height post content with padding control
            $html .= '<div class="aqm-post-content" style="position: relative; z-index: 2; padding:' . esc_attr($content_padding) . '; color: #fff; display: flex; flex-direction: column; justify-content: flex-end;">';
            $html .= '<h3 class="aqm-post-title" style="color:' . esc_attr($title_color) . '; font-size:' . esc_attr($title_font_size) . 'px; line-height:' . esc_attr($title_line_height) . 'em; margin: 0;">' . get_the_title() . '</h3>';
            
            // Meta (author and date with icons)
            $html .= '<p class="aqm-post-meta" style="color:' . esc_attr($meta_color) . '; font-size:' . esc_attr($meta_font_size) . 'px; line-height:' . esc_attr($meta_line_height) . 'em;">';
            $html .= '<i class="fas fa-user"></i> ' . esc_html($author) . ' &nbsp;|&nbsp; ';
            $html .= '<i class="fas fa-calendar-alt"></i> ' . esc_html($date);
            $html .= '</p>';
            
            // Excerpt with line height control and word limit from content
            $html .= '<p class="aqm-post-excerpt" style="color:' . esc_attr($content_color) . '; font-size:' . esc_attr($content_font_size) . 'px; line-height:' . esc_attr($content_line_height) . 'em;">' . wp_trim_words(has_excerpt() ? get_the_excerpt() : get_the_content(), $excerpt_limit, '...') . '</p>';
            
            // Read More Button
            $html .= '<a class="aqm-read-more" href="' . get_permalink() . '" style="transition: background-color 0.5s ease, color 0.5s ease; color:' . esc_attr($read_more_color) . '; background-color:' . esc_attr($read_more_bg_color) . '; padding:' . esc_attr($read_more_padding) . '; border-radius:' . esc_attr($read_more_border_radius) . 'px; display: inline-block; margin-top: 20px; font-size:' . esc_attr($read_more_font_size) . 'px; text-decoration: none; align-self: flex-start;' . $uppercase_style . '" onmouseover="this.style.color=\'' . esc_attr($read_more_hover_color) . '\'; this.style.backgroundColor=\'' . esc_attr($read_more_hover_bg_color) . '\';" onmouseout="this.style.color=\'' . esc_attr($read_more_color) . '\'; this.style.backgroundColor=\'' . esc_attr($read_more_bg_color) . '\';">' . esc_html($read_more_text) . '</a>';
            
            $html .= '</div>'; // Close aqm-post-content
            $html .= '</div>'; // Close aqm-post-item
        }
        
        // Check if there are more posts to load
        $has_more = $posts->max_num_pages > $page;
    }
    
    wp_reset_postdata();
    
    // Return the response
    wp_send_json(json_encode(array(
        'success' => true,
        'html' => $html,
        'has_more' => $has_more
    )));
    
    die();
}
add_action('wp_ajax_aqm_load_more_posts', 'aqm_load_more_posts_handler');
add_action('wp_ajax_nopriv_aqm_load_more_posts', 'aqm_load_more_posts_handler');

/**
 * Custom update handler to properly handle GitHub updates
 */
function aqm_custom_update_handler() {
    // Security check
    if (!current_user_can('update_plugins')) {
        wp_die('Sorry, you are not allowed to update plugins for this site.');
    }
    
    // Get the requested version
    $version = isset($_GET['version']) ? sanitize_text_field($_GET['version']) : '';
    if (empty($version)) {
        wp_die('No version specified.');
    }
    
    // Log the update request
    error_log('AQM Custom Update: Starting custom update process for version ' . $version);
    
    // Get the GitHub package URL
    $package_url = 'https://github.com/JustCasey76/aqm-blog-post-feed/archive/refs/tags/v' . $version . '.zip';
    error_log('AQM Custom Update: Package URL: ' . $package_url);
    
    // Download the package
    $download_file = download_url($package_url);
    if (is_wp_error($download_file)) {
        error_log('AQM Custom Update: Download failed: ' . $download_file->get_error_message());
        wp_die('Failed to download update package: ' . $download_file->get_error_message());
    }
    
    error_log('AQM Custom Update: Package downloaded to: ' . $download_file);
    
    // Store that the plugin was active
    $plugin_slug = 'aqm-blog-post-feed/aqm-blog-post-feed.php';
    $was_active = is_plugin_active($plugin_slug);
    if ($was_active) {
        error_log('AQM Custom Update: Plugin was active before update');
        update_option('aqm_blog_post_feed_active', true);
    } else {
        error_log('AQM Custom Update: Plugin was NOT active before update');
        update_option('aqm_blog_post_feed_active', false);
    }
    
    // Get WP_Filesystem
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH . '/wp-admin/includes/file.php';
        WP_Filesystem();
    }
    
    // Create the plugins directory if it doesn't exist
    $plugins_dir = WP_PLUGIN_DIR;
    if (!$wp_filesystem->is_dir($plugins_dir)) {
        error_log('AQM Custom Update: Creating plugins directory');
        $wp_filesystem->mkdir($plugins_dir);
    }
    
    // Unzip the package
    $unzip_result = unzip_file($download_file, $plugins_dir);
    if (is_wp_error($unzip_result)) {
        error_log('AQM Custom Update: Unzip failed: ' . $unzip_result->get_error_message());
        @unlink($download_file);
        wp_die('Failed to unzip update package: ' . $unzip_result->get_error_message());
    }
    
    error_log('AQM Custom Update: Package unzipped successfully');
    
    // Clean up the zip file
    @unlink($download_file);
    
    // Get the extracted directory name
    $extracted_dir = $plugins_dir . '/aqm-blog-post-feed-' . $version;
    error_log('AQM Custom Update: Extracted directory: ' . $extracted_dir);
    
    // Remove the existing plugin directory if it exists
    $plugin_dir = $plugins_dir . '/aqm-blog-post-feed';
    if ($wp_filesystem->is_dir($plugin_dir)) {
        error_log('AQM Custom Update: Removing existing plugin directory');
        $wp_filesystem->delete($plugin_dir, true);
    }
    
    // Rename the extracted directory to the correct plugin directory
    if (!$wp_filesystem->move($extracted_dir, $plugin_dir)) {
        error_log('AQM Custom Update: Failed to rename directory');
        wp_die('Failed to rename plugin directory.');
    }
    
    error_log('AQM Custom Update: Directory renamed successfully');
    
    // Clear plugin cache
    wp_clean_plugins_cache(true);
    
    // Reactivate the plugin if it was active
    if ($was_active) {
        error_log('AQM Custom Update: Reactivating plugin');
        activate_plugin($plugin_slug);
        
        if (is_plugin_active($plugin_slug)) {
            error_log('AQM Custom Update: Plugin reactivated successfully');
        } else {
            error_log('AQM Custom Update: Failed to reactivate plugin');
        }
    }
    
    // Set a transient to indicate successful update
    set_transient('aqm_update_successful', '1', 300);
    
    // Redirect back to the plugins page
    wp_redirect(admin_url('plugins.php?aqm_updated=1'));
    exit;
}
add_action('wp_ajax_aqm_custom_update', 'aqm_custom_update_handler');

/**
 * Display a success notice after update
 */
function aqm_update_success_notice() {
    if (isset($_GET['aqm_updated']) && $_GET['aqm_updated'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>AQM Blog Post Feed plugin has been successfully updated and activated.</p></div>';
    }
}
add_action('admin_notices', 'aqm_update_success_notice');



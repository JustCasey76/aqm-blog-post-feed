<?php
/*
Plugin Name: AQM Blog Post Feed V2.9
Plugin URI: https://aqmarketing.com/
Description: A custom Divi module to display blog posts in a customizable grid with Font Awesome icons, hover effects, and more.
Version: 2.9
Author: AQ Marketing
Author URI: https://aqmarketing.com/
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('AQM_BLOG_POST_FEED_FILE', __FILE__);
define('AQM_BLOG_POST_FEED_PATH', plugin_dir_path(__FILE__));
define('AQM_BLOG_POST_FEED_BASENAME', plugin_basename(__FILE__));
define('AQM_BLOG_POST_FEED_VERSION', '2.9');

// Include admin page
require_once AQM_BLOG_POST_FEED_PATH . 'includes/admin-page.php';

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

// Initialize GitHub Updater
function aqm_blog_post_feed_check_for_plugin_update($transient) {
    // Debug log
    if (!file_exists(WP_CONTENT_DIR . '/debug-update.log')) {
        @file_put_contents(WP_CONTENT_DIR . '/debug-update.log', '');
    }
    $log = "\n\n" . date('Y-m-d H:i:s') . " - Update check triggered\n";
    
    // If no update transient or transient is empty, return
    if (empty($transient->checked)) {
        $log .= "Transient checked is empty, returning\n";
        @file_put_contents(WP_CONTENT_DIR . '/debug-update.log', $log, FILE_APPEND);
        return $transient;
    }
    
    // Log the plugins being checked
    $log .= "Plugins being checked: " . print_r($transient->checked, true) . "\n";

    // Plugin slug, path to the main plugin file, and the URL of the update server
    $plugin_slug = 'aqm-blog-post-feed/aqm-blog-post-feed.php'; // This should match the directory/file structure on the website
    $update_url = 'https://raw.githubusercontent.com/JustCasey76/aqm-blog-post-feed/main/update-info.json';
    
    $log .= "Plugin slug: {$plugin_slug}\n";
    $log .= "Update URL: {$update_url}\n";

    // Fetch update information from GitHub
    $response = wp_remote_get($update_url);
    if (is_wp_error($response)) {
        $log .= "Error fetching update info: " . $response->get_error_message() . "\n";
        @file_put_contents(WP_CONTENT_DIR . '/debug-update.log', $log, FILE_APPEND);
        return $transient;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $log .= "Response code: {$response_code}\n";
    
    // Parse the JSON response
    $response_body = wp_remote_retrieve_body($response);
    $log .= "Response body: {$response_body}\n";
    
    $update_info = json_decode($response_body);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $log .= "JSON decode error: " . json_last_error_msg() . "\n";
        @file_put_contents(WP_CONTENT_DIR . '/debug-update.log', $log, FILE_APPEND);
        return $transient;
    }
    
    $log .= "Decoded update info: " . print_r($update_info, true) . "\n";
    
    // Check if the plugin is in the transient
    if (!isset($transient->checked[$plugin_slug])) {
        // Try to find the correct plugin slug
        $log .= "Plugin slug not found in transient, searching for alternatives...\n";
        $found = false;
        
        foreach ($transient->checked as $slug => $version) {
            $log .= "Checking slug: {$slug} with version {$version}\n";
            if (strpos($slug, 'aqm-blog-post-feed') !== false) {
                $plugin_slug = $slug;
                $log .= "Found matching slug: {$plugin_slug}\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $log .= "No matching plugin slug found\n";
            @file_put_contents(WP_CONTENT_DIR . '/debug-update.log', $log, FILE_APPEND);
            return $transient;
        }
    }

    // If a new version is available, modify the transient to reflect the update
    $current_version = isset($transient->checked[$plugin_slug]) ? $transient->checked[$plugin_slug] : '0';
    $log .= "Current version: {$current_version}, Latest version: {$update_info->new_version}\n";
    
    if (version_compare($current_version, $update_info->new_version, '<')) {
        $log .= "New version available, adding to update list\n";
        $plugin_data = array(
            'slug'        => 'aqm-blog-post-feed',
            'plugin'      => $plugin_slug,
            'new_version' => $update_info->new_version,
            'url'         => $update_info->url,
            'package'     => $update_info->package, // URL of the plugin zip file
        );
        $transient->response[$plugin_slug] = (object) $plugin_data;
        $log .= "Added to transient response\n";
    } else {
        $log .= "No new version available\n";
    }
    
    // Write debug log
    @file_put_contents(WP_CONTENT_DIR . '/debug-update.log', $log, FILE_APPEND);
    
    return $transient;
}
add_filter('pre_set_site_transient_update_plugins', 'aqm_blog_post_feed_check_for_plugin_update');

// Add function to reactivate plugin after update
function aqm_blog_post_feed_maybe_reactivate() {
    error_log('AQM Debug: aqm_blog_post_feed_maybe_reactivate running.');
    
    // Only run in admin
    if (!is_admin()) {
        error_log('AQM Debug: Not admin, exiting.');
        return;
    }

    // Check if the transient was set by the updater hook
    if (get_transient('aqm_reactivate_after_update') === '1') {
        error_log('AQM Debug: Reactivation transient found.');
        // Delete the transient so it doesn't run again
        delete_transient('aqm_reactivate_after_update');

        // Ensure the plugin file actually exists before trying to activate
        if (!file_exists(WP_PLUGIN_DIR . '/' . AQM_BLOG_POST_FEED_BASENAME)) {
            error_log('AQM Debug: Plugin file MISSING, cannot reactivate: ' . WP_PLUGIN_DIR . '/' . AQM_BLOG_POST_FEED_BASENAME);
            return; // Exit if the plugin file isn't there
        }
        
        // Check if already active (maybe it worked this time?)
        if (is_plugin_active(AQM_BLOG_POST_FEED_BASENAME)) {
            error_log('AQM Debug: Plugin already active, no reactivation needed.');
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
    if ($should_be_active && !$is_active) {
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
        
        // Redirect to updates page
        wp_redirect(admin_url('update-core.php'));
        exit;
    }
}
add_action('admin_init', 'aqm_blog_post_feed_handle_update_check');

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

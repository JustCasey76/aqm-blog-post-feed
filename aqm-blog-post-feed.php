<?php
/*
Plugin Name: AQM Blog Post Feed
Plugin URI: https://aqmarketing.com/
Description: A custom Divi module to display blog posts in a customizable grid with Font Awesome icons, hover effects, and more.
Version: 1.0.37
Author: AQ Marketing
Author URI: https://aqmarketing.com/
GitHub Plugin URI: https://github.com/JustCasey76/aqm-blog-post-feed
Primary Branch: main
Requires at least: 5.2
Requires PHP: 7.2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Version for cache busting
define('AQM_BLOG_POST_FEED_VERSION', '1.0.37');
define('AQM_BLOG_POST_FEED_FILE', __FILE__);
define('AQM_BLOG_POST_FEED_PATH', plugin_dir_path(__FILE__));
define('AQM_BLOG_POST_FEED_BASENAME', plugin_basename(__FILE__));

// Set up text domain for translations
function aqm_blog_post_feed_load_textdomain() {
    load_plugin_textdomain('aqm-blog-post-feed', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'aqm_blog_post_feed_load_textdomain');

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
    // Store that the plugin was active before deactivation
    update_option('aqm_blog_post_feed_was_active', false);
}

// Include the GitHub Updater class
require_once plugin_dir_path(__FILE__) . 'includes/class-aqmbpf-updater.php';

// Initialize the GitHub Updater
function aqm_blog_post_feed_init_github_updater() {
    // Log that we're initializing the updater
    error_log('=========================================================');
    error_log('[AQM BLOG POST FEED v' . AQM_BLOG_POST_FEED_VERSION . '] USING CUSTOM UPDATER CLASS');
    error_log('=========================================================');
    
    if (class_exists('AQMBPF_Updater')) {
        try {
            new AQMBPF_Updater(
                __FILE__,                // Plugin File
                'JustCasey76',           // GitHub username
                'aqm-blog-post-feed',    // GitHub repository name
                ''                       // Optional GitHub access token (for private repos)
            );
            
            // Set last update check time
            update_option('aqm_blog_post_feed_last_update_check', time());
        } catch (Exception $e) {
            error_log('[AQM BLOG POST FEED] Error initializing updater: ' . $e->getMessage());
        }
    } else {
        error_log('[AQM BLOG POST FEED] Updater class not found');
    }
}
add_action('admin_init', 'aqm_blog_post_feed_init_github_updater');

// Show update success message
function aqm_blog_post_feed_show_update_success() {
    // Only show on plugins page
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'plugins') {
        return;
    }
    
    // Check if we're coming from an update
    if (isset($_GET['aqm_updated']) && $_GET['aqm_updated'] === '1') {
        echo '<div class="notice notice-success is-dismissible">
            <p><strong>AQM Blog Post Feed Updated Successfully!</strong> The plugin has been updated to version ' . AQM_BLOG_POST_FEED_VERSION . '.</p>
        </div>';
    }
    
    // Check if we're showing a reactivation notice
    if (get_transient('aqmbpf_reactivated')) {
        // Delete the transient
        delete_transient('aqmbpf_reactivated');
        
        echo '<div class="notice notice-success is-dismissible">
            <p><strong>AQM Blog Post Feed Reactivated!</strong> The plugin has been reactivated after an update.</p>
        </div>';
    }
}
add_action('admin_notices', 'aqm_blog_post_feed_show_update_success');

/**
 * Handle the AJAX request to check for plugin updates.
 */
function aqm_blog_post_feed_handle_check_updates_ajax() {
    // Verify nonce
    check_ajax_referer('aqm-blog-post-feed-check-updates', 'nonce');
    
    // Clear update transients to force a fresh check
    delete_transient('aqmbpf_github_data_' . md5('JustCasey76' . 'aqm-blog-post-feed'));
    delete_site_transient('update_plugins');
    
    // Force WordPress to check for updates
    wp_clean_plugins_cache(true);
    
    // Log the manual update check
    error_log('[AQM BLOG POST FEED] Manual update check triggered');
    
    // Update the last check time
    update_option('aqm_blog_post_feed_last_update_check', time());
    
    // Send success response
    wp_send_json_success(array('message' => 'Update check completed successfully.'));
}
add_action('wp_ajax_aqm_blog_post_feed_check_updates', 'aqm_blog_post_feed_handle_check_updates_ajax');

/**
 * Attempts to reactivate the plugin after an update is complete.
 * Hooks into 'upgrader_process_complete'.
 * 
 * @param WP_Upgrader $upgrader_object WP_Upgrader instance.
 * @param array       $options         Array of bulk item update data.
 */
function aqm_blog_post_feed_reactivate_on_update($upgrader_object, $options) {
    // Check if this is a plugin update
    if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
        return;
    }
    
    // Get the plugin basename
    $plugin_basename = plugin_basename(__FILE__);
    
    // Check if our plugin was updated
    if (!isset($options['plugins']) || !in_array($plugin_basename, $options['plugins'])) {
        return;
    }
    
    error_log('[AQM BLOG POST FEED] Plugin update detected, checking activation state');
    
    // Check if the plugin was active before the update
    if (get_option('aqm_blog_post_feed_was_active', false)) {
        // Make sure plugin functions are loaded
        if (!function_exists('is_plugin_active') || !function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // If plugin is not active, reactivate it
        if (!is_plugin_active($plugin_basename)) {
            error_log('[AQM BLOG POST FEED] Plugin was active before update but is now inactive, reactivating');
            
            // Reactivate the plugin
            $result = activate_plugin($plugin_basename);
            
            if (is_wp_error($result)) {
                error_log('[AQM BLOG POST FEED] Reactivation failed: ' . $result->get_error_message());
            } else {
                error_log('[AQM BLOG POST FEED] Plugin successfully reactivated');
                
                // Set a transient to show a notice
                set_transient('aqmbpf_reactivated', true, 30);
            }
            
            // Clear plugin cache
            wp_clean_plugins_cache(true);
        }
    }
}
add_action('upgrader_process_complete', 'aqm_blog_post_feed_reactivate_on_update', 10, 2);

/**
 * Add custom action links to the plugin entry on the plugins page.
 *
 * @param array $links An array of plugin action links.
 * @return array An array of plugin action links.
 */
function aqm_blog_post_feed_add_action_links($links) {
    // Add 'Check for Updates' link
    $check_update_link = '<a href="' . wp_nonce_url(admin_url('admin-ajax.php?action=aqm_blog_post_feed_check_updates'), 'aqm-blog-post-feed-check-updates') . '" class="aqm-blog-check-updates">Check for Updates</a>';
    array_unshift($links, $check_update_link);
    
    return $links;
}
add_filter('plugin_action_links_' . AQM_BLOG_POST_FEED_BASENAME, 'aqm_blog_post_feed_add_action_links');

/**
 * Enqueue admin scripts specifically for the plugins page.
 *
 * @param string $hook The current admin page.
 */
function aqm_blog_post_feed_enqueue_admin_scripts($hook) {
    if ($hook !== 'plugins.php') {
        return;
    }
    
    // Enqueue the script
    wp_enqueue_script(
        'aqm-blog-post-feed-admin-js',
        plugins_url('assets/js/admin-updates.js', __FILE__),
        array('jquery'),
        AQM_BLOG_POST_FEED_VERSION,
        true
    );
    
    // Localize the script with our data
    wp_localize_script(
        'aqm-blog-post-feed-admin-js',
        'aqmBlogFeedData',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aqm-blog-post-feed-check-updates'),
            'checkingText' => 'Checking for updates...',
            'successText' => 'Update check complete!',
            'errorText' => 'Error checking for updates.'
        )
    );
}
add_action('admin_enqueue_scripts', 'aqm_blog_post_feed_enqueue_admin_scripts');

// Function to initialize the Divi module
function aqm_bpf_initialize_module() {
    // Ensure Divi's base module class exists before loading our module
    if ( class_exists('ET_Builder_Module') ) {
        require_once plugin_dir_path( __FILE__ ) . 'includes/AQM_Blog_Post_Feed_Module.php';
    } else {
        // Optionally log an error or display an admin notice if Divi isn't active
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo esc_html__( 'AQM Blog Post Feed requires the Divi Builder to be active.', 'aqm-blog-post-feed' );
            echo '</p></div>';
        });
    }
}
// Hook the initialization function to Divi's ready action
add_action( 'et_builder_ready', 'aqm_bpf_initialize_module' );

function aqm_blog_post_feed_divi_module() {
    if (class_exists('ET_Builder_Module')) {
        $posts_limit = 10; // Set default limit for posts to display

        // Check if a limit is set via a query parameter
        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
            $posts_limit = (int) $_GET['limit'];
        }

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

// This function has been replaced by the new implementation above

// This function has been replaced by the new implementation above

// This function has been replaced by the new implementation above
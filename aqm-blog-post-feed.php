<?php
/*
Plugin Name: AQM Blog Post Feed
Plugin URI: https://aqmarketing.com/
Description: A custom Divi module to display blog posts in a customizable grid with Font Awesome icons, hover effects, and more.
Version: 1.0.8
Author: AQ Marketing
Author URI: https://aqmarketing.com/
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('AQM_BLOG_POST_FEED_FILE', __FILE__);
define('AQM_BLOG_POST_FEED_PATH', plugin_dir_path(__FILE__));
define('AQM_BLOG_POST_FEED_BASENAME', plugin_basename(__FILE__));

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
function aqm_github_updater_init() {
    // Include the updater class
    require_once plugin_dir_path(__FILE__) . 'includes/class-github-updater.php';
    
    // Initialize the updater
    new AQM_Blog_Post_Feed_GitHub_Updater(
        __FILE__,                        // Plugin File
        'JustCasey76',                   // GitHub username
        'aqm-blog-post-feed',            // GitHub repository name
        ''                               // Optional GitHub access token (for private repos)
    );
}
add_action('init', 'aqm_github_updater_init');

// Conditionally load the GitHub updater class only in the admin area
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-github-updater.php';
    // Instantiate the updater with required arguments
    new AQM_Blog_Post_Feed_GitHub_Updater( __FILE__, 'JustCasey76', 'aqm-blog-post-feed' );
}

/**
 * Attempts to reactivate the plugin after an update is complete.
 * Hooks into 'upgrader_process_complete'.
 *
 * @param WP_Upgrader $upgrader_object WP_Upgrader instance.
 * @param array       $options         Array of bulk item update data.
 */
function aqm_blog_post_feed_reactivate_on_update( $upgrader_object, $options ) {
    // Check if the transient was set
    if ( get_transient( 'aqm_reactivate_after_update' ) === '1' ) {
        error_log('[AQM BPF Reactivate] Reactivation transient found.');
        // Delete the transient so we don't try again unnecessarily
        delete_transient( 'aqm_reactivate_after_update' );

        // Check if the update action was for plugins and specifically our plugin
        $current_plugin_path = plugin_basename( __FILE__ );
        if ( isset( $options['action'], $options['type'], $options['plugins'] ) &&
             $options['action'] === 'update' && $options['type'] === 'plugin' ) {

            $reactivated = false;
            foreach ( $options['plugins'] as $plugin ) {
                if ( $plugin === $current_plugin_path ) {
                    error_log('[AQM BPF Reactivate] Update process completed for our plugin: ' . esc_html($current_plugin_path));
                    // Check if the plugin is inactive
                    if ( ! is_plugin_active( $current_plugin_path ) ) {
                        error_log('[AQM BPF Reactivate] Plugin is inactive, attempting reactivation...');
                        $result = activate_plugin( $current_plugin_path );
                        if ( is_wp_error( $result ) ) {
                            error_log('[AQM BPF Reactivate] Error reactivating plugin: ' . $result->get_error_message());
                        } else {
                            error_log('[AQM BPF Reactivate] Plugin successfully reactivated.');
                            $reactivated = true;
                        }
                    } else {
                        error_log('[AQM BPF Reactivate] Plugin is already active.');
                    }
                    break; // Found our plugin, no need to loop further
                }
            }
        } else {
            error_log('[AQM BPF Reactivate] Update hook fired, but action/type/plugin info not as expected or did not match.');
            error_log('[AQM BPF Reactivate] Options array: ' . print_r($options, true)); // Log options for debugging
        }

    } else {
         // Optional: Log if the hook fired but transient wasn't found
         // error_log('[AQM BPF Reactivate] upgrader_process_complete hook fired, but reactivation transient not found.');
    }
}
add_action( 'upgrader_process_complete', 'aqm_blog_post_feed_reactivate_on_update', 10, 2 );

// Add a notice to show when the plugin has been reactivated
function aqm_blog_post_feed_reactivation_notice() {
    if (isset($_GET['aqm_reactivated']) && $_GET['aqm_reactivated'] === '1') {
        echo '<div class="notice notice-success is-dismissible"><p>AQM Blog Post Feed plugin has been automatically reactivated.</p></div>';
    }
}
add_action('admin_notices', 'aqm_blog_post_feed_reactivation_notice');

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

/**
 * Add custom action links to the plugin entry on the plugins page.
 *
 * @param array  $links An array of plugin action links.
 * @return array An array of plugin action links.
 */
function aqm_blog_post_feed_add_action_links( $links ) {
    // Change the link to use '#' and add an ID for JS targeting
    $check_updates_link = '<a href="#" id="aqm-check-updates-link">' . esc_html__( 'Check for Updates', 'aqm-blog-post-feed' ) . '</a>';
    // Add a placeholder for status messages
    $check_updates_link .= '<span id="aqm-update-status" style="margin-left: 5px;"></span>';
    // Add the link before other links
    return array_merge( array( 'check_updates' => $check_updates_link ), $links );
}
add_filter( 'plugin_action_links_' . AQM_BLOG_POST_FEED_BASENAME, 'aqm_blog_post_feed_add_action_links' );

/**
 * Enqueue admin scripts specifically for the plugins page.
 */
function aqm_enqueue_admin_scripts($hook) {
    // Only load on the plugins page
    if ('plugins.php' !== $hook) {
        return;
    }
    
    $script_path = plugin_dir_url(__FILE__) . 'assets/js/admin-updates.js';
    $script_asset_path = plugin_dir_path(__FILE__) . 'assets/js/admin-updates.asset.php';

    // Use asset file if it exists for dependencies and versioning
    $dependencies = array('jquery');
    $version = '1.0.0'; // Fallback version
    if (file_exists($script_asset_path)) {
        $asset_info = include($script_asset_path);
        $dependencies = $asset_info['dependencies'];
        $version = $asset_info['version'];
    }

    wp_enqueue_script(
        'aqm-admin-updates', 
        $script_path, 
        $dependencies, 
        $version, 
        true // Load in footer
    );

    // Pass data to the script
    wp_localize_script('aqm-admin-updates', 'aqm_update_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('aqm_check_updates_nonce'),
        'checking_text' => esc_html__('Checking...', 'aqm-blog-post-feed'),
        'success_text'  => esc_html__('Check initiated. Refresh or visit Updates page.', 'aqm-blog-post-feed'),
        'error_text'    => esc_html__('Error checking updates.', 'aqm-blog-post-feed')
    ));
}
add_action('admin_enqueue_scripts', 'aqm_enqueue_admin_scripts');

/**
 * Handle the AJAX request to check for plugin updates.
 */
function aqm_handle_check_updates_ajax() {
    // Verify nonce
    check_ajax_referer('aqm_check_updates_nonce', 'nonce');

    // Clear the transient to force a check on next load/update page visit
    delete_site_transient('update_plugins');
    
    // Optionally, force an immediate check (can be resource-intensive)
    // wp_update_plugins(); 

    // Send success response
    wp_send_json_success(array('message' => 'Update check initiated.'));
}
add_action('wp_ajax_aqm_check_plugin_updates', 'aqm_handle_check_updates_ajax');
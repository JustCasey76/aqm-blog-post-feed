<?php
/**
 * Admin Page for AQM Blog Post Feed
 * 
 * Provides admin settings and functionality for the plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Register the admin menu item
 */
function aqm_blog_post_feed_admin_menu() {
    add_submenu_page(
        'options-general.php',
        'AQM Blog Post Feed Settings',
        'AQM Blog Post Feed',
        'manage_options',
        'aqm-blog-post-feed',
        'aqm_blog_post_feed_admin_page'
    );
}
add_action('admin_menu', 'aqm_blog_post_feed_admin_menu');

/**
 * Enqueue admin styles
 */
function aqm_blog_post_feed_admin_styles($hook) {
    // Only load on plugin admin page
    if ($hook !== 'settings_page_aqm-blog-post-feed') {
        return;
    }
    
    wp_enqueue_style(
        'aqm-blog-post-feed-admin-styles',
        plugins_url('admin-styles.css', __FILE__),
        array(),
        AQM_BLOG_POST_FEED_VERSION
    );
}
add_action('admin_enqueue_scripts', 'aqm_blog_post_feed_admin_styles');

/**
 * Display the admin page
 */
function aqm_blog_post_feed_admin_page() {
    // Check if the update check was triggered
    if (isset($_POST['aqm_check_updates']) && check_admin_referer('aqm_check_updates_nonce')) {
        // Force WordPress to check for updates
        delete_site_transient('update_plugins');
        wp_update_plugins();
        
        // Add admin notice
        add_action('admin_notices', 'aqm_update_check_notice');
        
        // Redirect to updates page
        wp_redirect(admin_url('update-core.php'));
        exit;
    }
    
    // Get plugin data
    $plugin_data = get_plugin_data(AQM_BLOG_POST_FEED_FILE);
    
    // Get update info from GitHub
    $update_info = aqm_blog_post_feed_get_remote_version();
    $has_update = false;
    $latest_version = '';
    
    if ($update_info && isset($update_info->new_version)) {
        $latest_version = $update_info->new_version;
        $has_update = version_compare($plugin_data['Version'], $latest_version, '<');
    }
    
    ?>
    <div class="wrap">
        <div class="aqm-admin-header">
            <h1><?php echo esc_html($plugin_data['Name']); ?> Settings</h1>
            <p><?php echo wp_kses_post($plugin_data['Description']); ?></p>
        </div>
        
        <div class="aqm-card">
            <h2>Plugin Information</h2>
            
            <div class="aqm-version-info">
                <span><strong>Current Version:</strong></span>
                <span class="current-version"><?php echo esc_html($plugin_data['Version']); ?></span>
                
                <?php if ($latest_version): ?>
                    <span style="margin-left: 20px;"><strong>Latest Version:</strong></span>
                    <span class="current-version" style="<?php echo $has_update ? 'background:#e5f7ed;' : ''; ?>">
                        <?php echo esc_html($latest_version); ?>
                        <?php if ($has_update): ?>
                            <span style="color: #00a32a;"> ✓ Update Available</span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <p><strong>Author:</strong> <?php echo wp_kses_post($plugin_data['Author']); ?></p>
            <p><strong>Plugin URI:</strong> <a href="<?php echo esc_url($plugin_data['PluginURI']); ?>" target="_blank"><?php echo esc_html($plugin_data['PluginURI']); ?></a></p>
            
            <div class="aqm-check-updates-form">
                <form method="post" action="">
                    <?php wp_nonce_field('aqm_check_updates_nonce'); ?>
                    <button type="submit" name="aqm_check_updates" class="button button-primary">
                        <span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Check for Updates
                    </button>
                </form>
                
                <?php if ($has_update): ?>
                    <p style="margin-top: 15px;">
                        <a href="<?php echo esc_url(admin_url('update-core.php')); ?>" class="button">
                            <span class="dashicons dashicons-download" style="margin-top: 3px;"></span> Go to Updates Page
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="aqm-card">
            <h2>Usage Instructions</h2>
            <p>The AQM Blog Post Feed plugin adds a custom Divi module to display blog posts in a customizable grid with various options.</p>
            
            <ul class="aqm-usage-list">
                <li>Add the <strong>AQM Blog Post Feed</strong> module to your Divi page or post</li>
                <li>Customize the appearance through the module settings</li>
                <li>Adjust the number of posts, categories, and display options</li>
                <li>Configure hover effects, icons, and other visual elements</li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Display admin notice after checking for updates
 */
function aqm_update_check_notice() {
    ?>
    <div class="notice notice-info is-dismissible">
        <p><?php _e('Checking for AQM Blog Post Feed updates. If an update is available, it will be shown in the list below.', 'aqm-blog-post-feed'); ?></p>
    </div>
    <?php
}

/**
 * Get the remote version information from GitHub
 * 
 * @return object|false The update information or false on failure
 */
function aqm_blog_post_feed_get_remote_version() {
    // URL to the update info JSON file
    $update_url = 'https://raw.githubusercontent.com/JustCasey76/aqm-blog-post-feed/main/update-info.json';
    
    // Get the remote version info
    $response = wp_remote_get($update_url, array(
        'timeout' => 10,
        'headers' => array(
            'Accept' => 'application/json'
        )
    ));
    
    // Check for errors
    if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
        return false;
    }
    
    // Parse the response
    $response_body = wp_remote_retrieve_body($response);
    $update_info = json_decode($response_body);
    
    // Validate the response
    if (!is_object($update_info) || !isset($update_info->new_version) || !isset($update_info->package)) {
        return false;
    }
    
    return $update_info;
}

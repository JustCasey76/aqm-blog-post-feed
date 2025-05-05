<?php
/**
 * AQM GitHub Updater Class
 * 
 * This class provides update notifications for the AQM Blog Post Feed plugin
 * by checking the GitHub repository for new releases.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AQM_Blog_Post_Feed_GitHub_Updater {
    private $plugin_file;
    private $plugin_basename;
    private $github_username;
    private $github_repository;
    private $plugin_data;
    private $current_version;
    private $transient_name;
    private $cache_expiration = 43200; // 12 hours in seconds

    /**
     * Class constructor
     * 
     * @param string $plugin_file Path to the plugin file
     * @param string $github_username GitHub username
     * @param string $github_repository GitHub repository name
     */
    public function __construct($plugin_file, $github_username, $github_repository) {
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->github_username = $github_username;
        $this->github_repository = $github_repository;
        $this->transient_name = 'aqm_github_update_' . md5($this->plugin_basename);
        
        // Get current plugin data
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $this->plugin_data = get_plugin_data($this->plugin_file);
        $this->current_version = $this->plugin_data['Version'];
        
        // Log initialization
        error_log('=========================================================');
        error_log('[AQM BPF UPDATER FIXED] INITIALIZED - VERSION ' . $this->current_version);
        error_log('[AQM BPF UPDATER FIXED] PLUGIN: ' . $this->plugin_basename);
        error_log('[AQM BPF UPDATER FIXED] GITHUB REPO: ' . $this->github_username . '/' . $this->github_repository);
        error_log('=========================================================');
        
        // Add filters for plugin update checks
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        
        // Fix directory structure during updates
        add_filter('upgrader_source_selection', array($this, 'fix_directory_name'), 1, 4);
        
        // Handle plugin activation after update
        add_filter('upgrader_pre_install', array($this, 'pre_install'), 10, 2);
        add_action('upgrader_process_complete', array($this, 'post_install'), 10, 2);
        add_action('admin_init', array($this, 'maybe_reactivate_plugin'));
    }

    /**
     * Check for updates when WordPress checks for plugin updates
     * 
     * @param object $transient Transient data for plugin updates
     * @return object Modified transient data
     */
    public function check_for_updates($transient) {
        // Always check for updates, even if the transient is empty
        if (!is_object($transient)) {
            $transient = new stdClass();
        }
        
        if (!isset($transient->checked)) {
            $transient->checked = array();
        }
        
        // Force our plugin to be in the checked list
        $transient->checked[$this->plugin_basename] = $this->current_version;
        
        // Get update data from GitHub
        $update_data = $this->get_github_update_data();
        
        if ($update_data && version_compare($update_data['version'], $this->current_version, '>')) {
            // Create a standard plugin_information object
            $obj = new stdClass();
            $obj->id = $this->plugin_basename;
            $obj->slug = dirname($this->plugin_basename);
            $obj->plugin = $this->plugin_basename;
            $obj->new_version = $update_data['version'];
            $obj->url = $update_data['url'];
            $obj->package = $update_data['download_url'];
            $obj->tested = isset($update_data['tested']) ? $update_data['tested'] : '';
            $obj->requires_php = isset($update_data['requires_php']) ? $update_data['requires_php'] : '';
            $obj->compatibility = new stdClass();
            
            // Add to the response array
            if (!isset($transient->response)) {
                $transient->response = array();
            }
            
            $transient->response[$this->plugin_basename] = $obj;
            
            // Log that we found an update
            error_log('=========================================================');
            error_log('[AQM BPF UPDATER FIXED] UPDATE AVAILABLE: ' . $update_data['version']);
            error_log('[AQM BPF UPDATER FIXED] DOWNLOAD URL: ' . $update_data['download_url']);
            error_log('=========================================================');
        }
        
        return $transient;
    }

    /**
     * Get update data from GitHub
     * 
     * @param bool $force_check Whether to force a fresh check ignoring cache
     * @return array|false Update data or false on failure
     */
    private function get_github_update_data($force_check = false) {
        // Check cache first unless forcing a check
        if (!$force_check) {
            $cached_data = get_transient($this->transient_name);
            if ($cached_data !== false) {
                return $cached_data;
            }
        }
        
        // Get latest release from GitHub API
        $api_url = 'https://api.github.com/repos/' . $this->github_username . '/' . $this->github_repository . '/releases/latest';
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $release_data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($release_data) || !isset($release_data['tag_name'])) {
            return false;
        }
        
        // Format version number (remove 'v' prefix if present)
        $version = ltrim($release_data['tag_name'], 'v');
        
        // Use GitHub's zipball_url which is more reliable
        $download_url = isset($release_data['zipball_url']) ? $release_data['zipball_url'] : '';
        
        // If zipball_url is not available, fall back to a direct download URL
        if (empty($download_url)) {
            $download_url = 'https://github.com/' . $this->github_username . '/' . $this->github_repository . '/archive/refs/tags/' . $release_data['tag_name'] . '.zip';
        }
        
        $update_data = array(
            'version' => $version,
            'url' => isset($release_data['html_url']) ? $release_data['html_url'] : '',
            'download_url' => $download_url,
            'requires_php' => isset($this->plugin_data['RequiresPHP']) ? $this->plugin_data['RequiresPHP'] : '',
            'tested' => isset($this->plugin_data['RequiresWP']) ? $this->plugin_data['RequiresWP'] : '',
            'last_updated' => isset($release_data['published_at']) ? date('Y-m-d', strtotime($release_data['published_at'])) : '',
            'changelog' => isset($release_data['body']) ? $release_data['body'] : ''
        );
        
        // Cache the data
        set_transient($this->transient_name, $update_data, $this->cache_expiration);
        
        return $update_data;
    }

    /**
     * Provide plugin information for the WordPress updates screen
     * 
     * @param false|object|array $result The result object or array
     * @param string $action The API action being performed
     * @param object $args Plugin API arguments
     * @return false|object Plugin information
     */
    public function plugin_info($result, $action, $args) {
        // Check if this is the right plugin
        if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== dirname($this->plugin_basename)) {
            return $result;
        }
        
        $update_data = $this->get_github_update_data();
        
        if (!$update_data) {
            return $result;
        }
        
        $plugin_info = new stdClass();
        $plugin_info->name = $this->plugin_data['Name'];
        $plugin_info->slug = dirname($this->plugin_basename);
        $plugin_info->version = $update_data['version'];
        $plugin_info->author = $this->plugin_data['Author'];
        $plugin_info->author_profile = 'https://github.com/' . $this->github_username;
        $plugin_info->homepage = $this->plugin_data['PluginURI'];
        $plugin_info->requires = $update_data['tested'];
        $plugin_info->requires_php = $update_data['requires_php'];
        $plugin_info->downloaded = 0;
        $plugin_info->last_updated = $update_data['last_updated'];
        $plugin_info->sections = array(
            'description' => $this->plugin_data['Description'],
            'changelog' => $update_data['changelog']
        );
        $plugin_info->download_link = $update_data['download_url'];
        
        return $plugin_info;
    }

    /**
     * Before installation, check if the plugin is active and set a transient
     * 
     * @param bool $return Whether to proceed with installation
     * @param array $hook_extra Extra data about the plugin being updated
     * @return bool Whether to proceed with installation
     */
    public function pre_install($return, $hook_extra) {
        // Check if this is our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_basename) {
            return $return;
        }
        
        // Check if the plugin is active
        if (is_plugin_active($this->plugin_basename)) {
            // Set a transient to reactivate the plugin after update
            set_transient('aqm_blog_post_feed_was_active', true, 5 * MINUTE_IN_SECONDS);
            error_log('=========================================================');
            error_log('[AQM BPF UPDATER FIXED] PLUGIN WAS ACTIVE, SETTING TRANSIENT');
            error_log('=========================================================');
        }
        
        return $return;
    }

    /**
     * After installation, check if we need to reactivate the plugin
     * 
     * @param WP_Upgrader $upgrader_object WP_Upgrader instance
     * @param array $options Array of bulk item update data
     */
    public function post_install($upgrader_object, $options) {
        // Check if this is a plugin update
        if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
            return;
        }
        
        // Check if our plugin was updated
        if (!isset($options['plugins']) || !in_array($this->plugin_basename, $options['plugins'])) {
            return;
        }
        
        // Set a transient to reactivate on next admin page load
        // This is a fallback in case the plugin can't be activated immediately
        set_transient('aqm_blog_post_feed_reactivate', true, 5 * MINUTE_IN_SECONDS);
        
        error_log('=========================================================');
        error_log('[AQM BPF UPDATER FIXED] UPDATE COMPLETE, SETTING REACTIVATION TRANSIENT');
        error_log('=========================================================');
        
        // Try to reactivate the plugin now
        if (get_transient('aqm_blog_post_feed_was_active')) {
            // Delete the transient
            delete_transient('aqm_blog_post_feed_was_active');
            
            // Make sure plugin functions are loaded
            if (!function_exists('activate_plugin')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            
            // Reactivate the plugin
            $result = activate_plugin($this->plugin_basename);
            
            if (is_wp_error($result)) {
                error_log('=========================================================');
                error_log('[AQM BPF UPDATER FIXED] REACTIVATION FAILED: ' . $result->get_error_message());
                error_log('=========================================================');
            } else {
                error_log('=========================================================');
                error_log('[AQM BPF UPDATER FIXED] PLUGIN SUCCESSFULLY REACTIVATED');
                error_log('=========================================================');
                
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
    public function maybe_reactivate_plugin() {
        // Check if the reactivation transient exists
        if (get_transient('aqm_blog_post_feed_reactivate')) {
            // Delete the transient
            delete_transient('aqm_blog_post_feed_reactivate');
            
            error_log('=========================================================');
            error_log('[AQM BPF UPDATER FIXED] ATTEMPTING REACTIVATION ON ADMIN PAGE LOAD');
            error_log('=========================================================');
            
            // Make sure plugin functions are loaded
            if (!function_exists('activate_plugin')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            
            // Reactivate the plugin
            $result = activate_plugin($this->plugin_basename);
            
            if (is_wp_error($result)) {
                error_log('=========================================================');
                error_log('[AQM BPF UPDATER FIXED] REACTIVATION FAILED: ' . $result->get_error_message());
                error_log('=========================================================');
            } else {
                error_log('=========================================================');
                error_log('[AQM BPF UPDATER FIXED] PLUGIN SUCCESSFULLY REACTIVATED');
                error_log('=========================================================');
            }
            
            // Clear plugin cache
            wp_clean_plugins_cache(true);
        }
    }

    /**
     * Fix directory name during plugin update
     * 
     * @param string $source Source directory
     * @param string $remote_source Remote source
     * @param object $upgrader Upgrader instance
     * @param array $hook_extra Extra arguments
     * @return string Modified source
     */
    public function fix_directory_name($source, $remote_source, $upgrader, $hook_extra) {
        global $wp_filesystem;
        
        error_log('=========================================================');
        error_log('[AQM BPF UPDATER FIXED] DIRECTORY RENAMING HOOK FIRED');
        error_log('[AQM BPF UPDATER FIXED] SOURCE: ' . $source);
        error_log('[AQM BPF UPDATER FIXED] REMOTE SOURCE: ' . $remote_source);
        error_log('=========================================================');
        
        // Check if we're dealing with a plugin update
        if (!isset($hook_extra['plugin']) && isset($hook_extra['theme'])) {
            error_log('[AQM BPF UPDATER FIXED] This is a theme update, not our plugin');
            return $source; // This is a theme update, not our plugin
        }
        
        // For plugin updates, check if it's our plugin
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] !== $this->plugin_basename) {
            error_log('[AQM BPF UPDATER FIXED] Not our plugin: ' . $hook_extra['plugin'] . ' vs ' . $this->plugin_basename);
            return $source; // Not our plugin
        }
        
        // For core updates or bulk updates where plugin isn't specified
        if (!isset($hook_extra['plugin'])) {
            // Try to detect if this is our plugin based on the source directory
            $plugin_slug = dirname($this->plugin_basename);
            $source_basename = basename($source);
            
            // Check if the source directory contains our repository name
            if (strpos($source_basename, $this->github_repository) === false) {
                error_log('[AQM BPF UPDATER FIXED] Likely not our plugin (bulk update)');
                return $source; // Likely not our plugin
            }
            
            error_log('[AQM BPF UPDATER FIXED] Detected potential plugin update during bulk update');
        }
        
        // Get the expected plugin slug (folder name)
        $plugin_slug = dirname($this->plugin_basename);
        error_log('[AQM BPF UPDATER FIXED] Plugin slug: ' . $plugin_slug);
        
        // Check if the source directory already has the correct name
        $source_basename = basename($source);
        if ($source_basename === $plugin_slug) {
            error_log('[AQM BPF UPDATER FIXED] Source directory already has the correct name');
            return $source;
        }
        
        // GitHub zipball typically creates a directory like 'username-repository-hash' or 'repository-tag'
        // We need to rename it to match our plugin slug
        $correct_directory = trailingslashit($remote_source) . $plugin_slug;
        error_log('[AQM BPF UPDATER FIXED] Target directory: ' . $correct_directory);
        
        // If the target directory already exists, remove it first
        if ($wp_filesystem->exists($correct_directory)) {
            error_log('[AQM BPF UPDATER FIXED] Target directory exists, removing it');
            $wp_filesystem->delete($correct_directory, true);
        }
        
        // Check if source directory exists
        if (!$wp_filesystem->exists($source)) {
            error_log('[AQM BPF UPDATER FIXED] Source directory does not exist: ' . $source);
            return $source;
        }
        
        // Rename the directory
        error_log('[AQM BPF UPDATER FIXED] Attempting to rename ' . $source . ' to ' . $correct_directory);
        if ($wp_filesystem->move($source, $correct_directory)) {
            error_log('[AQM BPF UPDATER FIXED] Directory renamed successfully');
            return $correct_directory;
        } else {
            error_log('[AQM BPF UPDATER FIXED] Failed to rename directory');
            
            // Log filesystem details for debugging
            error_log('[AQM BPF UPDATER FIXED] WP Filesystem method: ' . get_filesystem_method());
            
            // Try to determine why the move failed
            if (!$wp_filesystem->is_writable($remote_source)) {
                error_log('[AQM BPF UPDATER FIXED] Remote source directory is not writable');
            }
            
            if ($wp_filesystem->exists($correct_directory)) {
                error_log('[AQM BPF UPDATER FIXED] Target directory already exists after failed move');
            }
        }
        
        return $source;
    }
}

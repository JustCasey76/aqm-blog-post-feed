<?php
/**
 * GitHub Updater Class V2
 * 
 * Enables automatic updates from GitHub for the AQM Blog Post Feed plugin.
 * This is a completely new implementation to avoid any caching issues.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class AQM_Blog_Post_Feed_GitHub_Updater_V2 {
    // Always enable debugging
    private $debug = true;
    private $slug;
    private $plugin_data;
    private $username;
    private $repository;
    private $plugin_file;
    private $github_api_result;
    private $github_api_url = 'https://api.github.com/repos/';
    private $access_token;
    private $plugin_activated;
    private $plugin_slug;
    private $version = '1.0.18'; // Hardcoded version for this updater

    /**
     * Class constructor.
     * 
     * @param string $plugin_file The path to the main plugin file
     * @param string $github_username The GitHub username
     * @param string $github_repository The GitHub repository name
     * @param string $access_token Optional GitHub access token for private repositories
     */
    public function __construct($plugin_file, $github_username, $github_repository, $access_token = '') {
        // Add extremely clear startup message
        error_log('=========================================================');
        error_log('[AQM BPF UPDATER V2] INITIALIZED - VERSION ' . $this->version);
        error_log('[AQM BPF UPDATER V2] Using plugin file: ' . $plugin_file);
        error_log('[AQM BPF UPDATER V2] GitHub repo: ' . $github_username . '/' . $github_repository);
        error_log('=========================================================');

        $this->plugin_file = $plugin_file;
        $this->username = $github_username;
        $this->repository = $github_repository;
        $this->access_token = $access_token;
        $this->slug = plugin_basename($plugin_file);
        $this->plugin_slug = dirname($this->slug);

        // Add our hooks with high priority to ensure they run before any other plugins
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'), 1);
        add_filter('plugins_api', array($this, 'plugin_api_call'), 1, 3);
        
        // Add hook to set reactivation transient BEFORE install
        add_action('upgrader_pre_install', array($this, 'set_reactivation_transient'), 1, 2);

        // Add a hook to handle plugin activation persistence AFTER install
        add_action('upgrader_process_complete', array($this, 'handle_activation_persistence'), 1, 2);
        
        // Hook into the filter that corrects the download directory name
        // Use a very high priority (1) to ensure our function runs before others
        add_filter('upgrader_source_selection', array($this, 'rename_github_zip_directory'), 1, 4);
        
        // Get plugin data
        $this->get_plugin_data();
        
        error_log('[AQM BPF UPDATER V2] All hooks added successfully');
    }

    /**
     * Log a message if debug mode is enabled
     *
     * @param string $message The message to log
     * @param string $level The log level (info, debug, error)
     */
    private function log($message, $level = 'info') {
        if (!$this->debug && $level !== 'error') {
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $prefix = '[AQM BPF UPDATER V2';  
            if ($level === 'debug') {
                $prefix .= ' DEBUG';  
            } elseif ($level === 'error') {
                $prefix .= ' ERROR';  
            }
            $prefix .= '] ';
            
            error_log($prefix . $message);
        }
    }
    
    /**
     * Get plugin data.
     */
    private function get_plugin_data() {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $this->plugin_data = get_plugin_data($this->plugin_file);
        $this->log('Plugin data loaded: ' . $this->plugin_data['Name'] . ' v' . $this->plugin_data['Version']);
    }

    /**
     * Get repository data from GitHub API.
     * 
     * @return object|bool Repository data or false on failure
     */
    private function get_repository_info() {
        if (null !== $this->github_api_result) {
            return $this->github_api_result;
        }

        // Query the GitHub API to get latest release info
        $endpoint = 'releases/latest';
        $url = $this->github_api_url . $this->username . '/' . $this->repository . '/' . $endpoint;
        
        // Prepare headers for GitHub API
        $headers = array(
            'Accept' => 'application/json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        );
        
        // Add authorization header if token is provided (preferred over URL parameter)
        if (!empty($this->access_token)) {
            $headers['Authorization'] = 'token ' . $this->access_token;
        }

        // Get the response
        $response = wp_remote_get($url, array(
            'headers' => $headers
        ));

        // Handle errors
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            $this->log('Error getting repository info: ' . (is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response)), 'error');
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body);

        // Store API result
        $this->github_api_result = $result;
        $this->log('Repository info retrieved successfully. Latest version: ' . $result->tag_name);
        
        return $result;
    }

    /**
     * Check for updates.
     * 
     * @param object $transient The update transient object
     * @return object Modified update transient
     */
    public function check_for_update($transient) {
        // Only proceed if we have a transient
        if (!is_object($transient)) {
            $transient = new stdClass;
        }

        if (empty($transient->checked)) {
            return $transient;
        }

        // Get repository info
        $repo_info = $this->get_repository_info();

        // Check if we have valid data
        if (is_object($repo_info) && !empty($repo_info->tag_name)) {
            // Remove 'v' prefix from tag name if present
            $version = ltrim($repo_info->tag_name, 'v');
            
            // Compare versions
            if (version_compare($version, $this->plugin_data['Version'], '>')) {
                // Construct the plugin information
                $plugin_info = new stdClass();
                $plugin_info->slug = $this->slug;
                $plugin_info->new_version = $version;
                $plugin_info->url = $this->plugin_data['PluginURI'];
                
                // CRITICAL: FORCE USE OF GITHUB ZIP URL
                // Get the direct GitHub ZIP URL for this release
                $download_link = $repo_info->zipball_url;
                
                // Add extremely clear logging
                error_log('=========================================================');
                error_log('[AQM BPF UPDATER V2] UPDATE AVAILABLE: ' . $version);
                error_log('[AQM BPF UPDATER V2] USING GITHUB ZIPBALL URL: ' . $download_link);
                error_log('=========================================================');
                
                // Set the package URL to GitHub's ZIP URL
                $plugin_info->package = $download_link;
                
                // Include in the update array
                $transient->response[$this->slug] = $plugin_info;
                
                $this->log('Update available: ' . $version . ' (current: ' . $this->plugin_data['Version'] . ')');
            } else {
                $this->log('No update available. Current: ' . $this->plugin_data['Version'] . ', Latest: ' . $version);
            }
        } else {
            $this->log('Invalid repository info or missing tag name', 'error');
        }
        
        return $transient;
    }

    /**
     * Plugin API call to get plugin information.
     * 
     * @param bool|object $result The result object or default false
     * @param string $action The API action being performed
     * @param object $args Plugin API arguments
     * @return object Plugin API response
     */
    public function plugin_api_call($result, $action, $args) {
        // Only process plugin information requests for this plugin
        if (empty($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }
        
        $this->log('Plugin API call for: ' . $args->slug);
        
        // Get repository info
        $repo_info = $this->get_repository_info();
        
        // Check if we have valid data
        if (!is_object($repo_info)) {
            return $result;
        }
        
        // Initialize plugin info
        $plugin_info = new stdClass();
        $plugin_info->name = $this->plugin_data['Name'];
        $plugin_info->slug = $this->slug;
        $plugin_info->version = ltrim($repo_info->tag_name, 'v');
        $plugin_info->author = $this->plugin_data['AuthorName'];
        $plugin_info->homepage = $this->plugin_data['PluginURI'];
        $plugin_info->requires = '5.0'; // Minimum WordPress version
        $plugin_info->downloaded = 0;
        $plugin_info->last_updated = $repo_info->published_at;
        
        // Add plugin description if available
        if (!empty($repo_info->body)) {
            $plugin_info->sections['description'] = $repo_info->body;
            $plugin_info->sections['changelog'] = $repo_info->body;
        } else {
            $plugin_info->sections['description'] = $this->plugin_data['Description'];
            $plugin_info->sections['changelog'] = 'View changes on GitHub: ' . $repo_info->html_url;
        }
        
        // CRITICAL: FORCE USE OF GITHUB ZIP URL
        // Get the direct GitHub ZIP URL for this release
        $download_link = $repo_info->zipball_url;
        
        // Add extremely clear logging
        error_log('=========================================================');
        error_log('[AQM BPF UPDATER V2] API CALL FOR: ' . $args->slug);
        error_log('[AQM BPF UPDATER V2] USING GITHUB ZIPBALL URL: ' . $download_link);
        error_log('=========================================================');
        
        $plugin_info->download_link = $download_link;
        
        return $plugin_info;
    }
    
    /**
     * Set a transient before the update is installed.
     * This allows the plugin to reactivate itself after the update.
     *
     * @param mixed $true Default value (usually true).
     * @param array $hook_extra Extra arguments passed to the filter. Contains 'plugin' if relevant.
     * @return mixed Original $true value.
     */
    public function set_reactivation_transient($true, $hook_extra) {
        // Check if this hook is for our plugin
        if (is_array($hook_extra) && isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->slug) {
            $this->log('upgrader_pre_install hook fired for our plugin: ' . esc_html($hook_extra['plugin']));
            
            // Store the current activation status
            if (!function_exists('is_plugin_active')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            
            $is_active = is_plugin_active($this->slug);
            update_option('aqm_blog_post_feed_active', $is_active);
            $this->log('Stored plugin activation status: ' . ($is_active ? 'active' : 'inactive'));
            
            // Set a transient that we can check after the update
            $set = set_transient('aqm_reactivate_after_update', '1', HOUR_IN_SECONDS);
            $this->log('Setting reactivation transient. Success: ' . ($set ? 'true' : 'false'));
            
            // Add extremely clear logging
            error_log('=========================================================');
            error_log('[AQM BPF UPDATER V2] PRE-INSTALL HOOK FIRED');
            error_log('[AQM BPF UPDATER V2] PLUGIN ACTIVE: ' . ($is_active ? 'YES' : 'NO'));
            error_log('[AQM BPF UPDATER V2] TRANSIENT SET: ' . ($set ? 'YES' : 'NO'));
            error_log('=========================================================');
        } else {
            $this->log('upgrader_pre_install hook fired for a different plugin: ' . 
                (isset($hook_extra['plugin']) ? esc_html($hook_extra['plugin']) : 'unknown'), 'debug');
        }
        return $true; // Pass through the original value
    }

    /**
     * Handles plugin activation persistence after update.
     *
     * @param WP_Upgrader $upgrader_object WP_Upgrader instance (or null if not available).
     * @param array       $options         Array of bulk item update data.
     */
    public function handle_activation_persistence($upgrader_object, $options) {
        $this->log('handle_activation_persistence START', 'debug');
        $this->log('Action: ' . (isset($options['action']) ? $options['action'] : 'N/A'), 'debug');
        $this->log('Type: ' . (isset($options['type']) ? $options['type'] : 'N/A'), 'debug');

        // Check if this is a plugin update
        if (isset($options['action']) && $options['action'] === 'update' && isset($options['type']) && $options['type'] === 'plugin') {
            $this->log('This is a plugin update action.', 'debug');
            
            // Check if our plugin was part of this update
            $our_plugin_updated = false;
            if (!empty($options['plugins']) && is_array($options['plugins'])) {
                foreach ($options['plugins'] as $plugin_basename) {
                    if ($plugin_basename === $this->slug) {
                        $our_plugin_updated = true;
                        break;
                    }
                }
            }
            
            // Add extremely clear logging
            error_log('=========================================================');
            error_log('[AQM BPF UPDATER V2] UPDATE PROCESS COMPLETED');
            error_log('[AQM BPF UPDATER V2] OUR PLUGIN UPDATED: ' . ($our_plugin_updated ? 'YES' : 'NO'));
            error_log('=========================================================');
            
            if ($our_plugin_updated) {
                $this->log('Our plugin (' . $this->slug . ') was updated.');

                // Check if the plugin directory exists RIGHT NOW
                $plugin_dir = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
                if (is_dir($plugin_dir)) {
                    $this->log('Plugin directory EXISTS at: ' . $plugin_dir, 'debug');
                    
                    // Check activation state option
                    $should_be_active = get_option('aqm_blog_post_feed_active', false);
                    $this->log('Should be active option: ' . ($should_be_active ? 'Yes' : 'No'));

                    if ($should_be_active) {
                        $this->log('Plugin should be active, attempting reactivation...');
                        
                        // Include plugin functions if not already loaded
                        if (!function_exists('activate_plugin')) {
                            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                        }
                        
                        // Try to reactivate the plugin
                        $result = activate_plugin($this->slug);
                        
                        if (is_wp_error($result)) {
                            $this->log('Error reactivating plugin: ' . $result->get_error_message(), 'error');
                            // Set a transient as a backup method
                            set_transient('aqm_reactivate_after_update', '1', 5 * MINUTE_IN_SECONDS);
                            
                            error_log('=========================================================');
                            error_log('[AQM BPF UPDATER V2] REACTIVATION FAILED: ' . $result->get_error_message());
                            error_log('=========================================================');
                        } else {
                            $this->log('Plugin successfully reactivated!');
                            // Clear any transients since we've successfully reactivated
                            delete_transient('aqm_reactivate_after_update');
                            
                            error_log('=========================================================');
                            error_log('[AQM BPF UPDATER V2] PLUGIN SUCCESSFULLY REACTIVATED');
                            error_log('=========================================================');
                        }
                        
                        // Clear plugin cache to ensure WordPress recognizes the activation
                        wp_clean_plugins_cache(true);
                    }
                } else {
                    $this->log('Plugin directory MISSING at: ' . $plugin_dir, 'error');
                    // Set a longer transient as a backup method since directory isn't ready yet
                    set_transient('aqm_reactivate_after_update', '1', 5 * MINUTE_IN_SECONDS);
                    
                    error_log('=========================================================');
                    error_log('[AQM BPF UPDATER V2] PLUGIN DIRECTORY MISSING: ' . $plugin_dir);
                    error_log('=========================================================');
                }
            } else {
                $this->log('Our plugin was NOT part of this update batch.', 'debug');
            }
        } else {
            $this->log('Not a plugin update action or missing data.', 'debug');
        }
        $this->log('handle_activation_persistence END', 'debug');
    }

    /**
     * Correct the directory name after extracting the GitHub ZIP file.
     * WordPress expects the directory to be named 'aqm-blog-post-feed',
     * but GitHub ZIPs often create 'repo-name-tag'.
     *
     * @param string      $source        Path to the extracted directory.
     * @param string      $remote_source Path to the remote source (usually the same as $source).
     * @param WP_Upgrader $upgrader      The WP_Upgrader instance.
     * @param array       $hook_extra    Extra arguments passed, contains 'plugin' if updating a plugin.
     * @return string|WP_Error Path to the corrected source directory or WP_Error on failure.
     */
    public function rename_github_zip_directory($source, $remote_source, $upgrader, $hook_extra = null) {
        global $wp_filesystem;

        // Detailed Logging Start
        $this->log('--- upgrader_source_selection hook fired ---', 'debug');
        $this->log('Input source: ' . print_r($source, true), 'debug');
        $this->log('Remote source: ' . print_r($remote_source, true), 'debug');
        $this->log('Hook Extra: ' . print_r($hook_extra, true), 'debug');
        $this->log('Target Plugin Slug (dir): ' . $this->plugin_slug, 'debug');
        $this->log('Target Plugin Basename: ' . $this->slug, 'debug');
        
        // Add extremely clear logging
        error_log('=========================================================');
        error_log('[AQM BPF UPDATER V2] DIRECTORY RENAMING HOOK FIRED');
        error_log('[AQM BPF UPDATER V2] SOURCE: ' . $source);
        error_log('[AQM BPF UPDATER V2] REMOTE SOURCE: ' . $remote_source);
        error_log('=========================================================');

        // Check if $wp_filesystem is initialized
        if (!$wp_filesystem instanceof WP_Filesystem_Base) {
            $this->log('WP_Filesystem not initialized!', 'error');
            // Attempt to initialize it (though it should be initialized by WP core during updates)
            WP_Filesystem();
            if (!$wp_filesystem instanceof WP_Filesystem_Base) {
                return new WP_Error('filesystem_error', 'WP_Filesystem could not be initialized.');
            }
            $this->log('WP_Filesystem initialized manually.', 'debug');
        }

        // Check if this is our plugin being updated.
        // $hook_extra might be null for core updates, so check its existence.
        $is_our_plugin = false;
        if (!empty($hook_extra['plugin']) && $hook_extra['plugin'] === $this->slug) {
            $is_our_plugin = true;
        } elseif (!empty($upgrader->skin->plugin_info['Name']) && $upgrader->skin->plugin_info['Name'] === $this->plugin_data['Name']) {
            // Fallback check using plugin name from the upgrader skin if hook_extra isn't reliable
            $is_our_plugin = true;
        }

        $this->log('Identifying as our plugin: ' . ($is_our_plugin ? 'Yes' : 'No'), 'debug');

        // If it's not our plugin, return the original source
        if (!$is_our_plugin) {
            $this->log('Not our plugin, returning original source.', 'debug');
            return $source;
        }

        $this->log('upgrader_source_selection triggered for our plugin.');
        $this->log('Original source path: ' . $source, 'debug');

        // Check if the source path is valid
        if (!$wp_filesystem->exists($source)) {
            $this->log('Source path does not exist: ' . $source, 'error');
            return new WP_Error('aqm_source_not_found', 'Plugin update source directory not found.', $source);
        }
        $this->log('Source path exists: ' . $source, 'debug');

        // List files in the source directory
        $source_files = $wp_filesystem->dirlist($source);
        $this->log('dirlist result for source (' . $source . '): ' . print_r($source_files, true), 'debug');

        // Check if it contains a single directory (the problematic GitHub format)
        if (is_array($source_files) && count($source_files) === 1 && reset($source_files)['type'] === 'd') {
            $subdir_name = key($source_files);
            $new_source = trailingslashit($source) . $subdir_name;

            $this->log('Found single subdirectory: ' . esc_html($subdir_name));
            
            // AGGRESSIVE APPROACH: Instead of just returning the path, let's actually rename the directory
            // Get the parent directory and create our target directory name
            $parent_dir = dirname($source);
            $target_dir = trailingslashit($parent_dir) . $this->plugin_slug;
            
            $this->log('Parent directory: ' . $parent_dir);
            $this->log('Target directory: ' . $target_dir);
            
            // Check if the target directory already exists and remove it if it does
            if ($wp_filesystem->exists($target_dir)) {
                $this->log('Target directory already exists, removing it: ' . $target_dir);
                $wp_filesystem->delete($target_dir, true); // true to recursively delete
            }
            
            // Move the contents from the GitHub subdirectory to our target directory
            if (!$wp_filesystem->mkdir($target_dir)) {
                $this->log('Failed to create target directory: ' . $target_dir, 'error');
                error_log('[AQM BPF UPDATER V2] FAILED TO CREATE TARGET DIRECTORY: ' . $target_dir);
                return $source; // Return original source as fallback
            }
            
            // Copy all files from the GitHub subdirectory to our target directory
            $github_files = $wp_filesystem->dirlist($new_source);
            if (is_array($github_files)) {
                foreach ($github_files as $file => $file_data) {
                    $source_file = trailingslashit($new_source) . $file;
                    $target_file = trailingslashit($target_dir) . $file;
                    
                    if ($file_data['type'] === 'd') {
                        // It's a directory, copy recursively
                        $wp_filesystem->mkdir($target_file);
                        copy_dir($source_file, $target_file);
                    } else {
                        // It's a file, copy directly
                        $wp_filesystem->copy($source_file, $target_file);
                    }
                }
                $this->log('Successfully copied all files to target directory');
            } else {
                $this->log('Failed to list files in GitHub subdirectory', 'error');
            }
            
            // Add extremely clear logging
            error_log('=========================================================');
            error_log('[AQM BPF UPDATER V2] GITHUB ZIP STRUCTURE DETECTED');
            error_log('[AQM BPF UPDATER V2] SUBDIRECTORY: ' . $subdir_name);
            error_log('[AQM BPF UPDATER V2] CREATED NEW TARGET: ' . $target_dir);
            error_log('=========================================================');

            // Return the path to our new target directory
            return $target_dir;

        } else {
            $this->log('Source directory does not have the GitHub structure (single sub-directory). Returning original source: ' . $source);
            
            error_log('=========================================================');
            error_log('[AQM BPF UPDATER V2] NO GITHUB ZIP STRUCTURE DETECTED');
            error_log('[AQM BPF UPDATER V2] RETURNING ORIGINAL SOURCE: ' . $source);
            error_log('=========================================================');
        }

        // If it wasn't the GitHub structure, or something went wrong listing files, return the original source path
        return $source;
    }
}

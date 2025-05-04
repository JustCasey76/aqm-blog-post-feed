<?php
/**
 * GitHub Updater Class
 * 
 * Enables automatic updates from GitHub for the AQM Blog Post Feed plugin.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class AQM_Blog_Post_Feed_GitHub_Updater {
    private $slug;
    private $plugin_data;
    private $username;
    private $repository;
    private $plugin_file;
    private $github_api_result;
    private $github_api_url = 'https://api.github.com/repos/';
    private $access_token;
    private $plugin_activated;

    /**
     * Class constructor.
     * 
     * @param string $plugin_file The path to the main plugin file
     * @param string $github_username The GitHub username
     * @param string $github_repository The GitHub repository name
     * @param string $access_token Optional GitHub access token for private repositories
     */
    public function __construct($plugin_file, $github_username, $github_repository, $access_token = '') {
        $this->plugin_file = $plugin_file;
        $this->username = $github_username;
        $this->repository = $github_repository;
        $this->access_token = $access_token;

        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_api_call'), 10, 3);
        
        // Add hook to set reactivation transient BEFORE install
        add_action('upgrader_pre_install', array($this, 'set_reactivation_transient'), 10, 2);

        // Add a hook to handle plugin activation persistence AFTER install (may or may not be needed with transient method)
        add_action('upgrader_process_complete', array($this, 'handle_activation_persistence'), 10, 2);
        
        // Hook into the filter that corrects the download directory name
        add_filter( 'upgrader_source_selection', array( $this, 'rename_github_zip_directory' ), 10, 4 );
        
        // Get plugin data
        $this->get_plugin_data();
    }

    /**
     * Get plugin data.
     */
    private function get_plugin_data() {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        $this->plugin_data = get_plugin_data($this->plugin_file);
        $this->slug = plugin_basename($this->plugin_file);
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
        
        // Include access token if provided
        if (!empty($this->access_token)) {
            $url = add_query_arg(array('access_token' => $this->access_token), $url);
        }

        // Get the response
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            )
        ));

        // Handle errors
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body);

        // Store API result
        $this->github_api_result = $result;
        
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
                
                // Download link (using zip from GitHub)
                $download_link = $repo_info->zipball_url;
                
                // Add access token if provided
                if (!empty($this->access_token)) {
                    $download_link = add_query_arg(array('access_token' => $this->access_token), $download_link);
                }
                
                $plugin_info->package = $download_link;
                
                // Include in the update array
                $transient->response[$this->slug] = $plugin_info;
            }
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
        
        // Download link
        $download_link = $repo_info->zipball_url;
        
        // Add access token if provided
        if (!empty($this->access_token)) {
            $download_link = add_query_arg(array('access_token' => $this->access_token), $download_link);
        }
        
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
    public function set_reactivation_transient( $true, $hook_extra ) {
        // Check if this hook is for our plugin
        if ( is_array( $hook_extra ) && isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->slug ) {
            error_log('[AQM BPF Updater] upgrader_pre_install hook fired for plugin: ' . esc_html($hook_extra['plugin']) . ' (Our slug: ' . esc_html($this->slug) . ')');
            // Set a transient that we can check after the update
            $set = set_transient( 'aqm_reactivate_after_update', '1', HOUR_IN_SECONDS );
            error_log('[AQM BPF Updater] Setting reactivation transient. Success: ' . ($set ? 'true' : 'false'));
        } else {
            // Optional: Log if the hook fired but didn't match our plugin
            // if (is_array($hook_extra) && isset($hook_extra['plugin'])) {
            //     error_log('[AQM BPF Updater] upgrader_pre_install hook fired for a different plugin: ' . esc_html($hook_extra['plugin']));
            // }
        }
        return $true; // Pass through the original value
    }

    /**
     * Handles plugin activation persistence after update.
     *
     * @param WP_Upgrader $upgrader_object WP_Upgrader instance (or null if not available).
     * @param array       $options         Array of bulk item update data.
     */
    public function handle_activation_persistence( $upgrader_object, $options ) {
        error_log('AQM GHU Debug: handle_activation_persistence START');
        error_log('AQM GHU Debug: Action: ' . (isset($options['action']) ? $options['action'] : 'N/A'));
        error_log('AQM GHU Debug: Type: ' . (isset($options['type']) ? $options['type'] : 'N/A'));

        // Check if this is a plugin update
        if ( isset($options['action']) && $options['action'] === 'update' && isset($options['type']) && $options['type'] === 'plugin' ) {
            error_log('AQM GHU Debug: This is a plugin update action.');
            
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
            
            if ($our_plugin_updated) {
                error_log('AQM GHU Debug: Our plugin (' . $this->slug . ') was updated.');

                // Check if the plugin directory exists RIGHT NOW
                $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($this->slug);
                if (is_dir($plugin_dir)) {
                    error_log('AQM GHU Debug: Plugin directory EXISTS at: ' . $plugin_dir);
                } else {
                    error_log('AQM GHU Debug: Plugin directory MISSING at: ' . $plugin_dir);
                }

                // Check activation state option
                $should_be_active = get_option('aqm_blog_post_feed_active', false);
                error_log('AQM GHU Debug: Should be active option: ' . ($should_be_active ? 'Yes' : 'No'));

                if ( $should_be_active ) {
                    error_log('AQM GHU Debug: Attempting reactivation...');
                    // Use a transient to signal reactivation needed, as doing it here might be too early
                    set_transient('aqm_reactivate_after_update', '1', 60); // Store for 60 seconds
                    error_log('AQM GHU Debug: Reactivation transient set.');

                    // Avoid reactivating directly here as files might not be fully settled
                    // activate_plugin( $this->slug );
                    // wp_clean_plugins_cache( true ); // Clear cache after potential activation
                }
            } else {
                 error_log('AQM GHU Debug: Our plugin was NOT part of this update batch.');
            }
        } else {
            error_log('AQM GHU Debug: Not a plugin update action or missing data.');
        }
         error_log('AQM GHU Debug: handle_activation_persistence END');
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
    public function rename_github_zip_directory( $source, $remote_source, $upgrader, $hook_extra = null ) {
        global $wp_filesystem;

        // Check if this is our plugin being updated.
        // $hook_extra might be null for core updates, so check its existence.
        $is_our_plugin = false;
        if ( ! empty( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->slug ) {
            $is_our_plugin = true;
        } elseif ( ! empty( $upgrader->skin->plugin_info['Name'] ) && $upgrader->skin->plugin_info['Name'] === $this->plugin_data['Name'] ) {
            // Fallback check using plugin name from the upgrader skin if hook_extra isn't reliable
            $is_our_plugin = true;
        }

        // If it's not our plugin, return the original source
        if ( ! $is_our_plugin ) {
            return $source;
        }

        error_log('[AQM BPF Updater] upgrader_source_selection triggered for our plugin.');
        error_log('[AQM BPF Updater] Original source path: ' . $source);

        // Check if the source path is valid
        if ( ! $wp_filesystem->exists( $source ) ) {
            error_log('[AQM BPF Updater] Source path does not exist: ' . $source);
            return new WP_Error('aqm_source_not_found', 'Plugin update source directory not found.', $source);
        }

        // List files in the source directory
        $source_files = $wp_filesystem->dirlist( $source );

        // Check if it contains a single directory (the problematic GitHub format)
        if ( is_array( $source_files ) && count( $source_files ) === 1 && reset( $source_files )['type'] === 'd' ) {
            $subdir_name = key( $source_files );
            $extracted_path = trailingslashit( $source ) . $subdir_name;

            // Construct the expected path (parent of the $source, with the correct plugin slug)
            $corrected_path = trailingslashit( dirname( $source ) ) . $this->slug; // e.g., /path/to/wp-content/upgrade/aqm-blog-post-feed

            error_log('[AQM BPF Updater] Found single subdirectory: ' . esc_html($subdir_name));
            error_log('[AQM BPF Updater] Moving from: ' . esc_html($extracted_path));
            error_log('[AQM BPF Updater] Moving to: ' . esc_html($corrected_path));

            // Attempt to move the contents
            if ( $wp_filesystem->move( $extracted_path, $corrected_path, true ) ) {
                 error_log('[AQM BPF Updater] Successfully moved contents to corrected path.');
                 // Clean up the now-empty $source directory
                 $wp_filesystem->delete($source, true);
                 error_log('[AQM BPF Updater] Deleted original source directory: ' . $source);
                 return $corrected_path; // Return the new, corrected path
            } else {
                 error_log('[AQM BPF Updater] Error moving directory contents.');
                 return new WP_Error('aqm_move_failed', 'Could not move plugin files to the correct directory name.', $corrected_path);
            }
        } else {
             error_log('[AQM BPF Updater] Source directory does not contain a single subdirectory. Assuming structure is correct or update is not from GitHub ZIP.');
             // If it doesn't look like the GitHub structure, assume it's correct or from a different source.
             // We might need to rename the $source directory itself if it's not the correct slug.
             $source_dir_name = basename($source);
             if ($source_dir_name !== $this->slug) {
                 $corrected_path = trailingslashit( dirname( $source ) ) . $this->slug;
                 error_log('[AQM BPF Updater] Source directory name (' . esc_html($source_dir_name) . ') is not the plugin slug (' . esc_html($this->slug) . '). Attempting rename.');
                 if ($wp_filesystem->move($source, $corrected_path, true)) {
                     error_log('[AQM BPF Updater] Successfully renamed source directory.');
                     return $corrected_path;
                 } else {
                     error_log('[AQM BPF Updater] Failed to rename source directory.');
                     // Proceed with original source, maybe it will work?
                 }
             }
        }

        return $source; // Return original source if no correction was needed or rename failed
    }

}
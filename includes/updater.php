<?php
/**
 * Simple GitHub Plugin Updater
 * 
 * A lightweight GitHub plugin updater that handles directory structure correctly
 * and ensures the plugin remains activated after updates.
 */

if (!class_exists('AQM_GitHub_Updater')) {
    class AQM_GitHub_Updater {
        private $slug;
        private $plugin_data;
        private $username;
        private $repo;
        private $plugin_file;
        private $github_api_result;
        private $access_token;
        private $plugin_activated;

        /**
         * Class constructor
         * 
         * @param string $plugin_file The path to the main plugin file
         * @param string $github_username The GitHub username
         * @param string $github_repo The GitHub repo name
         * @param string $access_token Optional GitHub access token
         */
        function __construct($plugin_file, $github_username, $github_repo, $access_token = '') {
            $this->plugin_file = $plugin_file;
            $this->username = $github_username;
            $this->repo = $github_repo;
            $this->access_token = $access_token;

            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
            add_filter('plugins_api', array($this, 'plugins_api_filter'), 10, 3);
            add_filter('upgrader_source_selection', array($this, 'upgrader_source_selection'), 10, 4);
            add_filter('upgrader_pre_install', array($this, 'upgrader_pre_install'), 10, 2);
            add_filter('upgrader_post_install', array($this, 'upgrader_post_install'), 10, 3);
            
            // Store the activation status before update
            add_filter('upgrader_pre_download', array($this, 'store_activation_status'), 10, 4);
        }

        /**
         * Store the plugin's activation status before update
         */
        public function store_activation_status($reply, $package, $upgrader, $hook_extra) {
            if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] != plugin_basename($this->plugin_file)) {
                return $reply;
            }

            $this->plugin_activated = is_plugin_active(plugin_basename($this->plugin_file));
            error_log('AQM Updater: Plugin activation status before update: ' . ($this->plugin_activated ? 'active' : 'inactive'));
            
            return $reply;
        }

        /**
         * Get plugin data from the main plugin file
         */
        private function init_plugin_data() {
            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            
            $this->plugin_data = get_plugin_data($this->plugin_file);
            $this->slug = plugin_basename($this->plugin_file);
        }

        /**
         * Get repo release info from GitHub
         */
        private function get_repo_release_info() {
            if (!empty($this->github_api_result)) {
                return;
            }

            // Get plugin data if not already initialized
            if (empty($this->plugin_data)) {
                $this->init_plugin_data();
            }

            // GitHub API URL for the latest release
            $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";
            
            // Add access token if provided
            if (!empty($this->access_token)) {
                $url = add_query_arg(array('access_token' => $this->access_token), $url);
            }

            // Get the latest release info
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
                )
            ));

            if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
                error_log('AQM Updater: Error fetching GitHub release info: ' . 
                    (is_wp_error($response) ? $response->get_error_message() : 'Response code: ' . wp_remote_retrieve_response_code($response)));
                return false;
            }

            $response_body = wp_remote_retrieve_body($response);
            $this->github_api_result = json_decode($response_body);

            // Check if the response is valid
            if (empty($this->github_api_result) || !is_object($this->github_api_result)) {
                error_log('AQM Updater: Invalid GitHub API response: ' . $response_body);
                return false;
            }

            return true;
        }

        /**
         * Check for plugin updates
         */
        public function check_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }

            // Get plugin data if not already initialized
            if (empty($this->plugin_data)) {
                $this->init_plugin_data();
            }

            // Get GitHub release info
            $this->get_repo_release_info();
            
            if (empty($this->github_api_result)) {
                return $transient;
            }

            // Check if a new version is available
            $current_version = $this->plugin_data['Version'];
            $remote_version = ltrim($this->github_api_result->tag_name, 'v');
            
            if (version_compare($current_version, $remote_version, '<')) {
                error_log("AQM Updater: New version available - Current: {$current_version}, Remote: {$remote_version}");
                
                // Construct the plugin information
                $update = array(
                    'slug' => $this->slug,
                    'plugin' => $this->slug,
                    'new_version' => $remote_version,
                    'url' => $this->plugin_data['PluginURI'],
                    'package' => $this->github_api_result->zipball_url,
                );
                
                // Add update info to the transient
                $transient->response[$this->slug] = (object) $update;
            }
            
            return $transient;
        }

        /**
         * Override the plugin API to provide our own plugin information
         */
        public function plugins_api_filter($result, $action, $args) {
            if ($action !== 'plugin_information') {
                return $result;
            }

            if (!isset($args->slug) || $args->slug !== dirname($this->slug)) {
                return $result;
            }

            // Get plugin data if not already initialized
            if (empty($this->plugin_data)) {
                $this->init_plugin_data();
            }

            // Get GitHub release info
            $this->get_repo_release_info();
            
            if (empty($this->github_api_result)) {
                return $result;
            }

            // Prepare the plugin info
            $plugin_info = array(
                'name' => $this->plugin_data['Name'],
                'slug' => dirname($this->slug),
                'version' => ltrim($this->github_api_result->tag_name, 'v'),
                'author' => $this->plugin_data['Author'],
                'author_profile' => $this->plugin_data['AuthorURI'],
                'homepage' => $this->plugin_data['PluginURI'],
                'requires' => $this->plugin_data['RequiresWP'] ?? '4.7',
                'tested' => $this->plugin_data['TestedUpTo'] ?? get_bloginfo('version'),
                'downloaded' => 0,
                'last_updated' => $this->github_api_result->published_at,
                'sections' => array(
                    'description' => $this->plugin_data['Description'],
                    'changelog' => $this->github_api_result->body ?? 'No changelog provided',
                ),
                'download_link' => $this->github_api_result->zipball_url,
            );

            return (object) $plugin_info;
        }

        /**
         * Fix the source directory name during update
         */
        public function upgrader_source_selection($source, $remote_source, $upgrader, $hook_extra = null) {
            global $wp_filesystem;
            
            // Check if this is our plugin
            if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
                return $source;
            }
            
            error_log("AQM Updater: Fixing source directory - Source: {$source}, Remote source: {$remote_source}");
            
            // Get the expected plugin directory name
            $plugin_dir = trailingslashit($remote_source) . dirname($this->slug);
            
            // Check if the source directory contains our plugin name
            if (strpos($source, $this->repo) !== false) {
                error_log("AQM Updater: Source directory contains our repo name");
                
                // If the correct directory already exists, delete it
                if ($wp_filesystem->exists($plugin_dir)) {
                    error_log("AQM Updater: Target directory already exists, deleting it");
                    $wp_filesystem->delete($plugin_dir, true);
                }
                
                // Rename the source directory to the correct plugin directory
                error_log("AQM Updater: Moving {$source} to {$plugin_dir}");
                if (!$wp_filesystem->move($source, $plugin_dir)) {
                    error_log("AQM Updater: Failed to move directory");
                    return new WP_Error('rename_failed', 'Unable to rename the update directory.');
                }
                
                error_log("AQM Updater: Directory renamed successfully");
                return $plugin_dir;
            }
            
            return $source;
        }

        /**
         * Store plugin info before update
         */
        public function upgrader_pre_install($return, $hook_extra) {
            if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
                return $return;
            }
            
            // Store plugin activation status
            $this->plugin_activated = is_plugin_active($this->slug);
            error_log("AQM Updater: Pre-install - Plugin activation status: " . ($this->plugin_activated ? 'active' : 'inactive'));
            
            return $return;
        }

        /**
         * Reactivate plugin after update if it was active before
         */
        public function upgrader_post_install($return, $hook_extra, $result) {
            global $wp_filesystem;
            
            if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
                return $return;
            }
            
            // Ensure the plugin directory is properly set up
            $plugin_dir = WP_PLUGIN_DIR . '/' . dirname($this->slug);
            $wp_filesystem->move($result['destination'], $plugin_dir);
            $result['destination'] = $plugin_dir;
            
            // Reactivate the plugin if it was active before the update
            if ($this->plugin_activated) {
                error_log("AQM Updater: Post-install - Reactivating plugin");
                activate_plugin($this->slug);
            }
            
            return $result;
        }
    }
}

// Initialize the updater
function aqm_blog_post_feed_github_updater_init() {
    // Only initialize the updater if we're in the admin area
    if (!is_admin()) {
        return;
    }
    
    // Initialize the updater with your GitHub username and repo
    new AQM_GitHub_Updater(
        AQM_BLOG_POST_FEED_FILE,
        'JustCasey76',
        'aqm-blog-post-feed'
    );
}
add_action('admin_init', 'aqm_blog_post_feed_github_updater_init');

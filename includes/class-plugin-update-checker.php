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
 * Class AQM_Blog_Post_Feed_PUC_Updater
 * 
 * Handles plugin updates from GitHub using the Plugin Update Checker library.
 */
class AQM_Blog_Post_Feed_PUC_Updater {
    /**
     * The single instance of this class.
     *
     * @var AQM_Blog_Post_Feed_PUC_Updater
     */
    private static $instance = null;

    /**
     * The update checker instance.
     *
     * @var Puc_v4_Factory
     */
    private $update_checker;

    /**
     * Debug mode flag.
     *
     * @var bool
     */
    private $debug = true;

    /**
     * Get the single instance of this class.
     *
     * @param string $plugin_file The path to the main plugin file.
     * @param string $github_username The GitHub username.
     * @param string $github_repository The GitHub repository name.
     * @param string $access_token Optional GitHub access token for private repositories.
     * @return AQM_Blog_Post_Feed_PUC_Updater
     */
    public static function get_instance($plugin_file, $github_username, $github_repository, $access_token = '') {
        if (null === self::$instance) {
            self::$instance = new self($plugin_file, $github_username, $github_repository, $access_token);
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @param string $plugin_file The path to the main plugin file.
     * @param string $github_username The GitHub username.
     * @param string $github_repository The GitHub repository name.
     * @param string $access_token Optional GitHub access token for private repositories.
     */
    private function __construct($plugin_file, $github_username, $github_repository, $access_token = '') {
        // Log initialization
        $this->log('Initializing Plugin Update Checker');
        $this->log('Plugin file: ' . $plugin_file);
        $this->log('GitHub username: ' . $github_username);
        $this->log('GitHub repository: ' . $github_repository);

        // Make sure the Plugin Update Checker library is available
        $this->ensure_puc_available();

        // Include the library
        require_once dirname(__FILE__) . '/plugin-update-checker/plugin-update-checker.php';

        // Set up the update checker
        $this->update_checker = Puc_v4_Factory::buildUpdateChecker(
            "https://github.com/{$github_username}/{$github_repository}/",
            $plugin_file,
            'aqm-blog-post-feed'
        );

        // Set the branch to master
        $this->update_checker->setBranch('master');

        // Set the access token if provided
        if (!empty($access_token)) {
            $this->update_checker->setAuthentication($access_token);
        }

        // Enable release assets (this will use the ZIP file from GitHub releases)
        $this->update_checker->getVcsApi()->enableReleaseAssets();

        // Set debug mode
        if ($this->debug) {
            $this->update_checker->setDebugMode(true);
        }

        // Add hooks for plugin activation persistence
        add_filter('upgrader_pre_install', array($this, 'set_reactivation_transient'), 10, 2);
        add_action('upgrader_process_complete', array($this, 'handle_activation_persistence'), 10, 2);
        
        // Add hook for admin_init to check for reactivation
        add_action('admin_init', array($this, 'maybe_reactivate_plugin'));

        // Log successful initialization
        $this->log('Plugin Update Checker initialized successfully');
        
        // Add extremely clear logging
        error_log('=========================================================');
        error_log('[AQM BPF PUC UPDATER] INITIALIZED');
        error_log('[AQM BPF PUC UPDATER] PLUGIN FILE: ' . basename($plugin_file));
        error_log('[AQM BPF PUC UPDATER] GITHUB REPO: ' . $github_username . '/' . $github_repository);
        error_log('=========================================================');
    }
    
    /**
     * Ensure the Plugin Update Checker library is available.
     * If not, download it from GitHub.
     */
    private function ensure_puc_available() {
        // Define the paths
        $puc_dir = dirname(__FILE__) . '/plugin-update-checker';
        $puc_file = $puc_dir . '/plugin-update-checker.php';
        
        // Check if the library is already available
        if (file_exists($puc_file)) {
            $this->log('Plugin Update Checker library already exists');
            return;
        }
        
        // Create the directory if it doesn't exist
        if (!file_exists($puc_dir)) {
            $this->log('Creating Plugin Update Checker directory');
            mkdir($puc_dir, 0755, true);
        }
        
        // Download the library from GitHub
        $this->log('Downloading Plugin Update Checker library from GitHub');
        
        // Define the GitHub repository and ZIP URL
        $zip_url = 'https://github.com/YahnisElsts/plugin-update-checker/archive/refs/heads/master.zip';
        $zip_file = $puc_dir . '/puc.zip';
        
        // Download the ZIP file
        $response = wp_remote_get($zip_url, array(
            'timeout' => 30,
            'stream' => true,
            'filename' => $zip_file
        ));
        
        if (is_wp_error($response)) {
            $this->log('Error downloading Plugin Update Checker: ' . $response->get_error_message(), 'error');
            return;
        }
        
        // Extract the ZIP file
        $this->log('Extracting Plugin Update Checker library');
        
        // Use WordPress's unzip_file function
        WP_Filesystem();
        global $wp_filesystem;
        
        $unzipped = unzip_file($zip_file, $puc_dir);
        
        if (is_wp_error($unzipped)) {
            $this->log('Error extracting Plugin Update Checker: ' . $unzipped->get_error_message(), 'error');
            return;
        }
        
        // Move the files from the extracted directory to the plugin-update-checker directory
        $extracted_dir = $puc_dir . '/plugin-update-checker-master';
        
        if (file_exists($extracted_dir)) {
            // Copy the plugin-update-checker.php file
            copy($extracted_dir . '/plugin-update-checker.php', $puc_file);
            
            // Copy the Puc directory
            $this->copy_directory($extracted_dir . '/Puc', $puc_dir . '/Puc');
            
            // Clean up
            $wp_filesystem->delete($extracted_dir, true);
            $wp_filesystem->delete($zip_file);
            
            $this->log('Plugin Update Checker library installed successfully');
        } else {
            $this->log('Error: Extracted directory not found', 'error');
        }
    }
    
    /**
     * Copy a directory recursively.
     *
     * @param string $source Source directory.
     * @param string $destination Destination directory.
     * @return bool True on success, false on failure.
     */
    private function copy_directory($source, $destination) {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $dir = opendir($source);
        $result = true;
        
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($source . '/' . $file)) {
                    $result = $this->copy_directory($source . '/' . $file, $destination . '/' . $file);
                } else {
                    $result = copy($source . '/' . $file, $destination . '/' . $file);
                }
                
                if (!$result) {
                    break;
                }
            }
        }
        
        closedir($dir);
        return $result;
    }

    /**
     * Log a message if debug mode is enabled.
     *
     * @param string $message The message to log.
     * @param string $level The log level (info, debug, error).
     */
    private function log($message, $level = 'info') {
        if ($this->debug) {
            $prefix = '[AQM BPF PUC UPDATER]';
            error_log("{$prefix} {$message}");
        }
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
        $this->log('set_reactivation_transient START');
        
        // Check if this is our plugin being updated
        if (!empty($hook_extra['plugin']) && $hook_extra['plugin'] === plugin_basename(dirname(__DIR__) . '/aqm-blog-post-feed.php')) {
            $this->log('This is our plugin being updated');
            
            // Check if the plugin is currently active
            if (is_plugin_active($hook_extra['plugin'])) {
                $this->log('Plugin is active, setting reactivation transient');
                set_transient('aqm_reactivate_after_update', '1', 5 * MINUTE_IN_SECONDS);
                
                error_log('=========================================================');
                error_log('[AQM BPF PUC UPDATER] PLUGIN IS ACTIVE, SETTING REACTIVATION TRANSIENT');
                error_log('=========================================================');
            } else {
                $this->log('Plugin is not active, no need to reactivate');
            }
        } else {
            $this->log('Not our plugin, skipping');
        }
        
        $this->log('set_reactivation_transient END');
        return $true;
    }

    /**
     * Handles plugin activation persistence after update.
     *
     * @param WP_Upgrader $upgrader_object WP_Upgrader instance.
     * @param array       $options         Array of bulk item update data.
     */
    public function handle_activation_persistence($upgrader_object, $options) {
        $this->log('handle_activation_persistence START');
        error_log('=========================================================');
        error_log('[AQM BPF PUC UPDATER] HANDLE ACTIVATION PERSISTENCE CALLED');
        error_log('=========================================================');
        
        // Check if this is a plugin update
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            $this->log('This is a plugin update');
            
            // Get the plugin slug
            $slug = plugin_basename(dirname(__DIR__) . '/aqm-blog-post-feed.php');
            
            // Check if our plugin was part of the update
            if (isset($options['plugins']) && in_array($slug, $options['plugins'])) {
                $this->log('Our plugin was part of this update batch');
                
                // Set a transient to reactivate the plugin
                set_transient('aqm_reactivate_after_update', '1', 5 * MINUTE_IN_SECONDS);
                
                error_log('=========================================================');
                error_log('[AQM BPF PUC UPDATER] OUR PLUGIN WAS UPDATED, SETTING REACTIVATION TRANSIENT');
                error_log('=========================================================');
            } else {
                $this->log('Our plugin was NOT part of this update batch');
            }
        } else {
            $this->log('Not a plugin update action');
        }
        
        $this->log('handle_activation_persistence END');
    }

    /**
     * Check if we need to reactivate the plugin.
     */
    public function maybe_reactivate_plugin() {
        $this->log('maybe_reactivate_plugin START');
        
        // Check if the reactivation transient exists
        if (get_transient('aqm_reactivate_after_update')) {
            $this->log('Reactivation transient found');
            
            // Delete the transient
            delete_transient('aqm_reactivate_after_update');
            
            // Get the plugin slug
            $slug = plugin_basename(dirname(__DIR__) . '/aqm-blog-post-feed.php');
            
            // Check if the plugin exists
            if (file_exists(WP_PLUGIN_DIR . '/' . $slug)) {
                $this->log('Plugin file exists, attempting to reactivate');
                
                // Include plugin functions if not already loaded
                if (!function_exists('activate_plugin')) {
                    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                }
                
                // Try to reactivate the plugin
                $result = activate_plugin($slug);
                
                if (is_wp_error($result)) {
                    $this->log('Error reactivating plugin: ' . $result->get_error_message(), 'error');
                    
                    error_log('=========================================================');
                    error_log('[AQM BPF PUC UPDATER] REACTIVATION FAILED: ' . $result->get_error_message());
                    error_log('=========================================================');
                } else {
                    $this->log('Plugin successfully reactivated!');
                    
                    error_log('=========================================================');
                    error_log('[AQM BPF PUC UPDATER] PLUGIN SUCCESSFULLY REACTIVATED');
                    error_log('=========================================================');
                }
                
                // Clear plugin cache to ensure WordPress recognizes the activation
                wp_clean_plugins_cache(true);
            } else {
                $this->log('Plugin file does not exist: ' . WP_PLUGIN_DIR . '/' . $slug, 'error');
                
                error_log('=========================================================');
                error_log('[AQM BPF PUC UPDATER] PLUGIN FILE DOES NOT EXIST: ' . WP_PLUGIN_DIR . '/' . $slug);
                error_log('=========================================================');
            }
        } else {
            $this->log('No reactivation transient found, skipping');
        }
        
        $this->log('maybe_reactivate_plugin END');
    }
}

<?php
/*
Plugin Name: AQM Blog Post Feed
Plugin URI: https://aqmarketing.com/
Description: A custom Divi module to display blog posts in a customizable grid with Font Awesome icons, hover effects, and more.
Version: 1.4
Author: AQ Marketing
Author URI: https://aqmarketing.com/
GitHub Plugin URI: https://github.com/JustCasey76/aqm-blog-post-feed
GitHub Branch: main
*/

if (!defined('ABSPATH')) exit;

// Initialize the update checker
$update_checker_path = plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';
if (file_exists($update_checker_path)) {
    require $update_checker_path;
    
    function aqm_blog_post_feed_init_updater() {
        if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
            $myUpdateChecker = PucFactory::buildUpdateChecker(
                'https://github.com/JustCasey76/aqm-blog-post-feed/',
                __FILE__,
                'aqm-blog-post-feed'
            );
            
            // Set the branch that contains the stable release
            $myUpdateChecker->setBranch('main');

            // Add the "Check for Updates" link to the plugins page
            $myUpdateChecker->addResultCallback(function ($update) {
                global $pagenow;
                if ($pagenow === 'plugins.php') {
                    if ($update !== null) {
                        echo '<script>
                            jQuery(document).ready(function($) {
                                $("tr[data-plugin=\'aqm-blog-post-feed/aqm-blog-post-feed.php\'] .row-actions").append(
                                    \'<span class="check-update"> | <a href="#" class="check-for-updates">Check for Updates</a></span>\'
                                );
                                $(".check-for-updates").on("click", function(e) {
                                    e.preventDefault();
                                    var $link = $(this);
                                    $link.text("Checking...");
                                    $.get(ajaxurl, {
                                        action: "aqm_check_for_updates"
                                    }).done(function() {
                                        location.reload();
                                    });
                                });
                            });
                        </script>\';
                    }
                }
            });

            return $myUpdateChecker;
        }
    }
    add_action('init', 'aqm_blog_post_feed_init_updater');

    // Add AJAX handler for update check
    function aqm_handle_update_check() {
        $updater = aqm_blog_post_feed_init_updater();
        if ($updater) {
            $updater->checkForUpdates();
        }
        wp_die();
    }
    add_action('wp_ajax_aqm_check_for_updates', 'aqm_handle_update_check');
}

function aqm_blog_post_feed_divi_module() {
    if (class_exists('ET_Builder_Module')) {
        include_once(plugin_dir_path(__FILE__) . 'includes/AQM_Blog_Post_Feed_Module.php');
    }
}
add_action('et_builder_ready', 'aqm_blog_post_feed_divi_module');

# AQM Blog Post Feed Changelog

## 1.0.63 - April 20, 2026
- **WP-CLI support for updates**: Updater is now registered whenever WordPress is in an upgrade-capable context (admin, WP-CLI, or cron) instead of only on `admin_init`. `wp plugin update aqm-blog-post-feed` now correctly discovers GitHub-hosted updates. Front-end requests still skip the updater entirely.
- **Less error_log noise from other plugins' updates**: `pre_install`, `post_install`, and `fix_directory_name` filters previously logged banner output every time *any* plugin was updated on the site (they ran before the "is this our plugin?" check). The identity check now runs first and the banners are also gated behind `AQMBPF_DEBUG`.
- **GitHub auth fix**: Switched from the deprecated `?access_token=` query-string parameter (removed by GitHub in 2021) to the `Authorization: token` header. Only matters if an access token is configured; behavior for public-repo use is unchanged.
- Rename-failure path still logs unconditionally as a real error, but the noisy debug breadcrumbs around it are now debug-only.

## 1.0.62 - April 20, 2026
- Performance: silenced verbose updater/init log spam that fired on every admin request (admin_init) and admin-ajax Heartbeat tick. Log output was filling `error_log` and adding unnecessary disk I/O on every admin page load.
- Added `AQMBPF_DEBUG` constant (default `false`). Define `AQMBPF_DEBUG` as `true` in `wp-config.php` to re-enable the full lifecycle logs when troubleshooting updates.
- `maybe_reactivate_plugin()` now short-circuits when no reactivation transient is set, instead of running (and logging) on every admin_init.
- Genuine error paths (exceptions, WP_Error, reactivation failures, GitHub API errors) still log unconditionally.

## 1.0.61 - September 23, 2025
- Added option to use first paragraph as excerpt when no manual excerpt is set
- Added support for limiting excerpts by characters instead of words
- Improved excerpt handling with new helper functions for consistent display
- Increased maximum excerpt limit to 500 words/characters

## 1.0.60 - September 23, 2025
- Changed card view and grid view background image size from percentage values to 'cover'
- Improved image display consistency across different screen sizes and image dimensions
- Updated transition effects to use 'all' instead of just 'background-size' for smoother animations

## 1.0.59 - August 4, 2025
- Enhanced module UI with conditional display of options based on selected layout type
- Added post bottom margin control for consistent vertical spacing across all layouts
- Updated Card View layout to prevent image cropping with background-size: contain
- Improved AJAX handler to use consistent styling for all layout types

## 1.0.43 - June 12, 2025
- Added toggle options for showing/hiding author in post meta
- Added toggle options for showing/hiding date in post meta
- Improved meta display to handle different combinations of visible elements

## 1.0.42 - May 12, 2025
- Added feature to exclude the current post when the module is displayed on a single post page
- Improved AJAX handler to maintain this behavior when loading more posts

## 1.0.41 - May 12, 2025
- Improved mobile experience: background images now cover the entire post on mobile devices
- Added responsive styles with media queries for better mobile display

## 1.0.40 - May 9, 2025
- Improved Load More button visibility: now only shows if there are more posts than the initial load amount
- Ensured consistent background image sizing in AJAX-loaded content

## 1.0.39 - May 9, 2025
- Updated default background image size to 125% with 140% on hover

## 1.0.38 - May 9, 2025
- Added font family options for post title and content/excerpt
- Improved background image handling with fixed 120% default size and 135% hover size
- Enhanced zoom effect on hover with smooth transitions

## 1.0.27 - May 5, 2025
- Removed redundant updater files:
  - Removed old GitHub updater files (`class-github-updater.php`, `class-github-updater-v2.php`)
  - Removed fixed GitHub updater file (`class-aqm-github-updater-fixed.php`)
  - Removed custom implementation file (`class-plugin-update-checker-impl.php`)
  - Removed duplicate plugin-update-checker-master directory
  - Removed source zip file (`puc.zip`)
- Improved code maintainability and reduced plugin size

## 1.0.26 - Previous Release
- Refactored plugin to improve maintainability and reliability
- Separated CSS and JS into dedicated files (`css/`, `js/`)
- Refactored the Divi module to use CSS classes and a helper method for post HTML generation
- Implemented dynamic styling by generating a scoped `<style>` block
- Handled module-specific JavaScript data using a static PHP array
- Updated JS file to use global and module-specific localized data
- Replaced custom GitHub updater with standard Plugin Update Checker library

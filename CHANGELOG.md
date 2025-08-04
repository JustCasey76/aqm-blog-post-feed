# AQM Blog Post Feed Changelog

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

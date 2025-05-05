# AQM Blog Post Feed Changelog

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

=== WP Theme Update ===
Tags: themes, update, core
Requires at least: 3.7.0
Tested up to: 3.9
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin helps to make a theme easier upgradable.

== Description ==

This plugin makes the themes upgrade easy to the user. Using the admin upgrade messages and system.


== Installation ==

(This plugin hopes that you host a zip file and a list of the theme's versions to be downloaded)

1. Upload `wp-theme-update.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. You need to create a update.php file inside your theme and add a filter to 'custom_theme_updater_parse_request' in this file to parse the list of versions, at least
4. You can also add 'custom_theme_updater_request_url' and 'custom_theme_updater_download_url' filters to change the URLs



== Changelog ==

= 1.0.3 =
* Fixed strange bug with other plugins updates

= 1.0.2 =
* Fixed wrong folder name inside the zip of update theme

= 1.0.0 =
* First version


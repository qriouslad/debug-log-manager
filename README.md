# Debug Log Manager

Contributors: qriouslad  
Donate link: https://paypal.me/qriouslad  
Tags: debug, errors, developer  
Requires at least: 4.8  
Tested up to: 6.0.2  
Stable tag: 1.4.0  
Requires PHP: 5.6  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html

![](.wordpress-org/banner-772x250.png)

Log PHP, database and JavaScript errors via WP_DEBUG. Create, view, filter and clear the debug.log file.

## Description

Debug Log Manager allows you to: 

* **Enable [WP_DEBUG](https://wordpress.org/support/article/debugging-in-wordpress/) with one click** to log PHP and database errors when you need to, and disable it when you're done. No need to manually edit wp-config.php file. 
* **Create the debug.log file for you** in a non-default location with a custom file name for enhanced security. 
* **Copy the content of the default / existing debug.log file** into the custom debug.log file, and delete the default / existing debug.log file. So there is continuation in logging and enhanced security going forward.
* **Log PHP, database and JavaScript errors** by default.
* Parse the debug.log file and **view distinct errors and when they last occurred**, which is better than looking at the raw log file (potentially) full of repetitive errors. 
* **Quickly find and filter more specific errors** for your debugging work.
* **Enable auto-refresh** to automatically load new log entries. No need to manually reload the browser tab, or to ```tail -f``` the log file on the command line.
* **Easily clear the debug.log file** to save disk space and more easily observe newly occurring errors on your site.

A more compact version of Debug Log Manager is included as part of the [System Dashboard plugin](https://wordpress.org/plugins/system-dashboard/), should you prefer a single plugin that does more.

### Give Back

* [A nice review](https://wordpress.org/plugins/debug-log-manager/#reviews) would be great!
* [Give feedback](https://wordpress.org/support/plugin/debug-log-manager/) and help improve future versions.
* [Github repo](https://github.com/qriouslad/debug-log-manager) to contribute code.
* [Donate](https://paypal.me/qriouslad) and support my work.

### Check These Out Too

* [System Dashboard](https://wordpress.org/plugins/system-dashboard/): Central dashboard to monitor various WordPress components, processes and data, including the server.
* [Variable Inspector](https://wordpress.org/plugins/variable-inspector/): Inspect PHP variables on a central dashboard in wp-admin for convenient debugging.
* [Code Explorer](https://wordpress.org/plugins/code-explorer/): Fast directory explorer and file/code viewer with syntax highlighting.
* [Database Admin](https://github.com/qriouslad/database-admin): Securely manage your WordPress website's database with a clean and user-friendly interface based on a custom-themed Adminer app. Only available on Github.

## Screenshots

1. Debug Log Manager main page
   ![Debug Log Manager main page](.wordpress-org/screenshot-1.png)

## Frequently Asked Questions

### How was this plugin built?

Debug Log Manager is built using the excellent [WPConfigTransformer class](https://plugins.svn.wordpress.org/debug-log-config-tool/tags/1.1/src/Classes/vendor/WPConfigTransformer.php) from [WP Debug Log – Config Tool](https://wordpress.org/plugins/debug-log-config-tool/), [DataTables.js](https://datatables.net/), [jSticky](https://github.com/AndrewHenderson/jSticky) and [jQuery Toast](https://github.com/kamranahmedse/jquery-toast-plugin).

## Changelog

### 1.4.0 (2022.09.21)

* JavaScript errors on wp-admin and the front end is now logged by default.
* Improve copy around error types for simplicity and clarity.
* Fix an issue where AJAX calls for the auto-refresh feature won't properly stop in multiple scenarios of clicking the Error Logging and/or Auto-Refresh toggles.

### 1.3.3 (2022.09.20)

* Bug fix: undefined function wp_filesize() in bootstrap.php. Props to [@gleysen](https://wordpress.org/support/users/gleysen/) for [reporting it](https://wordpress.org/support/topic/error-when-loading-2/).

### 1.3.1 (2022.09.18)

* Fix bugs around the auto-refresh feature. Disabling auto-refresh works only on the toggle and wp_option entry but not on the actual ajax calls. If auto-refresh is enabled, it only worked on clicking the toggle but not on page load. Both issues are fixed.

### 1.3.0 (2022.09.18)

* Implement toast notifications on various action completions, e.g. clearing log file.
* Change date format to M j, Y - H:i:s, e.g. Dec 31, 2021 - 20:06:34.
* Implment auto-refresh feature that will automatically load the latest error entries every 5 seconds.

### 1.2.0 (2022.09.16)

* Fix detection of existing debug log file, if there is one, so it is copied correctly into DLM's debug log file.
* Auto update entries table when logging is enabled, including when copying entries from existing debug.log file.
* Code refactor: added autoloader of plugin's PHP classes.

### 1.1.0 (2022.09.13)

* Improve implementation of [WP PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) across the codebase.
* Improve variable sanitization and escaping.
* Set initial data table sort based on the # column so that Last Occurence column is properly sorted according to the timestamp.
* Add get_value() method to WP_Config_Transformer class to work with existing debug log constants in wp-config.php.
* Enable $options argument for the add() and update() method in WP_Config_Transformer to ensure formatting of debug log constants is correct in wp-config.php.

### 1.0.1 (2022.08.31)

* Refactor code for better organization and maintainability.
* Improve plugin description / README.md.

### 1.0.0 (2022.08.29)

* Initial stable release. 
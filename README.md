# Debug Log Manager

Contributors: qriouslad  
Donate link: https://paypal.me/qriouslad  
Tags: debug, errors, developer  
Requires at least: 4.8  
Tested up to: 6.0.1  
Stable tag: 1.0.0  
Requires PHP: 5.6  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html

![](.wordpress-org/banner-772x250.png)

Log errors via WP_DEBUG. Create, view and clear debug.log file.

## Description

Debug Log Manager allows you to **enable [WP_DEBUG](https://wordpress.org/support/article/debugging-in-wordpress/) with one click** to log PHP and database errors when you need to, and disable it when you're done. No need to manually edit wp-config.php file. 

It will also **create the debug.log file for you** in a non-default location with a custom file name for better security.

The included debug.log viewer **parses the log file and extract distinct errors and when they last occurred**, which is better then looking at the raw log file (potentially) full of repetitive errors. It also allows you to quickly find and filter more specific errors for your debugging work.

When the debug.log file grows too large, you can **easily clear the file** to save disk space and more easily observe newly occurring errors on your site.

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

Debug Log Manager is built using [DataTables.js](https://datatables.net/) and [jSticky](https://github.com/AndrewHenderson/jSticky).

## Changelog

### 1.0.0 (2022.08.29)

* Initial stable release. 
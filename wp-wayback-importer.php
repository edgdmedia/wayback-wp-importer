<?php
/**
 * Plugin Name: EDGD Wayback WordPress Importer
 * Plugin URI: https://edgdmedia.com/plugins/wayback-wordpress-importer
 * Description: Import content from WordPress websites archived on the Wayback Machine.
 * Version: 1.0.4
 * Author: EDGD Media
 * Author URI: https://edgdmedia.com
 * Text Domain: edgd-wayback-wp-importer
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WAYBACK_WP_IMPORTER_VERSION', '1.0.4');
define('WAYBACK_WP_IMPORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WAYBACK_WP_IMPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader if it exists
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * The code that runs during plugin activation.
 */
function activate_wayback_wp_importer() {
    // Activation tasks
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wayback_wp_importer() {
    // Deactivation tasks
}

register_activation_hook(__FILE__, 'activate_wayback_wp_importer');
register_deactivation_hook(__FILE__, 'deactivate_wayback_wp_importer');



/**
 * The core plugin class.
 */
require_once WAYBACK_WP_IMPORTER_PLUGIN_DIR . 'includes/class-wayback-wp-importer.php';

/**
 * Begins execution of the plugin.
 */
function run_wayback_wp_importer() {
    $plugin = new Wayback_WP_Importer();
    $plugin->run();
}

// Start the plugin
run_wayback_wp_importer();

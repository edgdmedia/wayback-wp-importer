<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 */

class Wayback_WP_Importer {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Wayback_WP_Importer_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once WAYBACK_WP_IMPORTER_PLUGIN_DIR . 'includes/class-wayback-wp-importer-loader.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once WAYBACK_WP_IMPORTER_PLUGIN_DIR . 'admin/class-wayback-wp-importer-admin.php';

        /**
         * The class responsible for handling Wayback Machine API requests.
         */
        require_once WAYBACK_WP_IMPORTER_PLUGIN_DIR . 'includes/class-wayback-wp-importer-api.php';

        /**
         * The class responsible for parsing WordPress content from HTML.
         */
        require_once WAYBACK_WP_IMPORTER_PLUGIN_DIR . 'includes/class-wayback-wp-importer-parser.php';

        /**
         * The class responsible for handling taxonomies extraction and management.
         */
        require_once WAYBACK_WP_IMPORTER_PLUGIN_DIR . 'includes/class-wayback-wp-importer-taxonomies.php';
        
        /**
         * The class responsible for handling custom fields extraction.
         */
        require_once WAYBACK_WP_IMPORTER_PLUGIN_DIR . 'includes/class-wayback-wp-importer-custom-fields.php';

        $this->loader = new Wayback_WP_Importer_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Wayback_WP_Importer_Admin();

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        // Add AJAX handlers
        $this->loader->add_action('wp_ajax_wayback_extract_content', $plugin_admin, 'ajax_extract_content');
        $this->loader->add_action('wp_ajax_wayback_import_post', $plugin_admin, 'ajax_import_post');
        $this->loader->add_action('wp_ajax_wayback_export_csv', $plugin_admin, 'ajax_export_csv');
        
        // Add new AJAX handlers for batch processing
        $this->loader->add_action('wp_ajax_wayback_get_post_content', $plugin_admin, 'ajax_get_post_content');
        $this->loader->add_action('wp_ajax_wayback_import_multiple', $plugin_admin, 'ajax_import_multiple');
        $this->loader->add_action('wp_ajax_wayback_export_multiple_csv', $plugin_admin, 'ajax_export_multiple_csv');
        
        // Add AJAX handler for custom fields
        $this->loader->add_action('wp_ajax_wayback_extract_custom_fields', $plugin_admin, 'ajax_extract_custom_fields');

        // Register AJAX action for extracting content by selectors
        $this->loader->add_action('wp_ajax_wayback_extract_selectors', $plugin_admin, 'ajax_extract_selectors');

        // Register AJAX action for getting taxonomies
        $this->loader->add_action('wp_ajax_wayback_get_taxonomies', $plugin_admin, 'ajax_get_taxonomies');
        
        // Register AJAX action for checking duplicate posts
        $this->loader->add_action('wp_ajax_wayback_check_duplicate', $plugin_admin, 'ajax_check_duplicate');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }
}

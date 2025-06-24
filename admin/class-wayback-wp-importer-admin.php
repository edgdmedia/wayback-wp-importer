<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 */

class Wayback_WP_Importer_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->plugin_name = 'wayback-wp-importer';
        $this->version = WAYBACK_WP_IMPORTER_VERSION;

        // Initialize the taxonomies handler
        $this->taxonomies = new Wayback_WP_Importer_Taxonomies();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles($hook)
    {
        // Only load on plugin admin page
        if ('tools_page_wayback-wp-importer' !== $hook) {
            return;
        }

        // Add a debug comment to verify CSS is loaded
        echo "<!-- Wayback WP Importer CSS loaded -->\n";

        wp_enqueue_style(
            $this->plugin_name,
            WAYBACK_WP_IMPORTER_PLUGIN_URL . 'admin/css/wayback-wp-importer-admin.css',
            array(),
            $this->version . '.' . time(), // Add timestamp to prevent caching during development
            'all'
        );

        // Enqueue custom fields CSS
        wp_enqueue_style(
            $this->plugin_name . '-custom-fields',
            WAYBACK_WP_IMPORTER_PLUGIN_URL . 'admin/css/wayback-wp-importer-admin-custom-fields.css',
            array($this->plugin_name),
            $this->version . '.' . time(), // Add timestamp to prevent caching during development
            'all'
        );

        // Enqueue taxonomies CSS
        wp_enqueue_style(
            $this->plugin_name . '-taxonomies',
            WAYBACK_WP_IMPORTER_PLUGIN_URL . 'admin/css/wayback-wp-importer-admin-taxonomies.css',
            array($this->plugin_name),
            $this->version . '.' . time(), // Add timestamp to prevent caching during development
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts($hook)
    {
        // Only load on plugin admin page
        if ('tools_page_wayback-wp-importer' !== $hook) {
            return;
        }

        // Add a debug comment to verify JS is loaded
        echo "<!-- Wayback WP Importer JS loaded -->\n";
        echo "<script>console.log('Wayback WP Importer JS file loaded');</script>\n";

        // Enqueue WordPress media scripts
        wp_enqueue_media();

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/wayback-wp-importer-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Enqueue the extract content JavaScript
        wp_enqueue_script(
            $this->plugin_name . '-extract',
            plugin_dir_url(__FILE__) . 'js/wayback-wp-importer-admin-extract.js',
            array('jquery', $this->plugin_name),
            $this->version,
            false
        );

        // Enqueue the custom fields JavaScript
        wp_enqueue_script(
            $this->plugin_name . '-custom-fields',
            plugin_dir_url(__FILE__) . 'js/wayback-wp-importer-admin-custom-fields.js',
            array('jquery', $this->plugin_name),
            $this->version,
            false
        );

        // Enqueue the duplicate checking JavaScript
        wp_enqueue_script(
            $this->plugin_name . '-duplicates',
            plugin_dir_url(__FILE__) . 'js/wayback-wp-importer-admin-duplicates.js',
            array('jquery', $this->plugin_name),
            $this->version,
            false
        );

        // Enqueue the taxonomies JavaScript
        wp_enqueue_script(
            $this->plugin_name . '-taxonomies',
            plugin_dir_url(__FILE__) . 'js/wayback-wp-importer-admin-taxonomies.js',
            array('jquery', $this->plugin_name),
            $this->version,
            false
        );

        // Enqueue the custom selectors JavaScript
        wp_enqueue_script(
            $this->plugin_name . '-custom-selectors',
            plugin_dir_url(__FILE__) . 'js/wayback-wp-importer-admin-custom-selectors.js',
            array('jquery', $this->plugin_name),
            $this->version,
            false
        );

        // Enqueue select2 for better dropdown UI
        wp_enqueue_script(
            'select2',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js',
            array('jquery'),
            '4.0.13',
            false
        );

        wp_enqueue_style(
            'select2',
            'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css',
            array(),
            '4.0.13',
            'all'
        );

        // Localize the script with new data
        wp_localize_script(
            $this->plugin_name,
            'wayback_wp_importer',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url(),
                'nonce' => wp_create_nonce('wayback_wp_importer_nonce'),
                'loading_text' => __('Processing...', 'wayback-wp-importer'),
                'error_text' => __('An error occurred. Please try again.', 'wayback-wp-importer'),
                'debug' => true, // Enable debug mode

                // Custom fields text
                'add_custom_field' => __('Add Custom Field', 'wayback-wp-importer'),
                'remove_custom_field' => __('Remove', 'wayback-wp-importer'),

                // Custom selectors text
                'no_selectors' => __('No selectors added. Please add at least one selector.', 'wayback-wp-importer'),
                'no_content' => __('No HTML content available for extraction.', 'wayback-wp-importer'),
                'selector' => __('Selector', 'wayback-wp-importer'),
                'value' => __('Value', 'wayback-wp-importer'),
                'attribute' => __('Attribute', 'wayback-wp-importer'),
                'extracted_value' => __('Extracted Value', 'wayback-wp-importer'),
                'not_found' => __('Not found', 'wayback-wp-importer'),
                'no_results' => __('No results found', 'wayback-wp-importer'),
                'no_selection' => __('Please select at least one item', 'wayback-wp-importer'),
                'custom_attribute_required' => __('Please enter a custom attribute name', 'wayback-wp-importer'),
                'invalid_response' => __('Invalid response format from server', 'wayback-wp-importer'),
                'ajax_error' => __('An error occurred during the AJAX request', 'wayback-wp-importer'),

                // Taxonomies text
                'add_button_text' => __('Add', 'wayback-wp-importer'),
                'add_new_term' => __('Add new term...', 'wayback-wp-importer'),
                'select_or_add_terms' => __('Select or add new terms', 'wayback-wp-importer'),
                'new_term_text' => __('(New)', 'wayback-wp-importer'),
                'no_taxonomies_found' => __('No taxonomies found.', 'wayback-wp-importer'),
            )
        );
    }

    /**
     * Add menu item for the plugin.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu()
    {
        add_management_page(
            __('Wayback WordPress Importer', 'wayback-wp-importer'),
            __('Wayback Importer', 'wayback-wp-importer'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page')
        );
    }

    /**
     * Render the admin page for the plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page()
    {
        // Check if this is a proxy request for an image
        if (isset($_GET['proxy']) && $_GET['proxy'] == 1 && isset($_GET['url'])) {
            $this->proxy_wayback_image($_GET['url']);
            exit;
        }

        include_once WAYBACK_WP_IMPORTER_PLUGIN_DIR . 'admin/partials/wayback-wp-importer-admin-display.php';
    }

    /**
     * Proxy for Wayback Machine images to avoid CORS issues.
     *
     * @since    1.0.0
     * @param    string    $image_url    The image URL to proxy.
     */
    private function proxy_wayback_image($image_url)
    {
        // Validate the URL
        if (empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Invalid image URL.';
            exit;
        }

        // Only allow Wayback Machine URLs
        if (strpos($image_url, 'web.archive.org') === false) {
            header('HTTP/1.0 400 Bad Request');
            echo 'Only Wayback Machine URLs are allowed.';
            exit;
        }

        // Fetch the image
        $response = wp_remote_get($image_url, array(
            'timeout' => 30,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'headers' => array(
                'Referer' => 'https://web.archive.org/',
            ),
        ));

        // Check for errors
        if (is_wp_error($response)) {
            header('HTTP/1.0 500 Internal Server Error');
            echo 'Failed to fetch image: ' . $response->get_error_message();
            exit;
        }

        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            header('HTTP/1.0 ' . $response_code . ' ' . wp_remote_retrieve_response_message($response));
            echo 'Failed to fetch image. Status code: ' . $response_code;
            exit;
        }

        // Get the image content and headers
        $image_content = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        // Set appropriate headers
        header('Content-Type: ' . $content_type);
        header('Content-Length: ' . strlen($image_content));
        header('Cache-Control: public, max-age=86400'); // Cache for 24 hours

        // Output the image
        echo $image_content;
        exit;
    }

    /**
     * AJAX handler for extracting content from Wayback Machine.
     *
     * @since    1.0.0
     */
    public function ajax_extract_content()
    {
        // Check nonce for security
        check_ajax_referer('wayback_wp_importer_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'wayback-wp-importer')));
        }

        // Get the URL and import mode from the request
        $wayback_url = isset($_POST['wayback_url']) ? sanitize_text_field($_POST['wayback_url']) : '';
        $import_mode = isset($_POST['import_mode']) ? sanitize_text_field($_POST['import_mode']) : 'single_post';
        $post_limit = isset($_POST['post_limit']) ? intval($_POST['post_limit']) : 10;
        $crawl_depth = isset($_POST['crawl_depth']) ? intval($_POST['crawl_depth']) : 2;
        $check_duplicates = isset($_POST['check_duplicates']) && $_POST['check_duplicates'] === '1';

        // Validate post limit and crawl depth
        $post_limit = max(1, min(100, $post_limit)); // Between 1 and 100
        $crawl_depth = max(1, min(5, $crawl_depth)); // Between 1 and 5

        if (empty($wayback_url)) {
            wp_send_json_error(array('message' => __('Please provide a valid Wayback Machine URL.', 'wayback-wp-importer')));
        }

        // Initialize the API class
        $api = new Wayback_WP_Importer_API();

        // Process based on import mode
        switch ($import_mode) {
            case 'scan_links':
                // Scan the page for blog post links
                $api = new Wayback_WP_Importer_API();

                // Get post types and permalink structures from the request
                $post_types = isset($_POST['post_types']) ? (array) $_POST['post_types'] : array('post');
                $permalink_structures = array();

                // Check for permalink structure options
                if (isset($_POST['permalink_structures'])) {
                    $permalink_structures = $_POST['permalink_structures'];
                } else {
                    // Default to all permalink structures if none specified
                    $permalink_structures = array(
                        'date' => '1',
                        'postname' => '1',
                        'post_id' => '1',
                        'custom' => '1'
                    );
                }

                // Sanitize post types and permalink structures
                $sanitized_post_types = array_map('sanitize_text_field', $post_types);
                $sanitized_permalink_structures = array_map('sanitize_text_field', $permalink_structures);

                // Prepare options for the API call
                $options = array(
                    'post_types' => $sanitized_post_types,
                    'permalink_structures' => $sanitized_permalink_structures
                );

                // Call the API with the options
                $blog_post_links = $api->scan_page_for_blog_posts($wayback_url, $options);

                if (is_wp_error($blog_post_links)) {
                    wp_send_json_error(array('message' => $blog_post_links->get_error_message()));
                }

                // If duplicate checking is enabled, use URL-based duplicate checking
                if ($check_duplicates) {
                    // Check for duplicates based on URL slugs - much more efficient than fetching content
                    $result = $this->check_and_filter_url_duplicates($blog_post_links, true);

                    // Send the filtered blog post links data back to the client
                    wp_send_json_success(array(
                        'blog_post_links' => $result['blog_post_links'],
                        'duplicates_skipped' => $result['duplicates_skipped']
                    ));
                } else {
                    // If duplicate checking is not enabled, just send the links back
                    wp_send_json_success(array('blog_post_links' => $blog_post_links));
                }
                break;

            case 'single_post':
                // Get the archived content for a single post
                $archived_content = $api->get_archived_content($wayback_url);

                if (is_wp_error($archived_content)) {
                    wp_send_json_error(array('message' => $archived_content->get_error_message()));
                }

                // Initialize the parser class
                $parser = new Wayback_WP_Importer_Parser();

                // Parse the content
                $parsed_data = $parser->parse_wordpress_content($archived_content);

                if (is_wp_error($parsed_data)) {
                    wp_send_json_error(array('message' => $parsed_data->get_error_message()));
                }

                // For single post mode, we don't need to check if it's a post page
                // We assume the URL provided is for a single post

                // Add the Wayback URL to the data
                $parsed_data['wayback_url'] = $wayback_url;

                // IMPORTANT: Add the full HTML content to the response for custom fields extraction
                $parsed_data['html_content'] = base64_encode($archived_content);

                // Add debug info
                error_log('Single post parsed data: ' . print_r($parsed_data, true));
                error_log('HTML content length: ' . strlen($archived_content));

                // Ensure we have at least a title and content
                if (empty($parsed_data['title']) && empty($parsed_data['content'])) {
                    error_log('Single post import failed: No title or content found');
                    wp_send_json_error(array('message' => 'Could not extract post content. Please check if the URL points to a valid WordPress post.'));
                    return;
                }

                // Ensure content is properly formatted
                if (!empty($parsed_data['content']) && strpos($parsed_data['content'], '<div class="elementor-element') === 0) {
                    error_log('Fixing truncated Elementor content in response');
                    $parsed_data['content'] = '<div class="elementor-content-wrapper">' . $parsed_data['content'] . '</div>';
                }

                // Make sure arrays are properly initialized
                if (!isset($parsed_data['categories']) || !is_array($parsed_data['categories'])) {
                    $parsed_data['categories'] = array();
                }

                if (!isset($parsed_data['tags']) || !is_array($parsed_data['tags'])) {
                    $parsed_data['tags'] = array();
                }

                if (!isset($parsed_data['comments']) || !is_array($parsed_data['comments'])) {
                    $parsed_data['comments'] = array();
                }

                // Check for duplicates if requested
                if ($check_duplicates && !empty($parsed_data['title'])) {
                    $duplicate_id = $this->check_for_duplicate_post($parsed_data['title']);
                    $parsed_data['isDuplicate'] = ($duplicate_id !== false);
                    $parsed_data['duplicateId'] = $duplicate_id;
                    $parsed_data['duplicateChecked'] = true;
                } else {
                    $parsed_data['isDuplicate'] = false;
                    $parsed_data['duplicateId'] = null;
                    $parsed_data['duplicateChecked'] = false;
                }

                // Send the parsed data back to the client
                wp_send_json_success($parsed_data);
                break;

            case 'entire_website':
                // Get offset parameter if provided
                $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

                // Crawl the entire website for posts
                $post_urls = $api->crawl_website($wayback_url, $post_limit, $crawl_depth, $offset);

                if (is_wp_error($post_urls)) {
                    wp_send_json_error(array('message' => $post_urls->get_error_message()));
                }

                // Process each post URL to get basic info and check for duplicates if requested
                $result = $this->process_post_urls($post_urls, $check_duplicates, $check_duplicates);

                // Send the posts data back to the client
                wp_send_json_success(array(
                    'posts' => $result['posts'],
                    'duplicates_skipped' => $result['duplicates_skipped'],
                    'post_urls' => $post_urls
                ));
                break;

            case 'category':
                // Get offset parameter if provided
                $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

                // Crawl a category page for posts
                $post_urls = $api->crawl_category($wayback_url, $post_limit, $offset);

                if (is_wp_error($post_urls)) {
                    wp_send_json_error(array('message' => $post_urls->get_error_message()));
                }

                // Process each post URL to get basic info and check for duplicates if requested
                $result = $this->process_post_urls($post_urls, $check_duplicates, $check_duplicates);

                // Send the posts data back to the client
                wp_send_json_success(array(
                    'posts' => $result['posts'],
                    'duplicates_skipped' => $result['duplicates_skipped'],
                    'post_urls' => $post_urls
                ));
                break;

            default:
                wp_send_json_error(array('message' => __('Invalid import mode.', 'wayback-wp-importer')));
        }
    }

    /**
     * AJAX handler for importing a post into WordPress.
     *
     * @since    1.0.0
     */
    public function ajax_import_post()
    {
        // Check nonce for security
        check_ajax_referer('wayback_wp_importer_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'wayback-wp-importer')));
        }

        // Get the post data from the request
        $post_data = isset($_POST['post_data']) ? $_POST['post_data'] : array();
        $post_status = isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : 'draft';

        // Get custom fields if available
        $custom_fields = array();
        if (isset($_POST['custom_fields']) && !empty($_POST['custom_fields'])) {
            $custom_fields = json_decode(stripslashes($_POST['custom_fields']), true);
            if (!is_array($custom_fields)) {
                $custom_fields = array();
            }
            error_log('Custom fields received: ' . print_r($custom_fields, true));
        }

        // Debug log the raw post data
        error_log('Raw post_data received: ' . print_r($post_data, true));

        // Validate post status
        $valid_statuses = array('publish', 'draft', 'pending', 'private');
        if (!in_array($post_status, $valid_statuses)) {
            $post_status = 'draft'; // Default to draft if invalid
        }

        if (empty($post_data) || !is_array($post_data)) {
            wp_send_json_error(array('message' => __('Invalid post data.', 'wayback-wp-importer')));
        }

        // Sanitize the post data
        $title = isset($post_data['post_title']) ? sanitize_text_field($post_data['post_title']) : '';
        $content = isset($post_data['post_content']) ? wp_kses_post($post_data['post_content']) : '';
        $excerpt = isset($post_data['post_excerpt']) ? sanitize_textarea_field($post_data['post_excerpt']) : '';
        $date = isset($post_data['post_date']) ? sanitize_text_field($post_data['post_date']) : '';
        $author = isset($post_data['post_author']) ? sanitize_text_field($post_data['post_author']) : '';
        $categories = isset($post_data['post_categories']) ? array_map('sanitize_text_field', $post_data['post_categories']) : array();
        $tags = isset($post_data['post_tags']) ? array_map('sanitize_text_field', $post_data['post_tags']) : array();
        $wayback_url = isset($post_data['wayback_url']) ? esc_url_raw($post_data['wayback_url']) : '';

        // Check for featured image URL in multiple possible locations
        $featured_image_url = '';

        // First check if it's directly in the AJAX data (our new approach)
        if (isset($_POST['featured_image_url']) && !empty($_POST['featured_image_url'])) {
            $featured_image_url = esc_url_raw($_POST['featured_image_url']);
            error_log('Found featured image URL in direct AJAX data: ' . $featured_image_url);
        }
        // Then check if it's in the post_data array (original approach)
        else if (isset($post_data['featured_image']) && !empty($post_data['featured_image'])) {
            $featured_image_url = esc_url_raw($post_data['featured_image']);
            error_log('Found featured image URL in post_data: ' . $featured_image_url);
        }

        // Log the featured image URL
        error_log('Featured image URL from request: ' . $featured_image_url);

        // Log the import data for debugging
        error_log('Importing post with title: ' . $title);
        error_log('Wayback URL: ' . $wayback_url);

        // Check for duplicate posts to prevent multiple imports
        $existing_post = $this->check_for_duplicate_post($title);
        if ($existing_post) {
            // Create edit and view URLs manually to avoid using get_edit_post_link in AJAX context
            $edit_url = admin_url('post.php?post=' . $existing_post . '&action=edit');
            $view_url = home_url('?p=' . $existing_post);

            wp_send_json_error(array(
                'message' => sprintf(__('A post with the title "%s" already exists (ID: %d).', 'wayback-wp-importer'), $title, $existing_post),
                'duplicate' => true,
                'post_id' => $existing_post,
                'edit_url' => $edit_url,
                'view_url' => $view_url
            ));
            return;
        }

        // Always process post content to handle Wayback Machine images, with logging
        error_log('=== [ajax_import_post] BEFORE process_post_content. Wayback URL: ' . $wayback_url);
        $content = $this->process_post_content($content, $wayback_url);
        error_log('=== [ajax_import_post] AFTER process_post_content. Content length: ' . strlen($content));

        // Get post type if provided, default to 'post'
        $post_type = isset($post_data['post_type']) ? sanitize_text_field($post_data['post_type']) : 'post';

        // Validate post type exists
        $post_types = get_post_types(array('public' => true), 'names');
        if (!in_array($post_type, $post_types)) {
            $post_type = 'post'; // Default to post if invalid
        }

        // Create post array
        $new_post = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_excerpt'  => $excerpt,
            'post_status'   => $post_status,
            'post_type'     => $post_type,
            'post_author'   => $this->get_author_id($author),
            'tax_input'     => array()
        );

        // Add categories and tags if this post type supports them
        $post_taxonomies = get_object_taxonomies($post_type);

        if (in_array('category', $post_taxonomies)) {
            $new_post['tax_input']['category'] = $this->get_category_ids($categories);
        }

        if (in_array('post_tag', $post_taxonomies)) {
            $new_post['tax_input']['post_tag'] = $tags;
        }

        // Handle custom taxonomies if provided
        if (isset($post_data['taxonomies']) && is_array($post_data['taxonomies'])) {
            foreach ($post_data['taxonomies'] as $taxonomy => $terms) {
                // Skip categories and tags as they're already handled
                if ($taxonomy === 'category' || $taxonomy === 'post_tag') {
                    continue;
                }

                // Check if this taxonomy is registered for this post type
                if (!in_array($taxonomy, $post_taxonomies)) {
                    continue;
                }

                // Process the terms - they could be IDs or 'new:term_name' format
                $processed_terms = array();
                $new_terms = array();

                foreach ($terms as $term) {
                    if (is_string($term) && strpos($term, 'new:') === 0) {
                        // This is a new term to be created
                        $new_term_name = substr($term, 4); // Remove 'new:' prefix
                        $new_terms[] = sanitize_text_field($new_term_name);
                    } else {
                        // This is an existing term ID
                        $processed_terms[] = intval($term);
                    }
                }

                // Add the existing taxonomy terms to the post
                $new_post['tax_input'][$taxonomy] = $processed_terms;

                // Store new terms to be created after post insertion
                if (!empty($new_terms)) {
                    // We'll handle this after post creation
                    if (!isset($GLOBALS['wayback_new_terms'])) {
                        $GLOBALS['wayback_new_terms'] = array();
                    }
                    $GLOBALS['wayback_new_terms'][$taxonomy] = $new_terms;
                }
            }
        }

        // Set post date if available
        if (!empty($date)) {
            // Format the date properly for WordPress
            $date_obj = date_create($date);
            if ($date_obj) {
                $formatted_date = date_format($date_obj, 'Y-m-d H:i:s');
                $new_post['post_date'] = $formatted_date;
                $new_post['post_date_gmt'] = get_gmt_from_date($formatted_date);
            }
        }

        // Insert the post
        $post_id = wp_insert_post($new_post);

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
            return;
        }

        // Save the Wayback URL as post metadata
        if (!empty($wayback_url)) {
            update_post_meta($post_id, 'wayback_source_url', $wayback_url);
            error_log('Saved Wayback URL as post metadata for post ID: ' . $post_id);
        }

        // Handle new taxonomy terms if any were created
        if (isset($GLOBALS['wayback_new_terms']) && !empty($GLOBALS['wayback_new_terms'])) {
            foreach ($GLOBALS['wayback_new_terms'] as $taxonomy => $new_terms) {
                if (empty($new_terms)) {
                    continue;
                }

                // Check if taxonomy exists
                if (!taxonomy_exists($taxonomy)) {
                    continue;
                }

                // Create and assign each new term
                foreach ($new_terms as $new_term_name) {
                    // Create the term if it doesn't exist
                    $term_result = wp_insert_term($new_term_name, $taxonomy);

                    if (!is_wp_error($term_result)) {
                        // Term created successfully, assign to post
                        $term_id = $term_result['term_id'];
                        wp_set_object_terms($post_id, $term_id, $taxonomy, true);
                        error_log("Created and assigned new {$taxonomy} term: {$new_term_name} (ID: {$term_id})");
                    } else {
                        // Term creation failed, log error
                        error_log("Failed to create {$taxonomy} term: {$new_term_name} - " . $term_result->get_error_message());
                    }
                }
            }

            // Clear the global variable
            unset($GLOBALS['wayback_new_terms']);
        }

        // Save custom fields as post meta
        if (!empty($custom_fields)) {
            foreach ($custom_fields as $meta_key => $meta_value) {
                // Sanitize the meta key and value
                $meta_key = sanitize_text_field($meta_key);
                $meta_value = sanitize_text_field($meta_value);

                // Save as post meta
                if (!empty($meta_key)) {
                    update_post_meta($post_id, $meta_key, $meta_value);
                    error_log("Saved custom field: {$meta_key} = {$meta_value}");
                }
            }
        }

        // Handle featured image - this is critical for the post
        error_log('Featured image handling - starting process');

        // Check if a custom image ID was provided
        $custom_image_id = isset($post_data['custom_image_id']) ? intval($post_data['custom_image_id']) : 0;
        error_log('Custom image ID from post data: ' . $custom_image_id);

        // Also check if it was provided directly in the AJAX request
        if (!$custom_image_id && isset($_POST['custom_image_id'])) {
            $custom_image_id = intval($_POST['custom_image_id']);
            error_log('Custom image ID from direct AJAX data: ' . $custom_image_id);
        }

        $featured_image_set = false;

        // If we have a custom image ID from the media library, use that
        if ($custom_image_id > 0) {
            // Verify the attachment exists
            $attachment = get_post($custom_image_id);
            if ($attachment && $attachment->post_type === 'attachment') {
                // Set as featured image
                $result = set_post_thumbnail($post_id, $custom_image_id);
                if ($result) {
                    error_log('Successfully set custom image ID ' . $custom_image_id . ' as featured image for post ' . $post_id);
                    $featured_image_set = true;
                } else {
                    error_log('Failed to set custom image ID ' . $custom_image_id . ' as featured image');
                }
            } else {
                error_log('Invalid custom image ID: ' . $custom_image_id . ' - attachment does not exist or is not an attachment');
            }
        }

        // If custom image wasn't set successfully and we have a URL, try that
        if (!$featured_image_set && !empty($featured_image_url)) {
            error_log('Attempting to download and set featured image from URL: ' . $featured_image_url);
            $this->set_featured_image($post_id, $featured_image_url);
        } else if (!$featured_image_set && empty($featured_image_url)) {
            error_log('No featured image URL provided and custom image failed - post will not have a featured image');
        }

        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'message' => __('Post imported successfully!', 'wayback-wp-importer'),
        ));
    }

    /**
     * AJAX handler for exporting data to CSV.
     *
     * @since    1.0.0
     */
    public function ajax_export_csv()
    {
        // Check nonce for security
        check_ajax_referer('wayback_wp_importer_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'wayback-wp-importer')));
        }

        // Get the post data from the request
        $post_data = isset($_POST['post_data']) ? $_POST['post_data'] : array();

        if (empty($post_data) || !is_array($post_data)) {
            wp_send_json_error(array('message' => __('Invalid post data.', 'wayback-wp-importer')));
        }

        // Generate CSV content
        $csv_content = $this->generate_csv($post_data);

        // Return the CSV content
        wp_send_json_success(array(
            'csv_content' => $csv_content,
            'filename' => 'wayback-import-' . date('Y-m-d') . '.csv',
        ));
    }

    /**
     * Get author ID from name or create a new user if not found.
     *
     * @since    1.0.0
     * @param    string    $author_name    The author name.
     * @return   int       The author ID.
     */
    private function get_author_id($author_name)
    {
        // Default to current user if no author name provided
        if (empty($author_name)) {
            return get_current_user_id();
        }

        // Try to find the user by name
        $user = get_user_by('login', $author_name);

        if ($user) {
            return $user->ID;
        }

        // If user doesn't exist, return current user ID
        return get_current_user_id();
    }

    /**
     * Get category IDs from names or create new categories if not found.
     *
     * @since    1.0.0
     * @param    array    $categories    The category names.
     * @return   array    The category IDs.
     */
    private function get_category_ids($categories)
    {
        $category_ids = array();

        if (empty($categories)) {
            // Use default category if none provided
            $category_ids[] = get_option('default_category');
            return $category_ids;
        }

        foreach ($categories as $category_name) {
            $term = term_exists($category_name, 'category');

            if ($term) {
                $category_ids[] = (int) $term['term_id'];
            } else {
                // Create the category if it doesn't exist
                $new_term = wp_insert_term($category_name, 'category');

                if (!is_wp_error($new_term)) {
                    $category_ids[] = (int) $new_term['term_id'];
                }
            }
        }

        return $category_ids;
    }

    /**
     * Set featured image for a post.
     *
     * @since    1.0.0
     * @param    int       $post_id             The post ID.
     * @param    string    $featured_image_url  The featured image URL.
     */
    private function set_featured_image($post_id, $featured_image_url)
    {
        // Download image from URL
        $upload = $this->download_image($featured_image_url);

        if (is_wp_error($upload)) {
            return;
        }

        // Check the type of file. We'll use this as the 'post_mime_type'.
        $filetype = wp_check_filetype(basename($upload['file']), null);

        // Prepare an array of post data for the attachment.
        $attachment = array(
            'guid'           => $upload['url'],
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Insert the attachment.
        $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Generate the metadata for the attachment, and update the database record.
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Set as featured image
        set_post_thumbnail($post_id, $attach_id);
    }

    /**
     * Download an image from a URL.
     *
     * @since    1.0.0
     * @param    string    $url    The image URL.
     * @return   array|WP_Error    The upload data or WP_Error on failure.
     */
    private function download_image($url)
    {
        error_log('Attempting to download image from URL: ' . $url);

        // Ensure the URL is valid
        if (empty($url)) {
            error_log('Empty image URL provided');
            return new WP_Error('invalid_url', __('Empty image URL.', 'wayback-wp-importer'));
        }

        // Special handling for Wayback Machine URLs
        if (strpos($url, 'web.archive.org/web/') !== false) {
            error_log('Detected Wayback Machine URL, using proxy to download');
            // Use our proxy to fetch the image
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ));

            if (is_wp_error($response)) {
                error_log('Error fetching image via proxy: ' . $response->get_error_message());
                return $response;
            }

            if (wp_remote_retrieve_response_code($response) !== 200) {
                error_log('Non-200 response code: ' . wp_remote_retrieve_response_code($response));
                return new WP_Error('download_error', __('Failed to download image from Wayback Machine.', 'wayback-wp-importer'));
            }

            $image_data = wp_remote_retrieve_body($response);
            if (empty($image_data)) {
                error_log('Empty image data received');
                return new WP_Error('empty_image', __('Empty image data received.', 'wayback-wp-importer'));
            }

            // Get file extension from content type or URL
            $content_type = wp_remote_retrieve_header($response, 'content-type');
            $extension = $this->get_extension_from_content_type($content_type);
            if (!$extension && preg_match('/\.([a-zA-Z0-9]+)(?:[\?#].*)?$/', $url, $matches)) {
                $extension = $matches[1];
            }
            if (!$extension) {
                $extension = 'jpg'; // Default to jpg if we can't determine
            }

            // Create a temporary file
            $upload_dir = wp_upload_dir();
            $temp_file = wp_tempnam('wayback-image-');
            file_put_contents($temp_file, $image_data);

            // Rename the temp file with the correct extension
            $new_temp_file = $temp_file . '.' . $extension;
            rename($temp_file, $new_temp_file);
            $temp_file = $new_temp_file;

            error_log('Created temporary file: ' . $temp_file);
        } else {
            error_log('Using WordPress download_url function for non-Wayback URL');
            // Include necessary files for media handling
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            // Download the file using WordPress function
            $temp_file = download_url($url);

            if (is_wp_error($temp_file)) {
                error_log('Error downloading image: ' . $temp_file->get_error_message());
                return $temp_file;
            }
        }

        // Array based on $_FILE as seen in PHP file uploads
        $file = array(
            'name'     => basename(preg_replace('/\?.*$/', '', $url)),
            'type'     => mime_content_type($temp_file),
            'tmp_name' => $temp_file,
            'error'    => 0,
            'size'     => filesize($temp_file),
        );

        error_log('File info: ' . print_r($file, true));

        // Move the temporary file into the uploads directory
        $upload = wp_handle_sideload($file, array('test_form' => false));

        // Clean up the temporary file
        @unlink($temp_file);

        if (isset($upload['error'])) {
            error_log('Upload error: ' . $upload['error']);
            return new WP_Error('upload_error', $upload['error']);
        }

        error_log('Image successfully downloaded and processed: ' . $upload['url']);
        error_log('[download_image] Returning upload: ' . print_r($upload, true));
        return $upload;
    }

    /**
     * Get file extension from content type
     *
     * @param string $content_type The content type header
     * @return string|false The extension or false if not found
     */
    private function get_extension_from_content_type($content_type)
    {
        $map = array(
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg'
        );

        if (isset($map[$content_type])) {
            return $map[$content_type];
        }

        return false;
    }

    /**
     * Process post content to handle Wayback Machine images
     * 
     * This function processes the post content to handle images from the Wayback Machine.
     * It can either:
     * 1. Download the images to the WordPress media library and update the URLs (default)
     * 2. Keep the Wayback Machine URLs but ensure they work with proper formatting
     *
     * @since    1.0.0
     * @param    string    $content       The post content
     * @param    string    $wayback_url   The original Wayback Machine URL
     * @return   string    The processed content
     */
    private function process_post_content($content, $wayback_url)
    {
        error_log('=== [process_post_content] START for Wayback URL: ' . $wayback_url . ' ===');
        error_log('Processing post content for Wayback Machine images');

        // Check if content is empty
        if (empty($content)) {
            return $content;
        }

        // Option to choose whether to download images or keep Wayback URLs
        // Can be made configurable via plugin settings in the future
        $download_images = true; // Set to false to keep Wayback URLs

        // Extract timestamp from Wayback URL if available
        $timestamp = '';
        if (preg_match('/web\.archive\.org\/web\/([0-9]+)/', $wayback_url, $matches)) {
            $timestamp = $matches[1];
            error_log('Extracted timestamp from Wayback URL: ' . $timestamp);
        }

        // Create a DOMDocument to parse the HTML content
        $dom = new DOMDocument();

        // Suppress warnings during HTML parsing
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        // Find all image tags
        $images = $dom->getElementsByTagName('img');
        $modified = false;
        $image_count = $images->length;
        error_log('Found ' . $image_count . ' images in post content');

        // Process each image
        $processed_images = array();
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if (empty($src)) {
                error_log('[process_post_content] Skipping empty src attribute.');
                continue;
            }

            error_log('[process_post_content] Processing image with src: ' . $src);

            // Skip if we've already processed this image
            if (isset($processed_images[$src])) {
                error_log('[process_post_content] Already processed src, using cached: ' . $processed_images[$src]);
                $img->setAttribute('src', $processed_images[$src]);
                $modified = true;
                continue;
            }

            // Check if it's already a Wayback Machine URL
            $is_wayback_url = (strpos($src, 'web.archive.org/web/') !== false);

            // If it's not a Wayback URL and we have a timestamp, convert it
            if (!$is_wayback_url && !empty($timestamp)) {
                // Handle both absolute and relative URLs
                if (strpos($src, 'http') === 0) {
                    // Absolute URL - remove protocol
                    $clean_src = preg_replace('/^https?:\/\//', '', $src);
                    $wayback_src = 'https://web.archive.org/web/' . $timestamp . 'im_/' . $clean_src;
                } else {
                    // Relative URL - need to determine the base URL from the wayback_url
                    $base_url = '';
                    if (preg_match('/web\.archive\.org\/web\/[0-9]+(?:im_)?\/(https?:\/\/[^\/]+)/', $wayback_url, $matches)) {
                        $base_url = $matches[1];
                        // If src starts with /, it's a root-relative URL
                        if (strpos($src, '/') === 0) {
                            $wayback_src = 'https://web.archive.org/web/' . $timestamp . 'im_/' . $base_url . $src;
                        } else {
                            // It's a relative URL to the current path
                            // Extract the path from wayback_url
                            $path = '';
                            if (preg_match('/web\.archive\.org\/web\/[0-9]+(?:im_)?\/(https?:\/\/[^\?#]+)/', $wayback_url, $path_matches)) {
                                $full_url = $path_matches[1];
                                $path = dirname($full_url);
                                if ($path !== 'https:/' && $path !== 'http:/') {
                                    $wayback_src = 'https://web.archive.org/web/' . $timestamp . 'im_/' . $path . '/' . $src;
                                } else {
                                    $wayback_src = 'https://web.archive.org/web/' . $timestamp . 'im_/' . $base_url . '/' . $src;
                                }
                            } else {
                                $wayback_src = 'https://web.archive.org/web/' . $timestamp . 'im_/' . $base_url . '/' . $src;
                            }
                        }
                    } else {
                        // Can't determine base URL, skip this image
                        continue;
                    }
                }

                error_log('Converted to Wayback URL: ' . $wayback_src);
                $src = $wayback_src;
                $is_wayback_url = true;
            }

            if ($download_images && $is_wayback_url) {
                // Download the image and add to media library
                error_log('[process_post_content] Downloading image from Wayback Machine: ' . $src);
                $upload = $this->download_image($src);
                if (is_wp_error($upload)) {
                    error_log('[process_post_content] download_image returned WP_Error: ' . $upload->get_error_message());
                } else if (!isset($upload['file']) || !isset($upload['url'])) {
                    error_log('[process_post_content] download_image did not return file or url. Upload: ' . print_r($upload, true));
                } else {
                    error_log('[process_post_content] Image downloaded. File: ' . $upload['file'] . ' | URL: ' . $upload['url']);
                    // Create attachment in the media library
                    $filetype = wp_check_filetype(basename($upload['file']), null);
                    $attachment = array(
                        'guid'           => $upload['url'],
                        'post_mime_type' => $filetype['type'],
                        'post_title'     => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    $attach_id = wp_insert_attachment($attachment, $upload['file']);
                    if (is_wp_error($attach_id)) {
                        error_log('[process_post_content] wp_insert_attachment failed: ' . $attach_id->get_error_message());
                    } else {
                        error_log('[process_post_content] Attachment created. ID: ' . $attach_id);
                        // Generate attachment metadata
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                        wp_update_attachment_metadata($attach_id, $attach_data);
                        $new_src = wp_get_attachment_url($attach_id);
                        if (!$new_src) {
                            error_log('[process_post_content] wp_get_attachment_url failed for ID: ' . $attach_id);
                        } else {
                            $img->setAttribute('src', $new_src);
                            $processed_images[$src] = $new_src;
                            error_log('[process_post_content] Updated image src to: ' . $new_src);
                            $modified = true;
                        }
                    }
                }
            } else if (!$download_images && $is_wayback_url) {
                // Keep the Wayback Machine URL but ensure it's properly formatted
                // No changes needed as we've already converted URLs to Wayback format above
                $processed_images[$src] = $src;
            }
        }

        // If we modified the content, return the updated HTML
        if ($modified) {
            // Save the modified HTML
            $content = $dom->saveHTML();

            // Clean up the HTML (remove doctype, html and body tags added by DOMDocument)
            $content = preg_replace('/^<!DOCTYPE.+?>/', '', $content);
            $content = preg_replace('/<html><body>|<\/body><\/html>/', '', $content);

            error_log('Post content updated with ' . count($processed_images) . ' processed images');
        }

        return $content;
    }

    /**
     * Filter posts by checking for duplicates and optionally removing them
     *
     * @since    1.0.3
     * @param    array    $posts          Array of posts with at least a title field
     * @param    bool     $remove_duplicates    Whether to remove duplicates from the returned list
     * @return   array    Array with filtered posts and duplicate count: ['posts' => $filtered_posts, 'duplicates_skipped' => $count]
     */
    private function check_and_filter_duplicates($posts, $remove_duplicates = false)
    {
        $filtered_posts = array();
        $duplicates_skipped = 0;

        foreach ($posts as $post) {
            // Skip posts without a title
            if (empty($post['title'])) {
                $post['isDuplicate'] = false;
                $post['duplicateId'] = null;
                $post['duplicateChecked'] = true;
                $filtered_posts[] = $post;
                continue;
            }

            // Check for duplicate
            $duplicate_id = $this->check_for_duplicate_post($post['title']);
            $is_duplicate = ($duplicate_id !== false);

            // Mark the post with duplicate info
            $post['isDuplicate'] = $is_duplicate;
            $post['duplicateId'] = $duplicate_id;
            $post['duplicateChecked'] = true;

            // If it's a duplicate and we're removing duplicates, skip adding it to filtered posts
            if ($is_duplicate && $remove_duplicates) {
                $duplicates_skipped++;
            } else {
                $filtered_posts[] = $post;
            }
        }

        return array(
            'posts' => $filtered_posts,
            'duplicates_skipped' => $duplicates_skipped
        );
    }

    /**
     * Filter URLs by checking for duplicates based on URL slugs and optionally removing them
     * This is specifically designed for scan_links mode where we only have URLs initially
     *
     * @since    1.0.3
     * @param    array    $urls             Array of Wayback Machine URLs
     * @param    bool     $remove_duplicates Whether to remove duplicates from the returned list
     * @return   array    Array with filtered URLs and duplicate count: ['urls' => $filtered_urls, 'duplicates_skipped' => $count]
     */
    private function check_and_filter_url_duplicates($urls, $remove_duplicates = false)
    {
        $filtered_urls = array();
        $duplicates_skipped = 0;

        foreach ($urls as $url) {
            // Check for duplicate based on URL slug
            $duplicate_id = $this->check_for_duplicate_url($url);
            $is_duplicate = ($duplicate_id !== false);

            // If it's a duplicate and we're removing duplicates, skip adding it to filtered URLs
            if ($is_duplicate && $remove_duplicates) {
                $duplicates_skipped++;
            } else {
                // For URLs that pass the filter, create a simple URL object like the original format
                $filtered_urls[] = array(
                    'url' => $url,
                    'title' => $this->extract_slug_from_url($url) // Use the slug as a fallback title
                );
            }
        }

        return array(
            'blog_post_links' => $filtered_urls,
            'duplicates_skipped' => $duplicates_skipped
        );
    }

    /**
     * Process a list of post URLs to extract basic information.
     *
     * @since    1.0.0
     * @param    array    $post_urls    Array of post URLs from Wayback Machine.
     * @param    bool     $check_duplicates    Whether to check for duplicates during extraction.
     * @param    bool     $remove_duplicates   Whether to remove duplicates from the returned list.
     * @return   array    Array with posts and duplicate count if check_duplicates is true.
     */
    private function process_post_urls($post_urls, $check_duplicates = false, $remove_duplicates = false)
    {
        $posts = array();
        $api = new Wayback_WP_Importer_API();
        $parser = new Wayback_WP_Importer_Parser();

        foreach ($post_urls as $url) {
            // Get basic content for each post
            $archived_content = $api->get_archived_content($url);

            if (is_wp_error($archived_content)) {
                // Skip this post if there's an error
                continue;
            }

            // Extract just the title and date for the list view
            $post_data = $parser->extract_basic_info($archived_content);

            if (!is_wp_error($post_data)) {
                $post_data['wayback_url'] = $url;

                // Initialize duplicate fields (will be set by check_and_filter_duplicates if needed)
                if (!$check_duplicates) {
                    $post_data['isDuplicate'] = false;
                    $post_data['duplicateId'] = null;
                    $post_data['duplicateChecked'] = false;
                }

                $posts[] = $post_data;
            }
        }

        // Check for duplicates if requested
        if ($check_duplicates) {
            $result = $this->check_and_filter_duplicates($posts, $remove_duplicates);
            return $result; // Returns ['posts' => $filtered_posts, 'duplicates_skipped' => $count]
        }

        // Otherwise just return the posts
        return array(
            'posts' => $posts,
            'duplicates_skipped' => 0
        );
    }

    /**
     * AJAX handler for getting full post content for preview.
     *
     * @since    1.0.0
     */
    public function ajax_get_post_content()
    {
        // Check nonce for security
        check_ajax_referer('wayback_wp_importer_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'wayback-wp-importer')));
        }

        // Get the URL from the request
        $wayback_url = isset($_POST['wayback_url']) ? sanitize_text_field($_POST['wayback_url']) : '';

        if (empty($wayback_url)) {
            wp_send_json_error(array('message' => __('Please provide a valid Wayback Machine URL.', 'wayback-wp-importer')));
        }

        // Initialize the API class
        $api = new Wayback_WP_Importer_API();

        // Get the archived content
        $archived_content = $api->get_archived_content($wayback_url);

        if (is_wp_error($archived_content)) {
            wp_send_json_error(array('message' => $archived_content->get_error_message()));
        }

        // Initialize the parser class
        $parser = new Wayback_WP_Importer_Parser();

        // Parse the content
        $parsed_data = $parser->parse_wordpress_content($archived_content);

        if (is_wp_error($parsed_data)) {
            wp_send_json_error(array('message' => $parsed_data->get_error_message()));
        }

        // Add the Wayback URL to the data
        $parsed_data['wayback_url'] = $wayback_url;

        // Send the parsed data back to the client
        wp_send_json_success($parsed_data);
    }

    /**
     * AJAX handler for importing multiple posts into WordPress.
     *
     * @since    1.0.0
     */
    public function ajax_import_multiple()
    {
        // Check nonce for security
        check_ajax_referer('wayback_wp_importer_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'wayback-wp-importer')));
        }

        // Get the posts data from the request
        $posts_json = isset($_POST['posts']) ? $_POST['posts'] : '';
        $post_status = isset($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : 'draft';
        $categories = isset($_POST['categories']) ? sanitize_text_field($_POST['categories']) : '';

        // Validate post status
        $valid_statuses = array('publish', 'draft', 'pending', 'private');
        if (!in_array($post_status, $valid_statuses)) {
            $post_status = 'draft'; // Default to draft if invalid
        }

        if (empty($posts_json)) {
            wp_send_json_error(array('message' => __('No posts data provided.', 'wayback-wp-importer')));
        }

        // Decode the JSON data - don't sanitize here as it will break the JSON
        // We'll sanitize individual fields after decoding
        $posts = json_decode(stripslashes($posts_json), true);

        if (empty($posts) || !is_array($posts)) {
            wp_send_json_error(array('message' => __('Invalid posts data.', 'wayback-wp-importer')));
        }

        // Initialize the API and parser classes
        $api = new Wayback_WP_Importer_API();
        $parser = new Wayback_WP_Importer_Parser();

        // Initialize results array
        $results = array(
            'success' => array(),
            'failed' => array(),
            'imported' => 0,
            'total' => count($posts),
            'imported_indexes' => array()
        );

        // Process each post
        foreach ($posts as $index => $post) {
            // Sanitize the wayback URL
            $wayback_url = isset($post['wayback_url']) ? esc_url_raw($post['wayback_url']) : '';

            if (empty($wayback_url)) {
                $results['failed'][] = array(
                    'index' => $index,
                    'error' => __('Missing or invalid Wayback URL', 'wayback-wp-importer')
                );
                continue;
            }

            // Get the archived content
            $archived_content = $api->get_archived_content($wayback_url);

            if (is_wp_error($archived_content)) {
                $results['failed'][] = array(
                    'index' => $index,
                    'url' => $wayback_url,
                    'error' => $archived_content->get_error_message()
                );
                continue;
            }

            // Parse the content
            $parsed_data = $parser->parse_wordpress_content($archived_content);

            if (is_wp_error($parsed_data)) {
                $results['failed'][] = array(
                    'index' => $index,
                    'url' => $wayback_url,
                    'error' => $parsed_data->get_error_message()
                );
                continue;
            }

            // Check for duplicate post after parsing
            if (!empty($parsed_data['title'])) {
                $duplicate = $this->check_for_duplicate_post($parsed_data['title']);
                if ($duplicate) {
                    $results['failed'][] = array(
                        'index' => $index,
                        'url' => $wayback_url,
                        'error' => __('Duplicate post already exists: ', 'wayback-wp-importer') . $duplicate
                    );
                    continue;
                }
            }

            // Add categories to parsed data if provided
            if (!empty($categories)) {
                $parsed_data['categories'] = $categories;
            }

            // Create the post
            $post_id = $this->create_post_from_data($parsed_data, $post_status);

            if (is_wp_error($post_id)) {
                $results['failed'][] = array(
                    'index' => $index,
                    'url' => $wayback_url,
                    'error' => $post_id->get_error_message()
                );
            } else {
                $results['success'][] = array(
                    'index' => $index,
                    'url' => $wayback_url,
                    'post_id' => $post_id,
                    'edit_url' => get_edit_post_link($post_id, 'raw'),
                    'view_url' => get_permalink($post_id)
                );
                $results['imported']++;
                $results['imported_indexes'][] = $index;
            }
        }

        wp_send_json_success($results);
    }

    /**
     * Create a WordPress post from parsed data.
     *
     * @since    1.0.0
     * @param    array     $post_data     The parsed post data.
     * @param    string    $post_status   The post status (publish, draft, etc.).
     * @return   int|WP_Error            The post ID or WP_Error on failure.
     */
    private function create_post_from_data($post_data, $post_status = 'draft')
    {
        // Check for duplicate posts to prevent multiple imports
        $existing_post = $this->check_for_duplicate_post($post_data['title']);
        if ($existing_post) {
            return new WP_Error('duplicate_post', sprintf(__('A post with the title "%s" already exists (ID: %d).', 'wayback-wp-importer'), $post_data['title'], $existing_post));
        }

        // Create post array
        $post_arr = array(
            'post_title'    => wp_strip_all_tags($post_data['title']),
            'post_content'  => $post_data['content'],
            'post_excerpt'  => isset($post_data['excerpt']) ? $post_data['excerpt'] : '',
            'post_status'   => $post_status,
            'post_type'     => 'post',
            'post_author'   => $this->get_author_id(isset($post_data['author']) ? $post_data['author'] : ''),
        );

        // Set post date if available
        if (!empty($post_data['date'])) {
            // Format the date properly for WordPress
            $date_obj = date_create($post_data['date']);
            if ($date_obj) {
                $formatted_date = date_format($date_obj, 'Y-m-d H:i:s');
                $post_arr['post_date'] = $formatted_date;
                $post_arr['post_date_gmt'] = get_gmt_from_date($formatted_date);
            }
        }

        // Insert the post
        $post_id = wp_insert_post($post_arr, true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Set categories if available
        if (!empty($post_data['categories'])) {
            $category_ids = $this->get_category_ids($post_data['categories']);
            wp_set_post_categories($post_id, $category_ids);
        }

        // Set tags if available
        if (!empty($post_data['tags'])) {
            wp_set_post_tags($post_id, $post_data['tags']);
        }

        // Set featured image if available
        if (!empty($post_data['featured_image'])) {
            $this->set_featured_image($post_id, $post_data['featured_image']);
        }
        return $post_id;
    }


    /**
     * Check if a post with the same title already exists.
     *ß
     * @since    1.0.0
     * @param    string    $title    The post title to check.
     * @return   int|false          Post ID if exists, false otherwise.
     */
    private function check_for_duplicate_post($title)
    {
        $args = array(
            'post_type'      => 'post',
            'post_status'    => array('publish', 'draft', 'pending', 'private'),
            'title'          => $title,
            'posts_per_page' => 1,
            'no_found_rows'  => true,
            'fields'         => 'ids'
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            return $query->posts[0];
        }

        return false;
    }

    /**
     * Check if a post with the same URL slug already exists.
     *
     * @since    1.0.3
     * @param    string    $url    The Wayback Machine URL to check.
     * @return   int|false         Post ID if exists, false otherwise.
     */
    private function check_for_duplicate_url($url)
    {
        // Extract the slug from the URL
        $slug = $this->extract_slug_from_url($url);

        if (empty($slug)) {
            return false;
        }

        // Check if any post has this slug
        $args = array(
            'post_type'      => 'post',
            'post_status'    => array('publish', 'draft', 'pending', 'private'),
            'name'           => $slug,  // 'name' is the post slug
            'posts_per_page' => 1,
            'no_found_rows'  => true,
            'fields'         => 'ids'
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            return $query->posts[0];
        }

        return false;
    }

    /**
     * Extract a slug from a Wayback Machine URL.
     *
     * @since    1.0.3
     * @param    string    $url    The Wayback Machine URL.
     * @return   string            The extracted slug or empty string if not found.
     */
    private function extract_slug_from_url($url)
    {
        // Handle case where $url is an array (e.g., first element of an array)
        if (is_array($url)) {
            if (empty($url)) {
                return '';
            }
            // Use the first URL in the array
            $url = reset($url);
        }

        // Ensure we have a string
        if (!is_string($url)) {
            return '';
        }

        // Parse the Wayback URL to get the original URL
        if (!preg_match('#https://web\.archive\.org/web/[0-9]+/(.+)#', $url, $matches)) {
            return '';
        }

        $original_url = $matches[1];

        // Check for query parameter format (?p=123)
        if (preg_match('#[\?&]p=([0-9]+)#', $original_url, $id_matches)) {
            // For query parameter format, we'll use 'post-{ID}' as the slug
            return 'post-' . $id_matches[1];
        }

        // Common patterns for WordPress post URLs
        $patterns = array(
            // Post name: /sample-post/
            '#/([^/]+)/?$#',

            // Date-based (YYYY/MM/DD): /2023/05/15/sample-post/
            '#/[0-9]{4}/[0-9]{2}/[0-9]{2}/([^/]+)/?$#',

            // Category-based: /category/sample-post/
            '#/[^/]+/([^/]+)/?$#',

            // Nested category: /parent-category/child-category/sample-post/
            '#/[^/]+/[^/]+/([^/]+)/?$#',

            // Author-based: /author/username/sample-post/
            '#/author/[^/]+/([^/]+)/?$#'
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $original_url, $slug_matches)) {
                return sanitize_title($slug_matches[1]);
            }
        }

        return '';
    }

    /**
     * AJAX handler for checking if a post with the given title already exists
     *
     * @since    1.0.3
     */
    public function ajax_check_duplicate()
    {
        // Check nonce for security
        check_ajax_referer('wayback_wp_importer_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'wayback-wp-importer')));
        }

        // Check if title is provided
        if (!isset($_POST['title']) || empty($_POST['title'])) {
            wp_send_json_error(array('message' => __('No title provided.', 'wayback-wp-importer')));
        }

        $title = sanitize_text_field($_POST['title']);
        $duplicate_id = $this->check_for_duplicate_post($title);

        wp_send_json_success(array(
            'isDuplicate' => ($duplicate_id !== false),
            'duplicateId' => $duplicate_id,
            'title' => $title
        ));
    }

    /**
     * AJAX handler for exporting multiple posts to CSV.
     *
     * @since    1.0.0
     */
    public function ajax_export_multiple_csv()
    {
        // Check nonce for security
        check_ajax_referer('wayback_wp_importer_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'wayback-wp-importer')));
        }

        // Get the URLs from the request
        $wayback_urls = isset($_POST['wayback_urls']) ? $_POST['wayback_urls'] : array();

        if (empty($wayback_urls) || !is_array($wayback_urls)) {
            wp_send_json_error(array('message' => __('No valid URLs provided.', 'wayback-wp-importer')));
        }

        // Sanitize URLs
        $wayback_urls = array_map('sanitize_text_field', $wayback_urls);

        // Initialize the API class and parser
        $api = new Wayback_WP_Importer_API();
        $parser = new Wayback_WP_Importer_Parser();

        // Prepare CSV data
        $csv_data = array();

        // Add CSV headers
        $csv_data[] = array(
            'Title',
            'Content',
            'Excerpt',
            'Date',
            'Author',
            'Categories',
            'Tags',
            'Featured Image URL',
            'Original URL'
        );

        foreach ($wayback_urls as $url) {
            // Get the archived content
            $archived_content = $api->get_archived_content($url);

            if (is_wp_error($archived_content)) {
                continue;
            }

            // Parse the content
            $parsed_data = $parser->parse_wordpress_content($archived_content);

            if (is_wp_error($parsed_data)) {
                continue;
            }

            // Add post data to CSV
            $csv_data[] = array(
                isset($parsed_data['title']) ? $parsed_data['title'] : '',
                isset($parsed_data['content']) ? $parsed_data['content'] : '',
                isset($parsed_data['excerpt']) ? $parsed_data['excerpt'] : '',
                isset($parsed_data['date']) ? $parsed_data['date'] : '',
                isset($parsed_data['author']) ? $parsed_data['author'] : '',
                isset($parsed_data['categories']) ? implode(', ', $parsed_data['categories']) : '',
                isset($parsed_data['tags']) ? implode(', ', $parsed_data['tags']) : '',
                isset($parsed_data['featured_image']) ? $parsed_data['featured_image'] : '',
                $url
            );
        }

        // Convert array to CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        wp_send_json_success(array('csv' => $csv_content));
    }

    /**
     * Generate CSV content from post data.
     *
     * @param    array    $post_data    The post data.
     * @return   string   The CSV content.
     */
    private function generate_csv($post_data)
    {
        $csv = array();

        // Add CSV headers
        $csv[] = array(
            'Title',
            'Content',
            'Excerpt',
            'Date',
            'Author',
            'Categories',
            'Tags',
            'Featured Image URL',
        );

        // Add post data
        $csv[] = array(
            isset($post_data['title']) ? $post_data['title'] : '',
            isset($post_data['content']) ? $post_data['content'] : '',
            isset($post_data['excerpt']) ? $post_data['excerpt'] : '',
            isset($post_data['date']) ? $post_data['date'] : '',
            isset($post_data['author']) ? $post_data['author'] : '',
            isset($post_data['categories']) ? implode(', ', $post_data['categories']) : '',
            isset($post_data['tags']) ? implode(', ', $post_data['tags']) : '',
            isset($post_data['featured_image']) ? $post_data['featured_image'] : '',
        );

        // Convert array to CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        return $csv_content;
    }

    /**
     * AJAX handler for extracting custom fields from HTML content.
     *
     * @since    1.0.0
     */
    public function ajax_extract_custom_fields()
    {
        check_ajax_referer('wayback_wp_importer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'wayback-wp-importer')));
        }

        // Get the HTML content and custom field keys
        $wayback_url = isset($_POST['wayback_url']) ? sanitize_text_field($_POST['wayback_url']) : '';
        $html_content = isset($_POST['content']) ? $_POST['content'] : '';
        $custom_field_keys = isset($_POST['custom_field_keys']) ? json_decode(stripslashes($_POST['custom_field_keys']), true) : array();

        if (empty($html_content)) {
            wp_send_json_error(array('message' => __('No HTML content provided.', 'wayback-wp-importer')));
            return;
        }

        if (empty($custom_field_keys)) {
            wp_send_json_error(array('message' => __('No custom field keys provided.', 'wayback-wp-importer')));
            return;
        }

        // Initialize the custom fields handler
        $custom_fields_handler = new Wayback_WP_Importer_Custom_Fields();

        // Extract custom fields
        $extracted_data = $custom_fields_handler->extract_custom_fields_from_html($html_content, $custom_field_keys);

        // Return the results
        wp_send_json_success(array(
            'custom_fields' => isset($extracted_data['fields']) ? $extracted_data['fields'] : array(),
            'previews' => isset($extracted_data['previews']) ? $extracted_data['previews'] : array(),
        ));
    }

    /**
     * AJAX handler for extracting content using custom selectors.
     * 
     * Processes requests to extract content from HTML using custom CSS selectors.
     * Supports extracting both text content and specific attributes (href, src, etc.)
     * from matched elements using either inline attribute notation (selector::attr(name))
     * or separate attribute specifications.
     *
     * @since    1.0.0
     */
    public function ajax_extract_selectors()
    {
        check_ajax_referer('wayback_wp_importer_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have sufficient permissions.', 'wayback-wp-importer')]);
            return;
        }
    
        // Always unslash raw POST data from WordPress
        $html_content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
        
        $custom_selectors = isset($_POST['custom_selectors']) ?
            (is_array($_POST['custom_selectors']) ?
                array_map('sanitize_text_field', $_POST['custom_selectors']) :
                json_decode(wp_unslash($_POST['custom_selectors']), true) ?? []) : [];

        $attributes = isset($_POST['attributes']) ?
            (is_array($_POST['attributes']) ?
                array_map('sanitize_text_field', $_POST['attributes']) :
                json_decode(wp_unslash($_POST['attributes']), true) ?? []) : [];

        $special_cases = isset($_POST['special_cases']) ?
            (is_array($_POST['special_cases']) ?
                array_map('sanitize_text_field', $_POST['special_cases']) :
                json_decode(wp_unslash($_POST['special_cases']), true) ?? []) : [];

        // Check if HTML content is empty
        if (empty($html_content)) {
            error_log('Empty HTML content received in ajax_extract_selectors');
            wp_send_json_error(array('message' => __('No HTML content provided', 'wayback-wp-importer')));
            return;
        }

        if (empty($custom_selectors)) {
            wp_send_json_error(array('message' => __('No custom selectors provided.', 'wayback-wp-importer')));
            return;
        }

        // Initialize the custom fields handler
        $custom_fields_handler = new Wayback_WP_Importer_Custom_Fields();

        // Then pass to the extraction method
        $extracted_data = $custom_fields_handler->extract_content_by_selectors(
            $html_content,
            $custom_selectors,
            $attributes,
            $special_cases
        );

       // Return the results without including raw HTML content to prevent double-escaping
        wp_send_json_success(array(
            'fields' => $extracted_data['fields'] ?? [],
            'previews' => $extracted_data['previews'] ?? [],
            'html' => $extracted_data['html'] ?? ''
        ));

        
    }

    /**
     * AJAX handler for getting all taxonomies.
     *
     * @since    1.0.0
     */
    public function ajax_get_taxonomies()
    {
        check_ajax_referer('wayback_wp_importer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'wayback-wp-importer')));
        }

        // Get the Wayback URL and HTML content if provided (for extraction)
        $wayback_url = isset($_POST['wayback_url']) ? sanitize_text_field($_POST['wayback_url']) : '';
        $html_content = isset($_POST['html_content']) ? $_POST['html_content'] : '';
        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post';

        // Get all taxonomies for the specified post type
        $taxonomies = $this->taxonomies->get_all_taxonomies($post_type);

        $extracted_terms = array();

        // If HTML content is provided, try to extract taxonomy terms
        if (!empty($html_content)) {
            $extracted_terms = $this->taxonomies->extract_taxonomy_terms_from_html($html_content);
        }

        wp_send_json_success(array(
            'taxonomies' => $taxonomies,
            'extracted_terms' => $extracted_terms,
            'post_type' => $post_type
        ));
    }
}

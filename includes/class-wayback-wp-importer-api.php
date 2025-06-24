<?php
/**
 * The class responsible for handling Wayback Machine API requests.
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 */

class Wayback_WP_Importer_API {

    /**
     * Scan a page for links that look like blog posts.
     *
     * @since    1.0.0
     * @param    string    $wayback_url    The Wayback Machine URL of the page to scan.
     * @param    array     $options        Optional. Array of options to customize the scan.
     *                                    'post_types' - Array of post types to look for (default: ['post'])
     *                                    'permalink_structures' - Array of permalink structures to match
     *                                    'custom_patterns' - Array of custom regex patterns to match URLs
     * @return   array|WP_Error           Array of blog post links or WP_Error on failure.
     */
    public function scan_page_for_blog_posts($wayback_url, $options = array()) {
        error_log('Scanning page for blog post links: ' . $wayback_url);
        
        // Get the archived content
        $archived_content = $this->get_archived_content($wayback_url);
        
        if (is_wp_error($archived_content)) {
            return $archived_content;
        }
        
        // Create a new DOMDocument
        $dom = new DOMDocument();
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        // Load the HTML
        $dom->loadHTML(mb_convert_encoding($archived_content, 'HTML-ENTITIES', 'UTF-8'));
        
        // Clear errors
        libxml_clear_errors();
        
        // Create a new DOMXPath
        $xpath = new DOMXPath($dom);
        
        // Extract the base URL for resolving relative URLs
        $base_url = '';
        $base_nodes = $xpath->query('//base[@href]');
        if ($base_nodes->length > 0) {
            $base_url = $base_nodes->item(0)->getAttribute('href');
        } else {
            // Extract from the Wayback URL
            preg_match('#^https?://web\.archive\.org/web/[0-9]+/(https?://.+)$#', $wayback_url, $matches);
            if (isset($matches[1])) {
                $original_url = $matches[1];
                $parsed_url = parse_url($original_url);
                $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
            }
        }
        
        // Find all links on the page
        $links = $xpath->query('//a[@href]');
        
        $blog_post_links = array();
        $processed_urls = array(); // To avoid duplicates
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $text = trim($link->textContent);
            
            // Skip empty links or anchors
            if (empty($href) || $href === '#' || strpos($href, 'javascript:') === 0) {
                continue;
            }
            
            // Make the URL absolute if it's relative
            if (strpos($href, 'http') !== 0) {
                if (strpos($href, '/') === 0) {
                    // Absolute path
                    $href = $base_url . $href;
                } else {
                    // Relative path
                    $href = rtrim($base_url, '/') . '/' . $href;
                }
            }
            
            // Convert to Wayback Machine URL if it's not already
            if (strpos($href, 'web.archive.org') === false) {
                // Extract timestamp from the original Wayback URL
                preg_match('#^https?://web\.archive\.org/web/([0-9]+)/#', $wayback_url, $matches);
                if (isset($matches[1])) {
                    $timestamp = $matches[1];
                    $href = 'https://web.archive.org/web/' . $timestamp . '/' . $href;
                }
            }
            
            // Skip if we've already processed this URL
            if (isset($processed_urls[$href])) {
                continue;
            }
            
            // Set default options
            $default_options = array(
                'post_types' => array('post'),
                'permalink_structures' => array(
                    'date' => true,      // /{year}/{month}/{day}/{postname}/
                    'postname' => true,  // /{postname}/
                    'post_id' => true,   // ?p={post_id} or /?p={post_id}
                    'custom' => true,    // /{post_type}/{postname}/
                ),
                'custom_patterns' => array()
            );
            
            // Merge user options with defaults
            $options = wp_parse_args($options, $default_options);
            
            // Check if the URL looks like a blog post
            $is_blog_post = false;
            $detected_post_type = 'post'; // Default post type
            $detected_permalink_structure = 'unknown'; // Default permalink structure
            
            // Parse the URL to get path and query components
            $parsed_url = parse_url($href);
            $path = isset($parsed_url['path']) ? trim($parsed_url['path'], '/') : '';
            $query = isset($parsed_url['query']) ? $parsed_url['query'] : '';
            $path_parts = explode('/', $path);
            
            // Skip navigation and utility links
            $skip_patterns = array(
                '/\/feed\/?$/', // RSS feeds
                '/\/page\/\d+\/?$/', // Pagination
                '/\/wp-\w+\.php/', // WordPress system files
                '/\/wp-admin\/?/', // Admin area
                '/\/wp-content\//', // Content directory
                '/\/wp-includes\//', // Includes directory
                '/\/tag\//', // Tag archives
                // '/\/category\//', // Category archives
                '/\/author\//', // Author archives
                '/\/search\/?/', // Search pages
                '/\/comment-page-\d+\/?/', // Comment pagination
                '/\/(login|register|signup|signin|logout)\/?$/', // Auth pages
            );
            
            foreach ($skip_patterns as $pattern) {
                if (preg_match($pattern, $href)) {
                    continue 2; // Skip to the next link
                }
            }
            
            // Check if link is inside a jet-listing-grid element or other content areas
            $is_in_content_area = false;
            $parent = $link;
            $depth = 0;
            $max_depth = 5; // Increased depth to check more parent levels
            
            while ($parent && $depth < $max_depth) {
                if ($parent->hasAttributes()) {
                    $class_attr = $parent->getAttribute('class');
                    $id_attr = $parent->getAttribute('id');
                    
                    // Check for content area indicators
                    $content_indicators = array('jet-listing-grid', 'elementor-posts', 'elementor-post', 'post', 'article', 'entry', 'content', 'elementor-heading-title', 'elementor-widget-heading');
                    foreach ($content_indicators as $indicator) {
                        if (strpos($class_attr, $indicator) !== false || strpos($id_attr, $indicator) !== false) {
                            $is_in_content_area = true;
                            break 2;
                        }
                    }
                }
                
                $parent = $parent->parentNode;
                $depth++;
            }
            
            // Only skip if it's clearly in a navigation area AND not in a content area
            if (!$is_in_content_area) {
                $parent = $link;
                $depth = 0;
                $max_depth = 3;
                
                while ($parent && $depth < $max_depth) {
                    if ($parent->nodeName === 'nav') {
                        // Only skip if it's explicitly a nav element
                        continue 2; // Skip to the next link
                    }
                    
                    if ($parent->hasAttributes()) {
                        $class_attr = $parent->getAttribute('class');
                        $id_attr = $parent->getAttribute('id');
                        
                        // Reduced list of navigation indicators to be less strict
                        $nav_indicators = array('main-nav', 'main-menu', 'primary-nav', 'primary-menu');
                        foreach ($nav_indicators as $indicator) {
                            if (strpos($class_attr, $indicator) !== false || strpos($id_attr, $indicator) !== false) {
                                continue 3; // Skip to the next link
                            }
                        }
                    }
                    
                    $parent = $parent->parentNode;
                    $depth++;
                }
            }
            
            // Build URL patterns based on selected permalink structures
            $url_patterns = array();
            $permalink_structure_matched = false;
            
            // Date-based permalinks
            if (!empty($options['permalink_structures']['date'])) {
                if (preg_match('#/\d{4}/\d{2}/\d{2}/#', $href)) {
                    $is_blog_post = true;
                    $permalink_structure_matched = true;
                    $detected_permalink_structure = 'date';
                } elseif (preg_match('#/\d{4}/\d{2}/#', $href)) {
                    // This might be a monthly archive, but check if it has another segment after
                    if (count($path_parts) > 3) {
                        $is_blog_post = true;
                        $permalink_structure_matched = true;
                        $detected_permalink_structure = 'date';
                    }
                }
            }
            
            // Post ID permalinks
            if (!$permalink_structure_matched && !empty($options['permalink_structures']['post_id'])) {
                if (preg_match('/\?p=(\d+)/', $query, $matches) || 
                    preg_match('/-p(\d+)\/?$/', $href, $matches) || 
                    preg_match('/\?page_id=(\d+)/', $query, $matches)) {
                    $is_blog_post = true;
                    $permalink_structure_matched = true;
                    $detected_permalink_structure = 'post_id';
                }
            }
            
            // Custom post type permalinks
            if (!$permalink_structure_matched && !empty($options['permalink_structures']['custom']) && !empty($options['post_types'])) {
                foreach ($options['post_types'] as $post_type) {
                    if ($post_type !== 'post' && $post_type !== 'page' && !empty($path_parts)) {
                        // Check if the first path segment matches the post type
                        if ($path_parts[0] === $post_type && count($path_parts) > 1) {
                            $is_blog_post = true;
                            $permalink_structure_matched = true;
                            $detected_post_type = $post_type;
                            $detected_permalink_structure = 'custom';
                            break;
                        }
                    }
                }
            }
            
            // Postname permalinks - most difficult to detect without false positives
            // Only consider if other structures didn't match and we have specific indicators
            if (!$permalink_structure_matched && !empty($options['permalink_structures']['postname'])) {
                // Check for post-like structure: no file extension, not too short, not too many segments
                if (!empty($path) && 
                    !preg_match('/\.(html|php|asp|jsp)$/', $path) && 
                    strlen($path) > 5 && 
                    count($path_parts) <= 3) {
                    
                    // Look for post content indicators in the link or its context
                    $post_context_found = false;
                    
                    // Check if link is in an article element or has article-like parent
                    $parent = $link;
                    $depth = 0;
                    $max_depth = 5; // Increased depth to check more parent levels
                    
                    while ($parent && $depth < $max_depth) {
                        if ($parent->nodeName === 'article') {
                            $post_context_found = true;
                            break;
                        }
                        
                        if ($parent->hasAttributes()) {
                            $class_attr = $parent->getAttribute('class');
                            $id_attr = $parent->getAttribute('id');
                            
                            // Expanded list of content indicators to include Elementor and Jet elements
                            $content_indicators = array('post', 'article', 'entry', 'content', 'jet-listing', 'elementor-post', 'elementor-widget');
                            foreach ($content_indicators as $indicator) {
                                if (strpos($class_attr, $indicator) !== false || strpos($id_attr, $indicator) !== false) {
                                    $post_context_found = true;
                                    break 2;
                                }
                            }
                        }
                        
                        $parent = $parent->parentNode;
                        $depth++;
                    }
                    
                    // Check if the link text looks like a post title (longer than a few words)
                    $title_like = false;
                    if (!empty($text)) {
                        $word_count = str_word_count($text);
                        if ($word_count >= 3) { // Reduced from 5 to 3 to be less strict
                            $title_like = true;
                        }
                    }
                    
                    // Only consider it a post if it has context indicators or a title-like text
                    if ($post_context_found || $title_like) {
                        $is_blog_post = true;
                        $detected_permalink_structure = 'postname';
                    }
                }
            }
            
            // Add any custom patterns provided by the user as a final check
            if (!$is_blog_post && !empty($options['custom_patterns']) && is_array($options['custom_patterns'])) {
                foreach ($options['custom_patterns'] as $pattern) {
                    if (preg_match($pattern, $href)) {
                        $is_blog_post = true;
                        break;
                    }
                }
            }
            
            if ($is_blog_post) {
                // Use the detected post type and permalink structure
                $blog_post_links[] = array(
                    'url' => $href,
                    'title' => $text ?: $href,
                    'post_type' => $detected_post_type,
                    'permalink_structure' => $detected_permalink_structure
                );
                $processed_urls[$href] = true;
                
                // Debug log for development
                error_log(sprintf('Found blog post: %s (Type: %s, Structure: %s)', 
                    $href, $detected_post_type, $detected_permalink_structure));
            }
        }
        
        if (empty($blog_post_links)) {
            return new WP_Error('no_blog_posts_found', __('No blog post links found on the provided URL.', 'wayback-wp-importer'));
        }
        
        return $blog_post_links;
    }

    /**
     * Get the archived content from the Wayback Machine.
     *
     * @since    1.0.0
     * @param    string    $wayback_url    The Wayback Machine URL.
     * @return   string|WP_Error          The archived content or WP_Error.
     */
    public function get_archived_content($wayback_url) {
        error_log('Fetching content from Wayback Machine URL: ' . $wayback_url);
        
        // Validate the URL
        if (!filter_var($wayback_url, FILTER_VALIDATE_URL)) {
            error_log('Invalid URL format: ' . $wayback_url);
            return new WP_Error('invalid_url', 'Invalid URL format.');
        }
        
        // Check if the URL is a Wayback Machine URL
        if (strpos($wayback_url, 'web.archive.org/web/') === false) {
            error_log('Not a Wayback Machine URL: ' . $wayback_url);
            return new WP_Error('invalid_wayback_url', 'Not a Wayback Machine URL.');
        }
        
        // Validate the URL format
        if (!preg_match('#^https?://web\.archive\.org/web/[0-9]+/https?://#', $wayback_url)) {
            error_log('Invalid Wayback Machine URL format: ' . $wayback_url);
            return new WP_Error('invalid_url', __('Invalid Wayback Machine URL format. URL must start with http://web.archive.org/web/ or https://web.archive.org/web/', 'wayback-wp-importer'));
        }
        
        error_log('Fetching archived content from: ' . $wayback_url);
        
        // Extract the timestamp and original URL from the Wayback URL
        preg_match('#^https?://web\.archive\.org/web/([0-9]+)/(https?://.+)$#', $wayback_url, $matches);
        $timestamp = $matches[1] ?? '';
        $original_url = $matches[2] ?? '';
        
        error_log('Wayback timestamp: ' . $timestamp);
        error_log('Original URL: ' . $original_url);
        
        // Set up the request arguments with browser-like headers
        $args = array(
            'timeout' => 30, // Increase timeout for slow responses
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ),
            'sslverify' => false, // Sometimes needed for Wayback Machine
        );
        
        // Get the content from the URL
        $response = wp_remote_get($wayback_url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            error_log('Error fetching content: ' . $response->get_error_message());
            return $response;
        }
        
        // Get the response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Check if the response is successful
        if ($response_code !== 200) {
            error_log('Error fetching content: Response code ' . $response_code);
            
            // If we used the id_ format and it failed, try the original URL
            if (strpos($wayback_url, 'id_/') !== false) {
                error_log('Alternate URL format failed, trying original format');
                $wayback_url = 'https://web.archive.org/web/' . $timestamp . '/' . $original_url;
                $response = wp_remote_get($wayback_url, $args);
                
                if (is_wp_error($response)) {
                    error_log('Error fetching content with original format: ' . $response->get_error_message());
                    return $response;
                }
                
                $response_code = wp_remote_retrieve_response_code($response);
                if ($response_code !== 200) {
                    error_log('Error fetching content with original format: Response code ' . $response_code);
                    return new WP_Error('http_error', sprintf(__('Failed to fetch content. HTTP response code: %d', 'wayback-wp-importer'), $response_code));
                }
            } else {
                return new WP_Error('http_error', sprintf(__('Failed to fetch content. HTTP response code: %d', 'wayback-wp-importer'), $response_code));
            }
        }
        
        // Get the body
        $body = wp_remote_retrieve_body($response);
        
        // Check if the body is empty
        if (empty($body)) {
            error_log('Error fetching content: Empty response body');
            return new WP_Error('empty_response', 'Error fetching content: Empty response body');
        }
        
        // Check if the body contains common error messages from Wayback Machine
        if (strpos($body, 'The Wayback Machine has not archived that URL') !== false) {
            error_log('Wayback Machine has not archived that URL');
            return new WP_Error('not_archived', 'The Wayback Machine has not archived that URL.');
        }
        
        if (strpos($body, 'Hrm.') !== false && strpos($body, 'Wayback Machine doesn\'t have that page archived') !== false) {
            error_log('Wayback Machine doesn\'t have that page archived');
            return new WP_Error('not_archived', 'The Wayback Machine doesn\'t have that page archived.');
        }
        
        error_log('Successfully fetched content from Wayback Machine');
        
        return $body;
    }

    /**
     * Check if a URL is a valid Wayback Machine URL.
     *
     * @since    1.0.0
     * @param    string    $url    The URL to check.
     * @return   bool              Whether the URL is a valid Wayback Machine URL.
     */
    private function is_valid_wayback_url($url) {
        // Basic URL validation
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Check if it's a Wayback Machine URL
        $parsed_url = parse_url($url);
        
        if (!isset($parsed_url['host'])) {
            return false;
        }
        
        $host = $parsed_url['host'];
        
        // Check if the host is web.archive.org
        if ($host !== 'web.archive.org') {
            return false;
        }
        
        // Check if the path starts with /web/
        if (!isset($parsed_url['path']) || strpos($parsed_url['path'], '/web/') !== 0) {
            return false;
        }
        
        return true;
    }

    /**
     * Get available snapshots for a URL from the Wayback Machine CDX API.
     *
     * @since    1.0.0
     * @param    string    $url       The original URL to check for snapshots.
     * @param    int       $limit     Maximum number of snapshots to return.
     * @return   array|WP_Error       Array of snapshots or WP_Error on failure.
     */
    public function get_snapshots($url, $limit = 10) {
        // Validate the URL
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', __('Invalid URL for snapshot search.', 'wayback-wp-importer'));
        }

        // Build the CDX API URL
        $cdx_url = add_query_arg(
            array(
                'url' => $url,
                'output' => 'json',
                'limit' => $limit,
                'filter' => 'statuscode:200',
            ),
            'https://web.archive.org/cdx/search/cdx'
        );

        // Make the request to the CDX API
        $response = wp_remote_get($cdx_url);

        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }

        // Check the response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error(
                'cdx_error',
                sprintf(
                    __('Failed to retrieve snapshots from Wayback Machine. Response code: %d', 'wayback-wp-importer'),
                    $response_code
                )
            );
        }

        // Get the body
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return new WP_Error('empty_response', __('Empty response from Wayback Machine CDX API.', 'wayback-wp-importer'));
        }

        // Parse the JSON response
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Failed to parse JSON response from Wayback Machine CDX API.', 'wayback-wp-importer'));
        }

        // Check if we have any results
        if (count($data) <= 1) {
            return new WP_Error('no_snapshots', __('No snapshots found for the provided URL.', 'wayback-wp-importer'));
        }

        // The first row contains the field names
        $fields = $data[0];
        
        // Remove the field names row
        array_shift($data);
        
        // Format the results
        $snapshots = array();
        foreach ($data as $row) {
            $snapshot = array();
            foreach ($fields as $i => $field) {
                $snapshot[$field] = $row[$i];
            }
            
            // Add the Wayback Machine URL
            $timestamp = $snapshot['timestamp'];
            $original_url = $snapshot['original'];
            $snapshot['wayback_url'] = "https://web.archive.org/web/{$timestamp}/{$original_url}";
            
            $snapshots[] = $snapshot;
        }

        return $snapshots;
    }

    /**
     * Fix relative URLs in HTML content.
     *
     * @since    1.0.0
     * @param    string    $html           The HTML content.
     * @param    string    $wayback_url    The Wayback Machine URL.
     * @return   string                    The HTML content with fixed URLs.
     */
    public function fix_relative_urls($html, $wayback_url) {
        // Parse the Wayback URL to get the timestamp and original URL
        if (!preg_match('#https://web\.archive\.org/web/([0-9]+)/(.+)#', $wayback_url, $matches)) {
            return $html;
        }
        
        $timestamp = $matches[1];
        $original_url = $matches[2];
        
        // Parse the original URL to get the base URL
        $parsed_url = parse_url($original_url);
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        
        // Replace relative URLs with absolute URLs
        $html = preg_replace_callback(
            '/(href|src)=["\'](?!https?:\/\/|\/\/|#|mailto:|tel:)([^"\'\/]+)["\']/i',
            function($matches) use ($base_url, $timestamp) {
                $attr = $matches[1];
                $url = $matches[2];
                
                // If the URL starts with a slash, it's relative to the root
                if (substr($url, 0, 1) === '/') {
                    $absolute_url = $base_url . $url;
                } else {
                    // Otherwise, it's relative to the current path
                    $absolute_url = $base_url . '/' . $url;
                }
                
                // Convert to Wayback Machine URL
                $wayback_url = "https://web.archive.org/web/{$timestamp}/{$absolute_url}";
                
                return "{$attr}=\"{$wayback_url}\"";
            },
            $html
        );
        
        return $html;
    }

    /**
     * Extract WordPress post URLs from a website homepage or category page.
     *
     * @since    1.0.0
     * @param    string    $wayback_url    The Wayback Machine URL of the homepage or category.
     * @param    int       $post_limit     Maximum number of posts to extract.
     * @param    int       $crawl_depth    How many levels deep to crawl for posts.
     * @param    int       $offset         Number of posts to skip (for pagination).
     * @return   array|WP_Error           Array of post URLs or WP_Error on failure.
     */
    public function extract_post_urls($wayback_url, $post_limit = 10, $crawl_depth = 2, $offset = 0) {
        // Validate the URL
        if (!$this->is_valid_wayback_url($wayback_url)) {
            return new WP_Error('invalid_url', __('Invalid Wayback Machine URL. Please use a URL from web.archive.org.', 'wayback-wp-importer'));
        }
        
        // Get the HTML content
        $html = $this->get_archived_content($wayback_url);
        if (is_wp_error($html)) {
            return $html;
        }
        
        // Parse the Wayback URL to get the timestamp and original URL
        if (!preg_match('#https://web\.archive\.org/web/([0-9]+)/(.+)#', $wayback_url, $matches)) {
            return new WP_Error('invalid_wayback_url', __('Could not parse Wayback Machine URL.', 'wayback-wp-importer'));
        }
        
        $timestamp = $matches[1];
        $original_url = $matches[2];
        
        // Parse the original URL to get the base URL
        $parsed_url = parse_url($original_url);
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        
        // Create a DOMDocument to parse the HTML
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        
        // Create a DOMXPath object to query the DOM
        $xpath = new DOMXPath($dom);
        
        // Array to store found post URLs
        $post_urls = array();
        $processed_urls = array();
        
        // Queue for BFS crawling
        $queue = array(array('url' => $wayback_url, 'depth' => 0));
        
        while (!empty($queue) && count($post_urls) < $post_limit) {
            // Dequeue a URL
            $current = array_shift($queue);
            $current_url = $current['url'];
            $current_depth = $current['depth'];
            
            // Skip if we've already processed this URL
            if (isset($processed_urls[$current_url])) {
                continue;
            }
            
            // Mark as processed
            $processed_urls[$current_url] = true;
            
            // Skip if we've reached the maximum depth
            if ($current_depth > $crawl_depth) {
                continue;
            }
            
            // Get the HTML content if not the initial URL
            if ($current_url !== $wayback_url) {
                $html = $this->get_archived_content($current_url);
                if (is_wp_error($html)) {
                    continue;
                }
                
                // Create a new DOMDocument for this page
                $dom = new DOMDocument();
                @$dom->loadHTML($html);
                $xpath = new DOMXPath($dom);
            }
            
            // Check if this is a WordPress post page
            $is_post = $this->is_wordpress_post_page($xpath);
            
            if ($is_post) {
                // Add to post URLs if it's a post
                $post_urls[] = $current_url;
                
                // If we've reached the limit, break
                if (count($post_urls) >= $post_limit) {
                    break;
                }
            } else if ($current_depth < $crawl_depth) {
                // If not a post and we haven't reached max depth, find links to follow
                // First, try to find links that are likely to be posts based on their context
                $post_link_patterns = array(
                    // Links inside elements that typically contain post links
                    '//div[contains(@class, "post")]//a[@href]',
                    '//article//a[@href]',
                    '//h2[contains(@class, "entry-title")]//a[@href]',
                    '//h2[contains(@class, "post-title")]//a[@href]',
                    '//div[contains(@class, "entry")]//a[@href]',
                    '//div[contains(@class, "blog")]//a[@href]',
                    '//div[contains(@class, "archive")]//a[@href]',
                    '//div[contains(@class, "content")]//a[@href]',
                    '//ul[contains(@class, "post")]//a[@href]',
                    '//section[contains(@class, "posts")]//a[@href]',
                    '//div[contains(@class, "posts")]//a[@href]',
                    '//div[contains(@class, "main")]//a[@href]',
                    // Fallback to all links if needed
                    '//a[@href]'
                );
                
                $found_links = false;
                
                // Try each pattern until we find some links
                foreach ($post_link_patterns as $pattern) {
                    $links = $xpath->query($pattern);
                    
                    if ($links->length > 0) {
                        $found_links = true;
                        
                        foreach ($links as $link) {
                            $href = $link->getAttribute('href');
                            $link_text = trim($link->textContent);
                            
                            // Skip empty, fragment, or non-HTTP links
                            if (empty($href) || $href[0] === '#' || strpos($href, 'javascript:') === 0) {
                                continue;
                            }
                            
                            // Skip links that are likely navigation, not posts
                            $skip_keywords = array('login', 'register', 'sign in', 'signup', 'contact', 'about', 'page', 'next', 'previous', 'category', 'tag');
                            $is_navigation = false;
                            foreach ($skip_keywords as $keyword) {
                                if (stripos($link_text, $keyword) !== false || stripos($href, $keyword) !== false) {
                                    $is_navigation = true;
                                    break;
                                }
                            }
                            
                            if ($is_navigation) {
                                continue;
                            }
                            
                            // Convert to absolute URL if relative
                            if (strpos($href, 'http') !== 0) {
                                if ($href[0] === '/') {
                                    $href = $base_url . $href;
                                } else {
                                    $href = $base_url . '/' . $href;
                                }
                            }
                            
                            // Skip if not from the same domain
                            if (strpos($href, $base_url) !== 0) {
                                continue;
                            }
                            
                            // Check if URL looks like a post URL
                            $post_url_patterns = array(
                                '/\d{4}\/\d{2}\/\d{2}/', // Date pattern like /2020/01/01/
                                '/\d{4}\/\d{2}/',       // Year/month pattern like /2020/01/
                                '/post/',                // Contains 'post' in URL
                                '/article/',             // Contains 'article' in URL
                                '/blog/',                // Contains 'blog' in URL
                                '/news/',                // Contains 'news' in URL
                                '/-p\d+/',               // WordPress post ID pattern
                                '/p=\d+/'                // WordPress query string post ID
                            );
                            
                            $is_post_url = false;
                            foreach ($post_url_patterns as $pattern) {
                                if (preg_match($pattern, $href)) {
                                    $is_post_url = true;
                                    break;
                                }
                            }
                            
                            // Convert to Wayback Machine URL
                            $wayback_href = "https://web.archive.org/web/{$timestamp}/{$href}";
                            
                            // If it looks like a post URL, prioritize it
                            if ($is_post_url) {
                                // Add directly to post URLs if it looks like a post URL
                                if (!isset($processed_urls[$wayback_href])) {
                                    $processed_urls[$wayback_href] = true;
                                    $post_urls[] = $wayback_href;
                                    
                                    // If we've reached the limit, break out of both loops
                                    if (count($post_urls) >= $post_limit) {
                                        break 2; // Break out of both foreach loops
                                    }
                                }
                            } else {
                                // Otherwise, add to queue for further crawling
                                if (!isset($processed_urls[$wayback_href])) {
                                    $queue[] = array('url' => $wayback_href, 'depth' => $current_depth + 1);
                                }
                            }
                        }
                        
                        // If we found links with this pattern, no need to try others
                        if ($found_links && count($links) > 0) {
                            break;
                        }
                    }
                }
            }
        }
        
        if (empty($post_urls)) {
            return new WP_Error('no_posts_found', __('No WordPress posts found on the provided URL.', 'wayback-wp-importer'));
        }
        
        // Apply offset if specified
        if ($offset > 0) {
            $post_urls = array_slice($post_urls, $offset, $post_limit);
            
            // If after applying offset we have no posts, return an error
            if (empty($post_urls)) {
                return new WP_Error('no_posts_after_offset', __('No posts found after applying offset.', 'wayback-wp-importer'));
            }
        }
        
        return $post_urls;
    }

    /**
     * Check if a page is a WordPress post.
     *
     * @since    1.0.0
     * @param    DOMXPath  $xpath  The XPath object for the page.
     * @return   bool             Whether the page is a WordPress post.
     */
    private function is_wordpress_post_page($xpath) {
        // Check for common WordPress post indicators
        $indicators = array(
            '//body[contains(@class, "single-post")]',
            '//article[contains(@class, "post")]',
            '//div[contains(@class, "post-")]',
            '//meta[@property="og:type" and @content="article"]',
            '//link[@rel="canonical" and contains(@href, "/20")]', // Most posts have year in URL
            '//div[@class="entry-content"]',
            '//div[@class="post-content"]',
            '//div[@id="content" and //div[contains(@class, "post")]]',
            // Additional indicators for various WordPress themes
            '//h1[@class="entry-title"]',
            '//h2[@class="entry-title"]',
            '//header[contains(@class, "entry-header")]',
            '//div[contains(@class, "blog-post")]',
            '//div[contains(@class, "blog-entry")]',
            '//div[contains(@class, "article")]',
            '//time[contains(@class, "published")]',
            '//span[contains(@class, "posted-on")]'
        );
        
        foreach ($indicators as $indicator) {
            $nodes = $xpath->query($indicator);
            if ($nodes->length > 0) {
                return true;
            }
        }
        
        // Check URL patterns that typically indicate a post
        $url_patterns = array(
            '/\d{4}\/\d{2}\/\d{2}/', // Date pattern like /2020/01/01/
            '/\d{4}\/\d{2}/',       // Year/month pattern like /2020/01/
            '/post/',                // Contains 'post' in URL
            '/article/',             // Contains 'article' in URL
            '/blog/',                // Contains 'blog' in URL
            '/news/',                // Contains 'news' in URL
            '/-p\d+/',               // WordPress post ID pattern
            '/p=\d+/'                // WordPress query string post ID
        );
        
        // Get the current URL from canonical or og:url meta tags
        $url_nodes = $xpath->query('//link[@rel="canonical"]/@href | //meta[@property="og:url"]/@content');
        if ($url_nodes->length > 0) {
            $url = $url_nodes->item(0)->nodeValue;
            
            foreach ($url_patterns as $pattern) {
                if (preg_match($pattern, $url)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Crawl a category page for WordPress posts.
     *
     * @since    1.0.0
     * @param    string    $wayback_url    The Wayback Machine URL of the category page.
     * @param    int       $post_limit     Maximum number of posts to extract.
     * @param    int       $offset         Number of posts to skip (for pagination).
     * @return   array|WP_Error           Array of post URLs or WP_Error on failure.
     */
    public function crawl_category($wayback_url, $post_limit = 10, $offset = 0) {
        // This is similar to extract_post_urls but optimized for category pages
        return $this->extract_post_urls($wayback_url, $post_limit, 1, $offset); // Use depth of 1 for categories
    }

    /**
     * Crawl an entire WordPress site for posts.
     *
     * @since    1.0.0
     * @param    string    $wayback_url    The Wayback Machine URL of the homepage.
     * @param    int       $post_limit     Maximum number of posts to extract.
     * @param    int       $crawl_depth    How many levels deep to crawl for posts.
     * @param    int       $offset         Number of posts to skip (for pagination).
     * @return   array|WP_Error           Array of post URLs or WP_Error on failure.
     */
    public function crawl_website($wayback_url, $post_limit = 10, $crawl_depth = 2, $offset = 0) {
        return $this->extract_post_urls($wayback_url, $post_limit, $crawl_depth, $offset);
    }
}

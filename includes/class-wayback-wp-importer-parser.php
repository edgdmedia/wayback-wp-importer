<?php
/**
 * The class responsible for parsing WordPress content from HTML.
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 */

class Wayback_WP_Importer_Parser {

    /**
     * Parse the WordPress content from the HTML.
     *
     * @since    1.0.0
     * @param    string    $html    The HTML content.
     * @return   array              The parsed content.
     */
    public function parse_wordpress_content($html) {
        error_log('Parsing WordPress content from HTML');
        
        // Check if HTML is empty
        if (empty($html)) {
            error_log('Empty HTML content provided to parser');
            return new WP_Error('empty_html', 'Empty HTML content provided.');
        }
        
        // Create a new DOMDocument
        $doc = new DOMDocument();
        
        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);
        
        // Set encoding to handle special characters
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        
        // Load the HTML
        $doc->loadHTML($html);
        
        // Clear errors
        libxml_clear_errors();
        
        // Create a new DOMXPath
        $xpath = new DOMXPath($doc);
        
        error_log('HTML document loaded successfully, starting extraction');

        // Initialize the data array
        $data = array(
            'title' => '',
            'content' => '',
            'excerpt' => '',
            'date' => '',
            'author' => '',
            'categories' => array(),
            'tags' => array(),
            'featured_image' => '',
            'comments' => array(),
            'custom_fields' => array(),
        );

        // Extract the title
        $data['title'] = $this->extract_title($xpath);
        error_log('Extracted title: ' . $data['title']);
        
        // Extract the content
        $data['content'] = $this->extract_content($xpath);
        
        // Extract the excerpt
        $data['excerpt'] = $this->extract_excerpt($xpath);
        
        // Extract the date
        $data['date'] = $this->extract_date($xpath);
        
        // Extract the author
        $data['author'] = $this->extract_author($xpath);
        
        // Extract the categories
        $data['categories'] = $this->extract_categories($xpath);
        
        // Extract the tags
        $data['tags'] = $this->extract_tags($xpath);
        
        // Extract the featured image
        $data['featured_image'] = $this->extract_featured_image($xpath, $doc);
        
        // Extract the comments
        $data['comments'] = $this->extract_comments($xpath);
    
        // Extract custom fields if any field keys are provided
        $custom_field_keys = isset($_POST['custom_field_keys']) ? $_POST['custom_field_keys'] : array();
        if (!empty($custom_field_keys) && is_array($custom_field_keys)) {
            $data['custom_fields'] = $this->extract_custom_fields($xpath, $custom_field_keys);
        }

        return $data;
    }

    /**
     * Extract the title from the HTML.
     *
     * @since    1.0.0
     * @param    DOMXPath    $xpath    The DOMXPath object.
     * @return   string                The title.
     */
    private function extract_title($xpath) {
        // Try different selectors for the title
        $title_selectors = array(
            '//h1[contains(@class, "entry-title")]',
            '//h1[contains(@class, "post-title")]',
            '//h1[@class="title"]',
            '//header//h1',
            '//article//h1',
            '//h1',
        );

        foreach ($title_selectors as $selector) {
            $title_nodes = $xpath->query($selector);
            if ($title_nodes->length > 0) {
                return trim($title_nodes->item(0)->textContent);
            }
        }

        // If we couldn't find the title, try the <title> tag
        $title_tag = $xpath->query('//title');
        if ($title_tag->length > 0) {
            $title = trim($title_tag->item(0)->textContent);
            
            // Remove site name from title if present
            $title_parts = explode(' - ', $title);
            $title_parts = explode(' | ', $title_parts[0]);
            
            return $title_parts[0];
        }

        return '';
    }

    /**
     * Extract the content from the HTML.
     *
     * @since    1.0.0
     * @param    DOMXPath    $xpath    The DOMXPath object.
     * @return   string                The content.
     */
    private function extract_content($xpath) {
        error_log('Extracting content from HTML');
        
        // Try different selectors for the content
        $content_selectors = array(
            // CheckClimate.africa specific selectors - most specific first
            '//div[contains(@class, "post-content")]',
            '//article[contains(@class, "post")]//div[contains(@class, "entry-content")]',
            '//div[@class="entry-content"]',
            '//div[@id="post-content"]',
            '//div[contains(@class, "post-wrapper")]//div[contains(@class, "content")]',
            
            // Elementor specific selectors for post content (not header/logo)
            '//div[contains(@class, "elementor-widget-theme-post-content")]',
            '//div[contains(@class, "elementor-widget-text-editor")]',
            '//div[contains(@class, "elementor-text-editor")]',
            '//div[contains(@class, "elementor-post__content")]',
            
            // Exclude logo and header areas
            '//main//div[contains(@class, "elementor-widget-container") and not(contains(., "CHECK-CLIMATE-200-x-40-px"))]',
            '//div[contains(@class, "elementor-section-wrap") and not(contains(., "CHECK-CLIMATE-200-x-40-px"))]',
            
            // General content selectors
            '//div[@class="post-full"]',
            '//div[contains(@class, "post-content")]',
            '//div[contains(@class, "entry") and not(contains(@class, "header"))]',
            
            // Modern WordPress themes and blocks
            '//div[contains(@class, "wp-block-post-content")]',
            '//div[contains(@class, "entry-content")]',
            '//div[contains(@class, "post-content")]',
            '//div[contains(@class, "content-area")]',
            '//div[contains(@class, "site-content")]',
            '//div[contains(@class, "post-inner")]',
            '//div[contains(@class, "article-content")]',
            '//div[contains(@class, "single-content")]',
            '//div[contains(@class, "content-container")]',
            
            // Common content containers
            '//div[@class="content"]',
            '//article//div[contains(@class, "content")]',
            '//div[@id="content"]',
            '//div[@class="post"]//div[contains(@class, "content")]',
            
            // Fallbacks for various layouts
            '//article',
            '//div[contains(@class, "post")]',
            '//main',
            '//div[@role="main"]',
            '//div[contains(@class, "main")]',
            '//div[contains(@class, "single")]',
            '//div[contains(@class, "article")]',
        );
        
        error_log('Trying ' . count($content_selectors) . ' different content selectors');

        foreach ($content_selectors as $selector) {
            error_log('Trying selector: ' . $selector);
            $content_nodes = $xpath->query($selector);
            if ($content_nodes->length > 0) {
                error_log('Found content with selector: ' . $selector . ' (nodes: ' . $content_nodes->length . ')');
                $content_node = $content_nodes->item(0);
                
                // Get the HTML of the content node
                $content = $this->get_inner_html($content_node);
                
                // Clean up the content
                $content = $this->clean_content($content);
                
                error_log('Content extracted successfully. Length: ' . strlen($content));
                return $content;
            }
        }
        
        // Before giving up on specific selectors, try a more aggressive approach with partial class matches
        error_log('Trying more generic content selectors');
        $generic_selectors = array(
            '//div[contains(@class, "content")]',
            '//section[contains(@class, "content")]',
            '//div[contains(@class, "post")]',
            '//div[contains(@class, "article")]',
            '//div[contains(@class, "blog")]',
            '//div[contains(@class, "main")]',
        );
        
        foreach ($generic_selectors as $selector) {
            error_log('Trying generic selector: ' . $selector);
            $content_nodes = $xpath->query($selector);
            if ($content_nodes->length > 0) {
                error_log('Found content with generic selector: ' . $selector . ' (nodes: ' . $content_nodes->length . ')');
                // Take the largest content node by text length
                $best_node = null;
                $best_length = 0;
                
                for ($i = 0; $i < $content_nodes->length; $i++) {
                    $node = $content_nodes->item($i);
                    $text = $node->textContent;
                    $length = strlen($text);
                    
                    if ($length > $best_length && $length > 200) { // Minimum content length threshold
                        $best_node = $node;
                        $best_length = $length;
                    }
                }
                
                if ($best_node) {
                    $content = $this->get_inner_html($best_node);
                    $content = $this->clean_content($content);
                    error_log('Content extracted with generic selector. Length: ' . strlen($content));
                    return $content;
                }
            }
        }
        
        // If we couldn't find the content with specific selectors, try to get the body content
        // but exclude header, footer, sidebar, and navigation elements
        error_log('Could not find content with specific selectors, trying body content');
        $body_nodes = $xpath->query('//body');
        
        if ($body_nodes->length > 0) {
            $body_node = $body_nodes->item(0);
            $body_html = $this->get_inner_html($body_node);
            
            // Create a new DOMDocument to manipulate the body content
            $body_dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $body_dom->loadHTML(mb_convert_encoding($body_html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();
            
            $body_xpath = new DOMXPath($body_dom);
            
            // Remove elements that are likely not part of the content
            $remove_selectors = array(
                '//header',
                '//footer',
                '//nav',
                '//aside',
                '//div[contains(@class, "sidebar")]',
                '//div[contains(@class, "menu")]',
                '//div[contains(@class, "navigation")]',
                '//div[contains(@class, "comment")]',
                '//div[contains(@class, "widget")]',
                '//div[contains(@id, "sidebar")]',
                '//div[contains(@id, "menu")]',
                '//div[contains(@id, "nav")]',
                '//div[contains(@id, "header")]',
                '//div[contains(@id, "footer")]',
            );
            
            foreach ($remove_selectors as $selector) {
                $nodes_to_remove = $body_xpath->query($selector);
                foreach ($nodes_to_remove as $node) {
                    if ($node->parentNode) {
                        $node->parentNode->removeChild($node);
                    }
                }
            }
            
            // Get the cleaned body content
            $content = $body_dom->saveHTML();
            $content = $this->clean_content($content);
            
            error_log('Extracted content from body after cleaning. Length: ' . strlen($content));
            return $content;
        }
        
        error_log('Could not extract any content');
        return '';
    }

    /**
     * Extract the excerpt from the HTML.
     *
     * @since    1.0.0
     * @param    DOMXPath    $xpath    The DOMXPath object.
     * @return   string                The excerpt.
     */
    private function extract_excerpt($xpath) {
        // Try different selectors for the excerpt
        $excerpt_selectors = array(
            '//div[contains(@class, "entry-summary")]',
            '//div[contains(@class, "excerpt")]',
            '//div[@class="summary"]',
            '//meta[@name="description"]/@content',
        );

        foreach ($excerpt_selectors as $selector) {
            $excerpt_nodes = $xpath->query($selector);
            if ($excerpt_nodes->length > 0) {
                return trim($excerpt_nodes->item(0)->textContent);
            }
        }

        // If we couldn't find an excerpt, generate one from the content
        $content = $this->extract_content($xpath);
        if (!empty($content)) {
            $excerpt = strip_tags($content);
            $excerpt = preg_replace('/\s+/', ' ', $excerpt);
            $excerpt = substr($excerpt, 0, 160);
            $excerpt = rtrim($excerpt, '.!?,;:-') . '...';
            
            return $excerpt;
        }

        return '';
    }

    /**
     * Extract the date from the HTML.
     *
     * @since    1.0.0
     * @param    DOMXPath    $xpath    The DOMXPath object.
     * @return   string                The date.
     */
    private function extract_date($xpath) {
        // Try different selectors for the date
        $date_selectors = array(
            '//time[@class="entry-date"]/@datetime',
            '//time[@class="published"]/@datetime',
            '//meta[@property="article:published_time"]/@content',
            '//span[contains(@class, "posted-on")]/time/@datetime',
            '//span[contains(@class, "date")]',
            '//p[contains(@class, "date")]',
            '//div[contains(@class, "date")]',
        );

        foreach ($date_selectors as $selector) {
            $date_nodes = $xpath->query($selector);
            if ($date_nodes->length > 0) {
                $date = trim($date_nodes->item(0)->textContent);
                
                // Try to parse the date
                $timestamp = strtotime($date);
                if ($timestamp) {
                    return date('Y-m-d H:i:s', $timestamp);
                }
                
                return $date;
            }
        }

        // If we couldn't find a date, use the current date
        return current_time('mysql');
    }

    /**
     * Extract the author from the HTML.
     *
     * @since    1.0.0
     * @param    DOMXPath    $xpath    The DOMXPath object.
     * @return   string                The author.
     */
    private function extract_author($xpath) {
        // Try different selectors for the author
        $author_selectors = array(
            '//span[contains(@class, "author")]/a',
            '//span[contains(@class, "byline")]/a',
            '//a[contains(@class, "author")]',
            '//a[contains(@rel, "author")]',
            '//meta[@name="author"]/@content',
            '//span[contains(@class, "author")]',
            '//span[contains(@class, "byline")]',
        );

        foreach ($author_selectors as $selector) {
            $author_nodes = $xpath->query($selector);
            if ($author_nodes->length > 0) {
                return trim($author_nodes->item(0)->textContent);
            }
        }

        return '';
    }

    /**
     * Extract the categories from the HTML.
     *
     * @since    1.0.0
     * @param    DOMXPath    $xpath    The DOMXPath object.
     * @return   array                 The categories.
     */
    private function extract_categories($xpath) {
        // Try different selectors for the categories
        $category_selectors = array(
            '//span[contains(@class, "cat-links")]/a',
            '//div[contains(@class, "categories")]/a',
            '//a[contains(@rel, "category")]',
            '//a[contains(@class, "category")]',
            // JetEngine and other page builders
            '//div[contains(@class, "jet-listing")]//div[contains(@class, "category")]//a',
            '//div[contains(@class, "jet-listing jet-listing-dynamic-terms")]//a',
            '//div[contains(@class, "jet-listing")]//div[contains(@class, "cat")]//a',
            '//div[contains(@class, "elementor")]//div[contains(@class, "category")]//a',
            '//div[contains(@class, "elementor")]//div[contains(@class, "cat")]//a',
            // Data attributes often used by page builders
            '//*[@data-taxonomy="category"]/a',
            '//*[@data-element_type="category"]/a',
            '//*[contains(@class, "term-list")][contains(@class, "categories")]//a',
            // Generic but potentially useful selectors
            '//div[contains(text(), "Categories:")]//a',
            '//div[contains(text(), "Category:")]//a',
            '//span[contains(text(), "Categories:")]//a',
            '//span[contains(text(), "Category:")]//a',
        );

        $categories = array();

        foreach ($category_selectors as $selector) {
            $category_nodes = $xpath->query($selector);
            if ($category_nodes->length > 0) {
                foreach ($category_nodes as $category_node) {
                    $category = trim($category_node->textContent);
                    if (!empty($category) && !in_array($category, $categories)) {
                        $categories[] = $category;
                    }
                }
                
                if (!empty($categories)) {
                    return $categories;
                }
            }
        }
        
        // Try to find categories in meta tags (sometimes used for SEO)
        $meta_selectors = array(
            '//meta[@property="article:section"]/@content',
            '//meta[@name="categories"]/@content',
        );
        
        foreach ($meta_selectors as $selector) {
            $meta_nodes = $xpath->query($selector);
            if ($meta_nodes->length > 0) {
                foreach ($meta_nodes as $meta_node) {
                    $meta_content = trim($meta_node->textContent);
                    if (!empty($meta_content)) {
                        // Split by commas if multiple categories are in one meta tag
                        $meta_categories = explode(',', $meta_content);
                        foreach ($meta_categories as $category) {
                            $category = trim($category);
                            if (!empty($category) && !in_array($category, $categories)) {
                                $categories[] = $category;
                            }
                        }
                    }
                }
                
                if (!empty($categories)) {
                    return $categories;
                }
            }
        }

        return $categories;
    }

    /**
     * Extract the tags from the HTML.
     *
     * @since    1.0.0
     * @param    DOMXPath    $xpath    The DOMXPath object.
     * @return   array                 The tags.
     */
    private function extract_tags($xpath) {
        error_log('Extracting tags from HTML');
        
        // Try different selectors for the tags
        $tag_selectors = array(
            // JetEngine specific selectors - prioritized for checkclimate.africa
            '//div[contains(@class, "jet-listing-dynamic-terms")]/a[contains(@class, "jet-listing-dynamic-terms__link")]',
            '//div[contains(@class, "jet-listing")]/a[contains(@href, "/tag/")]',
            
            // Standard WordPress tag selectors
            '//span[contains(@class, "tags-links")]/a',
            '//div[contains(@class, "tags")]/a',
            '//a[contains(@rel, "tag")]',
            '//a[contains(@class, "tag")]',
            '//a[contains(@href, "/tag/")]',
            
            // JetEngine and other page builders
            '//div[contains(@class, "jet-listing")]//div[contains(@class, "tags")]//a',
            '//div[contains(@class, "jet-listing")]//div[contains(@class, "tag")]//a',
            '//div[contains(@class, "elementor")]//div[contains(@class, "tags")]//a',
            '//div[contains(@class, "elementor")]//div[contains(@class, "tag")]//a',
            
            // Data attributes often used by page builders
            '//*[@data-taxonomy="post_tag"]/a',
            '//*[@data-element_type="tag"]/a',
            '//*[contains(@class, "term-list")][contains(@class, "tags")]//a',
            
            // Generic but potentially useful selectors
            '//div[contains(text(), "Tags:")]/following-sibling::*//a',
            '//div[contains(text(), "Tags:")]//a',
            '//span[contains(text(), "Tags:")]/following-sibling::*//a',
            '//span[contains(text(), "Tags:")]//a',
            
            // Hash tags are often used for tags
            '//a[starts-with(text(), "#")]',
        );

        $tags = array();

        foreach ($tag_selectors as $selector) {
            $tag_nodes = $xpath->query($selector);
            if ($tag_nodes->length > 0) {
                foreach ($tag_nodes as $tag_node) {
                    $tag = trim($tag_node->textContent);
                    
                    // Check if we should extract from href instead (for JetEngine and similar structures)
                    if (empty($tag) || $tag === '#') {
                        // Try to get tag from href attribute
                        $href = $tag_node->getAttribute('href');
                        if (!empty($href) && strpos($href, '/tag/') !== false) {
                            // Extract tag name from URL
                            $url_parts = explode('/tag/', $href);
                            if (isset($url_parts[1])) {
                                // Remove trailing slash and any query parameters
                                $tag_slug = preg_replace('/[\/\?].*$/', '', $url_parts[1]);
                                // Convert slug to readable name
                                $tag = str_replace(array('-', '_'), ' ', $tag_slug);
                                $tag = ucwords($tag);
                            }
                        }
                    }
                    
                    // Remove hash symbol if present
                    if (substr($tag, 0, 1) === '#') {
                        $tag = substr($tag, 1);
                    }
                    
                    if (!empty($tag) && !in_array($tag, $tags)) {
                        $tags[] = $tag;
                        error_log('Found tag: ' . $tag);
                    }
                }
                
                if (!empty($tags)) {
                    return $tags;
                }
            }
        }
        
        // Try to find tags in meta tags (sometimes used for SEO)
        $meta_selectors = array(
            '//meta[@property="article:tag"]/@content',
            '//meta[@name="keywords"]/@content',
        );
        
        foreach ($meta_selectors as $selector) {
            $meta_nodes = $xpath->query($selector);
            if ($meta_nodes->length > 0) {
                foreach ($meta_nodes as $meta_node) {
                    $meta_content = trim($meta_node->textContent);
                    if (!empty($meta_content)) {
                        // Split by commas if multiple tags are in one meta tag
                        $meta_tags = explode(',', $meta_content);
                        foreach ($meta_tags as $tag) {
                            $tag = trim($tag);
                            if (!empty($tag) && !in_array($tag, $tags)) {
                                $tags[] = $tag;
                            }
                        }
                    }
                }
                
                if (!empty($tags)) {
                    return $tags;
                }
            }
        }

        return $tags;
    }

    /**
     * Extract the featured image from the HTML.
     *
     * @since    1.0.0
     * @param    DOMXPath      $xpath    The DOMXPath object.
     * @param    DOMDocument   $dom      The DOMDocument object.
     * @return   string                  The featured image URL.
     */
    private function extract_featured_image($xpath, $dom) {
        error_log('Extracting featured image');
        
        // Try different selectors for the featured image
        $image_selectors = array(
            '//meta[@property="og:image"]/@content',
            '//div[contains(@class, "post-thumbnail")]/img/@src',
            '//div[contains(@class, "featured-image")]/img/@src',
            '//img[contains(@class, "featured-image")]/@src',
            '//article//img[1]/@src',
            '//div[contains(@class, "elementor-widget-image")]//img/@src',
            '//figure[contains(@class, "wp-block-image")]//img/@src',
        );

        foreach ($image_selectors as $selector) {
            $image_nodes = $xpath->query($selector);
            if ($image_nodes->length > 0) {
                $image_url = $image_nodes->item(0)->textContent;
                error_log('Found image URL: ' . $image_url);
                
                // Check if this is already a Wayback Machine URL
                if (strpos($image_url, 'web.archive.org/web/') !== false) {
                    error_log('Image URL is already a Wayback Machine URL');
                    return $image_url;
                }
                
                // Check if the URL is relative
                if (substr($image_url, 0, 4) !== 'http') {
                    // Try to find the base URL
                    $base_nodes = $xpath->query('//base/@href');
                    if ($base_nodes->length > 0) {
                        $base_url = $base_nodes->item(0)->textContent;
                        $image_url = $this->make_absolute_url($image_url, $base_url);
                        error_log('Made relative URL absolute using base: ' . $image_url);
                    } else {
                        // If no base tag, try to use the current URL
                        $canonical_nodes = $xpath->query('//link[@rel="canonical"]/@href');
                        if ($canonical_nodes->length > 0) {
                            $canonical_url = $canonical_nodes->item(0)->textContent;
                            $image_url = $this->make_absolute_url($image_url, $canonical_url);
                            error_log('Made relative URL absolute using canonical: ' . $image_url);
                        }
                    }
                }
                
                // If we have a Wayback URL in the document, ensure the image URL is also from Wayback
                $wayback_url = '';
                $wayback_nodes = $xpath->query('//link[@rel="canonical"]/@href');
                if ($wayback_nodes->length > 0) {
                    $potential_wayback = $wayback_nodes->item(0)->textContent;
                    if (strpos($potential_wayback, 'web.archive.org/web/') !== false) {
                        $wayback_url = $potential_wayback;
                        error_log('Found Wayback URL in document: ' . $wayback_url);
                    }
                }
                
                if (!empty($wayback_url) && strpos($image_url, 'web.archive.org/web/') === false) {
                    // Extract timestamp from Wayback URL
                    preg_match('#web\.archive\.org/web/([0-9]+)/#', $wayback_url, $matches);
                    if (!empty($matches[1])) {
                        $timestamp = $matches[1];
                        // Convert image URL to Wayback URL format with im_ (image) flag
                        $wayback_image_url = 'https://web.archive.org/web/' . $timestamp . 'im_/' . preg_replace('#^https?://#', '', $image_url);
                        error_log('Converted image URL to Wayback format: ' . $wayback_image_url);
                        return $wayback_image_url;
                    }
                }
                
                return $image_url;
            }
        }

        return '';
    }

    /**
     * Extract the comments from the HTML.
     *
     * @since    1.0.0
     * @param    DOMXPath    $xpath    The DOMXPath object.
     * @return   array                 The comments.
     */
    private function extract_comments($xpath) {
        // Try different selectors for the comments
        $comment_selectors = array(
            '//ol[contains(@class, "commentlist")]/li',
            '//ol[contains(@class, "comment-list")]/li',
            '//div[contains(@class, "comment")]',
        );

        $comments = array();

        foreach ($comment_selectors as $selector) {
            $comment_nodes = $xpath->query($selector);
            if ($comment_nodes->length > 0) {
                foreach ($comment_nodes as $comment_node) {
                    // Extract comment author
                    $author_nodes = $xpath->query('.//cite[contains(@class, "comment-author")]', $comment_node);
                    $author = '';
                    if ($author_nodes->length > 0) {
                        $author = trim($author_nodes->item(0)->textContent);
                    }

                    // Extract comment date
                    $date_nodes = $xpath->query('.//time', $comment_node);
                    $date = '';
                    if ($date_nodes->length > 0) {
                        $date = trim($date_nodes->item(0)->textContent);
                    }

                    // Extract comment content
                    $content_nodes = $xpath->query('.//div[contains(@class, "comment-content")]', $comment_node);
                    $content = '';
                    if ($content_nodes->length > 0) {
                        $content = $this->get_inner_html($content_nodes->item(0));
                        $content = $this->clean_content($content);
                    }

                    if (!empty($content)) {
                        $comments[] = array(
                            'author' => $author,
                            'date' => $date,
                            'content' => $content,
                        );
                    }
                }
                
                if (!empty($comments)) {
                    return $comments;
                }
            }
        }

        return $comments;
    }

    /**
     * Extract custom fields from the HTML based on provided keys.
     *
     * @since    1.0.0
     * @param    DOMXPath    $xpath              The DOMXPath object.
     * @param    array       $custom_field_keys  Array of custom field keys to look for.
     * @return   array                           The extracted custom fields.
     */
    private function extract_custom_fields($xpath, $custom_field_keys) {
        $custom_fields = array();
        
        error_log('Looking for custom fields: ' . implode(', ', $custom_field_keys));
        
        foreach ($custom_field_keys as $key) {
            $key = sanitize_text_field($key);
            $value = '';
            
            // Try different selectors for custom fields
            $selectors = array(
                // Look for meta tags with the field name
                "//meta[@name='$key']/@content",
                "//meta[@property='$key']/@content",
                "//meta[@name='_$key']/@content",
                "//meta[@property='_$key']/@content",
                
                // Look for elements with class or ID matching the field name
                "//*[contains(@class, '$key')]//text()",
                "//*[@id='$key']//text()",
                "//*[contains(@class, 'meta-$key')]//text()",
                "//*[@id='meta-$key']//text()",
                
                // Look for elements with data attributes
                "//*[@data-$key]/@data-$key",
                "//*[@data-meta-$key]/@data-meta-$key",
                
                // Look for common WordPress custom field patterns
                "//div[contains(@class, 'custom-field') and contains(@class, '$key')]//text()",
                "//span[contains(@class, 'custom-field') and contains(@class, '$key')]//text()",
                "//div[contains(@class, 'meta') and contains(@class, '$key')]//text()",
                "//span[contains(@class, 'meta') and contains(@class, '$key')]//text()",
                
                // Look for ACF field patterns
                "//div[contains(@class, 'acf-field') and contains(@class, '$key')]//text()",
                "//div[contains(@class, 'acf-field-$key')]//text()",
                
                // Look for schema.org metadata
                "//*[@itemprop='$key']//text()",
                "//*[@itemprop='$key']/@content",
                
                // Look for JSON-LD script containing the key
                "//script[@type='application/ld+json']"
            );
            
            foreach ($selectors as $selector) {
                $nodes = $xpath->query($selector);
                if ($nodes && $nodes->length > 0) {
                    // Special handling for JSON-LD script
                    if ($selector === "//script[@type='application/ld+json']") {
                        foreach ($nodes as $node) {
                            $json = json_decode($node->nodeValue, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                                // Recursively search for the key in the JSON
                                $found_value = $this->find_key_in_array($json, $key);
                                if ($found_value !== null) {
                                    $value = is_array($found_value) ? json_encode($found_value) : $found_value;
                                    break 2; // Break out of both loops
                                }
                            }
                        }
                        continue;
                    }
                    
                    // Combine all text nodes if there are multiple
                    $combined_text = '';
                    foreach ($nodes as $node) {
                        $combined_text .= ' ' . trim($node->nodeValue);
                    }
                    
                    $value = trim($combined_text);
                    if (!empty($value)) {
                        error_log("Found custom field '$key' with value: $value");
                        break;
                    }
                }
            }
            
            // Store the custom field value even if empty
            $custom_fields[$key] = $value;
        }
        
        return $custom_fields;
    }
    
    /**
     * Recursively search for a key in an array (for JSON-LD parsing).
     *
     * @since    1.0.0
     * @param    array     $array  The array to search in.
     * @param    string    $key    The key to search for.
     * @return   mixed|null        The value if found, null otherwise.
     */
    private function find_key_in_array($array, $key) {
        // If the key exists directly in the array, return its value
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        // Search recursively in sub-arrays
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $result = $this->find_key_in_array($v, $key);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        
        return null;
    }

    /**
     * Get the inner HTML of a DOM node.
     *
     * @since    1.0.0
     * @param    DOMNode    $node    The DOM node.
     * @return   string              The inner HTML.
     */
    private function get_inner_html($node) {
        $html = '';
        $children = $node->childNodes;

        foreach ($children as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }

        return $html;
    }

    /**
     * Clean up the content.
     *
     * @since    1.0.0
     * @param    string    $content    The content.
     * @return   string                The cleaned content.
     */
    private function clean_content($content) {
        error_log('Cleaning content of length: ' . strlen($content));
        
        // Fix potentially truncated Elementor content
        if (strpos($content, '<div class="elementor-element') === 0) {
            error_log('Detected truncated Elementor content, attempting to fix');
            $content = '<div class="elementor-content-wrapper">' . $content . '</div>';
        }
        
        // Remove empty paragraphs
        $content = preg_replace('/<p>\s*<\/p>/i', '', $content);
        
        // Remove scripts
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        
        // Remove styles
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);
        
        // Remove comments
        $content = preg_replace('/<!--(.*)-->/Uis', '', $content);
        
        // Remove social sharing buttons
        $content = preg_replace('/<div\b[^>]*class="[^"]*share[^"]*"[^>]*>.*?<\/div>/is', '', $content);
        $content = preg_replace('/<div\b[^>]*class="[^"]*social[^"]*"[^>]*>.*?<\/div>/is', '', $content);
        
        // Remove related posts
        $content = preg_replace('/<div\b[^>]*class="[^"]*related[^"]*"[^>]*>.*?<\/div>/is', '', $content);
        
        // Remove author bio
        $content = preg_replace('/<div\b[^>]*class="[^"]*author-bio[^"]*"[^>]*>.*?<\/div>/is', '', $content);
        
        // Remove navigation
        $content = preg_replace('/<nav\b[^>]*>.*?<\/nav>/is', '', $content);
        
        // Fix Elementor specific issues
        // Remove Elementor edit links
        $content = preg_replace('/<span\s+class="elementor-element-edit-mode[^>]*>.*?<\/span>/is', '', $content);
        
        // Fix broken Elementor sections
        if (strpos($content, 'elementor') !== false && strpos($content, '</div>') === false) {
            $content .= '</div>';
        }
        
        // Trim whitespace
        $content = trim($content);
        
        error_log('Content cleaned. Final length: ' . strlen($content));
        return $content;
    }

    /**
     * Make a relative URL absolute.
     *
     * @since    1.0.0
     * @param    string    $url         The relative URL.
     * @param    string    $base_url    The base URL.
     * @return   string                 The absolute URL.
     */
    private function make_absolute_url($url, $base_url) {
        // If the URL is already absolute, return it
        if (substr($url, 0, 4) === 'http') {
            return $url;
        }
        
        // Parse the base URL
        $parsed_base = parse_url($base_url);
        $base = $parsed_base['scheme'] . '://' . $parsed_base['host'];
        
        // If the URL starts with a slash, it's relative to the root
        if (substr($url, 0, 1) === '/') {
            return $base . $url;
        }
        
        // Otherwise, it's relative to the current path
        $path = isset($parsed_base['path']) ? $parsed_base['path'] : '/';
        $path = rtrim(dirname($path), '/') . '/';
    
        return $path . $url;
    }
    
    /**
     * @param    string    $html    The HTML content.
     * @return   array|WP_Error     The basic post data or WP_Error on failure.
     */
    public function extract_basic_info($html) {
        // Check if the content is empty
        if (empty($html)) {
            return new WP_Error('empty_content', __('Empty HTML content.', 'wayback-wp-importer'));
        }

        // Load the HTML into DOMDocument
        $dom = new DOMDocument();
        
        // Suppress warnings from malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        // Create a new DOMXPath object
        $xpath = new DOMXPath($dom);

        // Initialize the data array with just the basic info we need for listings
        $data = array(
            'title' => '',
            'date' => '',
        );

        // Extract the title
        $data['title'] = $this->extract_title($xpath);
        
        // Extract the date
        $data['date'] = $this->extract_date($xpath);
        
        // Add a preview snippet of content if available
        $content = $this->extract_content($xpath);
        if (!empty($content)) {
            // Strip tags and get a short preview
            $preview = strip_tags($content);
            $preview = substr($preview, 0, 150) . (strlen($preview) > 150 ? '...' : '');
            $data['preview'] = $preview;
        }
        
        // Try to extract featured image for thumbnail
        $data['thumbnail'] = $this->extract_featured_image($xpath, $dom);

        return $data;
    }
}

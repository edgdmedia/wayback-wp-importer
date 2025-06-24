<?php
/**
 * Taxonomy Handler for Wayback WP Importer
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 * @subpackage Wayback_WP_Importer/includes
 */

/**
 * Taxonomy Handler class.
 *
 * This class handles the discovery, extraction, and processing of taxonomies
 * from Wayback Machine content.
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 * @subpackage Wayback_WP_Importer/includes
 */
class Wayback_WP_Importer_Taxonomies {

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Nothing to initialize
    }

    /**
     * Get all registered taxonomies and their terms.
     *
     * @since    1.0.0
     * @param    string    $post_type Optional. Post type to filter taxonomies by.
     * @return   array                Array of taxonomies with their terms.
     */
    public function get_all_taxonomies($post_type = 'post') {
        // Get all taxonomies that are shown in UI
        $taxonomies = get_taxonomies(array('show_ui' => true), 'objects');
        $taxonomy_data = array();
        
        foreach ($taxonomies as $taxonomy) {
            // Skip internal taxonomies like 'nav_menu', 'link_category', etc.
            if (in_array($taxonomy->name, array('nav_menu', 'link_category', 'post_format'))) {
                continue;
            }
            
            // Check if this taxonomy is registered for the given post type
            $object_types = $taxonomy->object_type;
            if (!empty($post_type) && !in_array($post_type, $object_types) && !in_array('post', $object_types)) {
                continue;
            }
            
            // Get all terms for this taxonomy
            $terms = get_terms(array(
                'taxonomy' => $taxonomy->name,
                'hide_empty' => false,
            ));
            
            $term_data = array();
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $term_data[] = array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'parent' => $term->parent,
                        'description' => $term->description,
                        'count' => $term->count,
                    );
                }
            }
            
            $taxonomy_data[$taxonomy->name] = array(
                'name' => $taxonomy->name,
                'label' => $taxonomy->label,
                'singular_label' => isset($taxonomy->labels->singular_name) ? $taxonomy->labels->singular_name : $taxonomy->label,
                'hierarchical' => $taxonomy->hierarchical,
                'description' => $taxonomy->description,
                'terms' => $term_data,
            );
        }
        
        return $taxonomy_data;
    }

    /**
     * Extract taxonomy terms from HTML content.
     *
     * @since    1.0.0
     * @param    string    $html              The HTML content.
     * @param    array     $taxonomy_selectors Optional. Array of custom CSS selectors to use for extraction.
     * @return   array                        The extracted taxonomy terms.
     */
    public function extract_taxonomy_terms_from_html($html, $taxonomy_selectors = array()) {
        // Check if HTML is empty
        if (empty($html)) {
            error_log('Empty HTML content provided to extract_taxonomy_terms_from_html');
            return array();
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
        
        // Extract taxonomy terms
        return $this->extract_taxonomy_terms($xpath, $taxonomy_selectors);
    }

    /**
     * Extract taxonomy terms from the HTML using XPath.
     *
     * @since    1.0.0
     * @param    DOMXPath    $xpath              The DOMXPath object.
     * @param    array       $taxonomy_selectors Array of custom CSS selectors to use.
     * @return   array                           The extracted taxonomy terms.
     */
    private function extract_taxonomy_terms($xpath, $taxonomy_selectors = array()) {
        $result = array();
        
        // Default selectors for common taxonomy patterns
        $default_selectors = array(
            // Categories
            "//div[contains(@class, 'cat-links')]/a",
            "//div[contains(@class, 'category-links')]/a",
            "//span[contains(@class, 'cat-links')]/a",
            "//span[contains(@class, 'category-links')]/a",
            "//div[contains(@class, 'categories')]/a",
            "//ul[contains(@class, 'post-categories')]/li/a",
            "//p[contains(@class, 'post-category')]/a",
            
            // Tags
            "//div[contains(@class, 'tag-links')]/a",
            "//div[contains(@class, 'tags-links')]/a",
            "//span[contains(@class, 'tag-links')]/a",
            "//span[contains(@class, 'tags-links')]/a",
            "//div[contains(@class, 'tags')]/a",
            "//ul[contains(@class, 'post-tags')]/li/a",
            "//p[contains(@class, 'post-tags')]/a",
            
            // Generic taxonomy links
            "//div[contains(@class, 'taxonomy-')]/a",
            "//span[contains(@class, 'taxonomy-')]/a",
            "//div[contains(@class, 'term-links')]/a",
            "//span[contains(@class, 'term-links')]/a",
            
            // Schema.org metadata
            "//*[@itemprop='keywords']",
            "//*[@itemprop='articleSection']",
            
            // JetEngine specific patterns
            "//div[contains(@class, 'jet-listing-dynamic-terms')]/a",
            "//div[contains(@class, 'jet-listing-dynamic-terms__link')]",
        );
        
        // Combine default and custom selectors
        $all_selectors = array_merge($default_selectors, $taxonomy_selectors);
        
        // Extract terms using each selector
        foreach ($all_selectors as $selector) {
            $nodes = $xpath->query($selector);
            
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $term_name = trim($node->nodeValue);
                    
                    if (!empty($term_name)) {
                        // Try to determine the taxonomy type from the context
                        $taxonomy_type = $this->determine_taxonomy_type($node, $selector);
                        
                        if (!isset($result[$taxonomy_type])) {
                            $result[$taxonomy_type] = array();
                        }
                        
                        if (!in_array($term_name, $result[$taxonomy_type])) {
                            $result[$taxonomy_type][] = $term_name;
                        }
                    }
                }
            }
        }
        
        // Look for JSON-LD metadata
        $json_ld_nodes = $xpath->query("//script[@type='application/ld+json']");
        if ($json_ld_nodes && $json_ld_nodes->length > 0) {
            foreach ($json_ld_nodes as $node) {
                $json = json_decode($node->nodeValue, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $this->extract_terms_from_json($json, $result);
                }
            }
        }
        
        return $result;
    }

    /**
     * Determine the taxonomy type from the node context.
     *
     * @since    1.0.0
     * @param    DOMNode    $node     The DOM node.
     * @param    string     $selector The selector used to find this node.
     * @return   string               The taxonomy type.
     */
    private function determine_taxonomy_type($node, $selector) {
        // Default to 'category' if we can't determine
        $taxonomy_type = 'category';
        
        // Check the node's class or parent's class
        $class = $node->getAttribute('class');
        if (empty($class) && $node->parentNode) {
            $class = $node->parentNode->getAttribute('class');
        }
        
        // Check for common taxonomy indicators in class names
        if (strpos($class, 'cat') !== false || strpos($class, 'category') !== false) {
            $taxonomy_type = 'category';
        } elseif (strpos($class, 'tag') !== false) {
            $taxonomy_type = 'post_tag';
        } else {
            // Check for custom taxonomy indicators
            $pattern = '/tax-([a-zA-Z0-9_-]+)|taxonomy-([a-zA-Z0-9_-]+)|term-([a-zA-Z0-9_-]+)/';
            if (preg_match($pattern, $class, $matches)) {
                $taxonomy_type = !empty($matches[1]) ? $matches[1] : (!empty($matches[2]) ? $matches[2] : $matches[3]);
            }
        }
        
        // Check the selector for clues
        if (strpos($selector, 'cat-links') !== false || strpos($selector, 'category-links') !== false) {
            $taxonomy_type = 'category';
        } elseif (strpos($selector, 'tag-links') !== false || strpos($selector, 'tags-links') !== false) {
            $taxonomy_type = 'post_tag';
        }
        
        return $taxonomy_type;
    }

    /**
     * Extract taxonomy terms from JSON-LD data.
     *
     * @since    1.0.0
     * @param    array    $json   The JSON-LD data.
     * @param    array    $result The result array to populate.
     */
    private function extract_terms_from_json($json, &$result) {
        // Check for keywords (usually tags)
        if (isset($json['keywords'])) {
            if (!isset($result['post_tag'])) {
                $result['post_tag'] = array();
            }
            
            $keywords = $json['keywords'];
            if (is_string($keywords)) {
                // Split by common separators
                $tags = preg_split('/[,;|]+/', $keywords);
                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag) && !in_array($tag, $result['post_tag'])) {
                        $result['post_tag'][] = $tag;
                    }
                }
            } elseif (is_array($keywords)) {
                foreach ($keywords as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag) && !in_array($tag, $result['post_tag'])) {
                        $result['post_tag'][] = $tag;
                    }
                }
            }
        }
        
        // Check for articleSection (usually categories)
        if (isset($json['articleSection'])) {
            if (!isset($result['category'])) {
                $result['category'] = array();
            }
            
            $sections = $json['articleSection'];
            if (is_string($sections)) {
                // Split by common separators
                $categories = preg_split('/[,;|]+/', $sections);
                foreach ($categories as $category) {
                    $category = trim($category);
                    if (!empty($category) && !in_array($category, $result['category'])) {
                        $result['category'][] = $category;
                    }
                }
            } elseif (is_array($sections)) {
                foreach ($sections as $category) {
                    $category = trim($category);
                    if (!empty($category) && !in_array($category, $result['category'])) {
                        $result['category'][] = $category;
                    }
                }
            }
        }
        
        // Recursively check nested objects
        foreach ($json as $key => $value) {
            if (is_array($value)) {
                $this->extract_terms_from_json($value, $result);
            }
        }
    }
}

<?php

/**
 * The custom fields functionality of the plugin.
 *
 * @link       https://www.getlitafrica.com
 * @since      1.0.0
 *
 * @package    Wayback_WP_Importer
 * @subpackage Wayback_WP_Importer/includes
 */

/**
 * The custom fields functionality of the plugin.
 *
 * This class handles the extraction and processing of custom fields from HTML content.
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 * @subpackage Wayback_WP_Importer/includes
 * @author     GetLit Africa <info@getlitafrica.com>
 */
class Wayback_WP_Importer_Custom_Fields
{

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        // Nothing to initialize
    }

    /**
     * Extract content using specific CSS selectors.
     *
     * @since    1.0.0
     * @param    string    $html              The HTML content.
     * @param    array     $selectors         Array of CSS selectors to use for extraction.
     * @param    array     $attributes        Optional. Array of attributes to extract for each selector.
     * @param    array     $special_cases     Optional. Array of special case identifiers for specific selectors.
     * @return   array                        The extracted content with selector information.
     */
    public function extract_content_by_selectors($html, $selectors = array(), $attributes = array(), $special_cases = array())
    {
        // Remove any escaping that might have been added
        if (is_string($html)) {
            $html = stripslashes($html);
            
            // Check if HTML is base64 encoded and decode if necessary
            if (preg_match('/^[A-Za-z0-9+\/=]+$/', $html)) {
                $decoded = base64_decode($html, true);
                if ($decoded !== false) {
                    error_log('Decoded base64 HTML content, length: ' . strlen($decoded));
                    $html = $decoded;
                }
            }
        }

        
        // Check if HTML is empty
        if (empty($html)) {
            error_log('Empty HTML content provided to extract_content_by_selectors');
            return array();
        }

        // Check if selectors are empty
        if (empty($selectors)) {
            error_log('No selectors provided to extract_content_by_selectors');
            return array();
        }

        $result = array(
            'fields' => array(),
            'previews' => array(),
            'html' => ''
        );

        $result['html'] = $html; // Store the processed HTML for reference

        // Create a new DOMDocument
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        
        // Convert to UTF-8 and handle HTML entities properly
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        
        // Suppress warnings for malformed HTML
        $old_error_level = error_reporting(0);
        
        // Load HTML with proper encoding
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Restore error reporting
        error_reporting($old_error_level);
        
        // Clear any XML errors that might have occurred during parsing
        libxml_clear_errors();
        
        $xpath = new DOMXPath($doc);
        $max_field_length = 10000; // 10KB limit for field values

        error_log('Extracting content using ' . count($selectors) . ' selectors');
        if (!empty($special_cases)) {
            error_log('Special cases received: ' . print_r($special_cases, true));
        }

        foreach ($selectors as $index => $selector) {
            $selector_parts = explode('::attr(', $selector);
            $base_selector = $selector_parts[0];
            $attribute = null;
            if (count($selector_parts) > 1) {
                $attribute = trim($selector_parts[1], ')');
            }
            if (isset($attributes[$index])) {
                $attribute = $attributes[$index];
                error_log("Attribute for selector $index set from attributes array: '$attribute'");
            }
            $field_key = $selector;
            try {
                $xpath_selector = $this->css_to_xpath($base_selector);
                error_log("Using XPath selector: $xpath_selector" . ($attribute ? " with attribute: $attribute" : ""));
                error_log("XPath for selector $base_selector: $xpath_selector");
                $nodes = $xpath->query($xpath_selector);
                error_log("[extract_content_by_selectors] Node count for selector $base_selector: " . ($nodes ? $nodes->length : 0));
                if ($nodes && $nodes->length > 0) {
                    foreach ($nodes as $node) {
                        $tag = (property_exists($node, 'tagName') ? $node->tagName : get_class($node));
                        error_log("[extract_content_by_selectors] Matched node type: {$node->nodeType}, tag/class: $tag");
                    }

                    $node_count = 0;
                    foreach ($nodes as $node) {
                        // Debug: log selector, attribute, and node details
                        $tag = (property_exists($node, 'tagName') ? $node->tagName : get_class($node));
                        error_log("[extract_content_by_selectors] Selector: $base_selector, Attribute: " . ($attribute ?? 'null') . ", Node tag: $tag");
                        if ($node_count > 100) {
                            error_log("Too many nodes matched by selector: $base_selector - limiting results");
                            break;
                        }
                        $node_text = '';
                        if ($attribute !== null && $attribute !== '') {
                            if ($node instanceof DOMElement) {
                                if ($node->hasAttribute($attribute)) {
                                    $node_text = $node->getAttribute($attribute);
                                    error_log("Found attribute '$attribute' directly on node: $node_text");
                                }
                            } elseif ($node->nodeType === XML_TEXT_NODE && $node->parentNode instanceof DOMElement) {
                                if ($node->parentNode->hasAttribute($attribute)) {
                                    $node_text = $node->parentNode->getAttribute($attribute);
                                    error_log("Found attribute '$attribute' on parent node: $node_text");
                                }
                            }
                        } else {
                            switch ($node->nodeType) {
                                case XML_ATTRIBUTE_NODE:
                                    $node_text = $node->value;
                                    break;
                                case XML_TEXT_NODE:
                                    $node_text = $node->data;
                                    break;
                                default:
                                    $node_text = $node->nodeValue;
                            }
                        }
                        if (strlen($node_text) > $max_field_length) {
                            error_log("Skipping oversized node content from selector '$selector': " . strlen($node_text) . " bytes");
                            continue;
                        }
                        $node_text = trim($node_text);
                        // $node_text = trim($node_text, "\"'");
                        // $node_text = stripslashes($node_text);
                        if (!empty($node_text)) {
                            $result['fields'][] = [
                                'selector' => $base_selector,
                                'attribute' => $attribute,
                                'value' => $node_text,
                            ];
                            $result['previews'][] = [
                                'selector' => $base_selector,
                                'attribute' => $attribute,
                                'value' => $node_text,
                                'source' => $selector,
                                'selector_used' => $base_selector,
                                'attribute' => $attribute,
                                'found' => true,
                            ];
                            $log_message = "Found content for selector '$base_selector'";
                            if ($attribute) {
                                $log_message .= " with attribute '$attribute'";
                            }
                            $log_message .= " with value length: " . strlen($node_text) . " bytes";
                            error_log($log_message);
                            $node_count++;
                        }
                    }
                } else {
                    error_log("No nodes found for selector: $base_selector");
                    $result['fields'][] = [
                        'selector' => $base_selector,
                        'attribute' => $attribute,
                        'value' => '',
                    ];
                    $result['previews'][] = [
                        'selector' => $base_selector,
                        'attribute' => $attribute,
                        'value' => '',
                        'source' => $selector,
                        'selector_used' => $base_selector,
                        'attribute' => $attribute,
                        'found' => false,
                    ];
                }
            } catch (Exception $e) {
                error_log("Error querying selector '$selector': " . $e->getMessage());
                $result['fields'][] = [
                    'selector' => $base_selector,
                    'attribute' => $attribute,
                    'value' => '',
                ];
                $result['previews'][] = [
                    'selector' => $base_selector,
                    'attribute' => $attribute,
                    'value' => '',
                    'source' => $selector,
                    'selector_used' => $base_selector,
                    'attribute' => $attribute,
                    'found' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        return $result;
    }

    /**
     * Extract custom fields from HTML content using provided selectors.
     *
     * @since    1.0.0
     * @param    string    $html              The HTML content.
     * @param    array     $custom_fields     Array of custom field definitions.
     * @return   array                        The extracted custom fields.
     */
    public function extract_custom_fields($html, $custom_fields = array())
    {
        error_log('[extract_custom_fields] AJAX received: custom_fields=' . json_encode($custom_fields));
        // Check if HTML is empty
        if (empty($html)) {
            error_log('Empty HTML content provided to extract_custom_fields');
            return array();
        }

        // Check if custom fields are empty
        if (empty($custom_fields)) {
            error_log('No custom fields provided to extract_custom_fields');
            return array();
        }

        $result = array();

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

        // Maximum length for extracted field values to prevent entire HTML extraction
        $max_field_length = 10000; // 10KB limit for field values

        error_log('Extracting custom fields using ' . count($custom_fields) . ' field definitions');

        // Process each custom field
        foreach ($custom_fields as $field_key => $field_data) {
            // Skip if no selector is provided
            if (empty($field_data['selector'])) {
                error_log("No selector provided for field '$field_key'");
                continue;
            }

            $selector = $field_data['selector'];
            $attribute = isset($field_data['attribute']) ? $field_data['attribute'] : null;

            try {
                // Convert CSS selector to XPath if needed
                $xpath_selector = $this->css_to_xpath($selector);

                error_log("Using XPath selector: $xpath_selector for field '$field_key'");
                $nodes = $xpath->query($xpath_selector);

                if ($nodes && $nodes->length > 0) {
                    // Combine all text nodes if there are multiple
                    $combined_text = '';
                    $node_count = 0;

                    foreach ($nodes as $node) {
                        // Skip if we've already processed too many nodes (likely a too-broad selector)
                        if ($node_count > 20) {
                            error_log("Too many nodes matched by selector: $selector - limiting results");
                            break;
                        }

                        // Extract text or attribute content safely
                        $node_text = '';
                        if ($attribute !== null) {
                            if ($node instanceof DOMElement && $node->hasAttribute($attribute)) {
                                $node_text = $node->getAttribute($attribute);
                            }
                        } else {
                            if ($node instanceof DOMAttr) {
                                $node_text = $node->value;
                            } elseif ($node instanceof DOMText) {
                                $node_text = $node->data;
                            } elseif ($node instanceof DOMElement) {
                                $node_text = $node->textContent;
                            } else {
                                $node_text = $node->nodeValue;
                            }
                        }

                        // Skip if the text is suspiciously large (likely the whole HTML)
                        if (strlen($node_text) > $max_field_length) {
                            error_log("Skipping oversized node content from field '$field_key': " . strlen($node_text) . " bytes");
                            continue;
                        }

                        $node_text = trim($node_text);
                        if (!empty($node_text)) {
                            $combined_text .= ' ' . $node_text;
                            $node_count++;
                        }
                    }

                    $value = trim($combined_text);

                    // Skip if the value is suspiciously large
                    if (strlen($value) > $max_field_length) {
                        error_log("Skipping oversized combined content from field '$field_key': " . strlen($value) . " bytes");
                        continue;
                    }

                    if (!empty($value)) {
                        $result[$field_key] = $value;
                        error_log("Found content for field '$field_key' with value length: " . strlen($value) . " bytes");
                    }
                } else {
                    error_log("No nodes found for field '$field_key' using selector: $selector");
                }
            } catch (Exception $e) {
                error_log("Error querying selector for field '$field_key': " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Convert a CSS selector to an XPath selector.
     *
     * @since    1.0.0
     * @param    string    $css_selector    The CSS selector to convert.
     * @return   string                     The equivalent XPath selector.
     */
    private function css_to_xpath($css_selector)
    {
        // Use Symfony CssSelector for robust conversion
        static $converter = null;
        if ($converter === null) {
            if (!class_exists('Symfony\\Component\\CssSelector\\CssSelectorConverter')) {
                throw new \RuntimeException('Symfony CssSelector component is not installed. Run composer require symfony/css-selector.');
            }
            $converter = new \Symfony\Component\CssSelector\CssSelectorConverter();
        }
        return $converter->toXPath($css_selector);
    }
}

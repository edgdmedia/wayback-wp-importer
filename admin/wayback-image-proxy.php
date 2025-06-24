<?php
/**
 * Wayback Machine Image Proxy
 * 
 * This file serves as a proxy for images from the Wayback Machine to avoid CORS issues.
 * 
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
    require_once(ABSPATH . 'wp-load.php');
}

// Check if user is logged in and has proper permissions
if (!current_user_can('manage_options')) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Access denied.';
    exit;
}

// Get the image URL from the request
$image_url = isset($_GET['url']) ? $_GET['url'] : '';

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

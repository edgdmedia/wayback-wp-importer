# Installation Guide for Wayback WordPress Importer

This guide will help you install and set up the Wayback WordPress Importer plugin on your WordPress site.

## Prerequisites

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Allow outbound connections to archive.org

## Installation

### Method 1: Direct Upload (Recommended)

1. Download the plugin zip file from the release page.
2. Log in to your WordPress admin dashboard.
3. Navigate to **Plugins > Add New > Upload Plugin**.
4. Click **Choose File** and select the downloaded zip file.
5. Click **Install Now**.
6. After installation completes, click **Activate Plugin**.

### Method 2: Manual Installation

1. Download the plugin zip file from the release page.
2. Extract the zip file to your computer.
3. Using FTP or your hosting control panel's file manager, upload the extracted `wayback-wp-importer` folder to your WordPress site's `/wp-content/plugins/` directory.
4. Log in to your WordPress admin dashboard.
5. Navigate to **Plugins**.
6. Find "Wayback WordPress Importer" and click **Activate**.

## Configuration

No additional configuration is required. The plugin is ready to use immediately after activation.

## Usage

1. In your WordPress admin dashboard, navigate to **Tools > Wayback Importer**.
2. Enter a Wayback Machine URL of a WordPress post you want to import.
   - Example format: `https://web.archive.org/web/20200101000000/https://example.com/sample-post/`
3. Click **Extract Content** to retrieve the post data.
4. Review and edit the extracted content in the preview section.
5. Click **Import to WordPress** to add the post to your site.
6. Alternatively, click **Export to CSV** to download the data as a CSV file.

## Troubleshooting

### Common Issues

1. **Unable to connect to Wayback Machine**
   - Ensure your server allows outbound connections to archive.org
   - Check if the Wayback Machine URL is valid and accessible in a browser

2. **Content extraction issues**
   - Some WordPress themes may use non-standard HTML structures
   - Try different Wayback Machine snapshots of the same page
   - Edit the extracted content manually before importing

3. **Image import failures**
   - Some images may not be properly archived by the Wayback Machine
   - Images with relative URLs might not be correctly resolved
   - You may need to manually download and upload some images

### Getting Help

If you encounter any issues not covered in this guide, please:

1. Check the [FAQ section](README.md#frequently-asked-questions) in the README
2. Submit an issue on the project's GitHub repository
3. Contact the plugin developer for support

## Uninstallation

1. Navigate to **Plugins** in your WordPress admin dashboard.
2. Find "Wayback WordPress Importer" and click **Deactivate**.
3. Click **Delete** to remove the plugin completely.

Note: Uninstalling the plugin will not remove any posts that have been imported.

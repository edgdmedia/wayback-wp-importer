# Wayback WordPress Importer

A WordPress plugin that allows you to import content from WordPress websites archived on the Wayback Machine.

## Description

Wayback WordPress Importer is a powerful tool for WordPress site owners who want to recover content from archived versions of WordPress websites. Whether you're restoring your own lost content or migrating content from an old site, this plugin simplifies the process by:

1. Fetching archived WordPress posts from the Wayback Machine
2. Extracting key elements (title, content, featured image, metadata)
3. Providing a preview interface to review content
4. Importing directly into your WordPress database
5. Offering CSV export as a fallback option

## Features

- **Wayback Machine Integration**: Enter any archived WordPress URL to extract content
- **Smart Content Extraction**: Automatically identifies and extracts WordPress post elements
- **Content Preview**: Review extracted content before importing
- **Direct WordPress Import**: Add posts directly to your WordPress database
- **Media Handling**: Attempts to recover and import featured images and in-content media
- **Taxonomy Support**: Preserves categories and tags from original posts
- **Author Attribution**: Maintains original authorship or maps to existing users
- **CSV Export**: Export extracted data to CSV for external processing
- **Batch Processing**: Import multiple posts in a single operation

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New > Upload Plugin
3. Upload the zip file and click "Install Now"
4. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

1. Navigate to Tools > Wayback Importer in your WordPress admin
2. Enter the Wayback Machine URL of the WordPress post you want to import
3. Click "Extract Content" to fetch and analyze the archived page
4. Review the extracted content in the preview pane
5. Make any necessary adjustments to the content
6. Click "Import to WordPress" to add the post to your site
7. Alternatively, click "Export to CSV" to download the data

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Allow outbound connections to archive.org

## Development Plan

### Phase 1: Core Functionality
- Set up plugin structure
- Create admin interface
- Implement Wayback Machine API connection
- Build HTML parser for WordPress content

### Phase 2: Import Features
- Develop content preview functionality
- Create WordPress database import process
- Implement media handling
- Add taxonomy and author mapping

### Phase 3: Enhancements
- Add CSV export functionality
- Implement batch processing
- Add settings for customization
- Create error handling and logging

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL v2 or later.

## Credits

Developed by [Your Name]

## Support

For support, please open an issue on the GitHub repository or contact [Your Contact Information].

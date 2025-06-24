# Usage Guide for Wayback WordPress Importer

This guide provides detailed instructions on how to use the Wayback WordPress Importer plugin to recover content from archived WordPress websites.

## Finding Archived Content

Before you can import content, you need to find the archived version of the WordPress post you want to recover:

1. Visit the [Wayback Machine](https://web.archive.org/) website.
2. Enter the URL of the WordPress site or specific post you want to recover.
3. Browse the calendar to find available snapshots (dates highlighted in blue).
4. Click on a date to see the snapshots available for that day.
5. Select a snapshot to view the archived page.
6. Copy the full URL from your browser's address bar - this is the Wayback Machine URL you'll use.

## Importing Content

### Basic Import Process

1. In your WordPress admin dashboard, navigate to **Tools > Wayback Importer**.
2. Paste the Wayback Machine URL into the "Wayback Machine URL" field.
3. Click **Extract Content** to retrieve the post data.
4. Review the extracted content in the preview section.
5. Click **Import to WordPress** to add the post to your site as a draft.
6. Edit the newly created post as needed.

### Detailed Content Review

After clicking "Extract Content," you'll see a preview with the following sections:

#### Title
- Review and edit the extracted post title.

#### Meta Information
- **Date**: The publication date of the original post.
- **Author**: The original author's name (will be mapped to an existing user if possible).
- **Categories**: Comma-separated list of categories (new categories will be created if needed).
- **Tags**: Comma-separated list of tags.

#### Featured Image
- Preview of the extracted featured image.
- The image will be downloaded and attached to your post during import.

#### Content
- Full post content with formatting preserved.
- Use the WordPress editor to make any necessary adjustments.

#### Excerpt
- The post excerpt, either extracted from the original post or auto-generated.

#### Comments
- If comments were found, they'll be displayed here.
- Check "Import comments" to include them with your post.

## Export Options

Instead of importing directly to WordPress, you can export the data as CSV:

1. Follow steps 1-4 of the import process.
2. Click **Export to CSV** instead of "Import to WordPress."
3. Save the CSV file to your computer.

The CSV file contains all extracted data and can be used for:
- Importing to other systems
- Data analysis
- Content backup
- Manual content migration

## Advanced Usage

### Handling Multiple Posts

To import multiple posts from an archived WordPress site:

1. Create a list of Wayback Machine URLs for each post you want to import.
2. Import each post individually following the basic import process.
3. Consider using the CSV export option for batch processing outside of WordPress.

### Troubleshooting Content Extraction

If the plugin has difficulty extracting content correctly:

1. Try different snapshots of the same page from different dates.
2. Look for snapshots where the CSS and JavaScript were properly archived.
3. For complex pages, you might need to manually copy and paste some content.

### Image Handling

The plugin attempts to recover images from the Wayback Machine, but:

1. Not all images may be properly archived.
2. Some images might have relative URLs that can't be resolved.
3. For best results, check all images after import and replace any missing ones.

## Tips for Best Results

1. **Choose recent snapshots**: More recent archives tend to be more complete.
2. **Check multiple snapshots**: If one snapshot doesn't work well, try another from a different date.
3. **Review before importing**: Always check the extracted content before importing.
4. **Edit post status**: Imported posts are created as drafts; review them before publishing.
5. **Verify media**: Check that all images and embedded media were properly imported.
6. **Attribution**: Consider adding a note about the content's origin if you're importing content you don't own.

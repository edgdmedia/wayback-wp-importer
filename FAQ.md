# Frequently Asked Questions (FAQ)

## General Questions

### What is the Wayback WordPress Importer plugin?
The Wayback WordPress Importer is a WordPress plugin that allows you to import content from WordPress websites archived on the Wayback Machine. It extracts post content, metadata, featured images, and comments, then imports them into your WordPress site.

### Why would I need this plugin?
This plugin is useful for:
- Recovering content from your own website that was lost due to server issues or accidental deletion
- Migrating content from an old website that is no longer accessible but archived on the Wayback Machine
- Preserving historical content from archived WordPress sites (with proper attribution)

### Is this plugin free to use?
Yes, the Wayback WordPress Importer plugin is free and open-source, licensed under GPL v2 or later.

## Technical Requirements

### What are the system requirements?
- WordPress 5.0 or higher
- PHP 7.2 or higher
- Outbound connections to archive.org must be allowed by your server

### Does this plugin work with any WordPress theme?
Yes, the plugin works independently of your theme. The imported content will adopt the styling of your current theme.

### Will this plugin slow down my website?
No, the plugin only runs when you're actively using it in the WordPress admin area. It doesn't add any scripts or styles to your public-facing website.

## Usage Questions

### How do I find the Wayback Machine URL for a post?
1. Go to [archive.org](https://archive.org)
2. Enter the original URL of the post you want to recover
3. Select a date from the calendar when the page was archived
4. Copy the full URL from your browser's address bar after viewing the archived page

### Can I import multiple posts at once?
The current version requires importing posts one at a time. However, you can use the CSV export feature to extract data from multiple posts and then process them in bulk using other tools.

### What content elements can be imported?
The plugin can extract and import:
- Post title
- Post content with formatting
- Featured image
- Publication date
- Author name
- Categories and tags
- Post excerpt
- Comments (optional)

### Will the plugin import images from the post content?
The plugin attempts to download and import the featured image. Images within the post content will maintain their original URLs pointing to the Wayback Machine. For best results, you may want to download and replace these images manually after import.

### Can I preview the content before importing?
Yes, the plugin provides a comprehensive preview of all extracted content. You can edit any element before importing it into your WordPress site.

## Troubleshooting

### The plugin can't extract content from a Wayback Machine URL
This could happen for several reasons:
- The URL might not be a valid Wayback Machine URL (it should start with `https://web.archive.org/web/`)
- The archived page might not be a WordPress post or page
- The archived page might be incomplete or corrupted in the Wayback Machine
- Your server might be blocking connections to archive.org

Try using a different snapshot (date) of the same page, as some archives are more complete than others.

### The extracted content is missing elements or looks incorrect
Different WordPress themes structure their HTML differently. The plugin tries to identify common patterns, but it might not work perfectly with all archived sites. You can:
- Try a different snapshot date
- Manually edit the extracted content before importing
- Use the browser's developer tools to identify the correct HTML structure in the archived page

### Images are not importing correctly
Images in archived pages can be problematic because:
- The Wayback Machine might not have archived all images
- Image URLs might be relative and difficult to resolve
- Some images might be loaded via JavaScript and not directly accessible

For best results, you may need to manually download and attach images after importing the content.

### I'm getting a timeout error when extracting content
If the archived page is large or your server has limited resources, you might experience timeout errors. Try:
- Increasing your server's PHP execution time limit
- Using a more recent snapshot that might load faster
- Breaking down the content into smaller pieces for import

## Legal Considerations

### Is it legal to import content from archived websites?
The legality depends on several factors:
- If you're recovering your own content, there's generally no issue
- If you're importing someone else's content, copyright laws may apply
- Always provide proper attribution when using others' content
- Consider reaching out to the original content owner when possible

This plugin is a tool, and users are responsible for ensuring they have the right to use the content they import.

### Does the Wayback Machine have any usage restrictions?
The Internet Archive (which runs the Wayback Machine) has its own [Terms of Use](https://archive.org/about/terms.php). This plugin is not affiliated with the Internet Archive and users should ensure their usage complies with those terms.

## Support and Development

### How do I report a bug or request a feature?
Please submit issues on the plugin's GitHub repository or contact the developer directly.

### Can I contribute to the development of this plugin?
Yes! Contributions are welcome. Please fork the repository on GitHub and submit pull requests with your improvements.

<?php
/**
 * Provide a admin area view for the plugin
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 */
?>

<div class="wrap wayback-wp-importer">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wayback-wp-importer-intro">
        <p><?php _e('This tool allows you to import content from WordPress websites archived on the Wayback Machine.', 'wayback-wp-importer'); ?></p>
        <p><?php _e('You can import a single post or multiple posts at once.', 'wayback-wp-importer'); ?></p>
    </div>
    
    <div class="nav-tab-wrapper">
        <a href="#single-post-tab" class="nav-tab nav-tab-active" id="single-post-tab-link"><?php _e('Single Post Import', 'wayback-wp-importer'); ?></a>
        <a href="#batch-import-tab" class="nav-tab" id="batch-import-tab-link"><?php _e('Batch Import', 'wayback-wp-importer'); ?></a>
    </div>
    
    <!-- Single Post Import Tab -->
    <div id="single-post-tab" class="wayback-tab-content active">
        <div class="wayback-wp-importer-form">
            <form id="wayback-extract-form">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="wayback-url"><?php _e('Wayback Machine URL', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <!-- value="https://web.archive.org/web/20250315170305/https://getlitafrica.com/2025/03/01/biodiversity-drawing-contest-2025/" -->
                                <div class="wayback-form-group">
                                    <input type="url" id="wayback-url" name="wayback_url" class="regular-text" placeholder="https://web.archive.org/web/20200101000000/https://example.com/post-name/" required value="https://web.archive.org/web/20250315170305/https://getlitafrica.com/2025/03/01/biodiversity-drawing-contest-2025/" >
                                    <p class="description"><?php _e('Enter the full Wayback Machine URL of the WordPress post.', 'wayback-wp-importer'); ?></p>
                                </div>
                                <div class="wayback-form-group">
                                    <label for="post-status"><?php _e('Import as:', 'wayback-wp-importer'); ?></label>
                                    <select id="post-status" name="post-status">
                                        <option value="publish"><?php _e('Published', 'wayback-wp-importer'); ?></option>
                                        <option value="draft"><?php _e('Draft', 'wayback-wp-importer'); ?></option>
                                        <option value="pending"><?php _e('Pending Review', 'wayback-wp-importer'); ?></option>
                                        <option value="private"><?php _e('Private', 'wayback-wp-importer'); ?></option>
                                    </select>
                                </div>
                                <div class="wayback-form-group" style="margin-top: 10px;">
                                    <label>
                                        <input type="checkbox" id="single-check-duplicates" name="check_duplicates" value="1" checked>
                                        <?php _e('Check for duplicates', 'wayback-wp-importer'); ?>
                                    </label>
                                    <p class="description"><?php _e('If a duplicate post is found, it will be indicated in the preview.', 'wayback-wp-importer'); ?></p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="post-types"><?php _e('Post Types', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <div class="wayback-form-group">
                                    <?php 
                                    $post_types = get_post_types(['public' => true], 'objects');
                                    foreach ($post_types as $post_type) :
                                        if ($post_type->name === 'attachment') continue;
                                    ?>
                                    <label>
                                        <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked($post_type->name === 'post'); ?>>
                                        <?php echo esc_html($post_type->label); ?>
                                    </label><br>
                                    <?php endforeach; ?>
                                    
                                    <div class="custom-post-type-field" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                        <label for="custom_post_type"><?php _e('Custom Post Type', 'wayback-wp-importer'); ?></label>
                                        <input type="text" id="custom_post_type" name="custom_post_type" class="regular-text" placeholder="<?php _e('e.g., product, event, portfolio', 'wayback-wp-importer'); ?>">
                                        <button type="button" id="add-custom-post-type" class="button button-secondary"><?php _e('Add', 'wayback-wp-importer'); ?></button>
                                        <p class="description"><?php _e('Add custom post types that might exist on the archived site but not on your current site.', 'wayback-wp-importer'); ?></p>
                                        <div id="custom-post-types-list" style="margin-top: 5px;"></div>
                                    </div>
                                    
                                    <p class="description"><?php _e('Select which post types to look for when scanning pages.', 'wayback-wp-importer'); ?></p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label><?php _e('Permalink Structures', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <div class="wayback-form-group">
                                    <label>
                                        <input type="checkbox" name="permalink_structures[date]" value="1" checked>
                                        <?php _e('Date-based', 'wayback-wp-importer'); ?> <span class="description">(<?php _e('e.g.', 'wayback-wp-importer'); ?> /2023/05/15/sample-post/)</span>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="permalink_structures[postname]" value="1" >
                                        <?php _e('Post name', 'wayback-wp-importer'); ?> <span class="description">(<?php _e('e.g.', 'wayback-wp-importer'); ?> /sample-post/)</span>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="permalink_structures[post_id]" value="1" >
                                        <?php _e('Post ID', 'wayback-wp-importer'); ?> <span class="description">(<?php _e('e.g.', 'wayback-wp-importer'); ?> /?p=123)</span>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="permalink_structures[custom]" value="1" >
                                        <?php _e('Custom post type', 'wayback-wp-importer'); ?> <span class="description">(<?php _e('e.g.', 'wayback-wp-importer'); ?> /product/sample-product/)</span>
                                    </label>
                                    <p class="description"><?php _e('Select which permalink structures to match when scanning pages.', 'wayback-wp-importer'); ?></p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="submit" id="extract-content-btn" class="button button-primary">
                        <?php _e('Extract Content', 'wayback-wp-importer'); ?>
                    </button>
                    <span class="spinner"></span>
                </p>
                <input type="hidden" name="import_mode" value="single_post">
                <input type="hidden" id="wayback-url-reference" name="wayback_url_reference" value="">
            </form>
        </div>
    </div>
    
    <!-- Batch Import Tab -->
    <div id="batch-import-tab" class="wayback-tab-content">
        <div class="wayback-wp-importer-form">
            <form id="wayback-batch-extract-form" onsubmit="return false;">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="batch-import-type"><?php _e('Import Type', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <select id="batch-import-type" name="batch_import_type" class="regular-text">
                                    <option value="scan_links"><?php _e('Scan Page for Blog Posts', 'wayback-wp-importer'); ?></option>
                                    <option value="entire_website"><?php _e('Entire Website', 'wayback-wp-importer'); ?></option>
                                    <option value="category"><?php _e('Category Page', 'wayback-wp-importer'); ?></option>
                                </select>
                                <p class="description"><?php _e('Select the type of batch import you want to perform.', 'wayback-wp-importer'); ?></p>
                                <div id="scan-links-description" class="import-type-description">
                                    <p><?php _e('Scan Page for Blog Posts: Enter a Wayback Machine URL of a page containing links to blog posts (e.g., a homepage, archive page, or category page). The plugin will scan the page and list all detected blog post links for selective import.', 'wayback-wp-importer'); ?></p>
                                </div>
                                <div id="entire-website-description" class="import-type-description">
                                    <p><?php _e('Entire Website: Enter a Wayback Machine URL of a website homepage. The plugin will crawl the entire website and import all detected posts.', 'wayback-wp-importer'); ?></p>
                                </div>
                                <div id="category-description" class="import-type-description">
                                    <p><?php _e('Category Page: Enter a Wayback Machine URL of a category or archive page. The plugin will extract all posts listed on that page.', 'wayback-wp-importer'); ?></p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="batch-wayback-url"><?php _e('Wayback Machine URL', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="batch-wayback-url" name="wayback_url" class="regular-text" placeholder="https://web.archive.org/web/20200101000000/https://example.com/" required>
                                <p class="description website-description"><?php _e('Enter the full Wayback Machine URL of the WordPress website homepage.', 'wayback-wp-importer'); ?></p>
                                <p class="description category-description" style="display: none;"><?php _e('Enter the full Wayback Machine URL of the WordPress category or archive page.', 'wayback-wp-importer'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="batch-post-limit"><?php _e('Post Limit', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="batch-post-limit" name="batch_post_limit" class="small-text" value="10" min="1" max="100">
                                <p class="description"><?php _e('Maximum number of posts to extract.', 'wayback-wp-importer'); ?></p>
                            </td>
                        </tr>
                        <tr id="batch-offset-row" style="display: none;">
                            <th scope="row">
                                <label for="batch-offset"><?php _e('Offset', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="batch-offset" name="batch_offset" class="small-text" value="0" min="0">
                                <p class="description"><?php _e('Number of posts to skip before starting extraction. Useful for pagination.', 'wayback-wp-importer'); ?></p>
                            </td>
                        </tr>
                        <tr id="crawl-depth-row" style="display:none;">
                            <th scope="row">
                                <label for="crawl-depth"><?php _e('Crawl Depth', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="crawl-depth" name="crawl_depth" class="small-text" value="2" min="1" max="5">
                                <p class="description"><?php _e('How many levels deep to crawl for posts (1-5).', 'wayback-wp-importer'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="batch-post-types"><?php _e('Post Types', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <div class="wayback-form-group">
                                    <?php 
                                    $post_types = get_post_types(['public' => true], 'objects');
                                    foreach ($post_types as $post_type) :
                                        if ($post_type->name === 'attachment') continue;
                                    ?>
                                    <label>
                                        <input type="checkbox" name="batch_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked($post_type->name === 'post'); ?>>
                                        <?php echo esc_html($post_type->label); ?>
                                    </label><br>
                                    <?php endforeach; ?>
                                    
                                    <div class="custom-post-type-field" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                        <label for="batch_custom_post_type"><?php _e('Custom Post Type', 'wayback-wp-importer'); ?></label>
                                        <input type="text" id="batch_custom_post_type" name="batch_custom_post_type" class="regular-text" placeholder="<?php _e('e.g., product, event, portfolio', 'wayback-wp-importer'); ?>">
                                        <button type="button" id="add-batch-custom-post-type" class="button button-secondary"><?php _e('Add', 'wayback-wp-importer'); ?></button>
                                        <p class="description"><?php _e('Add custom post types that might exist on the archived site but not on your current site.', 'wayback-wp-importer'); ?></p>
                                        <div id="batch-custom-post-types-list" style="margin-top: 5px;"></div>
                                    </div>
                                    
                                    <p class="description"><?php _e('Select which post types to look for when scanning pages.', 'wayback-wp-importer'); ?></p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label><?php _e('Permalink Structures', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <div class="wayback-form-group">
                                    <label>
                                        <input type="checkbox" name="batch_permalink_structures[date]" value="1" checked>
                                        <?php _e('Date-based', 'wayback-wp-importer'); ?> <span class="description">(<?php _e('e.g.', 'wayback-wp-importer'); ?> /2023/05/15/sample-post/)</span>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="batch_permalink_structures[postname]" value="1" checked>
                                        <?php _e('Post name', 'wayback-wp-importer'); ?> <span class="description">(<?php _e('e.g.', 'wayback-wp-importer'); ?> /sample-post/)</span>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="batch_permalink_structures[post_id]" value="1" checked>
                                        <?php _e('Post ID', 'wayback-wp-importer'); ?> <span class="description">(<?php _e('e.g.', 'wayback-wp-importer'); ?> /?p=123)</span>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="batch_permalink_structures[custom]" value="1" checked>
                                        <?php _e('Custom post type', 'wayback-wp-importer'); ?> <span class="description">(<?php _e('e.g.', 'wayback-wp-importer'); ?> /product/sample-product/)</span>
                                    </label>
                                    <p class="description"><?php _e('Select which permalink structures to match when scanning pages.', 'wayback-wp-importer'); ?></p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="batch-post-status"><?php _e('Import as:', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <select id="batch-post-status" name="post_status">
                                    <option value="publish"><?php _e('Published', 'wayback-wp-importer'); ?></option>
                                    <option value="draft"><?php _e('Draft', 'wayback-wp-importer'); ?></option>
                                    <option value="pending"><?php _e('Pending Review', 'wayback-wp-importer'); ?></option>
                                    <option value="private"><?php _e('Private', 'wayback-wp-importer'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="batch-categories"><?php _e('Categories:', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="batch-categories" name="batch_categories" class="regular-text" value="News, Fact Check, Environment">
                                <p class="description"><?php _e('Comma-separated list of categories.', 'wayback-wp-importer'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="check-duplicates"><?php _e('Duplicate Check:', 'wayback-wp-importer'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="check-duplicates" name="check_duplicates" value="1">
                                    <?php _e('Check for duplicates during extraction', 'wayback-wp-importer'); ?>
                                </label>
                                <p class="description"><?php _e('If checked, posts will be checked for duplicates during extraction. If unchecked, duplicate checking will happen in the background after extraction and duplicate posts will be highlighted in red.', 'wayback-wp-importer'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>            
                <div class="wayback-batch-import-actions">
                    <button type="submit" id="batch-extract-btn" class="button button-primary">
                        <?php _e('Extract Posts', 'wayback-wp-importer'); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            </form>
            
            <!-- Blog Post Links Container -->
            <div id="blog-post-links-container" class="wayback-blog-post-links" style="display: none;">
                <h3><?php _e('Blog Posts Found', 'wayback-wp-importer'); ?></h3>
                <p class="description"><?php _e('Click on any post to view and import it.', 'wayback-wp-importer'); ?></p>
                <div class="blog-post-links-actions">
                    <span class="spinner"></span>
                </div>
                <div id="blog-post-links-list" class="wayback-blog-post-links-list">
                    <!-- Links will be populated here -->
                </div>
            </div>
        </div>
        
        <div id="batch-results" style="display: none;">
            <h3><?php _e('Found Posts', 'wayback-wp-importer'); ?></h3>
            <div class="batch-posts-list"></div>
            <div class="batch-import-actions">
                <button type="button" id="import-selected-posts-btn" class="button button-primary">
                    <?php _e('Import Selected Posts', 'wayback-wp-importer'); ?>
                </button>
                <button type="button" id="select-all-posts-btn" class="button">
                    <?php _e('Select All', 'wayback-wp-importer'); ?>
                </button>
                <button type="button" id="deselect-all-posts-btn" class="button">
                    <?php _e('Deselect All', 'wayback-wp-importer'); ?>
                </button>
            </div>
            <div id="batch-import-progress" style="display: none;">
                <h3><?php _e('Import Progress', 'wayback-wp-importer'); ?></h3>
                <div class="progress-bar-container">
                    <div class="progress-bar"></div>
                </div>
                <p class="progress-status"></p>
            </div>
        </div>
    </div>
    
    <div id="wayback-content-preview" style="display: none;">
        <h2><?php _e('Content Preview', 'wayback-wp-importer'); ?></h2>
        
        <div class="wayback-preview-container">
            <form id="wayback-import-form">
                <div class="wayback-preview-header">
                    <div class="wayback-preview-title">
                        <h3><?php _e('Title', 'wayback-wp-importer'); ?></h3>
                        <input type="text" id="post-title" name="post_title" class="regular-text">
                    </div>
                    </div>
                    
                    <!-- Post Metadata Section -->
                    <div class="wayback-section wayback-preview-meta">
                        <h3><?php _e('Post Metadata', 'wayback-wp-importer'); ?></h3>
                        <div class="wayback-section-content">
                            <div class="wayback-meta-item">
                                <label><?php _e('Date', 'wayback-wp-importer'); ?></label>
                                <input type="text" id="post-date" name="post_date" class="regular-text">
                            </div>
                            
                            <div class="wayback-meta-item">
                                <label><?php _e('Author', 'wayback-wp-importer'); ?></label>
                                <input type="text" id="post-author" name="post_author" class="regular-text">
                            </div>
                            
                            <!-- Hidden fields to maintain backward compatibility -->
                            <input type="hidden" id="post-categories" name="post_categories">
                            <input type="hidden" id="post-tags" name="post_tags">
                        </div>
                    </div>
                    
                    <!-- Taxonomies Section -->
                    <div class="wayback-section wayback-preview-taxonomies">
                        <h3><?php _e('Taxonomies', 'wayback-wp-importer'); ?></h3>
                        <p class="wayback-section-description"><?php _e('Select from existing taxonomy terms or add new ones for the imported post:', 'wayback-wp-importer'); ?></p>
                        <div class="wayback-section-content">
                            <div class="wayback-post-type-selector">
                                <label for="post-type-selector"><?php _e('Post Type:', 'wayback-wp-importer'); ?></label>
                                <select id="post-type-selector" name="post_type">
                                    <?php 
                                    $post_types = get_post_types(array('public' => true), 'objects');
                                    foreach ($post_types as $post_type) {
                                        // Skip attachments and revisions
                                        if (in_array($post_type->name, array('attachment', 'revision'))) {
                                            continue;
                                        }
                                        echo '<option value="' . esc_attr($post_type->name) . '"' . ($post_type->name === 'post' ? ' selected' : '') . '>' . esc_html($post_type->labels->singular_name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div id="taxonomies-container" class="taxonomies-container">
                                <!-- Taxonomies will be loaded here dynamically -->
                                <div class="spinner is-active" style="float:none;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Fields Section -->
                    <div class="wayback-section wayback-preview-custom-fields">
                        <h3><?php _e('Custom Fields', 'wayback-wp-importer'); ?></h3>
                        <p class="wayback-section-description"><?php _e('Extract or manually add custom fields for the imported post:', 'wayback-wp-importer'); ?></p>
                        <div class="wayback-section-content">
                            <div class="custom-field-search">
                                <h4><?php _e('Search for Custom Fields', 'wayback-wp-importer'); ?></h4>
                                <div class="custom-field-search-input-group">
                                    <input type="text" id="custom-field-search-input" placeholder="<?php _e('Enter custom field key', 'wayback-wp-importer'); ?>">
                                    <button type="button" id="add-custom-field-search-key" class="button"><?php _e('Add Key', 'wayback-wp-importer'); ?></button>
                                </div>
                                <div id="custom-field-search-list" class="custom-field-search-list"></div>
                                <button type="button" id="extract-custom-fields-btn" class="button"><?php _e('Extract Custom Fields', 'wayback-wp-importer'); ?></button>
                            </div>
                            
                            <div class="custom-fields-container">
                                <h4><?php _e('Custom Fields to Import', 'wayback-wp-importer'); ?></h4>
                                <div id="custom-fields-list" class="custom-fields-list"></div>
                                <button type="button" id="add-custom-field-btn" class="button"><?php _e('Add Custom Field', 'wayback-wp-importer'); ?></button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Selectors Section -->
                    <div class="wayback-section wayback-preview-custom-selectors">
                        <h3><?php _e('Custom Selectors', 'wayback-wp-importer'); ?></h3>
                        <p class="wayback-section-description"><?php _e('Use CSS selectors to target and extract specific elements from the content:', 'wayback-wp-importer'); ?></p>
                        <div class="wayback-section-content">
                            <div class="custom-selectors-container">
                                <div class="custom-field-selectors-input">
                                    <select id="selector-type">
                                        <option value="css"><?php _e('CSS Selector', 'wayback-wp-importer'); ?></option>
                                        <option value="class"><?php _e('Class', 'wayback-wp-importer'); ?></option>
                                        <option value="id"><?php _e('ID', 'wayback-wp-importer'); ?></option>
                                        <option value="tag"><?php _e('HTML Tag', 'wayback-wp-importer'); ?></option>
                                        <option value="data"><?php _e('Data Attribute', 'wayback-wp-importer'); ?></option>
                                    </select>
                                    <input type="text" id="custom-selector-input" placeholder="<?php _e('Enter selector (e.g. .meta-field, #author, article, [data-key])', 'wayback-wp-importer'); ?>">
                                    <select id="attribute-type">
                                        <option value=""><?php _e('Extract Text', 'wayback-wp-importer'); ?></option>
                                        <option value="href"><?php _e('href (links)', 'wayback-wp-importer'); ?></option>
                                        <option value="src"><?php _e('src (images, scripts)', 'wayback-wp-importer'); ?></option>
                                        <option value="alt"><?php _e('alt (images)', 'wayback-wp-importer'); ?></option>
                                        <option value="title"><?php _e('title', 'wayback-wp-importer'); ?></option>
                                        <option value="value"><?php _e('value (inputs)', 'wayback-wp-importer'); ?></option>
                                        <option value="custom"><?php _e('Custom Attribute...', 'wayback-wp-importer'); ?></option>
                                    </select>
                                    <input type="text" id="custom-attribute-input" placeholder="<?php _e('Custom attribute name', 'wayback-wp-importer'); ?>" style="display: none;">
                                    <button type="button" id="add-custom-selector" class="button"><?php _e('Add Selector', 'wayback-wp-importer'); ?></button>
                                </div>
                                <div id="custom-field-selectors-list" class="custom-field-selectors-list"></div>
                                <div style="margin-top: 10px;">
                                    <button type="button" id="extract-custom-selectors" class="button"><?php _e('Extract Using Selectors', 'wayback-wp-importer'); ?></button>
                                </div>
                                <div id="custom-selectors-preview" class="custom-selectors-preview" style="margin-top: 15px;"></div>
                                <div style="margin-top: 10px;" class="custom-selectors-actions">
                                    <button type="button" id="add-selectors-to-custom-fields" class="button" style="display: none;"><?php _e('Add Selected to Custom Fields', 'wayback-wp-importer'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                
                
                <div class="wayback-preview-featured-image">
                    <h3><?php _e('Featured Image', 'wayback-wp-importer'); ?></h3>
                    <div id="featured-image-preview"></div>
                    <input type="hidden" id="featured-image-url" name="featured_image_url">
                    <div class="wayback-image-actions">
                        <button type="button" id="upload-custom-image" class="button button-secondary">
                            <?php _e('Upload New Image', 'wayback-wp-importer'); ?>
                        </button>
                        <button type="button" id="reset-featured-image" class="button button-secondary" style="display:none;">
                            <?php _e('Reset to Original', 'wayback-wp-importer'); ?>
                        </button>
                        <p class="description"><?php _e('You can use the original image from Wayback Machine or upload a new one.', 'wayback-wp-importer'); ?></p>
                    </div>
                </div>
                
                <div class="wayback-preview-content">
                    <h3><?php _e('Content', 'wayback-wp-importer'); ?></h3>
                    <?php wp_editor('', 'post-content', array('media_buttons' => false)); ?>
                </div>
                
                <div class="wayback-preview-excerpt">
                    <h3><?php _e('Excerpt', 'wayback-wp-importer'); ?></h3>
                    <textarea id="post-excerpt" name="post_excerpt" rows="3" class="large-text"></textarea>
                </div>
                
                <!-- Old Custom Fields section removed to avoid duplication -->
                
                <div class="wayback-preview-comments" style="display: none;">
                    <h3><?php _e('Comments', 'wayback-wp-importer'); ?></h3>
                    <div id="comments-preview"></div>
                    <label>
                        <input type="checkbox" id="import-comments" name="import_comments">
                        <?php _e('Import comments', 'wayback-wp-importer'); ?>
                    </label>
                </div>
                
                <div class="wayback-import-actions">
                    <button type="button" id="import-post-btn" class="button button-primary">
                        <?php _e('Import Post', 'wayback-wp-importer'); ?>
                    </button>
                    <button type="button" id="export-csv-btn" class="button">
                        <?php _e('Export to CSV', 'wayback-wp-importer'); ?>
                    </button>
                    <button type="button" id="reset-form-btn" class="button">
                        <?php _e('Reset', 'wayback-wp-importer'); ?>
                    </button>
                    <button type="button" id="back-to-list-btn" class="button" style="display: none;">
                        <?php _e('Back to List', 'wayback-wp-importer'); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            </form>
        </div>
    </div>
    
    <div id="wayback-import-result" style="display: none;">
        <div class="notice notice-success">
            <p id="import-result-message"></p>
            <p><a id="edit-post-link" href="#" class="button"><?php _e('Edit Post', 'wayback-wp-importer'); ?></a></p>
        </div>
    </div>
</div>

/**
 * Admin JavaScript for Wayback WordPress Importer.
 *
 * Handles all the AJAX interactions and UI updates for the admin interface.
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 */

(function($) {
  // Make sure we're using jQuery properly
  'use strict';

   // Global variables to store extraction data
   window.waybackExtractData = {
    waybackUrl: '',
    fullHtmlContent: '',
    extractedData: null
  };

  /**
   * Escape HTML special characters in a string
   *
   * @param {string} text The text to escape
   * @return {string} The escaped text
   */
  function escapeHtml(text) {
    if (!text) return "";

    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Initialize the admin functionality.
   */
  function init() {
    console.log("Initializing Wayback WP Importer admin JS...");

    // Tab switching
    $("#single-post-tab-link").on("click", function (e) {
      e.preventDefault();
      switchTab("single-post");
    });

    $("#batch-import-tab-link").on("click", function (e) {
      e.preventDefault();
      switchTab("batch-import");
    });

    // Handle batch import type changes
    $("#batch-import-type").on("change", function () {
      updateBatchImportUI($(this).val());
    });

    // Initialize batch import UI
    updateBatchImportUI($("#batch-import-type").val());
  
    // Trigger change event to set initial state of offset field
    $("#batch-import-type").trigger("change");

    // Single post extract form submission
    $("#wayback-extract-form").on("submit", function (e) {
      e.preventDefault();
      extractContent();
    });

    // Batch extract form submission
    $("#wayback-batch-extract-form").on("submit", function (e) {
      e.preventDefault();
      extractBatchContent();
    });

    // Direct click handler for batch extract button
    $("#batch-extract-btn").on("click", function (e) {
      e.preventDefault();
      console.log("Batch extract button clicked");
      extractBatchContent();
    });

    // Import selected posts button
    $("#import-selected-posts-btn").on("click", function () {
      importSelectedPosts();
    });

    // Select all posts button
    $("#select-all-posts-btn").on("click", function () {
      $(".batch-post-checkbox").prop("checked", true);
    });

    // Deselect all posts button
    $("#deselect-all-posts-btn").on("click", function () {
      $(".batch-post-checkbox").prop("checked", false);
    });

    // Custom fields handlers
    $("#add-custom-field-btn").on("click", function () {
      addCustomFieldRow();
    });

    // Add custom field search key
    $(".add-custom-field-search").on("click", function () {
      addCustomFieldSearchKey();
    });

    // Extract custom fields button is now handled in wayback-wp-importer-admin-custom-fields.js

    // Setup other import buttons
    setupImportButtons();

    console.log("All event handlers initialized");
  }

  /**
   * Switch between tabs
   *
   * @param {string} tabId The ID of the tab to switch to
   */
  function switchTab(tabId) {
    console.log("Switching to tab: " + tabId);

    // Hide all tab contents
    $(".wayback-tab-content").hide().removeClass("active");

    // Show the selected tab content
    $("#" + tabId + "-tab")
      .show()
      .addClass("active");

    // Update tab links
    $(".nav-tab").removeClass("nav-tab-active");
    $("#" + tabId + "-tab-link").addClass("nav-tab-active");

    // Log the visibility state after switching
    console.log(
      "Tab visibility after switch: " + $("#" + tabId + "-tab").is(":visible")
    );
    console.log("Tab display style: " + $("#" + tabId + "-tab").attr("style"));

    // Force remove any inline style that might be overriding our CSS
    $("#" + tabId + "-tab").attr("style", "");
  }

  /**
   * Update the batch import UI based on the selected import type.
   *
   * @param {string} type The selected batch import type
   */
  function updateBatchImportUI(type) {
    // Hide all type-specific elements
    $(".website-description, .category-description").hide();
    $(".website-option").hide();
    $(".import-type-description").hide();

    // Handle batch import type change
    $("#batch-import-type").on("change", function () {
      const importType = $(this).val();
      
      // Hide all descriptions first
      $(".import-type-description").hide();
      
      // Show the relevant description
      if (importType === "scan_links") {
        $("#scan-links-description").show();
        $("#crawl-depth-row").show();
        $("#batch-offset-row").hide();
      } else if (importType === "entire_website") {
        $("#entire-website-description").show();
        $("#crawl-depth-row").show();
        $("#batch-offset-row").show();
      } else if (importType === "category") {
        $("#category-description").show();
        $("#crawl-depth-row").hide();
        $("#batch-offset-row").show();
      }
    });

    // Show type-specific elements
    if (type === "scan_links") {
      // For scan_links mode, we just need the URL
      $(".website-option").hide();
      $("#batch-extract-btn").text("Scan for Blog Posts");
      $("#scan-links-description").show();
    } else if (type === "entire_website") {
      $(".website-description").show();
      $(".website-option").show();
      $("#entire-website-description").show();
    } else if (type === "category") {
      $(".category-description").show();
      $("#category-description").show();
    }
  }

  /**
   * Extract content from a batch of posts
   */
  function extractBatchContent() {
    console.log("Extracting batch content...");

    // Get form data
    const waybackUrl = $("#batch-wayback-url").val().trim();
    const batchImportType = $("#batch-import-type").val();
    const postLimit = $("#batch-post-limit").val();
    const crawlDepth = $("#batch-crawl-depth").val() || 1;
    const checkDuplicates = $("#check-duplicates").prop("checked");
    const offset = $("#batch-offset").val() || 0;

    // Show loading spinner
    $("#wayback-batch-extract-form .spinner").addClass("is-active");

    // Disable submit button
    $("#batch-extract-btn").prop("disabled", true);

    // Hide previous results
    $("#batch-results").hide();
    $(".batch-posts-list").empty();

    // Determine the correct import mode
    let importMode = batchImportType;
    if (!importMode) {
      // For backwards compatibility with radio buttons
      importMode =
        batchImportType === "entire_website" ? "entire_website" : "category";
    }

    console.log("Batch import parameters:", {
      type: batchImportType,
      url: waybackUrl,
      limit: postLimit,
      depth: crawlDepth,
      mode: importMode,
      offset: offset,
    });

    if (!waybackUrl) {
      showError("Please enter a valid Wayback Machine URL.");
      $("#wayback-batch-extract-form .spinner").removeClass("is-active");
      $("#batch-extract-content-btn").prop("disabled", false);
      return;
    }

    // Make AJAX request
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_extract_content",
        nonce: wayback_wp_importer.nonce,
        wayback_url: waybackUrl,
        import_mode: importMode,
        post_limit: postLimit,
        crawl_depth: crawlDepth,
        check_duplicates: checkDuplicates ? 1 : 0,
        offset: offset,
      },
      beforeSend: function () {
        console.log("Sending AJAX request to extract content...");
      },
      success: function (response) {
        console.log("AJAX response received:", response);

        // Hide loading spinner
        $("#wayback-batch-extract-form .spinner").removeClass("is-active");

        // Enable submit button
        $("#batch-extract-content-btn").prop("disabled", false);
        //Enable button
        $("#batch-extract-btn").prop("disabled", false);

        if (response.success) {
          if (importMode === "scan_links") {
            if (response.data.blog_post_links) {
              // Display the list of blog post links
              displayBlogPostLinks(response.data.blog_post_links, response.data.duplicates_skipped);
              
              // Start background duplicate checking if immediate checking was not enabled
              if (!checkDuplicates && typeof window.waybackDuplicateChecker !== 'undefined') {
                setTimeout(function() {
                  window.waybackDuplicateChecker.checkDuplicatesInBackground(response.data.blog_post_links);
                }, 1000); // Start after a short delay to allow the UI to render
              }
            }
          } else if (response.data.posts) {
            // Display the list of found posts for other modes
            displayBatchPosts(response.data.posts, response.data.duplicates_skipped);
            
            // Start background duplicate checking if immediate checking was not enabled
            if (!checkDuplicates && typeof window.waybackDuplicateChecker !== 'undefined') {
              setTimeout(function() {
                window.waybackDuplicateChecker.checkDuplicatesInBackground(response.data.posts);
              }, 1000); // Start after a short delay to allow the UI to render
            }
          } else {
            showError("No content found.");
          }
        } else {
          showError(response.data.message || "Failed to extract content.");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", { xhr, status, error });

        // Hide loading spinner
        $("#wayback-batch-extract-form .spinner").removeClass("is-active");

        // Enable submit button
        $("#batch-extract-content-btn").prop("disabled", false);

        showError(
          "AJAX error: " + error + ". Check browser console for details."
        );
      },
    });
  }

  /**
   * Display the list of blog post links found during scanning
   *
   * @param {Array} links List of blog post links with URLs and titles
   * @param {Number} duplicatesSkipped Optional number of duplicates that were filtered out
   */
  function displayBlogPostLinks(links, duplicatesSkipped) {
    console.log("Displaying blog post links:", links);

    // Clear any previous links
    $("#blog-post-links-list").empty();

    if (!links || links.length === 0) {
      $("#blog-post-links-list").html(
        "<p>No blog posts found on this page.</p>"
      );
      $("#blog-post-links-container").show();
      return;
    }

    // Create a list of links
    const $linksList = $('<ul class="wayback-blog-links"></ul>');

    // Add each link to the list
    $.each(links, function (index, link) {
      console.log("[DEBUG] Processing link:", link);
      console.log("[DEBUG] Link URL type:", typeof link.url);
      console.log("[DEBUG] Link URL value:", link.url);
      
      const $listItem = $('<li class="wayback-blog-link-item"></li>');
      const $linkElement = $('<a href="#" class="wayback-blog-link"></a>')
        .text(link.title || link.url)
        .data("url", link.url)
        .on("click", function (e) {
          e.preventDefault();
          
          // Debug the URL structure
          console.log("[DEBUG] Link clicked, link object:", link);
          console.log("[DEBUG] Link URL type:", typeof link.url);
          console.log("[DEBUG] Link URL structure:", link.url);
          
          // Determine the correct URL to use
          let urlToUse;
          if (typeof link.url === 'string') {
            urlToUse = link.url;
            console.log("[DEBUG] Using string URL:", urlToUse);
          } else if (link.url && typeof link.url === 'object') {
            if (link.url.url) {
              urlToUse = link.url.url;
              console.log("[DEBUG] Using nested URL object (link.url.url):", urlToUse);
            } else {
              urlToUse = link.url;
              console.log("[DEBUG] Using URL object directly:", urlToUse);
            }
          } else {
            console.error("[DEBUG] Could not determine URL from link:", link);
            return;
          }
          
          extractSinglePostFromLink(urlToUse);
        });

      $listItem.append($linkElement);
      $linksList.append($listItem);
    });

    // Add the list to the container
    $("#blog-post-links-list").append($linksList);

    // Update the heading to include the count and duplicates skipped if available
    let countText = links.length === 1 ? "1 Blog Post Found" : links.length + " Blog Posts Found";
    
    // Add duplicate information if available
    if (duplicatesSkipped && duplicatesSkipped > 0) {
      countText += " (" + duplicatesSkipped + " duplicate" + (duplicatesSkipped === 1 ? "" : "s") + " skipped)";
    }
    
    $("#blog-post-links-container h3").text(countText);

    // Show the container
    $("#blog-post-links-container").show();
  }

  /**
   * Extract a single post from a blog post link
   *
   * @param {string} url The Wayback Machine URL of the blog post
   */
  function extractSinglePostFromLink(url) {
    // Store the current state so we can go back
    sessionStorage.setItem("wayback_previous_tab", "batch-import");
    sessionStorage.setItem("wayback_has_blog_links", "true");
    console.log("Extracting content from blog post link:", url);

    // Show loading spinner
    $("#blog-post-links-container .spinner").addClass("is-active");

    // Make AJAX request to extract the post content
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_extract_content",
        nonce: wayback_wp_importer.nonce,
        wayback_url: url,
        import_mode: "single_post",
      },
      success: function (response) {
        // Hide loading spinner
        $("#blog-post-links-container .spinner").removeClass("is-active");

        if (response.success) {
          // Check if HTML content is in the response
          console.log("[DEBUG] HTML content in AJAX response:", !!response.data.html_content);
          if (response.data.html_content) {
            console.log("[DEBUG] HTML content length:", response.data.html_content.length);
          }
          
          // Switch to the single post tab
          switchTab("single-post");

          // Display the post content in the preview section
          displayContentPreview(response.data);
          
        } else {
          showError(response.data.message || "Failed to extract post content.");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", { xhr, status, error });
        // Hide loading spinner
        $("#blog-post-links-container .spinner").removeClass("is-active");
        showError("An error occurred while extracting the post content.");
      },
    });

  // Show the container
  $("#blog-post-links-container").show();
}

/**
 * Display the list of posts found in batch extraction
 *
 * @param {Array} posts List of posts
 * @param {Number} duplicatesSkipped Number of duplicate posts that were skipped
 */
function displayBatchPosts(posts, duplicatesSkipped) {
  if (!posts || posts.length === 0) {
    showError("No posts found. Try adjusting your search parameters.");
    return;
  }
  
  // Show notification if duplicates were skipped
  if (duplicatesSkipped && duplicatesSkipped > 0) {
    const notice = $(`<div class="notice notice-info"><p><strong>${duplicatesSkipped}</strong> duplicate post(s) were filtered out. Uncheck "Check duplicates" during extraction to see all posts.</p></div>`);
    $("#batch-results").before(notice);
  }

  // Clear previous results
  $(".batch-posts-list").empty();
  
  // Store the posts data globally for later use
  window.foundPosts = posts;
  
  // Check if we need to do background duplicate checking
  const checkDuplicates = $("#check-duplicates").prop("checked");
  const needBackgroundCheck = !checkDuplicates && posts.some(post => !post.duplicateChecked);
  
  // Create table to display posts
  let tableHtml = `
    <table class="wp-list-table widefat fixed striped posts">
      <thead>
        <tr>
          <th class="check-column"><input type="checkbox" id="select-all-batch"></th>
          <th>Title</th>
          <th>Date</th>
          <th>URL</th>
          <th>Duplicate</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
  `;

  // Add each post to the table
  posts.forEach((post, index) => {
    // Determine duplicate status and styling
    const isDuplicate = post.isDuplicate === true;
    const duplicateChecked = post.duplicateChecked === true;
    let statusText = duplicateChecked ? (isDuplicate ? 'Duplicate' : 'Unique') : 'Not checked';
    let rowStyle = isDuplicate ? ' style="background-color: #ffdddd;"' : '';
    let rowClass = isDuplicate ? ' class="duplicate-post"' : '';
    
    tableHtml += `
      <tr data-post-index="${index}"${rowClass}${rowStyle}>
        <td><input type="checkbox" name="selected_posts[]" value="${index}" class="post-checkbox" ${isDuplicate ? '' : 'checked'}></td>
        <td>${post.title || "Untitled"}</td>
        <td>${post.date || "Unknown"}</td>
        <td><a href="${post.wayback_url}" target="_blank">${typeof post.wayback_url === 'string' ? post.wayback_url.substring(0, 50) + '...' : post.wayback_url}</a></td>
        <td class="column-duplicate">${statusText}${isDuplicate && post.duplicateId ? '<br><small>ID: ' + post.duplicateId + '</small>' : ''}</td>
        <td>
          <button type="button" class="button preview-post" data-index="${index}">Preview</button>
        </td>
      </tr>
    `;
  });

  tableHtml += `
      </tbody>
    </table>
    <div class="tablenav bottom">
      <div class="alignleft actions">
        <button type="button" id="import-selected-posts-btn" class="button button-primary">Import Selected</button>
        <button type="button" id="export-selected-posts-btn" class="button">Export Selected to CSV</button>
      </div>
    </div>
  `;

  // Display the table
  $(".batch-posts-list").html(tableHtml);
  $("#batch-results").show();

  // Add event handlers
  $("#select-all-batch").on("change", function() {
    $(".post-checkbox").prop("checked", $(this).prop("checked"));
  });

  $(".preview-post").on("click", function() {
    const index = $(this).data("index");
    previewPost(posts[index]);
  });

  $("#import-selected-posts-btn").on("click", function() {
    importSelectedBatchPosts();
  });

  $("#export-selected-posts-btn").on("click", function() {
    exportSelectedBatchPosts();
  });
  
  // Start background duplicate checking if needed
  startBackgroundDuplicateChecking(posts);

    // Import all button click
    $("#import-all-btn").on("click", function () {
      importAllPosts();
    });

    // Export CSV button click
    $("#export-csv-btn").on("click", function () {
      exportCSV();
    });

    // Reset form button click
    $("#reset-form-btn").on("click", function () {
      resetForm();
    });
  }

  /**
   * Extract content from a single post URL
   */
  function extractSinglePostContent() {
    console.log("Extracting single post content...");
    
    // Get the Wayback URL from the form
    const waybackUrl = $("#wayback-url").val().trim();
    
    // Check if URL is empty
    if (!waybackUrl) {
      showError("Please enter a valid Wayback Machine URL.");
      return;
    }
    
    // Get duplicate checking preference
    const checkDuplicates = $("#single-check-duplicates").prop("checked");
    
    // Show loading spinner
    $("#wayback-extract-form .spinner").addClass("is-active");
    
    // Disable submit button
    $("#extract-content-btn").prop("disabled", true);
    
    // Hide any previous content preview
    $("#wayback-content-preview").hide();
    
    // Prepare data for AJAX request
    const data = {
      action: "wayback_extract_content",
      wayback_url: waybackUrl,
      import_mode: "single_post",
      check_duplicates: checkDuplicates,
      nonce: wayback_wp_importer.nonce
    };
    
    // Send AJAX request
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: data,
      success: function(response) {
        console.log("responsessssss" , response);
        
        // Hide loading spinner
        $("#wayback-extract-form .spinner").removeClass("is-active");
        
        // Enable submit button
        $("#extract-content-btn").prop("disabled", false);
        
        if (response.success) {
          // Display the content preview
          displayContentPreview(response.data);
        } else {
          // Show error message
          showError(response.data.message || "Failed to extract post content.");
        }
      },
      error: function(xhr, status, error) {
        console.error("AJAX error:", { xhr, status, error });
        
        // Hide loading spinner
        $("#wayback-extract-form .spinner").removeClass("is-active");
        
        // Enable submit button
        $("#extract-content-btn").prop("disabled", false);
        
        // Show error message
        showError("An error occurred while extracting the post content.");
      }
    });
  }

  /**
   * Setup import button event handlers
   */
  function setupImportButtons() {
    // Single post extract button
    $("#extract-content-btn").on("click", function(e) {
      e.preventDefault();
      extractSinglePostContent();
    });

    // Single post import button
    $("#import-post-btn").on("click", function(e) {
      e.preventDefault();
      importPost();
    });

    // Single post export to CSV button
    $("#export-csv-btn").on("click", function(e) {
      e.preventDefault();
      exportCSV();
    });

    // Batch extract button
    $("#batch-extract-btn").on("click", function(e) {
      e.preventDefault();
      extractBatchContent();
    });

    // Import selected posts button (for batch results)
    $(document).on("click", "#import-selected-posts-btn", function() {
      importSelectedBatchPosts();
    });

    // Export selected posts button (for batch results)
    $(document).on("click", "#export-selected-posts-btn", function() {
      exportSelectedBatchPosts();
    });

    console.log("Import buttons initialized");
  }

  /**
   * Update the UI based on the selected import mode.
   *
   * @param {string} mode The selected import mode.
   */
  function updateImportModeUI(mode) {
    // Hide all description texts and optional rows
    $(
      "#url-description-single, #url-description-website, #url-description-category"
    ).hide();
    $("#post-limit-row, #crawl-depth-row").hide();

    // Show the appropriate description text and optional rows based on mode
    switch (mode) {
      case "single_post":
        $("#url-description-single").show();
        break;
      case "entire_website":
        $("#url-description-website").show();
        $("#post-limit-row, #crawl-depth-row").show();
        break;
      case "category":
        $("#url-description-category").show();
        $("#post-limit-row").show();
    }
  }

  /**
   * Display a list of posts found from website or category crawling.
   *
   * @param {Object} data The response data containing found posts.
   */
  function displayPostsList(data) {
    console.log("Display posts list data:", data);

    // Handle case where we might have a single post object instead of a posts array
    if (data && data.title && data.content && !data.posts) {
      console.log(
        "Single post data detected in displayPostsList, redirecting to preview"
      );
      // This appears to be a single post, so display it as a preview instead
      displayContentPreview(data);
      return;
    }

    // Check if posts data exists and is an array
    if (!data || !data.posts || !Array.isArray(data.posts)) {
      showError("No posts found or invalid response format.");
      console.error("Invalid posts data:", data);
      return;
    }
    
    // Store the posts data globally for later use
    window.foundPosts = data.posts;

    // Hide the form
    $(".wayback-wp-importer-form").hide();

    // Create posts list HTML
    let postsListHtml = `
            <div id="posts-list">
                <h3>Found Posts (${data.posts.length})</h3>
                <p>The following posts were found. Select the ones you want to import.</p>
                
                <div class="select-actions">
                    <button type="button" class="button select-all">Select All</button>
                    <button type="button" class="button select-none">Select None</button>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="check-column"><input type="checkbox" id="select-all-posts"></th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>URL</th>
                            <th>Duplicate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

    // Add each post to the table
    data.posts.forEach((post, index) => {
      // Determine duplicate status and styling
      const isDuplicate = post.isDuplicate === true;
      const duplicateChecked = post.duplicateChecked === true;
      let statusText = duplicateChecked ? (isDuplicate ? 'Duplicate' : 'Unique') : 'Not checked';
      let rowStyle = isDuplicate ? ' style="background-color: #ffdddd;"' : '';
      let rowClass = isDuplicate ? ' class="duplicate-post"' : '';
      
      postsListHtml += `
                <tr data-post-index="${index}"${rowClass}${rowStyle}>
                    <td><input type="checkbox" name="selected_posts[]" value="${index}" class="post-checkbox"></td>
                    <td>${post.title || "Untitled"}</td>
                    <td>${post.date || "Unknown"}</td>
                    <td><a href="${
                      post.wayback_url
                    }" target="_blank">${post.wayback_url.substring(0, 50)}...</a></td>
                    <td class="column-duplicate">${statusText}${isDuplicate && post.duplicateId ? '<br><small>ID: ' + post.duplicateId + '</small>' : ''}</td>
                    <td>
                        <button type="button" class="button preview-post" data-index="${index}">Preview</button>
                    </td>
                </tr>
            `;
    });

    postsListHtml += `
                    </tbody>
                </table>
                
                <div class="bulk-actions">
                    <button type="button" id="import-selected-btn" class="button button-primary">Import Selected</button>
                    <button type="button" id="export-selected-btn" class="button">Export Selected to CSV</button>
                    <button type="button" id="reset-form-btn" class="button">Start Over</button>
                </div>
            </div>
        `;

    // Display the posts list
    $("#content-preview").html(postsListHtml).show();

    // Store the posts data for later use
    window.foundPosts = data.posts;

    // Add event handlers for the new buttons
    $(".select-all").on("click", function () {
      $(".post-checkbox").prop("checked", true);
      $("#select-all-posts").prop("checked", true);
    });

    $(".select-none").on("click", function () {
      $(".post-checkbox").prop("checked", false);
      $("#select-all-posts").prop("checked", false);
    });

    $("#select-all-posts").on("change", function () {
      $(".post-checkbox").prop("checked", $(this).prop("checked"));
    });

    $(".preview-post").on("click", function () {
      const index = $(this).data("index");
      previewSinglePost(window.foundPosts[index]);
    });

    $("#import-selected-btn").on("click", function () {
      importSelectedPosts();
    });

    $("#export-selected-btn").on("click", function () {
      exportSelectedPosts();
    });

    $("#reset-form-btn").on("click", function () {
      resetForm();
    });
  }

  /**
   * Import selected posts from the batch results
   */
  function importSelectedBatchPosts() {
    const selectedPosts = [];
    
    // Get all checked checkboxes
    $(".post-checkbox:checked").each(function() {
      const index = $(this).val();
      if (window.foundPosts && window.foundPosts[index]) {
        selectedPosts.push(window.foundPosts[index]);
      }
    });
    
    if (selectedPosts.length === 0) {
      showError("Please select at least one post to import.");
      return;
    }
    
    // Show loading spinner
    const spinner = $('<span class="spinner is-active" style="float:none;margin:0 10px;"></span>');
    $("#import-selected-posts-btn").after(spinner);
    $("#import-selected-posts-btn").prop("disabled", true);
    
    // Create a status container
    const statusContainer = $('<div class="import-status"></div>');
    $(".batch-posts-list").after(statusContainer);
    
    // Start importing posts one by one
    importPostsSequentially(selectedPosts, 0, statusContainer, function() {
      // All done
      spinner.remove();
      $("#import-selected-posts-btn").prop("disabled", false);
      statusContainer.append('<div class="notice notice-success"><p>All selected posts have been imported!</p></div>');
    });
  }
  
  /**
   * Import posts one by one sequentially
   * 
   * @param {Array} posts Array of posts to import
   * @param {number} index Current index
   * @param {jQuery} statusContainer Container to show status
   * @param {Function} callback Function to call when all done
   */
  function importPostsSequentially(posts, index, statusContainer, callback) {
    if (index >= posts.length) {
      callback();
      return;
    }
    
    const post = posts[index];
    const statusItem = $(`<div class="import-status-item"><strong>${post.title || 'Untitled'}</strong>: Importing...</div>`);
    statusContainer.append(statusItem);
    
    // Make AJAX request to import the post
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: 'POST',
      data: {
        action: 'wayback_import_post',
        nonce: wayback_wp_importer.nonce,
        wayback_url: post.wayback_url,
        post_status: $("#batch-post-status").val() || 'draft'
      },
      success: function(response) {
        if (response.success) {
          statusItem.html(`<strong>${post.title || 'Untitled'}</strong>: <span class="success">Imported successfully! ID: ${response.data.post_id}</span>`);
        } else {
          statusItem.html(`<strong>${post.title || 'Untitled'}</strong>: <span class="error">Failed: ${response.data.message || 'Unknown error'}</span>`);
        }
        
        // Import next post
        setTimeout(function() {
          importPostsSequentially(posts, index + 1, statusContainer, callback);
        }, 500);
      },
      error: function() {
        statusItem.html(`<strong>${post.title || 'Untitled'}</strong>: <span class="error">Failed: Server error</span>`);
        
        // Import next post
        setTimeout(function() {
          importPostsSequentially(posts, index + 1, statusContainer, callback);
        }, 500);
      }
    });
  }
  
  /**
   * Export selected posts to CSV
   */
  function exportSelectedBatchPosts() {
    const selectedPosts = [];
    
    // Get all checked checkboxes
    $(".post-checkbox:checked").each(function() {
      const index = $(this).val();
      if (window.foundPosts && window.foundPosts[index]) {
        selectedPosts.push(window.foundPosts[index]);
      }
    });
    
    if (selectedPosts.length === 0) {
      showError("Please select at least one post to export.");
      return;
    }
    
    // Create CSV content
    let csvContent = "Title,URL,Date\n";
    
    selectedPosts.forEach(function(post) {
      const title = post.title ? post.title.replace(/"/g, '""') : 'Untitled';
      csvContent += `"${title}","${post.wayback_url}","${post.date || ''}"\n`;
    });
    
    // Create a download link
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", "wayback-posts.csv");
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  /**
   * Start background duplicate checking for a list of posts
   * 
   * @param {Array} posts The posts to check for duplicates
   */
  function startBackgroundDuplicateChecking(posts) {
    // Check if we need to do background duplicate checking
    const checkDuplicates = $("#check-duplicates").prop("checked");
    const needBackgroundCheck = !checkDuplicates && posts.some(post => !post.duplicateChecked);
    
    // If we need to do background duplicate checking, show a notification and start the process
    if (needBackgroundCheck && typeof window.waybackDuplicateChecker !== 'undefined') {
      // Create a notification for background duplicate checking if it doesn't exist
      if ($("#duplicate-check-notice").length === 0) {
        const notice = $('<div id="duplicate-check-notice" class="notice notice-info"><p>Background duplicate checking will start in a moment...</p></div>');
        $("#batch-results").before(notice);
      }
      
      // Start the background duplicate checking after a short delay
      setTimeout(function() {
        window.waybackDuplicateChecker.checkDuplicatesInBackground(posts);
      }, 1000); // Start after a short delay to allow the UI to render
    }
  }

  /**
   * Preview a single post from the list.
   *
   * @param {Object} post The post data to preview.
   */
  function previewSinglePost(post) {
    // Display the post preview similar to displayContentPreview
    displayContentPreview(post);
  }

  /**
   * Import selected posts from the list.
   */
  function importSelectedPosts() {
    const selectedIndexes = [];

    $(".post-checkbox:checked").each(function () {
      selectedIndexes.push(parseInt($(this).val()));
    });

    if (selectedIndexes.length === 0) {
      showError("Please select at least one post to import.");
      return;
    }

    // Get the selected post status
    const postStatus = $("#batch-post-status").val();

    // Get the categories
    const categories = $("#batch-categories").val();

    // Create progress bar
    const progressContainer = $(
      '<div class="import-progress-container"><div class="import-progress-bar"></div><div class="import-progress-text">0/' +
        selectedIndexes.length +
        " posts imported</div></div>"
    );
    $("#posts-list-container").before(progressContainer);

    // Show loading spinner
    showLoading(true);

    // Disable buttons
    $("#import-selected-btn, #export-selected-btn, #reset-form-btn").prop(
      "disabled",
      true
    );

    // Prepare selected posts data
    const selectedPosts = selectedIndexes.map(
      (index) => window.foundPosts[index]
    );

    // Make AJAX request to import multiple posts
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_import_multiple",
        nonce: wayback_wp_importer.nonce,
        posts: JSON.stringify(selectedPosts),
        post_status: postStatus,
        categories: categories,
      },
      xhr: function () {
        const xhr = new window.XMLHttpRequest();
        // Setup progress tracking
        xhr.upload.addEventListener(
          "progress",
          function (evt) {
            if (evt.lengthComputable) {
              const percentComplete = (evt.loaded / evt.total) * 50; // First 50% is upload
              $(".import-progress-bar").css("width", percentComplete + "%");
              $(".import-progress-text").text(
                "Uploading data: " + Math.round(percentComplete) + "%"
              );
            }
          },
          false
        );
        return xhr;
      },
      success: function (response) {
        showLoading(false);
        $("#import-selected-btn, #export-selected-btn, #reset-form-btn").prop(
          "disabled",
          false
        );

        if (response.success) {
          // Update progress bar to 100%
          $(".import-progress-bar").css("width", "100%");
          $(".import-progress-text").text(
            `${response.data.imported}/${selectedPosts.length} posts imported`
          );

          // Show success message with import results
          showSuccess(
            `Successfully imported ${response.data.imported} out of ${selectedPosts.length} posts.`
          );

          // Mark imported posts in the list
          response.data.imported_indexes.forEach((index) => {
            $(`.post-checkbox[value="${index}"]`)
              .closest("tr")
              .addClass("imported-success");
          });

          // If there were failures, show them
          if (response.data.failed && response.data.failed.length > 0) {
            const failureMessages = response.data.failed
              .map(
                (failure) => `Post at index ${failure.index}: ${failure.error}`
              )
              .join("<br>");

            $(
              '<div class="notice notice-warning is-dismissible"><p>Some posts could not be imported:</p><ul><li>' +
                failureMessages.replace(/<br>/g, "</li><li>") +
                "</li></ul></div>"
            ).insertAfter(".import-progress-container");
          }
        } else {
          // Remove progress bar
          $(".import-progress-container").remove();
          showError(response.data.message || "Failed to import posts.");
        }
      },
      error: function () {
        showLoading(false);
        $("#import-selected-btn, #export-selected-btn, #reset-form-btn").prop(
          "disabled",
          false
        );
        showError("An error occurred while communicating with the server.");
      },
    });
  }

  /**
   * Export selected posts to CSV.
   */
  function exportSelectedPosts() {
    const selectedIndexes = [];

    $(".post-checkbox:checked").each(function () {
      selectedIndexes.push(parseInt($(this).val()));
    });

    if (selectedIndexes.length === 0) {
      showError("Please select at least one post to export.");
      return;
    }

    // Prepare selected posts data
    const selectedPosts = selectedIndexes.map(
      (index) => window.foundPosts[index]
    );

    // Generate and download CSV
    generateCSV(selectedPosts);
  }

  /**
   * Generate and download a CSV file from the given posts data.
   *
   * @param {Array} posts Array of post data objects.
   */
  function generateCSV(posts) {
    // Show loading spinner
    showLoading(true);

    // Make AJAX request
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_export_multiple_csv",
        nonce: wayback_wp_importer.nonce,
        posts: JSON.stringify(posts),
      },
      success: function (response) {
        showLoading(false);

        if (response.success) {
          // Create a download link for the CSV
          const blob = new Blob([response.data.csv_content], {
            type: "text/csv",
          });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.style.display = "none";
          a.href = url;
          a.download = response.data.filename || "wayback-export.csv";
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
        } else {
          showError(response.data.message || "Failed to export CSV.");
        }
      },
      error: function () {
        showLoading(false);
        showError("An error occurred while communicating with the server.");
      },
    });
  }

  /**
   * Import all posts found during extraction.
   */
  function importAllPosts() {
    if (!window.foundPosts || window.foundPosts.length === 0) {
      showError("No posts found to import.");
      return;
    }

    // Show loading spinner
    showLoading(true);

    // Disable buttons
    $("#import-all-btn, #export-csv-btn, #reset-form-btn").prop(
      "disabled",
      true
    );

    // Make AJAX request to import all posts
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_import_multiple",
        nonce: wayback_wp_importer.nonce,
        posts: JSON.stringify(window.foundPosts),
      },
      success: function (response) {
        showLoading(false);
        $("#import-all-btn, #export-csv-btn, #reset-form-btn").prop(
          "disabled",
          false
        );

        if (response.success) {
          // Show success message with import results
          showSuccess(
            `Successfully imported ${response.data.imported} out of ${window.foundPosts.length} posts.`
          );
        } else {
          showError(response.data.message || "Failed to import posts.");
        }
      },
      error: function () {
        showLoading(false);
        $("#import-all-btn, #export-csv-btn, #reset-form-btn").prop(
          "disabled",
          false
        );
        showError("An error occurred while communicating with the server.");
      },
    });
  }

  /**
   * Show a success message.
   *
   * @param {string} message The success message.
   */
  function showSuccess(message) {
    // Create the success notice
    const notice = $(
      '<div class="notice notice-success is-dismissible"><p>' +
        message +
        "</p></div>"
    );

    // Add the dismiss button
    const dismissButton = $(
      '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
    );
    dismissButton.on("click", function () {
      notice.remove();
    });
    notice.append(dismissButton);

    // Insert the notice at the top of the page
    $(".wrap.wayback-wp-importer").prepend(notice);

    // Scroll to the top
    $("html, body").animate(
      {
        scrollTop: 0,
      },
      500
    );
  }

  /**
   * Display the extracted content in the preview section.
   *
   * @param {Object} data The extracted content data.
   */
  function displayContentPreview(data) {

    console.log("Display content preview data:", data);
    // Check if we came from blog post links list
    const hasBlogLinks =
      sessionStorage.getItem("wayback_has_blog_links") === "true";

    // Show or hide the back to list button
    if (hasBlogLinks) {
      $("#back-to-list-btn").show();
    } else {
      $("#back-to-list-btn").hide();
    }
    console.log("Display content preview data:", data);

    // Check if data is valid
    if (!data) {
      showError("No content data received from server.");
      return;
    }

    // Handle potential string content (from JSON parsing issues)
    if (typeof data === "string") {
      try {
        // Try to parse it as JSON
        data = JSON.parse(data);
        console.log("Parsed string data into object:", data);
      } catch (e) {
        console.error("Failed to parse string data:", e);
        showError("Invalid response format from server.");
        return;
      }
    }

    // Check if we have at least title or content
    if (
      (!data.title || data.title.trim() === "") &&
      (!data.content || data.content.trim() === "")
    ) {
      showError(
        "Could not extract post content. The URL may not point to a valid WordPress post."
      );
      console.error(
        "Missing essential post data: No title or content found",
        data
      );
      return;
    }

    // Fix potentially truncated content
    if (data.content && typeof data.content === "string") {
      console.log("Processing content, length: " + data.content.length);

      // Check if we have the site logo instead of content
      if (
        data.content.indexOf('<a href="https://web.archive.org/web/') === 0 &&
        data.content.indexOf("CHECK-CLIMATE-200-x-40-px.png") > -1
      ) {
        console.log(
          "Detected site logo instead of content, marking as invalid"
        );
        showError(
          "Could not extract post content. The URL may not point to a valid WordPress post."
        );
        console.error(
          "Invalid content: Site logo found instead of post content"
        );
        return;
      }

      // Fix truncated Elementor content
      if (data.content.indexOf('<div class="elementor-element') === 0) {
        console.log("Detected truncated Elementor content, attempting to fix");
        // This appears to be truncated Elementor content, let's make it valid HTML
        data.content =
          '<div class="elementor-content-wrapper">' + data.content + "</div>";
      }
    }

    // Fill in the form fields with proper escaping
    $("#post-title").val(data.title || "");

    // Set content in the editor
    if (typeof tinyMCE !== "undefined" && tinyMCE.get("post-content")) {
      tinyMCE.get("post-content").setContent(data.content || "");
    } else {
      $("#post-content").val(data.content || "");
    }

    $("#post-excerpt").val(data.excerpt || "");
    $("#post-date").val(data.date || "");
    $("#post-author").val(data.author || "");
    $("#post-categories").val(
      data.categories ? data.categories.join(", ") : ""
    );
    $("#post-tags").val(data.tags ? data.tags.join(", ") : "");

    // Store the wayback URL for reference
    if (data.wayback_url) {
      $("#wayback-url-reference").val(data.wayback_url);
    }

    // Display featured image if available
    if (data.featured_image) {
      console.log("Featured image URL:", data.featured_image);

      // Ensure the image URL is from the Wayback Machine if needed
      let imageUrl = data.featured_image;

      // Check if the URL is not already a Wayback Machine URL but the post is from Wayback
      if (
        data.wayback_url &&
        data.wayback_url.indexOf("web.archive.org/web/") !== -1 &&
        imageUrl.indexOf("web.archive.org/web/") === -1
      ) {
        console.log("Converting image URL to Wayback Machine format");

        // Extract timestamp from Wayback URL
        const matches = data.wayback_url.match(
          /web\.archive\.org\/web\/([0-9]+)/
        );
        if (matches && matches[1]) {
          const timestamp = matches[1];
          // Remove http:// or https:// from the image URL
          const cleanImageUrl = imageUrl.replace(/^https?:\/\//, "");
          // Create Wayback Machine image URL with im_ flag
          imageUrl =
            "https://web.archive.org/web/" + timestamp + "im_/" + cleanImageUrl;
          console.log("Converted image URL:", imageUrl);
        }
      }

      // Use our proxy to avoid CORS issues
      const proxyUrl =
        wayback_wp_importer.admin_url +
        "admin.php?page=wayback-wp-importer&proxy=1&url=" +
        encodeURIComponent(imageUrl);
      console.log("Using proxy URL for image:", proxyUrl);

      $("#featured-image-preview").html(
        '<img src="' + escapeHtml(proxyUrl) + '" style="max-width:100%">'
      );
      $("#featured-image-section").show();

      // Store the corrected image URL back in the data object
      data.featured_image = imageUrl;
      // Also store the proxy URL for preview purposes
      data.featured_image_proxy = proxyUrl;

      // Set the hidden input field value for form submission
      $("#featured-image-url").val(imageUrl);
      console.log("Set featured image URL in hidden field:", imageUrl);
    } else {
      $("#featured-image-section").hide();
    }

    // Display comments if available
    if (data.comments && data.comments.length > 0) {
      let commentsHtml = '<ul class="wayback-comments-list">';

      data.comments.forEach(function (comment) {
        commentsHtml += "<li>";
        commentsHtml +=
          '<div class="comment-author">' +
          escapeHtml(comment.author || "Anonymous") +
          "</div>";
        commentsHtml +=
          '<div class="comment-date">' +
          escapeHtml(comment.date || "") +
          "</div>";
        commentsHtml +=
          '<div class="comment-content">' +
          escapeHtml(comment.content || "") +
          "</div>";
        commentsHtml += "</li>";
      });

      commentsHtml += "</ul>";

      $("#comments-preview").html(commentsHtml);
      $(".wayback-preview-comments").show();
    } else {
      $("#comments-preview").html("<p>No comments found.</p>");
      $(".wayback-preview-comments").hide();
    }

    // IMPORTANT: Store the full HTML content for later use by other components
    // This is critical for the custom fields functionality to work
    window.waybackExtractData.waybackUrl = data.wayback_url || "";
    
    // Store HTML content, handling base64 encoding if present
    let htmlContent = data.html_content || "";
    if (typeof htmlContent === 'string' && /^[A-Za-z0-9+/=]+$/.test(htmlContent)) {
      // Looks like base64, decode it
      try {
        htmlContent = atob(htmlContent);
        console.log("Decoded base64 HTML content, length:", htmlContent.length);
      } catch (e) {
        console.error("Failed to decode base64 HTML content:", e);
      }
    }
    window.waybackExtractData.fullHtmlContent = htmlContent;
    window.waybackExtractData.extractedData = data;
    
    console.log("Stored HTML content in waybackExtractData, available:", !!window.waybackExtractData.fullHtmlContent);
    console.log("HTML content length:", (window.waybackExtractData.fullHtmlContent || "").length);

    // Show the preview section
    $("#wayback-content-preview").show();

    // Scroll to the preview section
    $("html, body").animate(
      {
        scrollTop: $("#wayback-content-preview").offset().top,
      },
      500
    );
  }

  /**
   * Show an error message.
   *
   * @param {string} message The error message.
   */
  function showError(message) {
    // Create the error notice
    const notice = $(
      '<div class="notice notice-error is-dismissible"><p>' +
        message +
        "</p></div>"
    );

    // Add the dismiss button
    const dismissButton = $(
      '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
    );
    dismissButton.on("click", function () {
      notice.remove();
    });
    notice.append(dismissButton);
    
    // Insert the notice at the top of the page
    $(".wrap.wayback-wp-importer").prepend(notice);
    
    // Scroll to the top
    $("html, body").animate(
      {
        scrollTop: 0,
      },
      500
    );
  }
  
  /**
   * Import a post
   */
  function importPost() {
    const postData = {
      post_title: $("#post-title").val(),
      post_content:
        typeof tinyMCE !== "undefined" && tinyMCE.get("post-content")
          ? tinyMCE.get("post-content").getContent()
          : $("#post-content").val(),
      post_excerpt: $("#post-excerpt").val(),
      post_date: $("#post-date").val(),
      post_author: $("#post-author").val(),
      post_categories: $("#post-categories")
        .val()
        .split(",")
        .map((item) => item.trim())
        .filter(Boolean),
      post_tags: $("#post-tags")
        .val()
        .split(",")
        .map((item) => item.trim())
        .filter(Boolean),
      import_comments: $("#import-comments").is(":checked"),
      wayback_url: $("#wayback-url-reference").val(), // Include the original Wayback URL
      post_status: $("#post-status").val(),
      post_type: $("#post-type").val() || 'post', // Include the selected post type
    };
    
    // Get custom taxonomies data if available
    if (typeof window.WaybackTaxonomies !== 'undefined' && 
        typeof window.WaybackTaxonomies.getSelectedTerms === 'function') {
      // Get all selected taxonomy terms
      const taxonomyTerms = window.WaybackTaxonomies.getSelectedTerms();
      if (taxonomyTerms && Object.keys(taxonomyTerms).length > 0) {
        postData.taxonomies = taxonomyTerms;
        console.log('Adding custom taxonomies to import:', taxonomyTerms);
      }
    }

    // IMPORTANT: Get the featured image URL from the hidden input field
    // This is critical for the featured image to work
    const featuredImageUrl = $("#featured-image-url").val();
    console.log("Featured image URL from hidden field:", featuredImageUrl);

    if (featuredImageUrl && featuredImageUrl.trim() !== "") {
      // Add the featured image URL to the post data
      postData.featured_image = featuredImageUrl;
      console.log("Added featured image URL to post data");
    } else {
      console.warn("No featured image URL found in hidden field");
    }

    // Check if we have a custom image ID from the media library
    const customImageId = $("#wayback-content-preview").data("custom-image-id");
    if (customImageId) {
      postData.custom_image_id = customImageId;
      // Also add it directly to the AJAX data object for redundancy
      ajaxData.custom_image_id = customImageId;
      console.log("Using custom image ID:", customImageId);
    }

    console.log("Post data:", postData);

    // Validate required fields
    if (!postData.post_title || !postData.post_title.trim()) {
      showError("Post title is required.");
      return;
    }

    if (!postData.post_content || !postData.post_content.trim()) {
      showError("Post content is required.");
      return;
    }

    // Log the complete post data for debugging
    console.log("Complete post data for submission:", JSON.stringify(postData));

    // Get the selected post status
    const postStatus = $("#post-status").val();

    // Show loading spinner
    showLoading(true);

    // Disable buttons
    $(".wayback-import-actions button").prop("disabled", true);

    // Get custom fields data
    const customFields = getCustomFieldsData();

    // Create a separate object for the AJAX data to ensure all values are properly included
    const ajaxData = {
      action: "wayback_import_post",
      nonce: wayback_wp_importer.nonce,
      post_data: postData,
      post_status: postStatus,
      // Include the featured image URL directly in the AJAX data as well
      featured_image_url: $("#featured-image-url").val(),
      // Include custom fields
      custom_fields: JSON.stringify(customFields),
    };

    console.log("Sending AJAX data:", ajaxData);

    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: ajaxData,
      success: function (response) {
        showLoading(false);
        $(".wayback-import-actions button").prop("disabled", false);

        if (response.success) {
          // Show success message
          $("#import-result-message").text(
            response.data.message || "Post imported successfully!"
          );

          // Set edit link
          $("#edit-post-link").attr("href", response.data.edit_url || "#");

          // Show result section
          $("#wayback-import-result").show();

          // Scroll to the result section
          $("html, body").animate(
            {
              scrollTop: $("#wayback-import-result").offset().top - 50,
            },
            500
          );
        } else {
          // Check if this is a duplicate post error
          if (response.data && response.data.duplicate === true) {
            // Create a more informative message with a link to the existing post
            const message = response.data.message || "Duplicate post found.";
            const editLink = response.data.edit_url
              ? `<a href="${response.data.edit_url}" target="_blank">Edit Existing Post</a>`
              : "";
            const viewLink = response.data.view_url
              ? `<a href="${response.data.view_url}" target="_blank">View Existing Post</a>`
              : "";

            const fullMessage = `
                            <div class="duplicate-post-message">
                                <p>${message}</p>
                                <div class="duplicate-post-actions">
                                    ${editLink} ${viewLink}
                                </div>
                            </div>
                        `;

            // Show the error with HTML formatting
            showError(fullMessage, true);
          } else {
            // Regular error
            showError(response.data.message || "Failed to import post.");
          }
        }
      },
      error: function () {
        showLoading(false);
        $(".wayback-import-actions button").prop("disabled", false);
        showError("An error occurred while communicating with the server.");
      },
    });
  }

  /**
   * Export the post data to CSV.
   */
  function exportCSV() {
    // Collect the post data
    const postData = {
      title: $("#post-title").val(),
      content:
        typeof tinyMCE !== "undefined" && tinyMCE.get("post-content")
          ? tinyMCE.get("post-content").getContent()
          : $("#post-content").val(),
      excerpt: $("#post-excerpt").val(),
      date: $("#post-date").val(),
      author: $("#post-author").val(),
      categories: $("#post-categories")
        .val()
        .split(",")
        .map((item) => item.trim())
        .filter(Boolean),
      tags: $("#post-tags")
        .val()
        .split(",")
        .map((item) => item.trim())
        .filter(Boolean),
      featured_image: $("#featured-image-url").val(),
    };

    // Show loading spinner
    showLoading(true);

    // Disable buttons
    $(".wayback-import-actions button").prop("disabled", true);

    // Make AJAX request
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_export_csv",
        nonce: wayback_wp_importer.nonce,
        post_data: postData,
      },
      success: function (response) {
        showLoading(false);
        $(".wayback-import-actions button").prop("disabled", false);

        if (response.success) {
          // Create a download link for the CSV
          const blob = new Blob([response.data.csv_content], {
            type: "text/csv",
          });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.style.display = "none";
          a.href = url;
          a.download = response.data.filename || "wayback-export.csv";
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
        } else {
          showError(response.data.message || "Failed to export CSV.");
        }
      },
      error: function () {
        showLoading(false);
        $(".wayback-import-actions button").prop("disabled", false);
        showError("An error occurred while communicating with the server.");
      },
    });
  }

  /**
   * Add a new custom field row to the UI.
   *
   * @param {string} key - Optional key for the custom field
   * @param {string} value - Optional value for the custom field
   */
  function addCustomFieldRow(key = "", value = "") {
    const rowId = "custom-field-" + Date.now();
    const html = `
            <div class="custom-field-row" id="${rowId}">
                <input type="text" class="custom-field-key" value="${key}" placeholder="Field name">
                <input type="text" class="custom-field-value" value="${value}" placeholder="Field value">
                <button type="button" class="button button-small remove-custom-field" data-row-id="${rowId}">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        `;

    $("#custom-fields-rows").append(html);

    // Add event listener for the remove button
    $(`#${rowId} .remove-custom-field`).on("click", function () {
      const rowId = $(this).data("row-id");
      $(`#${rowId}`).remove();
    });
  }

  /**
   * Add a custom field search key to the list.
   */
  function addCustomFieldSearchKey() {
    const key = $(".custom-field-search-key").val().trim();

    if (!key) {
      return;
    }

    const keyId = "search-key-" + Date.now();
    const html = `
            <div class="custom-field-search-item" id="${keyId}">
                <span class="custom-field-search-key-text">${key}</span>
                <button type="button" class="button button-small remove-search-key" data-key-id="${keyId}">
                    <span class="dashicons dashicons-no"></span>
                </button>
            </div>
        `;

    $("#custom-field-search-list").append(html);

    // Clear the input
    $(".custom-field-search-key").val("");

    // Add event listener for the remove button
    $(`#${keyId} .remove-search-key`).on("click", function () {
      const keyId = $(this).data("key-id");
      $(`#${keyId}`).remove();
    });
  }

  /**
   * Get all custom fields data from the UI.
   *
   * @return {Object} An object containing all custom fields
   */
  function getCustomFieldsData() {
    const customFields = {};

    $(".custom-field-row").each(function () {
      const key = $(this).find(".custom-field-key").val().trim();
      const value = $(this).find(".custom-field-value").val().trim();

      if (key) {
        customFields[key] = value;
      }
    });

    return customFields;
  }

  /**
   * Reset the form and hide the preview section.
   */
  function resetForm() {
    // Reset the extract form
    $("#wayback-extract-form")[0].reset();

    // Hide the preview section
    $("#wayback-content-preview").hide();

    // Hide the result section
    $("#wayback-import-result").hide();

    // Clear custom fields
    $("#custom-fields-rows").empty();
    $("#custom-field-search-list").empty();

    // Show extract form
    $("#wayback-extract-form-container").show();

    // Clear the blog post links state
    sessionStorage.removeItem("wayback_has_blog_links");

    // Hide the back to list button
    $("#back-to-list-btn").hide();

    // Scroll to the top
    $("html, body").animate({
      scrollTop: 0,
    });
  }

  /**
   * Export the post data to CSV.
   */
  function exportCSV() {
    // Collect the post data
    const postData = {
      title: $("#post-title").val(),
      content:
        typeof tinyMCE !== "undefined" && tinyMCE.get("post-content")
          ? tinyMCE.get("post-content").getContent()
          : $("#post-content").val(),
      excerpt: $("#post-excerpt").val(),
      date: $("#post-date").val(),
      author: $("#post-author").val(),
      categories: $("#post-categories")
        .val()
        .split(",")
        .map((item) => item.trim())
        .filter(Boolean),
      tags: $("#post-tags")
        .val()
        .split(",")
        .map((item) => item.trim())
        .filter(Boolean),
      featured_image: $("#featured-image-url").val(),
    };

    // Show loading spinner
    showLoading(true);

    // Disable buttons
    $(".wayback-import-actions button").prop("disabled", true);

    // Make AJAX request
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_export_csv",
        nonce: wayback_wp_importer.nonce,
        post_data: postData,
      },
      success: function (response) {
        showLoading(false);
        $(".wayback-import-actions button").prop("disabled", false);

        if (response.success) {
          // Create a download link for the CSV
          const blob = new Blob([response.data.csv_content], {
            type: "text/csv",
          });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.style.display = "none";
          a.href = url;
          a.download = response.data.filename || "wayback-export.csv";
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
        } else {
          showError(response.data.message || "Failed to export CSV.");
        }
      },
      error: function () {
        showLoading(false);
        $(".wayback-import-actions button").prop("disabled", false);
        showError("An error occurred while communicating with the server.");
      },
    });
  }

  /**
   * Show or hide the loading spinner.
   *
   * @param {boolean} show Whether to show or hide the loading spinner.
   */
  function showLoading(show) {
    if (show) {
      $(".wayback-import-actions .spinner").addClass("is-active");
    } else {
      $(".wayback-import-actions .spinner").removeClass("is-active");
    }
  }

  /**
   * Display an error message.
   *
   * @param {string} message The error message.
   * @param {boolean} isHtml Whether the message contains HTML (default: false).
   */
  function showError(message, isHtml = false) {
    console.log("Showing error:", message);

    // Remove any existing error notices
    $(".notice.notice-error").remove();

    // Create the error notice
    let notice;
    if (isHtml) {
      // If HTML is allowed, use jQuery's html() method
      notice = $('<div class="notice notice-error is-dismissible"></div>');
      notice.append($("<div></div>").html(message));
    } else {
      // For regular text, use the safer approach
      notice = $(
        '<div class="notice notice-error is-dismissible"><p>' +
          message +
          "</p></div>"
      );
    }

    // Add the dismiss button
    const dismissButton = $(
      '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
    );
    dismissButton.on("click", function () {
      notice.remove();
    });
    notice.append(dismissButton);

    // Insert the notice at the top of the page
    $(".wrap.wayback-wp-importer").prepend(notice);

    // Also add the notice to the active tab for visibility
    const activeTab = $(".wayback-tab-content.active");
    if (activeTab.length) {
      activeTab.prepend(notice.clone());
    }

    // Scroll to the top
    $("html, body").animate(
      {
        scrollTop: 0,
      },
      500
    );
  }

  /**
   * Open WordPress media uploader to select a custom featured image
   */
  function openMediaUploader() {
    console.log("Opening WordPress media uploader");

    // If the uploader object has already been created, reopen it
    if (typeof mediaUploader !== "undefined") {
      mediaUploader.open();
      return;
    }

    // Create the media uploader
    var mediaUploader = wp.media({
      title: "Select Featured Image",
      button: {
        text: "Use this image",
      },
      multiple: false, // Set to true for multiple image selection
    });

    // When an image is selected, run a callback
    mediaUploader.on("select", function () {
      var attachment = mediaUploader.state().get("selection").first().toJSON();
      console.log("Selected attachment:", attachment);

      // Get the original image URL from the data object
      var originalImageUrl = $("#featured-image-url").val();

      // Store the original image URL if not already stored
      if (!$("#wayback-content-preview").data("original-image-url")) {
        $("#wayback-content-preview").data(
          "original-image-url",
          originalImageUrl
        );
        console.log("Stored original image URL:", originalImageUrl);
      }

      // Update the featured image preview with the selected image
      $("#featured-image-preview").html(
        '<img src="' + escapeHtml(attachment.url) + '" style="max-width:100%">'
      );

      // Update the hidden input with the new image URL
      $("#featured-image-url").val(attachment.url);

      // Store the attachment ID for WordPress media library integration
      $("#wayback-content-preview").data("custom-image-id", attachment.id);

      // Show the reset button
      $("#reset-featured-image").show();

      console.log("Updated featured image with custom image:", attachment.url);
    });

    // Open the uploader dialog
    mediaUploader.open();
  }

  /**
   * Reset the featured image to the original Wayback Machine image
   */
  function resetFeaturedImage() {
    console.log("Resetting to original featured image");

    // Get the original image URL
    var originalImageUrl = $("#wayback-content-preview").data(
      "original-image-url"
    );

    if (originalImageUrl) {
      // Use our proxy to avoid CORS issues
      var proxyUrl =
        wayback_wp_importer.admin_url +
        "admin.php?page=wayback-wp-importer&proxy=1&url=" +
        encodeURIComponent(originalImageUrl);

      // Update the featured image preview
      $("#featured-image-preview").html(
        '<img src="' + escapeHtml(proxyUrl) + '" style="max-width:100%">'
      );

      // Update the hidden input with the original image URL
      $("#featured-image-url").val(originalImageUrl);

      // Remove the custom image ID
      $("#wayback-content-preview").removeData("custom-image-id");

      // Hide the reset button
      $("#reset-featured-image").hide();

      console.log("Reset to original image:", originalImageUrl);
    } else {
      console.log("No original image URL found to reset to");
    }
  }

  /**
   * Extract custom fields from the current post content.
   * @deprecated This function is now implemented in wayback-wp-importer-admin-custom-fields.js
   * and uses the stored HTML content from waybackExtractData instead of form fields.
   */
  // Function removed to avoid conflicts with the new implementation

  /**
   * Add a custom field row to the UI.
   */
  function addCustomFieldRow(key = "", value = "") {
    const row = $('<div class="custom-field-row"></div>');
    const keyInput = $(
      '<input type="text" class="custom-field-key" placeholder="Key" />'
    );
    const valueInput = $(
      '<input type="text" class="custom-field-value" placeholder="Value" />'
    );
    const removeButton = $(
      '<span class="remove-custom-field dashicons dashicons-no-alt"></span>'
    );

    keyInput.val(key);
    valueInput.val(value);

    row.append(keyInput, valueInput, removeButton);
    $("#custom-fields-list").append(row);

    // Add event listener for remove button
    removeButton.on("click", function () {
      $(this).parent().remove();
    });
  }

  /**
   * Add a custom field search key to the list.
   */
  function addCustomFieldSearchKey(key = "") {
    if (!key) {
      key = $("#custom-field-search-input").val();
      $("#custom-field-search-input").val("");
    }

    if (!key) {
      return;
    }

    // Check if key already exists
    let exists = false;
    $("#custom-field-search-list .custom-field-search-item").each(function () {
      if ($(this).find("span").text() === key) {
        exists = true;
        return false;
      }
    });

    if (exists) {
      return;
    }

    const item = $('<div class="custom-field-search-item"></div>');
    const keyText = $("<span></span>").text(key);
    const removeButton = $(
      '<span class="remove-search-key dashicons dashicons-no-alt"></span>'
    );

    item.append(keyText, removeButton);
    $("#custom-field-search-list").append(item);

    // Add event listener for remove button
    removeButton.on("click", function () {
      $(this).parent().remove();
    });
  }

  // Note: Using getCustomFieldsData() instead of this function

  // Initialize when the document is ready
  $(document).ready(function () {
    console.log("Document ready, initializing Wayback WP Importer JS...");
    init();

    // Add a visible indicator that JS is loaded
    $(
      '<div id="js-loaded-indicator" style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 10px;">JavaScript successfully loaded and initialized</div>'
    ).prependTo(".wrap.wayback-wp-importer");

    // Force initialize tabs after a short delay
    setTimeout(function () {
      console.log("Force initializing tabs...");
      // Set initial tab state
      $(".wayback-tab-content").hide().removeClass("active");
      $("#single-post-tab").show().addClass("active");
      $(".nav-tab").removeClass("nav-tab-active");
      $("#single-post-tab-link").addClass("nav-tab-active");
    }, 500);

    // Setup media uploader button click handlers
    $("#upload-custom-image").on("click", function (e) {
      e.preventDefault();
      openMediaUploader();
    });

    $("#reset-featured-image").on("click", function (e) {
      e.preventDefault();
      resetFeaturedImage();
    });

    // Custom Fields event handlers
    $("#add-custom-field-search-key").on("click", function () {
      addCustomFieldSearchKey();
    });

    $("#custom-field-search-input").on("keypress", function (e) {
      if (e.which === 13) {
        // Enter key
        e.preventDefault();
        addCustomFieldSearchKey();
      }
    });

    // Extract custom fields button is handled in wayback-wp-importer-admin-custom-fields.js

    $("#add-custom-field-btn").on("click", function () {
      addCustomFieldRow();
    });

    // Back to list button handler
    $("#back-to-list-btn").on("click", function (e) {
      e.preventDefault();
      $("#wayback-content-preview").hide();
      $("#wayback-extract-form .spinner").removeClass("is-active");
      // Switch back to batch import tab
      switchTab("batch-import");
      // Hide the back button
      $(this).hide();
    });

    // Extract single post button click
    $("#extract-content-btn").on("click", function (e) {
      e.preventDefault();
      extractSinglePostContent();
    });

    // Initialize duplicate checking checkbox
    $("#single-check-duplicates").on("change", function() {
      localStorage.setItem("wayback_check_duplicates_single", $(this).prop("checked"));
    });

    // Load saved preference
    const singleCheckDuplicates = localStorage.getItem("wayback_check_duplicates_single");
    if (singleCheckDuplicates !== null) {
      $("#single-check-duplicates").prop("checked", singleCheckDuplicates === "true");
    }
  });
})(jQuery);

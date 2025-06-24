/**
 * Wayback WordPress Importer Extract Functions
 * This file contains functions for extracting content from Wayback Machine URLs.
 */
(function ($) {
  "use strict";

  // Global variables to store extraction data
  window.waybackExtractData = {
    waybackUrl: '',
    fullHtmlContent: '',
    extractedData: null
  };

  /**
   * Extract content function for Wayback WordPress Importer.
   * This function handles the extraction of content from a Wayback Machine URL.
   */
  window.extractContent = function () {
    console.log("Extracting content...");

    // Get form data
    const waybackUrl = $("#wayback-url").val().trim();
    const postStatus = $("#post-status").val();

    // Get selected post types
    const postTypes = [];
    $('input[name="post_types[]"]:checked').each(function () {
      postTypes.push($(this).val());
    });

    // Get selected permalink structures
    const permalinkStructures = {};
    $('input[name^="permalink_structures"]:checked').each(function () {
      const key = $(this)
        .attr("name")
        .match(/\[(.*?)\]/)[1];
      permalinkStructures[key] = $(this).val();
    });

    console.log("Post types:", postTypes);
    console.log("Permalink structures:", permalinkStructures);

    // Show loading spinner
    $("#wayback-extract-form .spinner").addClass("is-active");

    // Disable submit button
    $("#extract-content-btn").prop("disabled", true);

    // Hide previous results
    $("#wayback-content-preview").hide();
    $("#wayback-import-result").hide();

    if (!waybackUrl) {
      showError("Please enter a valid Wayback Machine URL.");
      $("#wayback-extract-form .spinner").removeClass("is-active");
      $("#extract-content-btn").prop("disabled", false);
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
        import_mode: "single_post",
        post_status: postStatus,
        post_types: postTypes,
        permalink_structures: permalinkStructures,
      },
      beforeSend: function () {
        console.log("Sending AJAX request to extract content...");
      },
      success: function (response) {
        console.log("AJAX response received:", response);

        // Hide loading spinner
        $("#wayback-extract-form .spinner").removeClass("is-active");

        // Enable submit button
        $("#extract-content-btn").prop("disabled", false);

        if (response.success) {
          // Display the content preview
          displayContentPreview(response.data);
        } else {
          showError(response.data.message || "Failed to extract content.");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", { xhr, status, error });

        // Hide loading spinner
        $("#wayback-extract-form .spinner").removeClass("is-active");

        // Enable submit button
        $("#extract-content-btn").prop("disabled", false);

        showError(
          "AJAX error: " + error + ". Check browser console for details."
        );
      },
    });
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
   * Show or hide the loading spinner.
   *
   * @param {boolean} show Whether to show or hide the spinner.
   */
  function showLoading(show) {
    if (show) {
      $(".spinner").addClass("is-active");
    } else {
      $(".spinner").removeClass("is-active");
    }
  }

  /**
   * Display the extracted content in the preview section.
   *
   * @param {Object} data The extracted content data.
   */
  function displayContentPreview(data) {
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
    // if (typeof data === "string") {
    //   try {
    //     // Try to parse it as JSON
    //     data = JSON.parse(data);
    //     console.log("Parsed string data into object:", data);
    //   } catch (e) {
    //     console.error("Failed to parse string data:", e);
    //     showError("Invalid response format from server.");
    //     return;
    //   }
    // }

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

    // Show the preview section
    $("#wayback-content-preview").show();

    // Fill in the preview fields
    $("#post-title").val(data.title || "");

    // For content, we need to use tinyMCE if it's initialized, otherwise fallback to direct value
    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('post-content')) {
      tinyMCE.get('post-content').setContent(data.content || "");
    } else {
      $("#post-content").val(data.content || "");
    }

    $("#post-excerpt").val(data.excerpt || "");
    $("#post-date").val(data.date || "");
    $("#post-author").val(data.author || "");

    // Populate the hidden legacy fields for backward compatibility
    if (data.categories && data.categories.length > 0) {
      $("#post-categories").val(data.categories.join(", "));
    } else {
      $("#post-categories").val("");
    }

    // Fill in tags
    if (data.tags && data.tags.length > 0) {
      $("#post-tags").val(data.tags.join(", "));
    } else {
      $("#post-tags").val("");
    }
    
    // IMPORTANT: Store the full HTML content for later use by other components
    // This is critical for the custom fields functionality to work
    window.waybackExtractData.waybackUrl = data.wayback_url || "";
    window.waybackExtractData.fullHtmlContent = data.html_content || "";
    window.waybackExtractData.extractedData = data;
    
    console.log("Stored HTML content in waybackExtractData, available:", !!window.waybackExtractData.fullHtmlContent);
    console.log("HTML content length:", (window.waybackExtractData.fullHtmlContent || "").length);
    
    // Store the content in hidden fields for persistence
    // First check if the hidden fields exist, if not create them
    if ($("#wayback-html-content").length === 0) {
      $("<input>").attr({
        type: "hidden",
        id: "wayback-html-content",
        name: "wayback_html_content"
      }).appendTo("#wayback-content-preview");
    }
    
    if ($("#wayback-url-reference").length === 0) {
      $("<input>").attr({
        type: "hidden",
        id: "wayback-url-reference",
        name: "wayback_url_reference"
      }).appendTo("#wayback-content-preview");
    }
    
    // Set the values in the hidden fields
    $("#wayback-html-content").val(data.html_content || "");
    $("#wayback-url-reference").val(data.wayback_url || "");
    
    console.log("Stored HTML content in hidden field, length:", (data.html_content || "").length);

    // Trigger taxonomy loading with extracted content
    loadTaxonomiesAndExtractTerms(data.wayback_url || "", data.html_content || "");

    // Fill in featured image
    if (data.featured_image) {
      // Create or update the featured image preview
      const featuredImageContainer = $("#featured-image-preview");
      let featuredImage = featuredImageContainer.find('img');

      if (featuredImage.length === 0) {
        featuredImage = $('<img>').addClass('featured-image-preview');
        featuredImageContainer.html(featuredImage);
      }

      featuredImage.attr("src", data.featured_image);
      featuredImageContainer.show();
      $("#featured-image-url").val(data.featured_image);
      $("#reset-featured-image").show();
    } else {
      $("#featured-image-preview").hide();
      $("#featured-image-url").val("");
      $("#reset-featured-image").hide();
    }

    // Fill in comments if available
    if (data.comments && data.comments.length > 0) {
      const commentsContainer = $("#preview-comments");
      commentsContainer.empty();

      data.comments.forEach(function (comment, index) {
        const commentHtml = `
          <div class="comment-preview">
            <div class="comment-author">${escapeHtml(
          comment.author || "Anonymous"
        )}</div>
            <div class="comment-date">${escapeHtml(
          comment.date || ""
        )}</div>
            <div class="comment-content">${escapeHtml(
          comment.content || ""
        )}</div>
          </div>
        `;
        commentsContainer.append(commentHtml);
      });

      $("#wayback-preview-comments").show();
      $("#wayback-import-btn").show();
    }
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
   * Handle custom post type additions
   */
  function setupCustomPostTypeHandlers() {
    // Handle custom post type additions
    $("#add-custom-post-type").on("click", function () {
      const customPostType = $("#custom_post_type").val().trim();

      if (customPostType) {
        // Create a hidden input for the custom post type
        const customPostTypeInput = $("<input>");
        customPostTypeInput.attr({
          type: "hidden",
          name: "post_types[]",
          value: customPostType,
        });

        // Create a visual representation with a remove button
        const customPostTypeTag = $("<span>");
        customPostTypeTag.addClass("custom-post-type-tag");
        customPostTypeTag.css({
          display: "inline-block",
          "background-color": "#f0f0f0",
          "border-radius": "3px",
          padding: "3px 8px",
          "margin-right": "5px",
          "margin-bottom": "5px",
        });

        const removeButton = $("<span>");
        removeButton.addClass("remove-custom-post-type");
        removeButton.html("&times;");
        removeButton.css({
          cursor: "pointer",
          "margin-left": "5px",
          color: "#999",
        });

        removeButton.on("click", function () {
          customPostTypeTag.remove();
          customPostTypeInput.remove();
        });

        customPostTypeTag.text(customPostType);
        customPostTypeTag.append(removeButton);

        // Add to the form
        $("#custom-post-types-list").append(customPostTypeTag);
        $("#wayback-extract-form").append(customPostTypeInput);

        // Clear the input field
        $("#custom_post_type").val("");
      }
    });

    // Allow pressing Enter to add custom post type
    $("#custom_post_type").on("keypress", function (e) {
      if (e.which === 13) {
        // Enter key
        e.preventDefault();
        $("#add-custom-post-type").click();
      }
    });
  }

  /**
   * Switch between tabs
   *
   * @param {string} tabId The ID of the tab to switch to
   */
  function switchTab(tabId) {
    console.log("Switching to tab: " + tabId);

    // Hide all tab contents
    $(".tab-content").hide();

    // Show the selected tab content
    $("#" + tabId + "-tab").show();

    // Update the active tab link
    $(".nav-tab").removeClass("nav-tab-active");
    $("#" + tabId + "-tab-link").addClass("nav-tab-active");
  }

  /**
   * Display the list of blog post links found during scanning with post type and permalink structure info
   *
   * @param {Array} links List of blog post links with URLs, titles, post types, and permalink structures
   */
  function displayBlogPostLinks(links) {
    console.log("Displaying blog post links with enhanced info:", links);

    // Clear any previous links
    $("#blog-post-links-list").empty();

    if (!links || links.length === 0) {
      $("#blog-post-links-list").html(
        "<p>No blog posts found on this page.</p>"
      );
      $("#blog-post-links-container").show();
      return;
    }

    // Create a table for the links
    const table = $(
      '<table class="wp-list-table widefat fixed striped posts"></table>'
    );

    // Create the table header
    const thead = $("<thead></thead>");
    const headerRow = $("<tr></tr>");
    headerRow.append(
      '<th class="check-column"><input type="checkbox" id="select-all-links-checkbox"></th>'
    );
    headerRow.append("<th>Title</th>");
    headerRow.append("<th>URL</th>");
    headerRow.append("<th>Post Type</th>");
    headerRow.append("<th>Permalink Structure</th>");
    headerRow.append("<th>Actions</th>");
    thead.append(headerRow);
    table.append(thead);

    // Create the table body
    const tbody = $("<tbody></tbody>");

    // Add each link to the table
    $.each(links, function (index, link) {
      const row = $("<tr></tr>");

      // Checkbox column
      const checkboxCell = $('<td class="check-column"></td>');
      const checkbox = $(
        '<input type="checkbox" class="batch-link-checkbox" />'
      );
      checkbox.data("url", link.url);
      checkbox.data("title", link.title || link.url);
      checkbox.data("post-type", link.post_type || "post");
      checkbox.data(
        "permalink-structure",
        link.permalink_structure || "unknown"
      );
      checkboxCell.append(checkbox);
      row.append(checkboxCell);

      // Title column
      const titleCell = $("<td></td>");
      titleCell.text(link.title || "Untitled");
      row.append(titleCell);

      // URL column
      const urlCell = $("<td></td>");
      const urlText =
        link.url.length > 50 ? link.url.substring(0, 47) + "..." : link.url;
      urlCell.append($("<code></code>").text(urlText));
      urlCell.attr("title", link.url);
      row.append(urlCell);

      // Post Type column
      const postTypeCell = $("<td></td>");
      postTypeCell.text(link.post_type || "post");
      row.append(postTypeCell);

      // Permalink Structure column
      const permalinkCell = $("<td></td>");
      permalinkCell.text(link.permalink_structure || "unknown");
      row.append(permalinkCell);

      // Actions column
      const actionsCell = $("<td></td>");
      const extractButton = $(
        '<button type="button" class="button button-small">Extract</button>'
      );
      extractButton.on("click", function () {
        extractSinglePostFromLink(link.url);
      });
      actionsCell.append(extractButton);
      row.append(actionsCell);

      tbody.append(row);
    });

    table.append(tbody);
    $("#blog-post-links-list").append(table);

    // Add batch actions
    const batchActions = $('<div class="tablenav bottom"></div>');
    const bulkActionsDiv = $(
      '<div class="alignleft actions bulkactions"></div>'
    );

    const selectAllButton = $(
      '<button type="button" class="button">Select All</button>'
    );
    selectAllButton.on("click", function () {
      $(".batch-link-checkbox").prop("checked", true);
    });

    const deselectAllButton = $(
      '<button type="button" class="button">Deselect All</button>'
    );
    deselectAllButton.on("click", function () {
      $(".batch-link-checkbox").prop("checked", false);
    });

    const extractSelectedButton = $(
      '<button type="button" class="button button-primary">Extract Selected</button>'
    );
    extractSelectedButton.on("click", function () {
      const selectedLinks = [];
      $(".batch-link-checkbox:checked").each(function () {
        selectedLinks.push($(this).data("url"));
      });

      if (selectedLinks.length > 0) {
        extractBatchSelectedLinks(selectedLinks);
      } else {
        showError("Please select at least one post to extract.");
      }
    });

    bulkActionsDiv.append(selectAllButton);
    bulkActionsDiv.append(" ");
    bulkActionsDiv.append(deselectAllButton);
    bulkActionsDiv.append(" ");
    bulkActionsDiv.append(extractSelectedButton);

    batchActions.append(bulkActionsDiv);
    $("#blog-post-links-list").append(batchActions);

    // Update the heading to include the count
    const countText =
      links.length === 1
        ? "1 Blog Post Found"
        : links.length + " Blog Posts Found";
    $("#blog-post-links-container h3").text(countText);

    // Show the container
    $("#blog-post-links-container").show();

    // Handle select all checkbox
    $("#select-all-links-checkbox").on("change", function () {
      $(".batch-link-checkbox").prop("checked", $(this).prop("checked"));
    });
  }

  /**
   * Extract a single post from a blog post link with post type and permalink structure info
   *
   * @param {string} url The Wayback Machine URL of the blog post
   * @param {string} postType Optional post type detected for this URL
   * @param {string} permalinkStructure Optional permalink structure detected for this URL
   */
  function extractSinglePostFromLink(url, postType, permalinkStructure) {
    // Store the current state so we can go back
    sessionStorage.setItem("wayback_previous_tab", "batch-import");
    sessionStorage.setItem("wayback_has_blog_links", "true");
    console.log("Extracting content from blog post link:", url);
    console.log("Post type:", postType || "Using default");
    console.log("Permalink structure:", permalinkStructure || "Using default");

    // Show loading spinner
    $("#blog-post-links-container .spinner").addClass("is-active");

    // Get the post type and permalink structure from the checkbox data if available
    if (!postType || !permalinkStructure) {
      const $checkbox = $(".batch-link-checkbox").filter(function () {
        return $(this).data("url") === url;
      });

      if ($checkbox.length) {
        postType = postType || $checkbox.data("post-type");
        permalinkStructure =
          permalinkStructure || $checkbox.data("permalink-structure");
      }
    }

    // Prepare post types array
    const postTypes = [];
    if (postType) {
      postTypes.push(postType);
    } else {
      // Use the selected post types from the form
      $('input[name="post_types[]"]:checked').each(function () {
        postTypes.push($(this).val());
      });
    }

    // Prepare permalink structures object
    const permalinkStructures = {};
    if (permalinkStructure) {
      // Map the permalink structure string to the appropriate key
      switch (permalinkStructure.toLowerCase()) {
        case "date":
        case "date-based":
          permalinkStructures.date = "1";
          break;
        case "postname":
        case "post-name":
          permalinkStructures.postname = "1";
          break;
        case "post_id":
        case "post-id":
          permalinkStructures.post_id = "1";
          break;
        case "custom":
          permalinkStructures.custom = "1";
          break;
        default:
          // Use all permalink structures if we can't determine
          $('input[name^="permalink_structures"]:checked').each(function () {
            const key = $(this)
              .attr("name")
              .match(/\[(.*?)\]/)[1];
            permalinkStructures[key] = $(this).val();
          });
      }
    } else {
      // Use the selected permalink structures from the form
      $('input[name^="permalink_structures"]:checked').each(function () {
        const key = $(this)
          .attr("name")
          .match(/\[(.*?)\]/)[1];
        permalinkStructures[key] = $(this).val();
      });
    }

    // Make AJAX request to extract the post content
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_extract_content",
        nonce: wayback_wp_importer.nonce,
        wayback_url: url,
        import_mode: "single_post",
        post_types: postTypes,
        permalink_structures: permalinkStructures,
      },
      success: function (response) {
        console.log("frull extract Response:", response);
        // Hide loading spinner
        $("#blog-post-links-container .spinner").removeClass("is-active");

        if (response.success) {
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

    // Get selected post types
    const postTypes = [];
    $('input[name="batch_post_types[]"]:checked').each(function () {
      postTypes.push($(this).val());
    });

    // Get selected permalink structures
    const permalinkStructures = {};
    $('input[name^="batch_permalink_structures"]:checked').each(function () {
      const key = $(this)
        .attr("name")
        .match(/\[(.*?)\]/)[1];
      permalinkStructures[key] = $(this).val();
    });

    console.log("Batch post types:", postTypes);
    console.log("Batch permalink structures:", permalinkStructures);

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
      post_types: postTypes,
      permalink_structures: permalinkStructures,
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
        post_types: postTypes,
        permalink_structures: permalinkStructures,
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

        if (response.success) {
          if (importMode === "scan_links" && response.data.blog_post_links) {
            // Display the list of blog post links
            displayBlogPostLinks(response.data.blog_post_links);
          } else if (response.data.posts) {
            // Display the list of found posts
            displayBatchPosts(response.data.posts);
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
   * Display the list of posts found in batch extraction
   *
   * @param {Array} posts List of posts
   */
  function displayBatchPosts(posts) {
    if (!posts || posts.length === 0) {
      showError("No posts found. Try adjusting your search parameters.");
      return;
    }

    // Clear previous results
    $(".batch-posts-list").empty();

    // Create a table for the posts
    const table = $(
      '<table class="wp-list-table widefat fixed striped posts"></table>'
    );

    // Create the table header
    const thead = $("<thead></thead>");
    const headerRow = $("<tr></tr>");
    headerRow.append(
      '<th class="check-column"><input type="checkbox" id="select-all-checkbox"></th>'
    );
    headerRow.append("<th>Title</th>");
    headerRow.append("<th>URL</th>");
    headerRow.append("<th>Post Type</th>");
    headerRow.append("<th>Permalink Structure</th>");
    headerRow.append("<th>Preview</th>");
    thead.append(headerRow);
    table.append(thead);

    // Create the table body
    const tbody = $("<tbody></tbody>");

    // Add each post to the table
    $.each(posts, function (index, post) {
      const row = $("<tr></tr>");

      // Checkbox column
      const checkboxCell = $('<td class="check-column"></td>');
      const checkbox = $(
        '<input type="checkbox" class="batch-post-checkbox" />'
      );
      checkbox.data("post", post);
      checkboxCell.append(checkbox);
      row.append(checkboxCell);

      // Title column
      const titleCell = $("<td></td>");
      titleCell.text(post.title || "Untitled");
      row.append(titleCell);

      // URL column
      const urlCell = $("<td></td>");
      const urlText = post.wayback_url
        ? post.wayback_url.length > 50
          ? post.wayback_url.substring(0, 47) + "..."
          : post.wayback_url
        : "N/A";
      urlCell.append($("<code></code>").text(urlText));
      if (post.wayback_url) {
        urlCell.attr("title", post.wayback_url);
      }
      row.append(urlCell);

      // Post Type column
      const postTypeCell = $("<td></td>");
      postTypeCell.text(post.post_type || "post");
      row.append(postTypeCell);

      // Permalink Structure column
      const permalinkCell = $("<td></td>");
      permalinkCell.text(post.permalink_structure || "unknown");
      row.append(permalinkCell);

      // Preview column
      const previewCell = $("<td></td>");
      if (post.content) {
        // Create a preview button
        const previewButton = $(
          '<button type="button" class="button button-small">Preview</button>'
        );
        previewButton.on("click", function () {
          // Display the post content in the preview section
          displayContentPreview(post);

          // Scroll to the preview section
          $("html, body").animate(
            {
              scrollTop: $("#wayback-content-preview").offset().top - 50,
            },
            500
          );
        });
        previewCell.append(previewButton);
      } else {
        previewCell.text("No preview available");
      }
      row.append(previewCell);

      tbody.append(row);
    });

    table.append(tbody);
    $(".batch-posts-list").append(table);

    // Show the batch results section
    $("#batch-results").show();

    // Handle select all checkbox
    $("#select-all-checkbox").on("change", function () {
      $(".batch-post-checkbox").prop("checked", $(this).prop("checked"));
    });
  }

  /**
   * Extract content from multiple selected blog post links
   * 
   * @param {Array} urls Array of Wayback Machine URLs to extract
   */
  function extractBatchSelectedLinks(urls) {
    if (!urls || urls.length === 0) {
      showError('No URLs selected for extraction.');
      return;
    }

    console.log('Extracting content from ' + urls.length + ' selected links');

    // Use the global initBatchExtraction function
    if (typeof window.initBatchExtraction === 'function') {
      window.initBatchExtraction(urls);
    } else {
      showError('Batch extraction function not available.');
    }
  }

  /**
   * Load taxonomies and extract terms from HTML content
   * 
   * @param {string} waybackUrl The Wayback Machine URL
   * @param {string} htmlContent The HTML content to extract terms from
   */
  function loadTaxonomiesAndExtractTerms(waybackUrl, htmlContent) {
    console.log("Loading taxonomies and extracting terms...");

    // Show spinner in taxonomies container
    $("#taxonomies-container .spinner").addClass("is-active");

    // Make AJAX request to get taxonomies and extract terms
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_get_taxonomies",
        nonce: wayback_wp_importer.nonce,
        wayback_url: waybackUrl,
        html_content: htmlContent
      },
      success: function (response) {
        console.log("Taxonomies loaded:", response);

        // Hide spinner
        $("#taxonomies-container .spinner").removeClass("is-active");

        if (response.success) {
          // Trigger custom event for taxonomies loaded
          // This will be handled by the taxonomies.js file
          $(document).trigger("wayback:taxonomies_loaded", [
            response.data.taxonomies,
            response.data.extracted_terms
          ]);
        } else {
          showError(response.data.message || "Failed to load taxonomies.");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error loading taxonomies:", { xhr, status, error });

        // Hide spinner
        $("#taxonomies-container .spinner").removeClass("is-active");

        showError("Error loading taxonomies: " + error);
      }
    });
  }

  /**
   * Initialize all event handlers when the document is ready
   */
  jQuery(document).ready(function ($) {
    // Set up custom post type handlers
    setupCustomPostTypeHandlers();

    // Make functions available globally if needed
    window.extractContent = extractContent;
    window.extractBatchContent = extractBatchContent;
    window.extractSinglePostFromLink = extractSinglePostFromLink;
    window.extractBatchSelectedLinks = extractBatchSelectedLinks;

    // Add the same functionality to the batch form
    $("#add-batch-custom-post-type").on("click", function () {
      const customPostType = $("#batch_custom_post_type").val().trim();

      if (customPostType) {
        // Create a hidden input for the custom post type
        const customPostTypeInput = $("<input>");
        customPostTypeInput.attr({
          type: "hidden",
          name: "batch_post_types[]",
          value: customPostType,
        });

        // Create a visual representation with a remove button
        const customPostTypeTag = $("<span>");
        customPostTypeTag.addClass("custom-post-type-tag");
        customPostTypeTag.css({
          display: "inline-block",
          "background-color": "#f0f0f0",
          "border-radius": "3px",
          padding: "3px 8px",
          "margin-right": "5px",
          "margin-bottom": "5px",
        });

        const removeButton = $("<span>");
        removeButton.addClass("remove-custom-post-type");
        removeButton.html("&times;");
        removeButton.css({
          cursor: "pointer",
          "margin-left": "5px",
          color: "#999",
        });

        removeButton.on("click", function () {
          customPostTypeTag.remove();
          customPostTypeInput.remove();
        });

        customPostTypeTag.text(customPostType);
        customPostTypeTag.append(removeButton);

        // Add to the form
        $("#batch-custom-post-types-list").append(customPostTypeTag);
        $("#wayback-batch-extract-form").append(customPostTypeInput);

        // Clear the input field
        $("#batch_custom_post_type").val("");
      }
    });

    // Allow pressing Enter to add custom post type in batch form
    $("#batch_custom_post_type").on("keypress", function (e) {
      if (e.which === 13) {
        // Enter key
        e.preventDefault();
        $("#add-batch-custom-post-type").click();
      }
    });

    // Handle the extract selected links button
    $("#extract-selected-links").on("click", function () {
      // Get all checked checkboxes
      const selectedCheckboxes = $(".batch-link-checkbox:checked");

      // Extract URLs from selected checkboxes
      const selectedUrls = [];
      selectedCheckboxes.each(function () {
        selectedUrls.push($(this).data("url"));
      });

      // Call the extraction function with the selected URLs
      if (selectedUrls.length > 0) {
        extractBatchSelectedLinks(selectedUrls);
      } else {
        showError("Please select at least one post to extract.");
      }
    });

    // Define initBatchExtraction function in the jQuery scope
    window.initBatchExtraction = function (urls) {
      // Show loading spinner
      $("#blog-post-links-container .spinner").addClass("is-active");

      // Disable buttons
      $("#blog-post-links-list button").prop("disabled", true);

      // Create a progress container
      const progressContainer = $('<div class="extraction-progress"></div>');
      progressContainer.html(
        "<p>Extracting " +
        urls.length +
        ' posts...</p><div class="progress-bar"><div class="progress-bar-inner"></div></div>'
      );
      $("#blog-post-links-list").append(progressContainer);

      // Track progress
      let completed = 0;
      const total = urls.length;
      const extractedPosts = [];

      // Function to update progress
      function updateProgress() {
        const percent = (completed / total) * 100;
        $(".progress-bar-inner").css("width", percent + "%");
        progressContainer
          .find("p")
          .text(
            "Extracting " +
            urls.length +
            " posts... " +
            completed +
            " of " +
            total +
            " completed"
          );
      }

      // Function to process the next URL
      function processNextUrl(index) {
        if (index >= urls.length) {
          // All done
          $("#blog-post-links-container .spinner").removeClass("is-active");
          $("#blog-post-links-list button").prop("disabled", false);

          // Show completion message
          progressContainer.html(
            "<p>Extraction complete! " +
            extractedPosts.length +
            " posts extracted successfully.</p>"
          );

          // If we have extracted posts, display them
          if (extractedPosts.length > 0) {
            displayBatchPosts(extractedPosts);
          }

          return;
        }

        const url = urls[index];

        // Get post type and permalink structure from checkbox data
        const $checkbox = $(".batch-link-checkbox").filter(function () {
          return $(this).data("url") === url;
        });

        const postType = $checkbox.length ? $checkbox.data("post-type") : null;
        const permalinkStructure = $checkbox.length
          ? $checkbox.data("permalink-structure")
          : null;

        // Prepare post types array
        const postTypes = [];
        if (postType) {
          postTypes.push(postType);
        } else {
          // Use the selected post types from the form
          $('input[name="batch_post_types[]"]:checked').each(function () {
            postTypes.push($(this).val());
          });
        }

        // Prepare permalink structures object
        const permalinkStructures = {};
        if (permalinkStructure) {
          // Map the permalink structure string to the appropriate key
          switch (permalinkStructure.toLowerCase()) {
            case "date":
            case "date-based":
              permalinkStructures.date = "1";
              break;
            case "postname":
            case "post-name":
              permalinkStructures.postname = "1";
              break;
            case "post_id":
            case "post-id":
              permalinkStructures.post_id = "1";
              break;
            case "custom":
              permalinkStructures.custom = "1";
              break;
            default:
              // Use all permalink structures if we can't determine
              $('input[name^="batch_permalink_structures"]').each(function () {
                if ($(this).is(":checked")) {
                  const key = $(this).attr("name").match(/\[(.*?)\]/)[1];
                  permalinkStructures[key] = $(this).val();
                }
              });
          }
        } else {
          // Use the selected permalink structures from the form
          $('input[name^="batch_permalink_structures"]').each(function () {
            if ($(this).is(":checked")) {
              const key = $(this).attr("name").match(/\[(.*?)\]/)[1];
              permalinkStructures[key] = $(this).val();
            }
          });
        }

        // Make AJAX request to extract the post content
        $.ajax({
          url: wayback_wp_importer.ajax_url,
          type: 'POST',
          data: {
            action: 'wayback_extract_content',
            nonce: wayback_wp_importer.nonce,
            wayback_url: url,
            import_mode: 'single_post',
            post_types: postTypes,
            permalink_structures: permalinkStructures
          },
          success: function (response) {
            completed++;
            updateProgress();

            if (response.success && response.data) {
              // Add the URL to the data for reference
              response.data.wayback_url = url;
              extractedPosts.push(response.data);
            }

            // Process the next URL
            processNextUrl(index + 1);
          },
          error: function () {
            completed++;
            updateProgress();

            // Process the next URL even if this one failed
            processNextUrl(index + 1);
          }
        });
      }

      // Start processing URLs
      updateProgress();
      processNextUrl(0);
    }
  });

  // Allow pressing Enter to add custom post type in batch form
  $("#batch_custom_post_type").on("keypress", function (e) {
    if (e.which === 13) {
      // Enter key
      e.preventDefault();
      $("#add-batch-custom-post-type").click();
    }
  });

  // Handle the extract selected links button
  $("#extract-selected-links").on("click", function () {
    // Get all checked checkboxes
    const selectedCheckboxes = $(".batch-link-checkbox:checked");

    // Extract URLs from selected checkboxes
    const selectedUrls = [];
    selectedCheckboxes.each(function () {
      selectedUrls.push($(this).data("url"));
    });

    // Call the extraction function with the selected URLs
    if (selectedUrls.length > 0) {
      window.initBatchExtraction(selectedUrls);
    } else {
      showError("Please select at least one post to extract.");
    }
  });

  // Make initBatchExtraction available globally
  window.initBatchExtraction = function (urls) {
    // Show loading spinner
    $("#blog-post-links-container .spinner").addClass("is-active");

    // Disable buttons
    $("#blog-post-links-list button").prop("disabled", true);

    // Create a progress container
    const progressContainer = $('<div class="extraction-progress"></div>');
    progressContainer.html(
      "<p>Extracting " +
      urls.length +
      ' posts...</p><div class="progress-bar"><div class="progress-bar-inner"></div></div>'
    );
    $("#blog-post-links-list").append(progressContainer);

    // Track progress
    let completed = 0;
    const total = urls.length;
    const extractedPosts = [];

    // Function to update progress
    function updateProgress() {
      const percent = (completed / total) * 100;
      $(".progress-bar-inner").css("width", percent + "%");
      progressContainer
        .find("p")
        .text(
          "Extracting " +
          urls.length +
          " posts... " +
          completed +
          " of " +
          total +
          " completed"
        );
    }

    // Function to process the next URL
    function processNextUrl(index) {
      if (index >= urls.length) {
        // All done
        $("#blog-post-links-container .spinner").removeClass("is-active");
        $("#blog-post-links-list button").prop("disabled", false);

        // Show completion message
        progressContainer.html(
          "<p>Extraction complete! " +
          extractedPosts.length +
          " posts extracted successfully.</p>"
        );

        // If we have extracted posts, display them
        if (extractedPosts.length > 0) {
          displayBatchPosts(extractedPosts);
        }

        return;
      }

      const url = urls[index];

      // Get post type and permalink structure from checkbox data
      const $checkbox = $(".batch-link-checkbox").filter(function () {
        return $(this).data("url") === url;
      });

      const postType = $checkbox.length ? $checkbox.data("post-type") : null;
      const permalinkStructure = $checkbox.length
        ? $checkbox.data("permalink-structure")
        : null;

      // Prepare post types array
      const postTypes = [];
      if (postType) {
        postTypes.push(postType);
      } else {
        // Use the selected post types from the form
        $('input[name="batch_post_types[]"]:checked').each(function () {
          postTypes.push($(this).val());
        });
      }

      // Prepare permalink structures object
      const permalinkStructures = {};
      if (permalinkStructure) {
        // Map the permalink structure string to the appropriate key
        switch (permalinkStructure.toLowerCase()) {
          case "date":
          case "date-based":
            permalinkStructures.date = "1";
            break;
          case "postname":
          case "post-name":
            permalinkStructures.postname = "1";
            break;
          case "post_id":
          case "post-id":
            permalinkStructures.post_id = "1";
            break;
          case "custom":
            permalinkStructures.custom = "1";
            break;
          default:
            // Use all permalink structures if we can't determine
            $('input[name^="batch_permalink_structures"]').each(function () {
              if ($(this).is(":checked")) {
                const key = $(this).attr("name").match(/\[(.*?)\]/)[1];
                permalinkStructures[key] = $(this).val();
              }
            });
        }
      } else {
        // Use the selected permalink structures from the form
        $('input[name^="batch_permalink_structures"]').each(function () {
          if ($(this).is(":checked")) {
            const key = $(this).attr("name").match(/\[(.*?)\]/)[1];
            permalinkStructures[key] = $(this).val();
          }
        });
      }

      // Make AJAX request to extract the post content
      $.ajax({
        url: wayback_wp_importer.ajax_url,
        type: "POST",
        data: {
          action: "wayback_extract_content",
          nonce: wayback_wp_importer.nonce,
          wayback_url: url,
          import_mode: "single_post",
          post_types: postTypes,
          permalink_structures: permalinkStructures,
        },
        success: function (response) {
          completed++;
          updateProgress();

          if (response.success && response.data) {
            // Add the URL to the data for reference
            response.data.wayback_url = url;
            extractedPosts.push(response.data);
          }

          // Process the next URL
          processNextUrl(index + 1);
        },
        error: function () {
          completed++;
          updateProgress();

          // Process the next URL even if this one failed
          processNextUrl(index + 1);
        },
      });
    }

    // Start processing URLs
    updateProgress();
    processNextUrl(0);
  };

  // No duplicate document ready needed
})(jQuery);

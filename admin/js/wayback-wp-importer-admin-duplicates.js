/**
 * Duplicate checking functionality for Wayback WordPress Importer.
 *
 * Handles duplicate post checking in both single and batch import modes.
 *
 * @since      1.0.3
 * @package    Wayback_WP_Importer
 */

(function($) {
  'use strict';

  // Store the posts data globally for background checking
  let batchPostsData = [];
  let duplicateCheckInProgress = false;
  let duplicateCheckQueue = [];
  let currentCheckIndex = 0;

  /**
   * Initialize the duplicate checking functionality
   */
  function init() {
    console.log("Initializing duplicate checking functionality...");

    // Add event handlers for the duplicate check checkboxes
    $("#single-check-duplicates").on("change", function() {
      // Store preference in localStorage
      localStorage.setItem("wayback_check_duplicates_single", $(this).prop("checked"));
    });

    $("#check-duplicates").on("change", function() {
      // Store preference in localStorage
      localStorage.setItem("wayback_check_duplicates_batch", $(this).prop("checked"));
    });

    // Load saved preferences
    const singleCheckDuplicates = localStorage.getItem("wayback_check_duplicates_single");
    if (singleCheckDuplicates !== null) {
      $("#single-check-duplicates").prop("checked", singleCheckDuplicates === "true");
    }

    const batchCheckDuplicates = localStorage.getItem("wayback_check_duplicates_batch");
    if (batchCheckDuplicates !== null) {
      $("#check-duplicates").prop("checked", batchCheckDuplicates === "true");
    }
  }

  /**
   * Check for duplicate posts in the background
   * 
   * @param {Array} posts List of posts to check
   */
  function checkDuplicatesInBackground(posts) {
    if (!posts || posts.length === 0) {
      return;
    }

    // Store the posts data globally
    batchPostsData = posts;
    duplicateCheckQueue = [];
    currentCheckIndex = 0;
    duplicateCheckInProgress = true;

    // Create a queue of posts to check
    for (let i = 0; i < posts.length; i++) {
      duplicateCheckQueue.push(i);
    }

    // Start checking for duplicates
    processDuplicateCheckQueue();
  }

  /**
   * Process the duplicate check queue
   */
  function processDuplicateCheckQueue() {
    if (duplicateCheckQueue.length === 0) {
      // All posts have been checked
      duplicateCheckInProgress = false;
      
      // Update the notification
      $("#duplicate-check-notice").removeClass("notice-info").addClass("notice-success")
        .html("<p>Duplicate checking complete. Duplicate posts are highlighted in red.</p>");
      
      // Remove the notice after 5 seconds
      setTimeout(function() {
        $("#duplicate-check-notice").fadeOut(500, function() {
          $(this).remove();
        });
      }, 5000);
      
      return;
    }

    // Get the next post index to check
    const postIndex = duplicateCheckQueue.shift();
    const post = batchPostsData[postIndex];

    // Update the notification
    $("#duplicate-check-notice").html("<p>Checking for duplicates: " + (currentCheckIndex + 1) + " of " + batchPostsData.length + "</p>");
    currentCheckIndex++;

    // Check if the post is a duplicate
    checkIfDuplicate(post.title, function(isDuplicate, duplicateId) {
      // Update the post data
      batchPostsData[postIndex].isDuplicate = isDuplicate;
      batchPostsData[postIndex].duplicateId = duplicateId;
      batchPostsData[postIndex].duplicateChecked = true;

      // Update the UI
      const row = $("tr[data-post-index='" + postIndex + "']");
      const statusCell = row.find(".column-duplicate");

      if (isDuplicate) {
        row.addClass("duplicate-post");
        row.css("background-color", "#ffdddd");
        statusCell.html('<span class="duplicate-indicator">Duplicate</span>');
        if (duplicateId) {
          statusCell.append('<br><small>ID: ' + duplicateId + '</small>');
        }
      } else {
        statusCell.text("Unique");
      }

      // Process the next post in the queue after a short delay
      setTimeout(processDuplicateCheckQueue, 300);
    });
  }

  /**
   * Check if a post with the given title already exists
   * 
   * @param {string} title The post title to check
   * @param {function} callback Callback function with result (isDuplicate, duplicateId)
   */
  function checkIfDuplicate(title, callback) {
    if (!title) {
      callback(false, null);
      return;
    }

    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_check_duplicate",
        nonce: wayback_wp_importer.nonce,
        title: title
      },
      success: function(response) {
        if (response.success) {
          callback(response.data.isDuplicate, response.data.duplicateId);
        } else {
          callback(false, null);
        }
      },
      error: function() {
        callback(false, null);
      }
    });
  }

  /**
   * Check if a single post is a duplicate and update the UI
   * 
   * @param {string} title The post title to check
   */
  function checkSinglePostDuplicate(title) {
    if (!title || !$("#single-check-duplicates").prop("checked")) {
      return;
    }

    // Show loading indicator
    const duplicateNotice = $("<div>").addClass("duplicate-check-notice").html(
      '<span class="spinner is-active" style="float:left;margin:0 5px 0 0;"></span>' +
      '<span>Checking for duplicates...</span>'
    );
    
    // Add the notice to the preview section
    $("#wayback-preview-header").after(duplicateNotice);

    // Check if the post is a duplicate
    checkIfDuplicate(title, function(isDuplicate, duplicateId) {
      // Remove the loading indicator
      duplicateNotice.remove();

      if (isDuplicate) {
        // Create a duplicate notice
        const notice = $("<div>").addClass("notice notice-warning duplicate-notice").html(
          '<p><strong>Duplicate Post Found!</strong> A post with this title already exists (ID: ' + duplicateId + ').</p>'
        );
        
        // Add the notice to the preview section
        $("#wayback-preview-header").after(notice);
      }
    });
  }

  // Initialize when document is ready
  $(document).ready(function() {
    init();
  });

  // Expose functions to global scope
  window.waybackDuplicateChecker = {
    checkDuplicatesInBackground: checkDuplicatesInBackground,
    checkSinglePostDuplicate: checkSinglePostDuplicate
  };

})(jQuery);

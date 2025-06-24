/**
 * Wayback WordPress Importer Custom Fields Functions
 * This file contains functions for handling custom fields extraction.
 */
(function ($) {
  "use strict";
  
  // Create a global namespace for our functions if it doesn't exist
  window.WaybackWpImporter = window.WaybackWpImporter || {};

  /**
   * Initialize custom fields functionality
   */
  function initCustomFields() {
    console.log("Initializing custom fields functionality...");

    // Setup event handlers for custom field keys
    setupCustomFieldKeyHandlers();

    // Setup event handlers for custom field selectors
    setupCustomFieldSelectorHandlers();

    // Setup extract custom fields button handler
    $("#extract-custom-fields-btn").on("click", function () {
      window.extractCustomFields();
    });
  }

  /**
   * Setup handlers for custom field keys
   */
  function setupCustomFieldKeyHandlers() {
    // Add custom field search key
    $("#add-custom-field-search-key").on("click", function () {
      const key = $("#custom-field-search-input").val().trim();
      if (key) {
        addCustomFieldKey(key);
        $("#custom-field-search-input").val("").focus();
      }
    });

    // Allow pressing Enter to add custom field key
    $("#custom-field-search-input").on("keypress", function (e) {
      if (e.which === 13) {
        e.preventDefault();
        $("#add-custom-field-search-key").click();
      }
    });

    // Handle removing custom field keys (delegated event)
    $("#custom-field-search-list").on(
      "click",
      ".remove-custom-field-key",
      function () {
        $(this).parent().remove();
      }
    );
  }

  /**
   * Setup handlers for custom field selectors
   */
  function setupCustomFieldSelectorHandlers() {
    // This function is intentionally left empty.
    // Custom selectors are now handled by wayback-wp-importer-admin-custom-selectors.js
    console.log("Custom selectors are now handled by wayback-wp-importer-admin-custom-selectors.js");
  }

  /**
   * Add a custom field key to the search list
   *
   * @param {string} key The custom field key to add
   */
  function addCustomFieldKey(key) {
    // Create the key tag
    const keyTag = $("<div>").addClass("custom-field-key-tag");

    // Create the key text
    const keyText = $("<span>").text(key);

    // Create the remove button
    const removeButton = $("<button>")
      .attr("type", "button")
      .addClass("remove-custom-field-key")
      .html("&times;");

    // Assemble the tag
    keyTag.append(keyText).append(removeButton);

    // Add to the list
    $("#custom-field-search-list").append(keyTag);
  }

  // The addCustomFieldSelector function has been removed.
  // Custom selectors are now handled by wayback-wp-importer-admin-custom-selectors.js

  /**
   * Extract custom fields from the current content
   */
  window.extractCustomFields = function () {
    console.log("Extracting custom fields...");

    // Try to get the stored HTML content from multiple possible sources
    let waybackUrl = "";
    let content = "";

    // First try waybackExtractData (defined in extract.js)
    if (
      window.waybackExtractData &&
      window.waybackExtractData.fullHtmlContent
    ) {
      console.log("Found content in waybackExtractData");
      waybackUrl = window.waybackExtractData.waybackUrl;
      content = window.waybackExtractData.fullHtmlContent;
    }

    // If not found, try to get from hidden field if it exists
    if (!content && $("#wayback-html-content").length) {
      console.log("Trying to get content from hidden field");
      content = $("#wayback-html-content").val();
      waybackUrl = $("#wayback-url-reference").val();
    }

    // If still not found, try to get from the response data
    if (!content && window.waybackExtractData && window.waybackExtractData.extractedData) {
      console.log("Trying to get content from extractedData");
      content = window.waybackExtractData.extractedData.html_content;
      waybackUrl = window.waybackExtractData.extractedData.wayback_url;
    }

    // Debug: Check content availability
    console.log("Content available:", !!content);
    console.log("Content length:", content ? content.length : 0);
    console.log("Wayback URL:", waybackUrl);

    // Debug: Check what items exist in the DOM
    console.log("Custom field search list items:", $("#custom-field-search-list").html());
    console.log("Custom field key tags count:", $("#custom-field-search-list .custom-field-search-item").length);

    // Get custom field keys
    const customFieldKeys = [];
    $("#custom-field-search-list .custom-field-search-item span").each(function() {
      customFieldKeys.push($(this).text());
    });

    console.log("Found custom field keys:", customFieldKeys);

   // Check if we have keys to search for
    if (customFieldKeys.length === 0) {
      showError("Please add at least one custom field key to search for.");
      return;
    }

    // Check if we have content to search in
    if (!content) {
      showError("No archived content available. Please extract content from a Wayback Machine URL first.");
      return;
    }

    // Show loading spinner
    $("#extract-custom-fields-btn").prop("disabled", true);
    $("#extract-custom-fields-btn").next(".spinner").addClass("is-active");

    // Make AJAX request
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_extract_custom_fields",
        nonce: wayback_wp_importer.nonce,
        wayback_url: waybackUrl,
        content: content,
        custom_field_keys: JSON.stringify(customFieldKeys)
      },
      success: function(response) {
        // Hide loading spinner
        $("#extract-custom-fields-btn").prop("disabled", false);
        $("#extract-custom-fields-btn").next(".spinner").removeClass("is-active");

        if (response.success) {
          displayCustomFieldsPreview(response.data.custom_fields, response.data.previews);
        } else {
          showError(response.data.message || "Failed to extract custom fields.");
        }
      },
      error: function(xhr, status, error) {
        // Hide loading spinner
        $("#extract-custom-fields-btn").prop("disabled", false);
        $("#extract-custom-fields-btn").next(".spinner").removeClass("is-active");

        showError("AJAX error: " + error + ". Check browser console for details.");
        console.error("AJAX error:", { xhr, status, error });
      }
    });
  };

  /**
   * Display custom fields preview
   *
   * @param {Object} customFields The extracted custom fields
   * @param {Object} previews The preview data for each field
   */
  function displayCustomFieldsPreview(customFields, previews) {
    // Clear the preview container
    const previewContainer = $("#custom-fields-list");
    previewContainer.empty();

    // Clear any existing custom field rows
    $(".custom-field-row").remove();

    if (Object.keys(customFields).length === 0) {
      previewContainer.html("<p>No custom fields were found.</p>");
      return;
    }

    // Create a table for the preview
    const table = $("<table>").addClass("widefat custom-fields-preview-table");

    // Add table header
    const thead = $("<thead>").append(
      $("<tr>")
        .append($("<th>").text("Field Key"))
        .append($("<th>").text("Value"))
        .append($("<th>").text("Source"))
        .append($("<th>").text("Actions"))
    );
    table.append(thead);

    // Add table body
    const tbody = $("<tbody>");

    // Add each custom field to the table
    $.each(customFields, function (key, value) {
      const preview = previews[key] || {};
      const source = preview.selector_used || "Not found";

      const tr = $("<tr>")
        .append($("<td>").text(key))
        .append($("<td>").addClass("custom-field-value").text(value))
        .append($("<td>").addClass("custom-field-source").text(source))
        .append(
          $("<td>").append(
            $("<button>")
              .attr("type", "button")
              .addClass("button button-small add-to-import")
              .text("Add to Import")
              .data("key", key)
              .data("value", value)
          )
        );

      tbody.append(tr);
    });

    table.append(tbody);
    previewContainer.append(table);

    // Add event handler for "Add to Import" buttons
    $(".add-to-import").on("click", function () {
      const key = $(this).data("key");
      const value = $(this).data("value");
      addCustomFieldRow(key, value);
    });
  }

  /**
   * Add a custom field row to the import form
   *
   * @param {string} key The custom field key
   * @param {string} value The custom field value
   */
  function addCustomFieldRow(key, value) {
    // Create a unique ID for this row
    const rowId = "custom-field-" + Date.now();

    // Create the row
    const row = $("<div>").addClass("custom-field-row").attr("id", rowId);

    // Create the key input
    const keyInput = $("<input>")
      .attr({
        type: "text",
        name: "custom_fields[keys][]",
        value: key,
      })
      .addClass("custom-field-key");

    // Create the value textarea
    const valueTextarea = $("<textarea>")
      .attr({
        name: "custom_fields[values][]",
        rows: 2,
      })
      .addClass("custom-field-value")
      .val(value);

    // Create the remove button
    const removeButton = $("<button>")
      .attr("type", "button")
      .addClass("button remove-custom-field")
      .text("Remove");

    // Add event handler for remove button
    removeButton.on("click", function () {
      $("#" + rowId).remove();
    });

    // Assemble the row
    row.append(keyInput).append(valueTextarea).append(removeButton);

    // Add the row to the container
    $("#custom-fields-list").append(row);
  }

  /**
   * Show an error message
   *
   * @param {string} message The error message to display
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
   * Initialize when the document is ready
   */
  $(document).ready(function () {
    initCustomFields();

    // Add custom field row button handler
    $("#add-custom-field-btn").on("click", function () {
      addCustomFieldRow("", "");
    });
  });
  
  // Expose functions to the global namespace
  window.WaybackWpImporter.addCustomFieldRow = addCustomFieldRow;
})(jQuery);

/**
 * Custom Selectors functionality for Wayback WP Importer
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 * @subpackage Wayback_WP_Importer/admin/js
 */

(function ($) {
  "use strict";

  // Store the HTML content for extraction
  let htmlContent = "";

  /**
   * Initialize the custom selectors functionality
   */
  function initCustomSelectors() {
    console.log("Initializing custom selectors functionality");
    setupCustomSelectorHandlers();

    // Check if we have HTML content available
    if (
      typeof waybackExtractData !== "undefined" &&
      waybackExtractData.content
    ) {
      htmlContent = waybackExtractData.content;
    } else {
      htmlContent = $("#wayback-content-html").val();
    }
  }

  /**
   * Setup handlers for custom selectors
   */
  function setupCustomSelectorHandlers() {
    // Show/hide custom attribute input based on attribute type selection
    $("#attribute-type").on("change", function () {
      if ($(this).val() === "custom") {
        $("#custom-attribute-input").show().focus();
      } else {
        $("#custom-attribute-input").hide();
      }
    });

    // Add custom selector
    $("#add-custom-selector").on("click", function () {
      const selectorText = $("#custom-selector-input").val().trim();
      const selectorType = $("#selector-type").val();
      let attributeType = $("#attribute-type").val();

      // Debug: Log the selected attribute type
      console.log("Selected attribute type from dropdown:", attributeType);
      console.log(
        "Attribute dropdown value:",
        $("#attribute-type option:selected").text()
      );

      // Force attributeType to be a string, not undefined
      if (attributeType === undefined) {
        attributeType = "";
      }

      // If custom attribute is selected, get the value from the custom attribute input
      if (attributeType === "custom") {
        attributeType = $("#custom-attribute-input").val().trim();
        if (!attributeType) {
          alert(
            wayback_wp_importer.custom_attribute_required ||
              "Please enter a custom attribute name"
          );
          $("#custom-attribute-input").focus();
          return;
        }
      }

      if (selectorText) {
        addCustomSelector(selectorText, selectorType, attributeType);
        $("#custom-selector-input").val("").focus();
        if (attributeType === "custom") {
          $("#custom-attribute-input").val("");
        }
      }
    });

    // Allow pressing Enter to add custom selector
    $("#custom-selector-input").on("keypress", function (e) {
      if (e.which === 13) {
        e.preventDefault();
        $("#add-custom-selector").click();
      }
    });

    // Handle removing custom selectors (delegated event)
    $("#custom-field-selectors-list").on(
      "click",
      ".remove-custom-field-selector",
      function () {
        $(this).parent().remove();
      }
    );

    // Extract content using custom selectors
    $("#extract-custom-selectors").on("click", function () {
      extractContentBySelectors();
    });
  }

  /**
   * Add a custom selector to the selectors list
   *
   * @param {string} selectorText The selector text to add
   * @param {string} selectorType The type of selector (css, class, id, tag, data)
   * @param {string} attributeType Optional. The attribute to extract
   */
  function addCustomSelector(selectorText, selectorType, attributeType) {
    // Format the selector based on type
    let formattedSelector = selectorText;

    // Debug log to see what attribute value is being passed
    console.log(
      `Adding selector: ${selectorText} (${selectorType}) with attribute: '${attributeType}'`
    );

    // Clean up selector text to ensure it doesn't have extra spaces or characters
    selectorText = selectorText.trim();

    switch (selectorType) {
      case "class":
        // Handle class selectors properly
        formattedSelector = selectorText.startsWith(".")
          ? selectorText
          : "." + selectorText;
        break;
      case "id":
        formattedSelector = selectorText.startsWith("#")
          ? selectorText
          : "#" + selectorText;
        break;
      case "tag":
        // For tag selectors, just use the tag name as is
        formattedSelector = selectorText;
        break;
      case "data":
        // For data attributes, format as [data-*]
        if (selectorText.startsWith("[data-") && selectorText.endsWith("]")) {
          formattedSelector = selectorText;
        } else if (selectorText.startsWith("data-")) {
          formattedSelector = "[" + selectorText + "]";
        } else {
          formattedSelector = "[data-" + selectorText + "]";
        }
        break;
      case "css":
      default:
        // For CSS selectors, use as is
        formattedSelector = selectorText;
    }

    // Create the selector tag
    const selectorTag = $("<div>").addClass("custom-field-selector-tag");

    // Create the selector text span with clear attribute indication
    const selectorTextSpan = $("<span>").text(formattedSelector);

    // Add a styled attribute indicator
    if (attributeType !== undefined && attributeType !== "") {
      // Create a styled badge for the attribute
      const attrBadge = $("<span>")
        .addClass("attribute-badge")
        .text(attributeType) // Just show the attribute name without prefix
        .css({
          "background-color": "#e7f3ff",
          color: "#0073aa",
          "border-radius": "3px",
          padding: "2px 6px",
          "margin-left": "8px",
          "font-size": "0.9em",
          "font-weight": "bold",
          display: "inline-block",
          border: "1px solid #0073aa",
        });
      selectorTextSpan.append(" ").append(attrBadge);
    } else {
      // Create a badge for text content
      const textBadge = $("<span>")
        .addClass("text-badge")
        .text("Text content")
        .css({
          "background-color": "#f1f1f1",
          color: "#666",
          "border-radius": "3px",
          padding: "2px 6px",
          "margin-left": "8px",
          "font-size": "0.9em",
          display: "inline-block",
          border: "1px solid #ccc",
        });
      selectorTextSpan.append(" ").append(textBadge);
    }

    // Add data attributes to store the original type and attribute
    selectorTag.attr("data-selector-type", selectorType);

    // Special handling for known problematic selectors
    if (
      (formattedSelector.includes("jet-listing-dynamic-link__link") ||
        formattedSelector.includes("jet-listing-dynamic-link")) &&
      attributeType === "href"
    ) {
      console.log(
        "Special handling for jet-listing-dynamic-link with href attribute"
      );
      // Store a hint for the backend to use special extraction logic
      selectorTag.attr("data-special-case", "jet-listing-link");
    }

    // ALWAYS store the attribute value as a data attribute
    // This is critical for the attribute extraction to work
    selectorTag.attr("data-attribute", attributeType);
    console.log(`Setting data-attribute to: '${attributeType}'`);

    // Create the remove button
    const removeButton = $("<button>")
      .attr("type", "button")
      .addClass("remove-custom-field-selector")
      .html("&times;");

    // Assemble the tag
    selectorTag.append(selectorTextSpan).append(removeButton);

    // Add to the list
    $("#custom-field-selectors-list").append(selectorTag);

    console.log(
      `Added custom selector: ${formattedSelector}${
        attributeType ? " with attribute: " + attributeType : ""
      }`
    );
  }

  /**
   * Extract content using custom selectors
   */
  function extractContentBySelectors() {
    // Get all custom selectors and their attributes
    const customSelectors = [];
    const selectorAttributes = {};
    const specialCases = {};

    $("#custom-field-selectors-list .custom-field-selector-tag").each(function (
      index
    ) {
      // Get only the first text node of the span, which contains just the selector
      // This avoids including the attribute badge text
      let selectorElement = $(this).find("span").get(0);
      let selectorText = "";

      if (selectorElement && selectorElement.childNodes.length > 0) {
        // Get just the text content of the first text node
        selectorText = selectorElement.childNodes[0].nodeValue.trim();
      } else {
        // Fallback to getting all text and cleaning it
        selectorText = $(this).find("span").text();
        // Remove any attribute badge text that might be included
        selectorText = selectorText.replace(/\s*Attribute:\s*[\w-]+\s*$/, "");
        selectorText = selectorText.replace(/\s*text content\s*$/, "");
      }

      // Clean up any remaining attribute notation
      selectorText = selectorText.replace(/\s*\[attr:\s*([^\]]+)\]\s*$/, "");
      selectorText = selectorText.replace(/::attr\([^)]+\)\s*$/, "");

      console.log(`Clean selector text extracted: '${selectorText}'`);
      customSelectors.push(selectorText);

      // Get the attribute value directly from the data attribute
      // This is the key fix - we're explicitly retrieving the data-attribute value
      const attribute = $(this).attr("data-attribute");

      console.log(
        `DEBUG: Raw data-attribute value for selector ${index}: '${attribute}'`
      );
      console.log(`DEBUG: typeof attribute: ${typeof attribute}`);

      // Check if this is a special case selector
      const specialCase = $(this).attr("data-special-case");
      if (specialCase) {
        console.log(
          `DEBUG: Special case for selector ${index}: '${specialCase}'`
        );
        specialCases[index] = specialCase;
      }

      // Always store the attribute in the attributes object
      // This ensures we send the attribute information even if it's an empty string
      selectorAttributes[index] = attribute;

      // Log for debugging
      if (attribute && attribute.length > 0) {
        console.log(
          `Selector ${index}: ${selectorText} with attribute: ${attribute}`
        );
      } else {
        console.log(
          `Selector ${index}: ${selectorText} (extracting text content)`
        );
      }
    });

    if (customSelectors.length === 0) {
      alert(wayback_wp_importer.no_selectors);
      return;
    }

    // Get the HTML content and Wayback URL
    let waybackUrl = "";
    let htmlContent = "";

    // First try waybackExtractData (defined in extract.js)
    if (
      window.waybackExtractData &&
      window.waybackExtractData.fullHtmlContent
    ) {
      waybackUrl = window.waybackExtractData.waybackUrl;
      htmlContent = window.waybackExtractData.fullHtmlContent;
    } else if (window.waybackExtractData && window.waybackExtractData.content) {
      waybackUrl = window.waybackExtractData.waybackUrl;
      htmlContent = window.waybackExtractData.content;
    }

    // If not found, try to get from hidden field if it exists
    if (!htmlContent && $("#wayback-html-content").length) {
      console.log(
        "Trying to get content from wayback-html-content hidden field"
      );
      htmlContent = $("#wayback-html-content").val();
      waybackUrl = $("#wayback-url-reference").val();
    }

    // If still not found, try to get from the response data
    if (
      !htmlContent &&
      window.waybackExtractData &&
      window.waybackExtractData.extractedData
    ) {
      console.log("Trying to get content from extractedData");
      htmlContent = window.waybackExtractData.extractedData.html_content;
      waybackUrl = window.waybackExtractData.extractedData.wayback_url;
    }

    // Debug: Check content availability
    console.log("Content available:", !!htmlContent);
    console.log("Content length:", htmlContent ? htmlContent.length : 0);
    console.log("Wayback URL:", waybackUrl);

    if (!htmlContent) {
      alert(wayback_wp_importer.no_content);
      return;
    }

    // Process the HTML content to handle encoding and escaping
    if (typeof htmlContent === "string") {
      try {
        // First, handle any double-escaped content
        if (htmlContent.includes('\\"') || htmlContent.includes("\\\\")) {
          // Replace escaped quotes and backslashes
          htmlContent = htmlContent
            .replace(/\\\\/g, "\\")
            .replace(/\\"/g, '"')
            .replace(/\\'/g, "'");
          console.log("Processed escaped characters in HTML content");
        }

        // Check if content is base64 encoded (after unescaping)
        const cleanContent = htmlContent.trim();
        if (/^[A-Za-z0-9+/=]+$/.test(cleanContent)) {
          try {
            const decoded = atob(cleanContent);
            if (decoded) {
              htmlContent = decoded;
              console.log("Successfully decoded base64 HTML content");
            }
          } catch (e) {
            console.log(
              "Content is not base64 encoded or decoding failed, using as-is"
            );
          }
        }
      } catch (e) {
        console.error("Error processing HTML content:", e);
      }
    }

    console.log(
      "HTML content ready for parsing, length:",
      htmlContent ? htmlContent.length : 0
    );

    // Show loading indicator
    $("#custom-selectors-preview").html(
      '<div class="spinner is-active" style="float:none;"></div>'
    );

    // Make sure we have valid attributes for all selectors
    // This ensures we don't send undefined values
    customSelectors.forEach((selector, index) => {
      if (selectorAttributes[index] === undefined) {
        console.log(`Fixing undefined attribute for selector ${index}`);
        selectorAttributes[index] = "";
      }
    });
    console.log("HTML content before AJAX:", htmlContent.substring(0, 500));

    // Make AJAX request

    // Make sure we have valid attributes for all selectors
    // This ensures we don't send undefined values
    customSelectors.forEach((selector, index) => {
      if (selectorAttributes[index] === undefined) {
        console.log(`Fixing undefined attribute for selector ${index}`);
        selectorAttributes[index] = "";
      }
    });

    // Make AJAX request to extract content
    $.ajax({
      url: wayback_wp_importer.ajax_url,
      type: "POST",
      data: {
        action: "wayback_extract_selectors",
        nonce: wayback_wp_importer.nonce,
        content: htmlContent,
        custom_selectors: JSON.stringify(customSelectors),
        attributes: JSON.stringify(selectorAttributes),
        special_cases: JSON.stringify(specialCases)
      },
      success: function (response) {
        console.log("AJAX response:", response);

        if (response.success) {
          // Check if we have valid data structure
          if (response.data && response.data.previews) {
            // Display the results
            displaySelectorsPreview(response.data);
            $("#custom-selectors-preview").show();
          } else {
            console.error("Invalid response format:", response);
            alert(
              wayback_wp_importer.invalid_response ||
                "Invalid response format from server"
            );
            $("#custom-selectors-preview").html("");
          }
        } else {
          // Show error message
          alert(response.data.message || wayback_wp_importer.ajax_error);
          $("#custom-selectors-preview").html("");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", { xhr, status, error });
        alert(wayback_wp_importer.ajax_error);
        $("#custom-selectors-preview").html("");
      },
    });
  }

  /**
   * Display the selectors preview
   *
   * @param {object} data The data returned from the AJAX request
   */
  function displaySelectorsPreview(data) {
    console.log("Displaying selectors preview:", data);
    // Reset stored data
    extractedSelectorData = {};

    // Expecting data to be an array of {selector, attribute, value}
    let results = Array.isArray(data.previews)
      ? data.previews
      : Array.isArray(data.fields)
      ? data.fields
      : [];

    // Create the preview table
    const previewTable = $("<table>").addClass("widefat fixed");

    // Create table header with attribute column
    const tableHeader = $("<thead>").append(
      $("<tr>").append(
        $("<th>").html('<input type="checkbox" id="select-all-selectors">'),
        $("<th>").text("Selector"),
        $("<th>").html(
          '<strong>Attribute Type</strong> <span class="dashicons dashicons-info" title="The type of data being extracted: attribute name or text content"></span>'
        ),
        $("<th>").text("Extracted Value")
      )
    );
    previewTable.append(tableHeader);

    // Create table body
    const tableBody = $("<tbody>");
    let hasResults = false;

    results.forEach(function (item, idx) {
      let selector = item.selector || "";
      let attribute = item.attribute || "";
      let value = item.value;
      // Defensive: always string and trim
      if (typeof value !== "string") {
        value = value == null ? "" : String(value);
      }
      value = value.trim();
      const found = value !== "";
      // Store the value if found
      if (found) {
        extractedSelectorData[selector] = value;
        hasResults = true;
      }
      // Create table row
      const row = $("<tr>").addClass(found ? "found" : "not-found");
      // Add checkbox cell
      row.append(
        $("<td>").append(
          $("<input>")
            .attr("type", "checkbox")
            .addClass("selector-checkbox")
            .attr("data-selector", selector)
            .prop("disabled", !found)
        )
      );
      // Add selector cell
      row.append($("<td>").text(selector));
      // Add attribute cell with better formatting and visual styling
      const attrCell = $("<td>");
      if (attribute) {
        attrCell.html(
          '<span style="background-color: #e7f3ff; color: #0073aa; border-radius: 3px; padding: 2px 6px; ' +
            'font-weight: bold; display: inline-block; border: 1px solid #0073aa;">' +
            attribute +
            "</span>"
        );
      } else {
        attrCell.html(
          '<span style="background-color: #f1f1f1; color: #666; border-radius: 3px; padding: 2px 6px; ' +
            'display: inline-block; border: 1px solid #ccc;">' +
            "text content</span>"
        );
      }
      row.append(attrCell);
      // Add value cell
      const valueCell = $("<td>");
      if (value) {
        valueCell.text(value);
      } else {
        valueCell.html(
          "<em>" + (wayback_wp_importer.not_found || "Not found") + "</em>"
        );
      }
      row.append(valueCell);
      // Add row to table body
      tableBody.append(row);
    });

    // If no results were found, add a message
    if (!hasResults) {
      tableBody.append(
        $("<tr>").append(
          $("<td>")
            .attr("colspan", "4")
            .html(
              "<em>" +
                (wayback_wp_importer.no_results || "No results found") +
                "</em>"
            )
        )
      );
    }

    // Add body to table
    previewTable.append(tableBody);

    // Update the preview container
    $("#custom-selectors-preview").empty().append(previewTable);

    // Show/hide the add to custom fields button based on results
    if (hasResults) {
      $("#add-selectors-to-custom-fields").show();
    } else {
      $("#add-selectors-to-custom-fields").hide();
    }
  }

  // Store extracted data for later use
  let extractedSelectorData = {};

  // Initialize when document is ready
  $(document).ready(function () {
    initCustomSelectors();
    setupSelectorsToCustomFieldsHandlers();
  });

  // Setup handlers for adding selectors to custom fields
  function setupSelectorsToCustomFieldsHandlers() {
    // Handle select all checkbox
    $(document).on("click", "#select-all-selectors", function () {
      $(".selector-checkbox:not(:disabled)").prop(
        "checked",
        $(this).is(":checked")
      );
    });

    // Handle add selected to custom fields button
    $(document).on("click", "#add-selectors-to-custom-fields", function () {
      const selectedSelectors = $(".selector-checkbox:checked");

      if (selectedSelectors.length === 0) {
        alert(
          wayback_wp_importer.no_selection || "Please select at least one item"
        );
        return;
      }

      // Add each selected selector as a custom field
      selectedSelectors.each(function () {
        const selector = $(this).data("selector");
        const value = extractedSelectorData[selector];

        // Get attribute information from the row
        const row = $(this).closest("tr");
        const selectorText = row.find("td:nth-child(2)").text();
        const attributeText = row.find("td:nth-child(3)").text();

        // Create a descriptive key that includes attribute information if available
        let fieldKey = selectorText;
        if (attributeText && attributeText !== "-") {
          // Make attribute information more prominent
          fieldKey += ` [Attribute: ${attributeText}]`;
        } else {
          // Explicitly indicate text content extraction
          fieldKey += " [Text content]";
        }

        if (value) {
          // Use the exposed addCustomFieldRow function
          if (
            typeof window.WaybackWpImporter !== "undefined" &&
            typeof window.WaybackWpImporter.addCustomFieldRow === "function"
          ) {
            window.WaybackWpImporter.addCustomFieldRow(fieldKey, value);
          } else {
            // Fallback if the function doesn't exist
            console.warn(
              "WaybackWpImporter.addCustomFieldRow function not found"
            );
            const customFieldsList = $("#custom-fields-list");
            const fieldHtml =
              '<div class="custom-field-row">' +
              '<input type="text" class="custom-field-key" name="custom_fields[keys][]" value="' +
              fieldKey +
              '">' +
              '<textarea class="custom-field-value" name="custom_fields[values][]" rows="2">' +
              value +
              "</textarea>" +
              '<button type="button" class="button remove-custom-field">' +
              wayback_wp_importer.remove_custom_field +
              "</button>" +
              "</div>";
            customFieldsList.append(fieldHtml);
          }
        }
      });

      // Scroll to the custom fields section
      $("html, body").animate(
        {
          scrollTop: $(".wayback-preview-custom-fields").offset().top,
        },
        500
      );
    });
  }
})(jQuery);

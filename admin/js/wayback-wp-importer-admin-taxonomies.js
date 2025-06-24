/**
 * JavaScript for handling taxonomies in Wayback WP Importer.
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 */

/**
 * Wayback WordPress Importer Taxonomies Functions
 * This file contains functions for handling taxonomies in the Wayback WP Importer.
 *
 * @since      1.0.0
 * @package    Wayback_WP_Importer
 */

// Create a global namespace for our taxonomies functions
window.WaybackTaxonomies = window.WaybackTaxonomies || {};

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize the taxonomies handler
        WaybackTaxonomies.init();
        
        // Load taxonomies initially with empty content
        // This ensures the UI is available even before extraction
        var postType = $('#post-type-selector').val() || 'post';
        WaybackTaxonomies.loadTaxonomies(postType);
    });

    /**
     * Taxonomies handler object
     */
    var WaybackTaxonomies = {
        // Store loaded taxonomies
        taxonomies: {},
        
        // Initialize the taxonomies handler
        init: function() {
            // Handle post type changes
            $(document).on('change', '#post-type-selector', function() {
                var selectedPostType = $(this).val();
                WaybackTaxonomies.loadTaxonomies(selectedPostType);
            });
            
            // Handle adding new taxonomy terms
            $(document).on('click', '.add-new-taxonomy-term', function(e) {
                e.preventDefault();
                var taxonomyName = $(this).data('taxonomy');
                var newTermInput = $('#new-term-' + taxonomyName);
                var newTerm = newTermInput.val().trim();
                
                if (newTerm) {
                    WaybackTaxonomies.addNewTerm(taxonomyName, newTerm);
                    newTermInput.val('');
                }
            });
            
            // Populate the hidden category and tag fields for backward compatibility
            $(document).on('change', '.hierarchical-taxonomy input[type="checkbox"]', function() {
                WaybackTaxonomies.updateLegacyFields();
            });
            
            // Update legacy fields when tags are added or removed
            $(document).on('wayback_tag_updated', function() {
                WaybackTaxonomies.updateLegacyFields();
            });
        },
        
        // Load all taxonomies via AJAX
        loadTaxonomies: function(postType) {
            $('#taxonomies-container').html('<div class="spinner is-active" style="float:none;"></div>');
            
            // Get the post type from the selector if not provided
            if (!postType) {
                postType = $('#post-type-selector').val() || 'post';
            }
            
            // Get the HTML content from the preview if available
            var htmlContent = '';
            var waybackUrl = $('#wayback-url').val();
            if ($('#content-preview').length) {
                htmlContent = $('#content-preview').html() || '';
            }
            
            $.ajax({
                url: wayback_wp_importer.ajax_url,
                type: 'POST',
                data: {
                    action: 'wayback_get_taxonomies',
                    nonce: wayback_wp_importer.nonce,
                    post_type: postType,
                    wayback_url: waybackUrl,
                    html_content: htmlContent
                },
                success: function(response) {
                    if (response.success) {
                        WaybackTaxonomies.taxonomies = response.data.taxonomies;
                        WaybackTaxonomies.renderTaxonomyUI(response.data.taxonomies);
                        
                        // If there are extracted terms, populate them
                        if (response.data.extracted_terms) {
                            WaybackTaxonomies.populateExtractedTerms(response.data.extracted_terms);
                        }
                        
                        // Trigger event that taxonomies are loaded
                        $(document).trigger('wayback_taxonomies_loaded');
                        
                        // Update legacy fields for backward compatibility
                        WaybackTaxonomies.updateLegacyFields();
                    } else {
                        $('#taxonomies-container').html('<p class="error">' + wayback_wp_importer.error_text + '</p>');
                    }
                },
                error: function() {
                    $('#taxonomies-container').html('<p class="error">' + wayback_wp_importer.error_text + '</p>');
                }
            });
        },
        
        // Render the taxonomy UI
        renderTaxonomyUI: function(taxonomies) {
            const container = $('#taxonomies-container');
            container.empty();
            
            // If no taxonomies, show message
            if (Object.keys(taxonomies).length === 0) {
                container.html('<p>' + wayback_wp_importer.no_taxonomies_found + '</p>');
                return;
            }
            
            // Add each taxonomy section
            $.each(taxonomies, function(name, taxonomy) {
                const taxonomySection = $('<div>').addClass('taxonomy-section');
                taxonomySection.append($('<h4>').text(taxonomy.label));
                
                if (taxonomy.hierarchical) {
                    // Create hierarchical UI (like categories)
                    const hierarchicalUI = WaybackTaxonomies.createHierarchicalTaxonomyUI(taxonomy);
                    taxonomySection.append(hierarchicalUI);
                } else {
                    // Create non-hierarchical UI (like tags)
                    const nonHierarchicalUI = WaybackTaxonomies.createNonHierarchicalTaxonomyUI(taxonomy);
                    taxonomySection.append(nonHierarchicalUI);
                }
                
                container.append(taxonomySection);
            });
        },
        
        // Create UI for hierarchical taxonomies (like categories)
        createHierarchicalTaxonomyUI: function(taxonomy) {
            const container = $('<div>').addClass('hierarchical-taxonomy').attr('data-taxonomy', taxonomy.name);
            
            // Create a tree view of terms
            const termTree = WaybackTaxonomies.buildTermTree(taxonomy.terms);
            const termList = $('<ul>').addClass('hierarchical-terms-list');
            
            // Add terms to the tree
            WaybackTaxonomies.addTermsToList(termList, termTree, taxonomy.name);
            
            container.append(termList);
            
            // Add field for new terms
            const newTermField = $('<div>').addClass('add-new-term-field');
            newTermField.append($('<input>').attr({
                type: 'text',
                id: 'new-term-' + taxonomy.name,
                placeholder: wayback_wp_importer.add_new_term
            }));
            newTermField.append($('<button>').addClass('button add-new-taxonomy-term')
                .attr('data-taxonomy', taxonomy.name)
                .text(wayback_wp_importer.add_button_text));
            
            container.append(newTermField);
            
            return container;
        },
        
        // Create UI for non-hierarchical taxonomies (like tags)
        createNonHierarchicalTaxonomyUI: function(taxonomy) {
            const container = $('<div>').addClass('non-hierarchical-taxonomy').attr('data-taxonomy', taxonomy.name);
            
            // Create a tags container like WordPress
            const tagsContainer = $('<div>').addClass('taxonomy-tags-container');
            
            // Create a text input for adding new tags
            const tagInput = $('<input>').attr({
                type: 'text',
                class: 'taxonomy-tag-input',
                id: 'taxonomy-input-' + taxonomy.name,
                placeholder: wayback_wp_importer.add_new_tag || 'Add new tag...'
            });
            
            // Create a hidden input to store all selected tags for form submission
            const hiddenInput = $('<input>').attr({
                type: 'hidden',
                name: 'taxonomy[' + taxonomy.name + ']',
                id: 'taxonomy-hidden-' + taxonomy.name,
                value: ''
            });
            
            // Create the tags display area
            const tagsDisplay = $('<div>').addClass('taxonomy-tags-display');
            
            // Add existing terms as tags
            if (taxonomy.terms && taxonomy.terms.length > 0) {
                $.each(taxonomy.terms, function(i, term) {
                    WaybackTaxonomies.addTagToDisplay(tagsDisplay, taxonomy.name, term.id, term.name);
                });
            }
            
            // Add event handler for adding new tags
            tagInput.on('keydown', function(e) {
                if (e.which === 13 || e.which === 188) { // Enter or comma
                    e.preventDefault();
                    const tagName = $(this).val().trim().replace(/,+$/, '');
                    if (tagName) {
                        // Add as a new tag with 'new:' prefix
                        WaybackTaxonomies.addTagToDisplay(tagsDisplay, taxonomy.name, 'new:' + tagName, tagName);
                        $(this).val('');
                    }
                }
            });
            
            tagsContainer.append(tagsDisplay).append(tagInput).append(hiddenInput);
            container.append(tagsContainer);
            
            return container;
        },
        
        // Build a tree structure from flat terms list
        buildTermTree: function(terms) {
            // First pass: create a map of terms by ID
            const termsMap = {};
            $.each(terms, function(i, term) {
                termsMap[term.id] = {
                    id: term.id,
                    name: term.name,
                    slug: term.slug,
                    parent: term.parent,
                    children: []
                };
            });
            
            // Second pass: build the tree
            const tree = [];
            $.each(termsMap, function(id, term) {
                if (term.parent === 0) {
                    // This is a root term
                    tree.push(term);
                } else {
                    // This is a child term
                    if (termsMap[term.parent]) {
                        termsMap[term.parent].children.push(term);
                    }
                }
            });
            
            return tree;
        },
        
        // Add terms to a list recursively
        addTermsToList: function(list, terms, taxonomyName) {
            $.each(terms, function(i, term) {
                const item = $('<li>');
                
                // Add checkbox
                const checkbox = $('<input>').attr({
                    type: 'checkbox',
                    name: 'taxonomy[' + taxonomyName + '][]',
                    value: term.id,
                    id: 'term-' + term.id
                });
                
                // Add label
                const label = $('<label>').attr('for', 'term-' + term.id).text(term.name);
                
                item.append(checkbox).append(label);
                
                // Add children if any
                if (term.children && term.children.length > 0) {
                    const childList = $('<ul>').addClass('children');
                    WaybackTaxonomies.addTermsToList(childList, term.children, taxonomyName);
                    item.append(childList);
                }
                
                list.append(item);
            });
        },
        
        // Add a new term to the UI
        addNewTerm: function(taxonomyName, termName) {
            // For hierarchical taxonomies, add a new checkbox
            if (WaybackTaxonomies.taxonomies[taxonomyName].hierarchical) {
                const termsList = $('.hierarchical-taxonomy ul.hierarchical-terms-list').first();
                
                const item = $('<li>');
                const checkbox = $('<input>').attr({
                    type: 'checkbox',
                    name: 'taxonomy[' + taxonomyName + '][]',
                    value: 'new:' + termName,
                    id: 'term-new-' + termName.replace(/\s+/g, '-').toLowerCase(),
                    checked: 'checked'
                });
                
                const label = $('<label>').attr('for', 'term-new-' + termName.replace(/\s+/g, '-').toLowerCase())
                    .text(termName + ' ' + wayback_wp_importer.new_term_text);
                
                item.append(checkbox).append(label);
                termsList.append(item);
            } else {
                // For non-hierarchical taxonomies, add a new option to select2
                const select = $('#taxonomy-' + taxonomyName);
                
                // Create new option
                const newOption = new Option(termName, 'new:' + termName, true, true);
                select.append(newOption).trigger('change');
            }
        },
        
        // Add a tag to the display area
        addTagToDisplay: function(tagsDisplay, taxonomyName, termId, termName) {
            // Create unique ID for this tag
            const tagId = 'tag-' + taxonomyName + '-' + termId.toString().replace(/:/g, '-');
            
            // Check if this tag already exists
            if ($('#' + tagId).length) {
                return; // Skip if already added
            }
            
            // Create the tag element
            const tagElement = $('<span>').addClass('taxonomy-tag').attr('id', tagId);
            tagElement.attr('data-term-id', termId);
            
            // Add the term name
            tagElement.append($('<span>').addClass('tag-name').text(termName));
            
            // Add remove button
            const removeButton = $('<span>').addClass('tag-remove').html('&times;');
            removeButton.on('click', function() {
                $(this).parent().remove();
                WaybackTaxonomies.updateHiddenField(taxonomyName);
            });
            
            // Assemble the tag
            tagElement.append(removeButton);
            
            // Add to display
            tagsDisplay.append(tagElement);
            
            // Update the hidden field
            WaybackTaxonomies.updateHiddenField(taxonomyName);
            
            // Trigger event for tag updates
            $(document).trigger('wayback_tag_updated', [taxonomyName]);
        },
        
        // Update the hidden field with all selected tags
        updateHiddenField: function(taxonomyName) {
            const hiddenField = $('#taxonomy-hidden-' + taxonomyName);
            const tagsDisplay = hiddenField.siblings('.taxonomy-tags-display');
            
            // Collect all term IDs
            const termIds = [];
            tagsDisplay.find('.taxonomy-tag').each(function() {
                termIds.push($(this).data('term-id'));
            });
            
            // Update the hidden field
            hiddenField.val(termIds.join(','));
        },
        
        // Populate extracted terms from the content
        populateExtractedTerms: function(extractedTerms) {
            $.each(extractedTerms, function(taxonomyName, terms) {
                if (WaybackTaxonomies.taxonomies[taxonomyName]) {
                    if (WaybackTaxonomies.taxonomies[taxonomyName].hierarchical) {
                        // For hierarchical taxonomies, try to match terms by name
                        $.each(terms, function(i, termName) {
                            // Look for an existing term with this name
                            let found = false;
                            $.each(WaybackTaxonomies.taxonomies[taxonomyName].terms, function(j, existingTerm) {
                                if (existingTerm.name.toLowerCase() === termName.toLowerCase()) {
                                    // Check the checkbox for this term
                                    $('#term-' + existingTerm.id).prop('checked', true);
                                    found = true;
                                    return false; // Break the loop
                                }
                            });
                            
                            // If not found, add as a new term
                            if (!found) {
                                WaybackTaxonomies.addNewTerm(taxonomyName, termName);
                            }
                        });
                    } else {
                        // For non-hierarchical taxonomies, add to tags display
                        const tagsDisplay = $('#taxonomy-hidden-' + taxonomyName).siblings('.taxonomy-tags-display');
                        
                        if (tagsDisplay.length) {
                            $.each(terms, function(i, termName) {
                                // Look for an existing term with this name
                                let found = false;
                                $.each(WaybackTaxonomies.taxonomies[taxonomyName].terms, function(j, existingTerm) {
                                    if (existingTerm.name.toLowerCase() === termName.toLowerCase()) {
                                        // Add this term to the tags display
                                        WaybackTaxonomies.addTagToDisplay(tagsDisplay, taxonomyName, existingTerm.id, existingTerm.name);
                                        found = true;
                                        return false; // Break the loop
                                    }
                                });
                                
                                // If not found, add as a new term
                                if (!found) {
                                    WaybackTaxonomies.addTagToDisplay(tagsDisplay, taxonomyName, 'new:' + termName, termName);
                                }
                            });
                        }
                    }
                }
            });
        },
        
        // Get all selected taxonomy terms for import
        getSelectedTaxonomyTerms: function() {
            const selectedTerms = {};
            
            // Process hierarchical taxonomies (checkboxes)
            $('.hierarchical-taxonomy').each(function() {
                // Get the taxonomy name from the data attribute instead of the heading text
                const taxonomyName = $(this).data('taxonomy');
                selectedTerms[taxonomyName] = [];
                
                $(this).find('input[type="checkbox"]:checked').each(function() {
                    selectedTerms[taxonomyName].push($(this).val());
                });
            });
            
            // Process non-hierarchical taxonomies (tag display)
            $('.non-hierarchical-taxonomy').each(function() {
                // Get the taxonomy name from the data attribute instead of the heading text
                const taxonomyName = $(this).data('taxonomy');
                const hiddenInput = $(this).find('input[type="hidden"]');
                
                if (hiddenInput.val()) {
                    selectedTerms[taxonomyName] = hiddenInput.val().split(',');
                } else {
                    selectedTerms[taxonomyName] = [];
                }
            });
            
            return selectedTerms;
        }
    };

    // Expose the taxonomies handler to the global scope
    window.WaybackTaxonomies = WaybackTaxonomies;
    
    // Expose getSelectedTerms as a convenience method
    window.WaybackTaxonomies.getSelectedTerms = WaybackTaxonomies.getSelectedTaxonomyTerms;
    
    /**
     * Update the legacy category and tag fields for backward compatibility
     * This populates the hidden fields with comma-separated values from the dynamic UI
     */
    WaybackTaxonomies.updateLegacyFields = function() {
        // Get all selected terms
        const selectedTerms = WaybackTaxonomies.getSelectedTaxonomyTerms();
        
        // Update categories field if category taxonomy exists
        if (selectedTerms.hasOwnProperty('category')) {
            const categoryTerms = [];
            
            // Extract term names from the selected terms
            $.each(selectedTerms.category, function(i, termId) {
                if (termId.toString().startsWith('new:')) {
                    // This is a new term, use the name directly
                    categoryTerms.push(termId.substring(4));
                } else {
                    // Find the term in the taxonomies object
                    $.each(WaybackTaxonomies.taxonomies.category.terms, function(j, term) {
                        if (term.id.toString() === termId.toString()) {
                            categoryTerms.push(term.name);
                            return false; // Break the loop
                        }
                    });
                }
            });
            
            // Update the hidden field
            $('#post-categories').val(categoryTerms.join(', '));
        }
        
        // Update tags field if post_tag taxonomy exists
        if (selectedTerms.hasOwnProperty('post_tag')) {
            const tagTerms = [];
            
            // Extract term names from the selected terms
            $.each(selectedTerms.post_tag, function(i, termId) {
                if (termId.toString().startsWith('new:')) {
                    // This is a new term, use the name directly
                    tagTerms.push(termId.substring(4));
                } else {
                    // Find the term in the taxonomies object
                    $.each(WaybackTaxonomies.taxonomies.post_tag.terms, function(j, term) {
                        if (term.id.toString() === termId.toString()) {
                            tagTerms.push(term.name);
                            return false; // Break the loop
                        }
                    });
                }
            });
            
            // Update the hidden field
            $('#post-tags').val(tagTerms.join(', '));
        }
    };

})(jQuery);

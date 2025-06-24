/**
 * Test script for Wayback WordPress Importer.
 * This file contains tests for the post type and permalink structure functionality.
 */
(function($) {
    'use strict';

    /**
     * Run tests for the post type and permalink structure functionality.
     */
    function runTests() {
        console.log('Running Wayback WP Importer tests...');
        
        // Test that the post types are correctly collected
        testPostTypeCollection();
        
        // Test that the permalink structures are correctly collected
        testPermalinkStructureCollection();
        
        // Test that the AJAX request includes the correct parameters
        testAjaxRequestParameters();
        
        console.log('All tests completed!');
    }
    
    /**
     * Test that post types are correctly collected from form inputs.
     */
    function testPostTypeCollection() {
        console.log('Testing post type collection...');
        
        // Mock the post type checkboxes
        const mockPostTypes = ['post', 'page', 'custom_post_type'];
        let mockHtml = '';
        
        mockPostTypes.forEach(type => {
            mockHtml += `<input type="checkbox" name="post_types[]" value="${type}" checked>`;
        });
        
        // Add the mock HTML to the document
        $('body').append('<div id="test-container">' + mockHtml + '</div>');
        
        // Collect the post types
        const postTypes = [];
        $('input[name="post_types[]"]:checked').each(function() {
            postTypes.push($(this).val());
        });
        
        // Check that all post types were collected
        const allTypesCollected = mockPostTypes.every(type => postTypes.includes(type));
        console.log('All post types collected:', allTypesCollected);
        console.log('Collected post types:', postTypes);
        
        // Clean up
        $('#test-container').remove();
    }
    
    /**
     * Test that permalink structures are correctly collected from form inputs.
     */
    function testPermalinkStructureCollection() {
        console.log('Testing permalink structure collection...');
        
        // Mock the permalink structure checkboxes
        const mockStructures = {
            'date': '1',
            'postname': '1',
            'post_id': '1',
            'custom': '1'
        };
        
        let mockHtml = '';
        Object.keys(mockStructures).forEach(key => {
            mockHtml += `<input type="checkbox" name="permalink_structures[${key}]" value="${mockStructures[key]}" checked>`;
        });
        
        // Add the mock HTML to the document
        $('body').append('<div id="test-container">' + mockHtml + '</div>');
        
        // Collect the permalink structures
        const permalinkStructures = {};
        $('input[name^="permalink_structures"]:checked').each(function() {
            const key = $(this).attr('name').match(/\[(.*?)\]/)[1];
            permalinkStructures[key] = $(this).val();
        });
        
        // Check that all structures were collected
        const allStructuresCollected = Object.keys(mockStructures).every(key => 
            permalinkStructures[key] === mockStructures[key]
        );
        
        console.log('All permalink structures collected:', allStructuresCollected);
        console.log('Collected permalink structures:', permalinkStructures);
        
        // Clean up
        $('#test-container').remove();
    }
    
    /**
     * Test that the AJAX request includes the correct parameters.
     */
    function testAjaxRequestParameters() {
        console.log('Testing AJAX request parameters...');
        
        // Mock jQuery.ajax
        const originalAjax = $.ajax;
        
        $.ajax = function(options) {
            console.log('Mock AJAX call with data:', options.data);
            
            // Check that the post_types and permalink_structures are included
            const hasPostTypes = options.data.post_types !== undefined;
            const hasPermalinkStructures = options.data.permalink_structures !== undefined;
            
            console.log('Request includes post_types:', hasPostTypes);
            console.log('Request includes permalink_structures:', hasPermalinkStructures);
            
            // Restore the original ajax function
            $.ajax = originalAjax;
            
            // Return a mock promise
            return {
                success: function(callback) {
                    callback({
                        success: true,
                        data: {
                            title: 'Test Post',
                            content: 'Test Content',
                            post_type: 'post',
                            permalink_structure: 'date'
                        }
                    });
                    return this;
                },
                error: function() {
                    return this;
                }
            };
        };
        
        // Mock the form data
        $('body').append(`
            <div id="test-container">
                <input id="wayback-url" value="https://web.archive.org/web/20200101000000/https://example.com/post">
                <input id="post-status" value="draft">
                <input type="checkbox" name="post_types[]" value="post" checked>
                <input type="checkbox" name="permalink_structures[date]" value="1" checked>
            </div>
        `);
        
        // Call extractContent
        if (typeof extractContent === 'function') {
            extractContent();
        } else {
            console.error('extractContent function not found!');
        }
        
        // Clean up
        $('#test-container').remove();
    }
    
    // Run tests when the document is ready
    $(document).ready(function() {
        // Only run tests if we're on the admin page
        if ($('.wrap.wayback-wp-importer').length) {
            // Wait a bit to ensure all scripts are loaded
            setTimeout(runTests, 1000);
        }
    });
    
})(jQuery);

jQuery(document).ready(function($) {
    
    /**
     * Update customer statistics dynamically in the Customer Statistics section
     * @param {object} stats - Statistics object with created, updated, skipped counts
     */
    function updateCustomerStatistics(stats) {
        // Update the statistics in the Customer Statistics section
        var $statsContainer = $('.edt-sync-customers-page .import-stats').last();
        if ($statsContainer.length) {
            var statsHtml = '<h3>Last Import</h3>' +
                '<ul>' +
                '<li><strong>Created:</strong> ' + stats.created + '</li>' +
                '<li><strong>Updated:</strong> ' + stats.updated + '</li>' +
                '<li><strong>Skipped:</strong> ' + stats.skipped + '</li>' +
                '</ul>';
            $statsContainer.html(statsHtml);
        }
    }
    
    $('#import-customers').on('click', function() {
        var $button = $(this);
        var $status = $('#import-status');
        var $progressContainer = $('#progress-container');
        var $progressBar = $('#progress-bar');
        var $progressText = $('#progress-text');
        
        // Disable the button to prevent multiple clicks
        $button.prop('disabled', true).text('Importing...');
        
        // Show progress container
        $progressContainer.show();
        $progressBar.css('width', '0%').text('0%');
        $progressText.text('Initializing import...');
        
        // Start with chunk 0
        processChunk(0);
        
        /**
         * Process a single chunk of customer data
         * @param {number} chunkIndex - The index of the chunk to process
         */
        function processChunk(chunkIndex) {
            $status.html('<p>Processing chunk ' + (chunkIndex + 1) + '...</p>');
            
            // Make the AJAX request for this chunk
            $.ajax({
                url: edtSyncAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edt_sync_import_customers',
                    chunk: chunkIndex,
                    nonce: edtSyncAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update progress bar
                        var progress = response.data.progress || 0;
                        $progressBar.css('width', progress + '%').text(progress + '%');
                        $progressText.text(progress + '%');
                        
                        // Check if we need to process more chunks
                        if (!response.data.isComplete) {
                            // Display interim stats
                            var stats = response.data.stats;
                            var statsHtml = '<p>Progress: ' + progress + '%</p>' +
                                '<ul>' +
                                '<li>Processed: ' + stats.processed + '</li>' +
                                '<li>Created: ' + stats.created + '</li>' +
                                '<li>Updated: ' + stats.updated + '</li>' +
                                '<li>Skipped: ' + stats.skipped + '</li>' +
                                '</ul>';
                            $status.html(statsHtml);
                            
                            // Process the next chunk
                            setTimeout(function() {
                                processChunk(response.data.nextChunk);
                            }, 500); // Small delay to allow UI to update
                        } else {
                            // Import is complete
                            $button.prop('disabled', false).text('Import Customers');
                            $progressBar.css('width', '100%').text('100%');
                            $progressText.text('Import completed!');
                            
                            // Display final statistics
                            var stats = response.data.stats;
                            var statsHtml = '<div class="import-stats">' +
                                '<h3>Import Summary</h3>' +
                                '<ul>' +
                                '<li><strong>Total customers:</strong> ' + stats.total + '</li>' +
                                '<li><strong>Processed:</strong> ' + stats.processed + '</li>' +
                                '<li><strong>New accounts created:</strong> ' + stats.created + '</li>' +
                                '<li><strong>Existing accounts updated:</strong> ' + stats.updated + '</li>' +
                                '<li><strong>Skipped:</strong> ' + stats.skipped + '</li>' +
                                '<li><strong>Exported to Edge:</strong> ' + stats.exported + '</li>' +
                                '</ul></div>';
                            $status.html('<p>Import completed successfully!</p>' + statsHtml);
                            
                            // Update statistics dynamically in the Customer Statistics section
                            updateCustomerStatistics(stats);
                            
                            // Hide progress container with smooth fade after showing completion for 2 seconds
                            setTimeout(function() {
                                $progressContainer.fadeOut(500);
                            }, 2000);
                        }
                    } else {
                        // Error handling
                        $button.prop('disabled', false).text('Import Customers');
                        $status.html('<p class="error">Error: ' + (response.data || 'Unknown error') + '</p>');
                        
                        // Hide progress container on error
                        setTimeout(function() {
                            $progressContainer.fadeOut(500);
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    // Network error handling
                    $button.prop('disabled', false).text('Import Customers');
                    $status.html('<p class="error">Network error: ' + error + '</p>');
                    console.error('AJAX Error:', xhr.responseText);
                    
                    // Hide progress container on network error
                    setTimeout(function() {
                        $progressContainer.fadeOut(500);
                    }, 3000);
                }
            });
        }
    });
    
    // Function to toggle custom minutes field
    function toggleCustomMinutes() {
        var selected = $('#edge_cron_interval').val();
        if (selected === 'edge_custom_minutes') {
            $('.custom-minutes-container').show();
        } else {
            $('.custom-minutes-container').hide();
        }
    }
    
    // Run when the document loads
    toggleCustomMinutes();
    
    // Run when select value changes
    $('#edge_cron_interval').on('change', toggleCustomMinutes);
}); 
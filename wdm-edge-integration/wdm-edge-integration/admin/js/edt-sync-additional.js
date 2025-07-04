jQuery(document).ready(function($) {
    // Function to update product statistics dynamically
    function updateProductStatistics(stats) {
        // Update the statistics in the Product Statistics section
        var statsContainer = $('.edt-sync-products-page .import-stats').last();
        if (statsContainer.length) {
            var statsHtml = '<h3>Last Import</h3>' +
                '<ul>' +
                '<li><strong>Created:</strong> ' + stats.created + '</li>' +
                '<li><strong>Updated:</strong> ' + stats.updated + '</li>' +
                '<li><strong>Skipped:</strong> ' + stats.skipped + '</li>' +
                '</ul>';
            statsContainer.html(statsHtml);
        }
    }
    
    // Function to toggle custom minutes field for customers
    function toggleCustomerCustomMinutes() {
        var selected = $('#edge_customer_cron_interval').val();
        if (selected === 'edge_customer_custom_minutes') {
            $('#customer-custom-minutes-container').show();
        } else {
            $('#customer-custom-minutes-container').hide();
        }
    }
    
    // Function to toggle custom minutes field for products
    function toggleProductCustomMinutes() {
        var selected = $('#edge_product_cron_interval').val();
        if (selected === 'edge_product_custom_minutes') {
            $('#product-custom-minutes-container').show();
        } else {
            $('#product-custom-minutes-container').hide();
        }
    }
    
    // Initialize form visibility on page load
    toggleCustomerCustomMinutes();
    toggleProductCustomMinutes();
    
    // Handle changes to select fields
    $('#edge_customer_cron_interval').on('change', toggleCustomerCustomMinutes);
    $('#edge_product_cron_interval').on('change', toggleProductCustomMinutes);
    
    // Handle product import button click with chunked processing
    $('#import-products').on('click', function() {
        var button = $(this);
        button.prop('disabled', true);
        $('#product-import-status').html('<p>Importing products... This may take a few minutes.</p>');
        
        // Show progress container
        $('#progress-container').show();
        $('#progress-bar').css('width', '0%').text('0%');
        $('#progress-text').text('Starting import...');
        
        // Start chunked import process
        function processProductChunk(chunk = 0) {
            $.ajax({
                url: edtSyncAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edge_import_products',
                    chunk: chunk,
                    nonce: edtSyncAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.isComplete) {
                            // Import completed
                            $('#progress-bar').css('width', '100%').text('100%');
                            $('#progress-text').text('Import completed!');
                            $('#product-import-status').html('<p>Product import completed successfully.</p><ul>' +
                                '<li>Total: ' + response.data.stats.total + '</li>' +
                                '<li>Created: ' + response.data.stats.created + '</li>' +
                                '<li>Updated: ' + response.data.stats.updated + '</li>' +
                                '<li>Skipped: ' + response.data.stats.skipped + '</li>' +
                            '</ul>');
                            button.prop('disabled', false);
                            
                            // Update statistics dynamically instead of page reload
                            updateProductStatistics(response.data.stats);
                            
                            // Hide progress container with smooth fade after showing completion
                            setTimeout(function() {
                                $('#progress-container').fadeOut(500);
                            }, 2000);
                        } else {
                            // Continue with next chunk
                            $('#progress-bar').css('width', response.data.progress + '%').text(response.data.progress + '%');
                            $('#progress-text').text(response.data.message + ' (' + response.data.stats.processed + ' processed)');
                            
                            // Process next chunk after a short delay
                            setTimeout(function() {
                                processProductChunk(response.data.nextChunk);
                            }, 100);
                        }
                    } else {
                        $('#product-import-status').html('<p>Error: ' + response.data + '</p>');
                        $('#progress-container').hide();
                        button.prop('disabled', false);
                    }
                },
                error: function() {
                    $('#product-import-status').html('<p>Error: Could not complete the import. Please check the logs.</p>');
                    $('#progress-container').hide();
                    button.prop('disabled', false);
                }
            });
        }
        
        // Start the import
        processProductChunk(0);
    });
    
    // Handle sync existing users button click
    $('#sync-existing-users').on('click', function() {
        var button = $(this);
        button.prop('disabled', true);
        $('#sync-existing-status').html('<p>Checking existing WordPress users... This may take a few minutes.</p>');
        
        // Show progress container
        $('#sync-progress-container').show();
        $('#sync-progress-bar').css('width', '0%').text('0%');
        $('#sync-progress-text').text('Starting sync...');
        
        // Start chunked sync process
        function processSyncChunk(chunk = 0) {
            $.ajax({
                url: edtSyncAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edge_sync_existing_users',
                    chunk: chunk,
                    nonce: edtSyncAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.isComplete) {
                            // Sync completed
                            $('#sync-progress-bar').css('width', '100%').text('100%');
                            $('#sync-progress-text').text('Sync completed!');
                            $('#sync-existing-status').html('<p>Existing users sync completed successfully.</p><ul>' +
                                '<li>Total Users Checked: ' + response.data.stats.total + '</li>' +
                                '<li>Already Synced: ' + response.data.stats.already_synced + '</li>' +
                                '<li>Newly Synced to EDGE: ' + response.data.stats.synced + '</li>' +
                                '<li>Skipped: ' + response.data.stats.skipped + '</li>' +
                            '</ul>');
                            button.prop('disabled', false);
                            
                            // Hide progress container with smooth fade after showing completion
                            setTimeout(function() {
                                $('#sync-progress-container').fadeOut(500);
                            }, 2000);
                        } else {
                            // Continue with next chunk
                            $('#sync-progress-bar').css('width', response.data.progress + '%').text(response.data.progress + '%');
                            $('#sync-progress-text').text(response.data.message + ' (' + response.data.stats.processed + ' processed)');
                            
                            // Process next chunk after a short delay
                            setTimeout(function() {
                                processSyncChunk(response.data.nextChunk);
                            }, 100);
                        }
                    } else {
                        $('#sync-existing-status').html('<p>Error: ' + response.data + '</p>');
                        $('#sync-progress-container').hide();
                        button.prop('disabled', false);
                    }
                },
                error: function() {
                    $('#sync-existing-status').html('<p>Error: Could not complete the sync. Please check the logs.</p>');
                    $('#sync-progress-container').hide();
                    button.prop('disabled', false);
                }
            });
        }
        
        // Start the sync
        processSyncChunk(0);
    });
}); 
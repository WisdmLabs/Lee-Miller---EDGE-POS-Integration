jQuery(document).ready(function($) {
    // Utility class for managing chunked operations
    class ChunkedOperation {
        constructor(config) {
            this.config = {
                action: '',
                buttonId: '',
                statusId: '',
                progressContainerId: '',
                progressBarId: '',
                progressTextId: '',
                onComplete: null,
                ...config
            };
            this.initializeHandlers();
        }

        initializeHandlers() {
            $(this.config.buttonId).on('click', () => this.start());
        }

        updateProgress(progress, message) {
            $(this.config.progressBarId).css('width', progress + '%').text(progress + '%');
            $(this.config.progressTextId).text(message);
        }

        updateStatus(html) {
            $(this.config.statusId).html(html);
        }

        toggleButton(enabled) {
            $(this.config.buttonId).prop('disabled', !enabled);
        }

        showProgress() {
            $(this.config.progressContainerId).show();
            this.updateProgress(0, 'Starting...');
        }

        hideProgress() {
            setTimeout(() => {
                $(this.config.progressContainerId).fadeOut(500);
            }, 2000);
        }

        handleError(message) {
            this.updateStatus('<p>Error: ' + message + '</p>');
            $(this.config.progressContainerId).hide();
            this.toggleButton(true);
        }

        processChunk(chunk = 0) {
            $.ajax({
                url: edtSyncAjax.ajax_url,
                type: 'POST',
                data: {
                    action: this.config.action,
                    chunk: chunk,
                    nonce: edtSyncAjax.nonce
                },
                success: (response) => this.handleResponse(response, chunk),
                error: () => this.handleError('Could not complete the operation. Please check the logs.')
            });
        }

        handleResponse(response, chunk) {
            if (!response.success) {
                this.handleError(response.data);
                return;
            }

            if (response.data.isComplete) {
                this.handleCompletion(response.data);
            } else {
                this.handleProgress(response.data);
                setTimeout(() => this.processChunk(response.data.nextChunk), 100);
            }
        }

        handleCompletion(data) {
            this.updateProgress(100, 'Operation completed!');
            if (this.config.onComplete) {
                this.config.onComplete(data);
            }
            this.toggleButton(true);
            this.hideProgress();
        }

        handleProgress(data) {
            this.updateProgress(
                data.progress,
                data.message + ' (' + data.stats.processed + ' processed)'
            );
        }

        start() {
            this.toggleButton(false);
            this.showProgress();
            this.processChunk(0);
        }
    }

    // Function to update product statistics dynamically
    function updateProductStatistics(stats) {
        const statsContainer = $('.edt-sync-products-page .import-stats').last();
        if (statsContainer.length) {
            const statsHtml = `
                <h3>Last Import</h3>
                <ul>
                    <li><strong>Created:</strong> ${stats.created}</li>
                    <li><strong>Updated:</strong> ${stats.updated}</li>
                    <li><strong>Skipped:</strong> ${stats.skipped}</li>
                </ul>`;
            statsContainer.html(statsHtml);
        }
    }

    // Function to toggle custom minutes field
    function toggleCustomMinutes(selectId, containerId) {
        const selected = $(selectId).val();
        $(containerId).toggle(selected === selectId.replace('#', '') + '_custom_minutes');
    }

    // Initialize form visibility and handlers
    ['customer', 'product'].forEach(type => {
        const selectId = `#edge_${type}_cron_interval`;
        const containerId = `#${type}-custom-minutes-container`;
        
        // Initialize visibility
        toggleCustomMinutes(selectId, containerId);
        
        // Set up change handler
        $(selectId).on('change', () => toggleCustomMinutes(selectId, containerId));
    });

    // Create and store chunked operation handlers
    const operations = {
        productImport: new ChunkedOperation({
            action: 'edge_import_products',
            buttonId: '#import-products',
            statusId: '#product-import-status',
            progressContainerId: '#progress-container',
            progressBarId: '#progress-bar',
            progressTextId: '#progress-text',
            onComplete: (data) => {
                const statsHtml = `
                    <p>Product import completed successfully.</p>
                    <ul>
                        <li>Total: ${data.stats.total}</li>
                        <li>Created: ${data.stats.created}</li>
                        <li>Updated: ${data.stats.updated}</li>
                        <li>Skipped: ${data.stats.skipped}</li>
                    </ul>`;
                $('#product-import-status').html(statsHtml);
                updateProductStatistics(data.stats);
            }
        }),

        userSync: new ChunkedOperation({
            action: 'edge_sync_existing_users',
            buttonId: '#sync-existing-users',
            statusId: '#sync-existing-status',
            progressContainerId: '#sync-progress-container',
            progressBarId: '#sync-progress-bar',
            progressTextId: '#sync-progress-text',
            onComplete: (data) => {
                const statsHtml = `
                    <p>Existing users sync completed successfully.</p>
                    <ul>
                        <li>Total Users Checked: ${data.stats.total}</li>
                        <li>Already Synced: ${data.stats.already_synced}</li>
                        <li>Newly Synced to EDGE: ${data.stats.synced}</li>
                        <li>Skipped: ${data.stats.skipped}</li>
                    </ul>`;
                $('#sync-existing-status').html(statsHtml);
            }
        })
    };

    // Export operations for potential external use or debugging
    window.edgeOperations = operations;
}); 
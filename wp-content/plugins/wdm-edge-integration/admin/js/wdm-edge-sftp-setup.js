jQuery(document).ready(function($){
	let currentPath = '/';
	let selectedPath = '';
	
	// Function to update default port based on connection type
	window.updatePortDefault = function(connectionType) {
		var portField = $('#edge_sftp_port');
		var currentPort = portField.val();
		
		// Only update if port is currently set to a default value
		if (connectionType === 'ftp' && (currentPort === '22' || currentPort === '')) {
			portField.val('21');
		} else if (connectionType === 'sftp' && (currentPort === '21' || currentPort === '')) {
			portField.val('22');
		}
	};
	
	// Update hidden fields in folder form when connection settings change
	function updateFolderFormHiddenFields() {
		$('#edge-sftp-folder-form input[name="edge_sftp_host"]').val($('#edge_sftp_host').val());
		$('#edge-sftp-folder-form input[name="edge_sftp_username"]').val($('#edge_sftp_username').val());
		$('#edge-sftp-folder-form input[name="edge_sftp_password"]').val($('#edge_sftp_password').val());
		$('#edge-sftp-folder-form input[name="edge_sftp_port"]').val($('#edge_sftp_port').val());
		$('#edge-sftp-folder-form input[name="edge_connection_type"]').val($('input[name="edge_connection_type"]:checked').val());
	}
	
	// Update hidden fields when connection settings change
	$('#edge_sftp_host, #edge_sftp_username, #edge_sftp_password, #edge_sftp_port, input[name="edge_connection_type"]').on('input change', function() {
		updateFolderFormHiddenFields();
	});

	$('#edge-sftp-test-connection').on('click', function(e){
		e.preventDefault();
		var button = $(this);
		var connectionType = $('input[name="edge_connection_type"]:checked').val();
		button.prop('disabled', true).text('Testing...');
		
		var data = {
			action: 'edge_sftp_test_connection',
			host: $('#edge_sftp_host').val(),
			username: $('#edge_sftp_username').val(),
			password: $('#edge_sftp_password').val(),
			port: $('#edge_sftp_port').val(),
			connection_type: connectionType,
			nonce: edgeSftpAjax.nonce
		};
		
		$('#edge-sftp-test-result').html('<div class="test-result testing"><span class="dashicons dashicons-update spin"></span> Testing ' + connectionType.toUpperCase() + ' connection...</div>');
		
		$.post(edgeSftpAjax.ajax_url, data, function(response){
			button.prop('disabled', false).text('Test Connection');
			if(response.success){
				$('#edge-sftp-test-result').html('<div class="test-result success"><span class="dashicons dashicons-yes-alt"></span> ' + response.data + '! You can now browse folders below.</div>');
				$('#edge-sftp-folder-browser').show();
				currentPath = '/';
				selectedPath = '';
				updateSelectedFolder();
				loadSftpFolders(currentPath);
			}else{
				$('#edge-sftp-test-result').html('<div class="test-result error"><span class="dashicons dashicons-dismiss"></span> Connection failed: '+response.data+'</div>');
				$('#edge-sftp-folder-browser').hide();
			}
		}).fail(function() {
			button.prop('disabled', false).text('Test Connection');
			$('#edge-sftp-test-result').html('<div class="test-result error"><span class="dashicons dashicons-dismiss"></span> Connection test failed. Please check your settings.</div>');
		});
	});

	function updateBreadcrumbs(path) {
		let parts = path.split('/').filter(Boolean);
		let html = '<span class="breadcrumb-item"><a href="#" class="edge-sftp-breadcrumb" data-path="/"><span class="dashicons dashicons-admin-home"></span> Root</a></span>';
		let fullPath = '';
		for(let i=0; i<parts.length; i++) {
			fullPath += '/' + parts[i];
			html += '<span class="breadcrumb-separator">/</span><span class="breadcrumb-item"><a href="#" class="edge-sftp-breadcrumb" data-path="'+fullPath+'">'+parts[i]+'</a></span>';
		}
		$('#edge-sftp-breadcrumbs').html('<div class="breadcrumb-trail">' + html + '</div>');
	}

	function updateSelectedFolder() {
		if(selectedPath) {
			$('#edge-sftp-selected-folder').html('<div class="selected-folder"><span class="dashicons dashicons-yes-alt"></span> <strong>Selected:</strong> <code>' + selectedPath + '</code></div>');
		} else {
			$('#edge-sftp-selected-folder').html('');
		}
	}

	function loadSftpFolders(path){
		currentPath = path;
		updateBreadcrumbs(path);
		$('#edge-sftp-select-folder').show();
		
		var connectionType = $('input[name="edge_connection_type"]:checked').val();
		var data = {
			action: 'edge_sftp_list_folders',
			host: $('#edge_sftp_host').val(),
			username: $('#edge_sftp_username').val(),
			password: $('#edge_sftp_password').val(),
			port: $('#edge_sftp_port').val(),
			path: path,
			connection_type: connectionType,
			nonce: edgeSftpAjax.nonce
		};
		
		$('#edge-sftp-folder-list').html('<div class="folder-loading"><span class="dashicons dashicons-update spin"></span> Loading folders...</div>');
		
		$.post(edgeSftpAjax.ajax_url, data, function(response){
			if(response.success){
				var html = '<div class="folder-items">';
				if(path !== '/') {
					html += '<div class="folder-item folder-up"><span class="folder-icon dashicons dashicons-arrow-up-alt2"></span><a href="#" class="folder-link edge-sftp-folder-up" data-path="'+parentPath(path)+'">.. (Go Up)</a></div>';
				}
				if(response.data.length === 0) {
					html += '<div class="folder-item no-folders"><span class="folder-icon dashicons dashicons-info"></span><span class="folder-name">No subfolders found</span></div>';
				} else {
					$.each(response.data, function(i, folder){
						var folderName = folder.split('/').pop();
						html += '<div class="folder-item"><span class="folder-icon dashicons dashicons-portfolio"></span><a href="#" class="folder-link edge-sftp-folder" data-path="'+folder+'">'+folderName+'</a></div>';
					});
				}
				html += '</div>';
				$('#edge-sftp-folder-list').html(html);
			}else{
				$('#edge-sftp-folder-list').html('<div class="folder-error"><span class="dashicons dashicons-warning"></span> Error loading folders: '+response.data+'</div>');
			}
		}).fail(function() {
			$('#edge-sftp-folder-list').html('<div class="folder-error"><span class="dashicons dashicons-warning"></span> Failed to load folders. Please try again.</div>');
		});
	}

	function parentPath(path) {
		if(path === '/' || !path) return '/';
		let parts = path.split('/').filter(Boolean);
		parts.pop();
		return '/' + parts.join('/');
	}

	$(document).on('click', '.edge-sftp-folder', function(e){
		e.preventDefault();
		var path = $(this).data('path');
		loadSftpFolders(path);
	});

	$(document).on('click', '.edge-sftp-folder-up', function(e){
		e.preventDefault();
		var path = $(this).data('path');
		loadSftpFolders(path);
	});

	$(document).on('click', '.edge-sftp-breadcrumb', function(e){
		e.preventDefault();
		var path = $(this).data('path');
		loadSftpFolders(path);
	});

	$('#edge-sftp-select-folder').on('click', function(e){
		e.preventDefault();
		selectedPath = currentPath;
		$('#edge_sftp_folder').val(selectedPath);
		// Update the hidden field in the folder form
		$('input[name="edge_sftp_folder"]').val(selectedPath);
		updateSelectedFolder();
		$('#edge-sftp-folder-browser').hide();
		
		// Update the current folder display
		if($('#edge-sftp-current-folder-path').length) {
			$('#edge-sftp-current-folder-path').text(selectedPath);
			$('#edge-sftp-current-folder').show();
		} else {
			$('<div id="edge-sftp-current-folder" class="current-folder-display"><div class="folder-info"><span class="dashicons dashicons-portfolio"></span><strong>Current SFTP Folder:</strong> <code id="edge-sftp-current-folder-path">'+selectedPath+'</code></div><button type="button" class="edt-button" id="edge-sftp-change-folder">Change Folder</button></div>').insertBefore('#edge-sftp-folder-browser');
		}
	});

	// Show folder browser when clicking 'Change Folder'
	$(document).on('click', '#edge-sftp-change-folder', function(e){
		e.preventDefault();
		$('#edge-sftp-folder-browser').show();
		currentPath = $('#edge_sftp_folder').val() || '/';
		selectedPath = '';
		updateSelectedFolder();
		loadSftpFolders(currentPath);
	});

	// If a folder is already selected, show it
	if($('#edge_sftp_folder').val()) {
		selectedPath = $('#edge_sftp_folder').val();
		updateSelectedFolder();
	}

	// Initialize folder form hidden fields on page load
	updateFolderFormHiddenFields();
}); 
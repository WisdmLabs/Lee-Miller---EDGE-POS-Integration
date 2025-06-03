<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$host = get_option('edge_sftp_host', '');
$username = get_option('edge_sftp_username', '');
$password = get_option('edge_sftp_password', '');
$port = get_option('edge_sftp_port', '22');
$folder = get_option('edge_sftp_folder', '');
$connection_type = get_option('edge_connection_type', 'sftp');
?>

<!-- Connection Settings Card -->
<div class="edt-card">
	<h2>Remote Connection Settings</h2>
	<div class="edt-card-content">
		<p>Configure your EDGE server connection details below. These settings are required for importing customers and products from your EDGE system.</p>
		
		<form method="post" action="options.php" id="edge-sftp-setup-form">
			<?php settings_fields('edge_sftp_options'); ?>
			<?php do_settings_sections('edge_sftp_options'); ?>
			
			<div class="edt-form-row">
				<label>Connection Type</label>
				<label style="display: inline-block; margin-right: 20px;">
					<input type="radio" name="edge_connection_type" value="sftp" <?php checked($connection_type, 'sftp'); ?> onchange="updatePortDefault(this.value)"> 
					SFTP (Secure FTP) - Recommended
				</label>
				<label style="display: inline-block;">
					<input type="radio" name="edge_connection_type" value="ftp" <?php checked($connection_type, 'ftp'); ?> onchange="updatePortDefault(this.value)"> 
					FTP (Standard)
				</label>
				<p class="description">SFTP is more secure and recommended. Use FTP only if SFTP is not available.</p>
			</div>
			
			<div class="edt-form-row">
				<label for="edge_sftp_host">Server Host</label>
				<input type="text" id="edge_sftp_host" name="edge_sftp_host" value="<?php echo esc_attr($host); ?>" placeholder="ftp.example.com" required />
				<p class="description">The hostname or IP address of your server.</p>
			</div>
			
			<div class="edt-form-row">
				<label for="edge_sftp_username">Username</label>
				<input type="text" id="edge_sftp_username" name="edge_sftp_username" value="<?php echo esc_attr($username); ?>" placeholder="username" required />
				<p class="description">Your account username.</p>
			</div>
			
			<div class="edt-form-row">
				<label for="edge_sftp_password">Password</label>
				<input type="password" id="edge_sftp_password" name="edge_sftp_password" value="<?php echo esc_attr($password); ?>" placeholder="password" required />
				<p class="description">Your account password.</p>
			</div>
			
			<div class="edt-form-row">
				<label for="edge_sftp_port">Port</label>
				<input type="number" id="edge_sftp_port" name="edge_sftp_port" value="<?php echo esc_attr($port); ?>" min="1" max="65535" style="max-width: 100px;" required />
				<p class="description">Port number (SFTP default: 22, FTP default: 21).</p>
			</div>
			
			<div class="edt-form-row">
				<button type="button" class="edt-button" id="edge-sftp-test-connection">Test Connection</button>
				<div id="edge-sftp-test-result" style="margin-top: 10px;"></div>
			</div>
			
			<div class="edt-form-row">
				<?php submit_button('Save Connection Settings', 'primary', 'submit', false, array('class' => 'edt-button')); ?>
			</div>
		</form>
	</div>
</div>

<!-- Folder Selection Card -->
<div class="edt-card">
	<h2>Folder Selection</h2>
	<div class="edt-card-content">
		<p>Select the folder on your SFTP server where EDGE files are located. This folder should contain the Inbox and Outbox directories.</p>
		
		<?php if ($folder): ?>
		<div id="edge-sftp-current-folder" class="current-folder-display">
			<div class="folder-info">
				<span class="dashicons dashicons-portfolio"></span>
				<strong>Current SFTP Folder:</strong> 
				<code id="edge-sftp-current-folder-path"><?php echo esc_html($folder); ?></code>
			</div>
			<button type="button" class="edt-button" id="edge-sftp-change-folder">Change Folder</button>
		</div>
		<?php endif; ?>
		
		<div id="edge-sftp-folder-browser" class="folder-browser" style="display:<?php echo $folder ? 'none' : 'block'; ?>;">
			<div class="folder-browser-header">
				<h3>Browse SFTP Folders</h3>
				<p class="description">Navigate to the folder containing your EDGE data files.</p>
			</div>
			
			<div id="edge-sftp-breadcrumbs" class="breadcrumbs"></div>
			<div id="edge-sftp-folder-list" class="folder-list"></div>
			
			<div class="folder-selection-actions">
				<button type="button" class="edt-button" id="edge-sftp-select-folder" style="display:none;">Select This Folder</button>
				<input type="hidden" name="edge_sftp_folder" id="edge_sftp_folder" value="<?php echo esc_attr($folder); ?>" />
				<div id="edge-sftp-selected-folder" class="selected-folder-info"></div>
			</div>
		</div>
		
		<form method="post" action="options.php" id="edge-sftp-folder-form" style="margin-top: 20px;">
			<?php settings_fields('edge_sftp_options'); ?>
			<!-- Hidden fields to preserve other settings -->
			<input type="hidden" name="edge_sftp_host" value="<?php echo esc_attr($host); ?>" />
			<input type="hidden" name="edge_sftp_username" value="<?php echo esc_attr($username); ?>" />
			<input type="hidden" name="edge_sftp_password" value="<?php echo esc_attr($password); ?>" />
			<input type="hidden" name="edge_sftp_port" value="<?php echo esc_attr($port); ?>" />
			<input type="hidden" name="edge_connection_type" value="<?php echo esc_attr($connection_type); ?>" />
			<input type="hidden" name="edge_sftp_folder" value="<?php echo esc_attr($folder); ?>" />
			<?php submit_button('Save Folder Selection', 'primary', 'submit', false, array('class' => 'edt-button')); ?>
		</form>
	</div>
</div>

<!-- Connection Status Card -->
<div class="edt-card">
	<h2>Connection Status</h2>
	<div class="edt-card-content">
		<div id="edge-sftp-status-display">
			<?php if ($host && $username && $password && $folder): ?>
				<div class="status-item status-configured">
					<span class="dashicons dashicons-yes-alt"></span>
					<strong>Connection Configuration:</strong> Complete
				</div>
				<div class="status-details">
					<p><strong>Type:</strong> <?php echo esc_html(strtoupper($connection_type)); ?></p>
					<p><strong>Host:</strong> <?php echo esc_html($host); ?></p>
					<p><strong>Port:</strong> <?php echo esc_html($port); ?></p>
					<p><strong>Username:</strong> <?php echo esc_html($username); ?></p>
					<p><strong>Folder:</strong> <?php echo esc_html($folder); ?></p>
				</div>
			<?php else: ?>
				<div class="status-item status-incomplete">
					<span class="dashicons dashicons-warning"></span>
					<strong>Connection Configuration:</strong> Incomplete
				</div>
				<p class="description">Please complete the connection settings above.</p>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
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
			connection_type: connectionType
		};
		
		$('#edge-sftp-test-result').html('<div class="test-result testing"><span class="dashicons dashicons-update spin"></span> Testing ' + connectionType.toUpperCase() + ' connection...</div>');
		
		$.post(ajaxurl, data, function(response){
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
			connection_type: connectionType
		};
		
		$('#edge-sftp-folder-list').html('<div class="folder-loading"><span class="dashicons dashicons-update spin"></span> Loading folders...</div>');
		
		$.post(ajaxurl, data, function(response){
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
</script> 
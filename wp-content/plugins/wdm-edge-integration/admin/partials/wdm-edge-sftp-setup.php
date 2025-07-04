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
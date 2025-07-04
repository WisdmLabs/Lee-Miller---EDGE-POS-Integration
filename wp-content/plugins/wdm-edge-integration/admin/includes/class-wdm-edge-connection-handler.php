<?php

/**
 * Connection Handler for EDGE Integration
 *
 * Handles FTP/SFTP connections and file operations
 *
 * @link       https://www.wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 */

/**
 * Connection Handler for EDGE Integration
 *
 * Handles all FTP/SFTP connection operations and file management.
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 * @author     WisdmLabs <info@wisdmlabs.com>
 */
class Wdm_Edge_Connection_Handler {

	/**
	 * Create SFTP or FTP connection based on settings.
	 *
	 * @return \phpseclib3\Net\SFTP|resource Connection object or FTP resource
	 * @throws Exception If connection fails
	 */
	public function create_connection() {
		$connection_type = get_option('edge_connection_type', 'sftp');
		$host = get_option('edge_sftp_host');
		$username = get_option('edge_sftp_username');
		$password = get_option('edge_sftp_password');
		
		if (empty($host) || empty($username) || empty($password)) {
			throw new Exception('Missing connection credentials');
		}
		
		if ($connection_type === 'ftp') {
			$port = intval(get_option('edge_sftp_port', 21)); // Default FTP port
			
			// Set up FTP connection
			$conn_id = ftp_ssl_connect($host, $port);
			if (!$conn_id) {
				throw new Exception('FTP connection to host failed');
			}
			
			// Login to FTP
			if (!ftp_login($conn_id, $username, $password)) {
				ftp_close($conn_id);
				throw new Exception('FTP Login Failed - Invalid credentials');
			}
			
			// Enable passive mode for better firewall compatibility
			ftp_pasv($conn_id, true);
			
			return $conn_id;
		} else {
			// SFTP connection using phpseclib3
			require_once ABSPATH . 'vendor/autoload.php';
			$port = intval(get_option('edge_sftp_port', 22)); // Default SFTP port
			$sftp = new \phpseclib3\Net\SFTP($host, $port);
			if (!$sftp->login($username, $password)) {
				throw new Exception('SFTP Login Failed');
			}
			return $sftp;
		}
	}

	/**
	 * Get connection type display name.
	 *
	 * @return string
	 */
	public function get_connection_type_name() {
		$connection_type = get_option('edge_connection_type', 'sftp');
		return strtoupper($connection_type);
	}

	/**
	 * Upload file to remote server (SFTP/FTP).
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $remote_path Remote file path
	 * @param string $local_path Local file path
	 * @return bool Success status
	 */
	public function upload_file($connection, $remote_path, $local_path) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			// Use FTP upload with ASCII mode for text files, BINARY for others
			$file_extension = strtolower(pathinfo($local_path, PATHINFO_EXTENSION));
			$mode = in_array($file_extension, ['txt', 'json', 'xml', 'csv']) ? FTP_ASCII : FTP_BINARY;
			
			return ftp_put($connection, $remote_path, $local_path, $mode);
		} else {
			// Use SFTP upload
			return $connection->put($remote_path, $local_path, \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE);
		}
	}

	/**
	 * Get file content from remote server.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $remote_path Remote file path
	 * @return string|false File content or false on failure
	 */
	public function get_file($connection, $remote_path) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			// Create temporary file for FTP download
			$temp_file = tempnam(sys_get_temp_dir(), 'ftp_download_');
			
			if (ftp_get($connection, $temp_file, $remote_path, FTP_BINARY)) {
				$content = file_get_contents($temp_file);
				unlink($temp_file);
				return $content;
			} else {
				if (file_exists($temp_file)) {
					unlink($temp_file);
				}
				return false;
			}
		} else {
			// Use SFTP get
			return $connection->get($remote_path);
		}
	}

	/**
	 * List directory contents.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $path Directory path
	 * @return array|false Directory listing or false on failure
	 */
	public function list_directory($connection, $path) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			// Get raw directory listing for FTP
			$list = ftp_rawlist($connection, $path);
			if ($list === false) {
				return false;
			}
			
			// Parse FTP listing to extract directories and files with metadata
			$items = array();
			foreach ($list as $item) {
				// Parse Unix-style listing (most common)
				if (preg_match('/^([\-ld])([\-rwx]{9})\s+\d+\s+\w+\s+\w+\s+(\d+)\s+(\w{3}\s+\d{1,2}\s+[\d:]+)\s+(.+)$/', $item, $matches)) {
					$name = $matches[5];
					$is_dir = ($matches[1] === 'd');
					$size = intval($matches[3]);
					
					// Skip . and .. entries
					if ($name === '.' || $name === '..') {
						continue;
					}
					
					$items[$name] = array(
						'type' => $is_dir ? 2 : 1, // 2 = directory, 1 = file (to match SFTP format)
						'size' => $size,
						'mtime' => strtotime($matches[4]) ?: time()
					);
				}
			}
			
			return $items;
		} else {
			// Use SFTP rawlist
			return $connection->rawlist($path);
		}
	}

	/**
	 * Check if path exists on remote server.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $path Remote path
	 * @return bool True if path exists
	 */
	public function file_exists($connection, $path) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			// For FTP, try to get file size (works for files) or change directory (works for directories)
			$size = ftp_size($connection, $path);
			if ($size !== -1) {
				return true; // File exists
			}
			
			// Try as directory
			$current_dir = ftp_pwd($connection);
			if (ftp_chdir($connection, $path)) {
				ftp_chdir($connection, $current_dir); // Restore original directory
				return true;
			}
			
			return false;
		} else {
			// Use SFTP file_exists
			return $connection->file_exists($path);
		}
	}

	/**
	 * Get last connection error message.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @return string Error message
	 */
	public function get_connection_error($connection) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			// FTP doesn't have specific error messages, return generic
			return 'FTP operation failed';
		} else {
			// Use SFTP error method
			return method_exists($connection, 'getLastSFTPError') ? $connection->getLastSFTPError() : 'SFTP operation failed';
		}
	}

	/**
	 * Close connection properly.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 */
	public function close_connection($connection) {
		$connection_type = get_option('edge_connection_type', 'sftp');
		
		if ($connection_type === 'ftp') {
			if (is_resource($connection)) {
				ftp_close($connection);
			}
		}
		// SFTP connections are closed automatically when the object is destroyed
	}

	/**
	 * AJAX handler to test SFTP/FTP connection.
	 */
	public function ajax_test_sftp_connection() {
		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'edt_sync_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}
		
		$host = $_POST['host'] ?? '';
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';
		$port = intval($_POST['port'] ?? 22);
		$connection_type = $_POST['connection_type'] ?? 'sftp';
		
		if ( empty($host) || empty($username) || empty($password) ) {
			wp_send_json_error( 'Missing credentials' );
		}
		
		// Temporarily set the connection options for testing
		$old_host = get_option('edge_sftp_host');
		$old_username = get_option('edge_sftp_username');
		$old_password = get_option('edge_sftp_password');
		$old_port = get_option('edge_sftp_port');
		$old_connection_type = get_option('edge_connection_type');
		
		update_option('edge_sftp_host', $host);
		update_option('edge_sftp_username', $username);
		update_option('edge_sftp_password', $password);
		update_option('edge_sftp_port', $port);
		update_option('edge_connection_type', $connection_type);
		
		try {
			$connection = $this->create_connection();
			wp_send_json_success( ucfirst($connection_type) . ' connection successful' );
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		} finally {
			// Restore original settings
			update_option('edge_sftp_host', $old_host);
			update_option('edge_sftp_username', $old_username);
			update_option('edge_sftp_password', $old_password);
			update_option('edge_sftp_port', $old_port);
			update_option('edge_connection_type', $old_connection_type);
		}
	}

	/**
	 * AJAX handler to list SFTP/FTP folders.
	 */
	public function ajax_list_sftp_folders() {
		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'edt_sync_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}
		
		$host = $_POST['host'] ?? '';
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';
		$port = intval($_POST['port'] ?? 22);
		$path = $_POST['path'] ?? '/';
		$connection_type = $_POST['connection_type'] ?? 'sftp';
		
		if ( empty($host) || empty($username) || empty($password) ) {
			wp_send_json_error( 'Missing credentials' );
		}
		
		// Temporarily set the connection options for testing
		$old_host = get_option('edge_sftp_host');
		$old_username = get_option('edge_sftp_username');
		$old_password = get_option('edge_sftp_password');
		$old_port = get_option('edge_sftp_port');
		$old_connection_type = get_option('edge_connection_type');
		
		update_option('edge_sftp_host', $host);
		update_option('edge_sftp_username', $username);
		update_option('edge_sftp_password', $password);
		update_option('edge_sftp_port', $port);
		update_option('edge_connection_type', $connection_type);
		
		try {
			$connection = $this->create_connection();
			$items = $this->list_directory($connection, $path);
			if ( $items === false ) {
				wp_send_json_error( 'Failed to list directory' );
			}
			$folders = array();
			foreach ( $items as $name => $info ) {
				if ( $name === '.' || $name === '..' ) continue;
				if ( $info['type'] === 2 ) { // 2 = directory
					$folders[] = rtrim($path, '/') . '/' . $name;
				}
			}
			$this->close_connection($connection);
			wp_send_json_success( $folders );
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		} finally {
			// Restore original settings
			update_option('edge_sftp_host', $old_host);
			update_option('edge_sftp_username', $old_username);
			update_option('edge_sftp_password', $old_password);
			update_option('edge_sftp_port', $old_port);
			update_option('edge_connection_type', $old_connection_type);
		}
	}
} 
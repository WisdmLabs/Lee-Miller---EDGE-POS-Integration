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
	 * Store temporary connection settings
	 */
	private $temp_settings = [];

	/**
	 * Get connection settings
	 *
	 * @param array $override Optional settings to override defaults
	 * @return array Connection settings
	 */
	private function get_connection_settings($override = []) {
		$defaults = [
			'host' => get_option('edge_sftp_host'),
			'username' => get_option('edge_sftp_username'),
			'password' => get_option('edge_sftp_password'),
			'port' => intval(get_option('edge_sftp_port', 22)),
			'connection_type' => get_option('edge_connection_type', 'sftp')
		];
		
		return array_merge($defaults, $override);
	}

	/**
	 * Temporarily set connection settings
	 *
	 * @param array $settings New settings to apply
	 * @return array Original settings for restoration
	 */
	private function set_temp_settings($settings) {
		$original = [];
		foreach ($settings as $key => $value) {
			$option_key = 'edge_' . ($key === 'connection_type' ? $key : 'sftp_' . $key);
			$original[$key] = get_option($option_key);
			update_option($option_key, $value);
		}
		$this->temp_settings = $original;
		return $original;
	}

	/**
	 * Restore original settings
	 */
	private function restore_settings() {
		foreach ($this->temp_settings as $key => $value) {
			$option_key = 'edge_' . ($key === 'connection_type' ? $key : 'sftp_' . $key);
			update_option($option_key, $value);
		}
		$this->temp_settings = [];
	}

	/**
	 * Validate connection credentials
	 *
	 * @param array $settings Connection settings to validate
	 * @throws Exception If credentials are missing
	 */
	private function validate_credentials($settings) {
		if (empty($settings['host']) || empty($settings['username']) || empty($settings['password'])) {
			throw new Exception('Missing connection credentials');
		}
	}

	/**
	 * Create SFTP or FTP connection based on settings.
	 *
	 * @param array $override Optional settings to override defaults
	 * @return \phpseclib3\Net\SFTP|resource Connection object or FTP resource
	 * @throws Exception If connection fails
	 */
	public function create_connection($override = []) {
		$settings = $this->get_connection_settings($override);
		$this->validate_credentials($settings);
		
		if ($settings['connection_type'] === 'ftp') {
			return $this->create_ftp_connection($settings);
		}
		return $this->create_sftp_connection($settings);
	}

	/**
	 * Create FTP connection
	 */
	private function create_ftp_connection($settings) {
		$conn_id = ftp_ssl_connect($settings['host'], $settings['port']);
		if (!$conn_id) {
			throw new Exception('FTP connection to host failed');
		}
		
		if (!ftp_login($conn_id, $settings['username'], $settings['password'])) {
			ftp_close($conn_id);
			throw new Exception('FTP Login Failed - Invalid credentials');
		}
		
		ftp_pasv($conn_id, true);
		return $conn_id;
	}

	/**
	 * Create SFTP connection
	 */
	private function create_sftp_connection($settings) {
		require_once ABSPATH . 'vendor/autoload.php';
		$sftp = new \phpseclib3\Net\SFTP($settings['host'], $settings['port']);
		if (!$sftp->login($settings['username'], $settings['password'])) {
			throw new Exception('SFTP Login Failed');
		}
		return $sftp;
	}

	/**
	 * Get connection type display name.
	 *
	 * @return string
	 */
	public function get_connection_type_name() {
		return strtoupper($this->get_connection_settings()['connection_type']);
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
		$settings = $this->get_connection_settings();
		
		if ($settings['connection_type'] === 'ftp') {
			$file_extension = strtolower(pathinfo($local_path, PATHINFO_EXTENSION));
			$mode = in_array($file_extension, ['txt', 'json', 'xml', 'csv']) ? FTP_ASCII : FTP_BINARY;
			return ftp_put($connection, $remote_path, $local_path, $mode);
		}
		
		return $connection->put($remote_path, $local_path, \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE);
	}

	/**
	 * Get file content from remote server.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $remote_path Remote file path
	 * @return string|false File content or false on failure
	 */
	public function get_file($connection, $remote_path) {
		$settings = $this->get_connection_settings();
		
		if ($settings['connection_type'] === 'ftp') {
			return $this->get_ftp_file($connection, $remote_path);
		}
		
		return $connection->get($remote_path);
	}

	/**
	 * Get file via FTP
	 */
	private function get_ftp_file($connection, $remote_path) {
		$temp_file = tempnam(sys_get_temp_dir(), 'ftp_download_');
		
		if (ftp_get($connection, $temp_file, $remote_path, FTP_BINARY)) {
			$content = file_get_contents($temp_file);
			unlink($temp_file);
			return $content;
		}
		
		if (file_exists($temp_file)) {
			unlink($temp_file);
		}
		return false;
	}

	/**
	 * List directory contents.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $path Directory path
	 * @return array|false Directory listing or false on failure
	 */
	public function list_directory($connection, $path) {
		$settings = $this->get_connection_settings();
		
		if ($settings['connection_type'] === 'ftp') {
			return $this->list_ftp_directory($connection, $path);
		}
		
		return $connection->rawlist($path);
	}

	/**
	 * List FTP directory contents
	 */
	private function list_ftp_directory($connection, $path) {
		$list = ftp_rawlist($connection, $path);
		if ($list === false) {
			return false;
		}
		
		$items = [];
		foreach ($list as $item) {
			if (preg_match('/^([\-ld])([\-rwx]{9})\s+\d+\s+\w+\s+\w+\s+(\d+)\s+(\w{3}\s+\d{1,2}\s+[\d:]+)\s+(.+)$/', $item, $matches)) {
				$name = $matches[5];
				if ($name === '.' || $name === '..') {
					continue;
				}
				
				$items[$name] = [
					'type' => ($matches[1] === 'd') ? 2 : 1,
					'size' => intval($matches[3]),
					'mtime' => strtotime($matches[4]) ?: time()
				];
			}
		}
		
		return $items;
	}

	/**
	 * Check if path exists on remote server.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @param string $path Remote path
	 * @return bool True if path exists
	 */
	public function file_exists($connection, $path) {
		$settings = $this->get_connection_settings();
		
		if ($settings['connection_type'] === 'ftp') {
			return $this->ftp_path_exists($connection, $path);
		}
		
		return $connection->file_exists($path);
	}

	/**
	 * Check if path exists on FTP server
	 */
	private function ftp_path_exists($connection, $path) {
		$size = ftp_size($connection, $path);
		if ($size !== -1) {
			return true;
		}
		
		$current_dir = ftp_pwd($connection);
		if (ftp_chdir($connection, $path)) {
			ftp_chdir($connection, $current_dir);
			return true;
		}
		
		return false;
	}

	/**
	 * Get last connection error message.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 * @return string Error message
	 */
	public function get_connection_error($connection) {
		$settings = $this->get_connection_settings();
		
		if ($settings['connection_type'] === 'ftp') {
			return 'FTP operation failed';
		}
		
		return method_exists($connection, 'getLastSFTPError') ? $connection->getLastSFTPError() : 'SFTP operation failed';
	}

	/**
	 * Close connection properly.
	 *
	 * @param mixed $connection SFTP object or FTP resource
	 */
	public function close_connection($connection) {
		$settings = $this->get_connection_settings();
		
		if ($settings['connection_type'] === 'ftp' && is_resource($connection)) {
			ftp_close($connection);
		}
	}

	/**
	 * Handle common AJAX security checks
	 */
	private function verify_ajax_request() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'edt_sync_nonce')) {
			wp_send_json_error('Security check failed');
		}
		
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Unauthorized');
		}
	}

	/**
	 * Get connection settings from POST data
	 */
	private function get_post_connection_settings() {
		return [
			'host' => $_POST['host'] ?? '',
			'username' => $_POST['username'] ?? '',
			'password' => $_POST['password'] ?? '',
			'port' => intval($_POST['port'] ?? 22),
			'connection_type' => $_POST['connection_type'] ?? 'sftp'
		];
	}

	/**
	 * AJAX handler to test SFTP/FTP connection.
	 */
	public function ajax_test_sftp_connection() {
		$this->verify_ajax_request();
		
		try {
			$settings = $this->get_post_connection_settings();
			$this->validate_credentials($settings);
			
			$original = $this->set_temp_settings($settings);
			$connection = $this->create_connection();
			
			wp_send_json_success(ucfirst($settings['connection_type']) . ' connection successful');
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		} finally {
			$this->restore_settings();
		}
	}

	/**
	 * AJAX handler to list SFTP/FTP folders.
	 */
	public function ajax_list_sftp_folders() {
		$this->verify_ajax_request();
		
		try {
			$settings = $this->get_post_connection_settings();
			$this->validate_credentials($settings);
			
			$path = $_POST['path'] ?? '/';
			$original = $this->set_temp_settings($settings);
			
			$connection = $this->create_connection();
			$items = $this->list_directory($connection, $path);
			
			if ($items === false) {
				wp_send_json_error('Failed to list directory');
			}
			
			$folders = [];
			foreach ($items as $name => $info) {
				if ($name === '.' || $name === '..') continue;
				if ($info['type'] === 2) {
					$folders[] = rtrim($path, '/') . '/' . $name;
				}
			}
			
			$this->close_connection($connection);
			wp_send_json_success($folders);
		} catch (\Exception $e) {
			wp_send_json_error($e->getMessage());
		} finally {
			$this->restore_settings();
		}
	}
} 
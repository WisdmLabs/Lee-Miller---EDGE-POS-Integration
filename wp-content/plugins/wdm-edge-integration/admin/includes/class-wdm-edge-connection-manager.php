<?php

/**
 * Connection Manager for EDGE Integration
 *
 * Handles FTP/SFTP connections and file operations
 *
 * @package    Wdm_Edge_Integration
 * @subpackage Wdm_Edge_Integration/admin/includes
 */

class Wdm_Edge_Connection_Manager {

    /**
     * Create connection based on the configured connection type (FTP or SFTP)
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @param int $port
     * @return array Connection info with type and resource
     */
    public function create_connection($host = null, $username = null, $password = null, $port = null) {
        // Use provided parameters or get from settings
        $host = $host ?: get_option('edge_sftp_host');
        $username = $username ?: get_option('edge_sftp_username');
        $password = $password ?: get_option('edge_sftp_password');
        $connection_type = get_option('edge_connection_type', 'sftp');
        
        if ($connection_type === 'ftp') {
            $port = $port ?: intval(get_option('edge_sftp_port', 21));
            return $this->create_ftp_connection($host, $username, $password, $port);
        } else {
            $port = $port ?: intval(get_option('edge_sftp_port', 22));
            return $this->create_sftp_connection($host, $username, $password, $port);
        }
    }

    /**
     * Create FTP connection
     */
    private function create_ftp_connection($host, $username, $password, $port) {
        $ftp = ftp_connect($host, $port);
        if (!$ftp) {
            throw new \Exception('FTP connection failed');
        }
        
        if (!ftp_login($ftp, $username, $password)) {
            ftp_close($ftp);
            throw new \Exception('FTP login failed');
        }
        
        // Enable passive mode for better compatibility
        ftp_pasv($ftp, true);
        
        return array(
            'type' => 'ftp',
            'connection' => $ftp
        );
    }

    /**
     * Create SFTP connection
     */
    private function create_sftp_connection($host, $username, $password, $port) {
        require_once ABSPATH . 'vendor/autoload.php';
        
        $sftp = new \phpseclib3\Net\SFTP($host, $port);
        if (!$sftp->login($username, $password)) {
            throw new \Exception('SFTP login failed');
        }
        
        return array(
            'type' => 'sftp',
            'connection' => $sftp
        );
    }

    /**
     * Close connection based on type
     */
    public function close_connection($connection_info) {
        if ($connection_info['type'] === 'ftp') {
            ftp_close($connection_info['connection']);
        }
        // SFTP connections are closed automatically when the object is destroyed
    }

    /**
     * List files in directory
     */
    public function list_files($connection_info, $path) {
        if ($connection_info['type'] === 'ftp') {
            return ftp_nlist($connection_info['connection'], $path);
        } else {
            $files = $connection_info['connection']->rawlist($path);
            return $files === false ? false : array_keys($files);
        }
    }

    /**
     * Get file modification time
     */
    public function get_file_mtime($connection_info, $path) {
        if ($connection_info['type'] === 'ftp') {
            return ftp_mdtm($connection_info['connection'], $path);
        } else {
            $files = $connection_info['connection']->rawlist(dirname($path));
            $filename = basename($path);
            return isset($files[$filename]) ? $files[$filename]['mtime'] : false;
        }
    }

    /**
     * Download file content
     */
    public function get_file_content($connection_info, $remote_path) {
        if ($connection_info['type'] === 'ftp') {
            $temp_file = tempnam(sys_get_temp_dir(), 'edge_ftp_');
            if (ftp_get($connection_info['connection'], $temp_file, $remote_path, FTP_BINARY)) {
                $content = file_get_contents($temp_file);
                unlink($temp_file);
                return $content;
            }
            return false;
        } else {
            return $connection_info['connection']->get($remote_path);
        }
    }

    /**
     * Upload file
     */
    public function upload_file($connection_info, $local_path, $remote_path) {
        if ($connection_info['type'] === 'ftp') {
            return ftp_put($connection_info['connection'], $remote_path, $local_path, FTP_BINARY);
        } else {
            return $connection_info['connection']->put($remote_path, $local_path, \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE);
        }
    }

    /**
     * Check if directory exists
     */
    public function is_dir($connection_info, $path) {
        if ($connection_info['type'] === 'ftp') {
            $current = ftp_pwd($connection_info['connection']);
            if (ftp_chdir($connection_info['connection'], $path)) {
                ftp_chdir($connection_info['connection'], $current);
                return true;
            }
            return false;
        } else {
            return $connection_info['connection']->is_dir($path);
        }
    }

    /**
     * Create directory
     */
    public function mkdir($connection_info, $path) {
        if ($connection_info['type'] === 'ftp') {
            return ftp_mkdir($connection_info['connection'], $path);
        } else {
            return $connection_info['connection']->mkdir($path, 0777, true);
        }
    }

    /**
     * List folders in directory for FTP/SFTP
     */
    public function list_folders($connection_info, $path) {
        if ($connection_info['type'] === 'ftp') {
            $files = ftp_nlist($connection_info['connection'], $path);
            $folders = array();
            
            if ($files) {
                foreach ($files as $file) {
                    $full_path = rtrim($path, '/') . '/' . $file;
                    if ($this->is_dir($connection_info, $full_path) && $file !== '.' && $file !== '..') {
                        $folders[] = $full_path;
                    }
                }
            }
            
            return $folders;
        } else {
            $items = $connection_info['connection']->rawlist($path);
            if ($items === false) {
                return false;
            }
            
            $folders = array();
            foreach ($items as $name => $info) {
                if ($name === '.' || $name === '..') continue;
                if ($info['type'] === 2) { // 2 = directory
                    $folders[] = rtrim($path, '/') . '/' . $name;
                }
            }
            
            return $folders;
        }
    }

    /**
     * AJAX handler to test FTP/SFTP connection.
     */
    public function ajax_test_connection() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        
        $connection_type = $_POST['connection_type'] ?? get_option('edge_connection_type', 'sftp');
        $host = $_POST['host'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $port = intval($_POST['port'] ?? ($connection_type === 'ftp' ? 21 : 22));
        
        if ( empty($host) || empty($username) || empty($password) ) {
            wp_send_json_error( 'Missing credentials' );
        }
        
        try {
            $connection_info = $this->create_connection($host, $username, $password, $port);
            $this->close_connection($connection_info);
            wp_send_json_success( ucfirst($connection_type) . ' connection successful' );
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }

    /**
     * AJAX handler to list FTP/SFTP folders.
     */
    public function ajax_list_folders() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        
        $connection_type = $_POST['connection_type'] ?? get_option('edge_connection_type', 'sftp');
        $host = $_POST['host'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $port = intval($_POST['port'] ?? ($connection_type === 'ftp' ? 21 : 22));
        $path = $_POST['path'] ?? '/';
        
        if ( empty($host) || empty($username) || empty($password) ) {
            wp_send_json_error( 'Missing credentials' );
        }
        
        try {
            $connection_info = $this->create_connection($host, $username, $password, $port);
            $folders = $this->list_folders($connection_info, $path);
            $this->close_connection($connection_info);
            
            if ( $folders === false ) {
                wp_send_json_error( 'Failed to list directory' );
            }
            
            wp_send_json_success( $folders );
        } catch ( \Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }
} 
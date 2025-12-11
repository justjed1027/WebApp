<?php

class DatabaseConnection {
    private $host;
    private $username;
    private $password;
    private $database;

    public $connection;

    public function __construct() {
        // Detect environment and set credentials inline
        $hostHeader = $_SERVER['HTTP_HOST'] ?? '';
        $serverName = $_SERVER['SERVER_NAME'] ?? '';

        $isProdHost = stripos($hostHeader, 'dacc-appdev.com') !== false
            || stripos($hostHeader, 'atspace') !== false
            || stripos($serverName, 'dacc-appdev.com') !== false
            || stripos($serverName, 'atspace') !== false;

        $isLocalHost = in_array($hostHeader, ['localhost', '127.0.0.1', '::1'], true)
            || in_array($serverName, ['localhost', '127.0.0.1', '::1'], true)
            || stripos($hostHeader, 'local') !== false
            || stripos($serverName, 'local') !== false
            || PHP_SAPI === 'cli';

        if ($isProdHost || !$isLocalHost) {
            // Atspace production settings
            $this->host = "pdb1050.atspace.me";
            $this->username = "4237754_skillswap";
            $this->password = "PxbBuA1/9ornvsM!";
            $this->database = "4237754_skillswap";
        } else {
            // Local XAMPP settings
            $this->host = 'localhost';
            $this->username = 'root';
            $this->password = 'password';
            $this->database = 'bpa_skillswap';
        }

        // Create connection
        try {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset('utf8mb4');
        } catch (Exception $e) {
            error_log("DatabaseConnection error: " . $e->getMessage());
            die("Error loading database configuration");
        }
    }

    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>

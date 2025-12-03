<?php

class DatabaseConnection {
    private $host;
    private $username;
    private $password;
    private $database;

    public $connection;

    public function __construct() {
        // Load configuration from db_config.ini
        $this->loadConfig();

        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->connection->connect_error) {
            // Handle connection error, e.g., log it or throw an exception
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

    private function loadConfig() {
        // Find the db_config.ini file in the project root (two directories up from BPA/database/)
        $configPath = realpath(__DIR__ . '/../../db_config.ini');

        if (!file_exists($configPath)) {
            die("Error: db_config.ini file not found at " . ($configPath ?: 'project root'));
        }

        // Parse the ini file
        $dbSettings = parse_ini_file($configPath, true);

        if (!$dbSettings || !isset($dbSettings['database'])) {
            die("Error: Failed to parse db_config.ini or [database] section not found.");
        }

        // Extract database settings
        $this->host = isset($dbSettings['database']['host']) ? $dbSettings['database']['host'] : null;
        $this->username = isset($dbSettings['database']['username']) ? $dbSettings['database']['username'] : null;
        $this->password = isset($dbSettings['database']['password']) ? trim($dbSettings['database']['password'], "'\"") : null;
        $this->database = isset($dbSettings['database']['dbname']) ? $dbSettings['database']['dbname'] : null;

        // Validate that all required settings are present
        if (empty($this->host) || empty($this->username) || empty($this->database)) {
            die("Error: Missing required database configuration in db_config.ini (host, username, or dbname).");
        }
    }

    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>
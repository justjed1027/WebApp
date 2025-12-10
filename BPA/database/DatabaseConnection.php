<?php

class DatabaseConnection {
    private $host;
    private $username;
    private $password;
    private $database;

    public $connection;

    public function __construct() {
        // Load configuration from db_config.ini
        try{
            $this->loadConfig();
        
             $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
        } 
        catch (Exception $e){
            die("Error loading database configuration");
        }

       

       
    }

    private function loadConfig() {
        // Find the db_config.ini file in the project root (two directories up from BPA/database/)
        $configPath = realpath(__DIR__ . '/../../db_config.ini');

        if (!file_exists($configPath)) {
            die("Error: database config not found.");
        }

        // Parse the ini file
        $dbSettings = parse_ini_file($configPath, true);

        if (!$dbSettings || !isset($dbSettings['database'])) {
            die("Error: Failed to parse or [database] section not found.");
        }

        // Extract database settings
        $this->host = isset($dbSettings['database']['host']) ? $dbSettings['database']['host'] : null;
        $this->username = isset($dbSettings['database']['username']) ? $dbSettings['database']['username'] : null;
        $this->password = isset($dbSettings['database']['password']) ? trim($dbSettings['database']['password'], "'\"") : null;
        $this->database = isset($dbSettings['database']['dbname']) ? $dbSettings['database']['dbname'] : null;

        // Validate that all required settings are present
        if (empty($this->host) || empty($this->username) || empty($this->database)) {
            die("Error: Missing required database configuration (host, username, or dbname).");
        }
    }

    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>
<?php

class DatabaseConnection {
    private $host;
    private $username;
    private $password;
    private $database;

    public $connection;

    public function __construct() {
<<<<<<< HEAD
        // Load configuration from db_config.ini
        try{
            $this->loadConfig();
        
             $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
        } 
        catch (Exception $e){
            die("Error loading database configuration");
=======
        // Check if we're on Atspace hosting or local environment
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'dacc-appdev.com') !== false) {
            // Atspace production settings
            $this->host = "pdb1050.atspace.me";
            $this->username = "4237754_skillswap";
            $this->password = "PxbBuA1/9ornvsM!";
            $this->database = "4237754_skillswap";
        } else {
            // Local XAMPP settings
            $this->host = "localhost";
            $this->username = "root";
            $this->password = "password";
            $this->database = "bpa_skillswap";
        }

        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->connection->connect_error) {
            // Handle connection error, e.g., log it or throw an exception
            die("Connection failed: " . $this->connection->connect_error);
>>>>>>> merge1
        }

       

       
    }

<<<<<<< HEAD
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

=======
>>>>>>> merge1
    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
<<<<<<< HEAD
}
?>
=======
}
>>>>>>> merge1

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
 
        if (!$dbSettings) {
            die("Error: Failed to parse");
        }
 
        // Determine which configuration section to use (production or default database)
        $isProduction = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'dacc-appdev.com') !== false;
        $section = ($isProduction && isset($dbSettings['production'])) ? 'production' : 'database';
 
        if (!isset($dbSettings[$section])) {
            die("Error: section not found");
        }
 
        // Extract database settings from the appropriate section
        $config = $dbSettings[$section];
        $this->host = isset($config['host']) ? $config['host'] : null;
        $this->username = isset($config['username']) ? $config['username'] : null;
        $this->password = isset($config['password']) ? trim($config['password'], "'\"") : null;
        $this->database = isset($config['dbname']) ? $config['dbname'] : null;
 
        // Validate that all required settings are present
        if (empty($this->host) || empty($this->username) || empty($this->database)) {
            die("Error: Missing required database configuration settings.");
        }
    }
 
    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>
 
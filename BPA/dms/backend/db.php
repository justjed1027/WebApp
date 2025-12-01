<?php
/**
 * Database Connection Singleton
 * Returns mysqli connection for the DM system
 */

class DB {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $host = 'localhost';
        $username = 'root';
        $password = 'password';
        $database = 'bpa_skillswap';

        $this->connection = new mysqli($host, $username, $password, $database);

        if ($this->connection->connect_error) {
            error_log("Database connection failed: " . $this->connection->connect_error);
            die(json_encode(['success' => false, 'error' => 'Database connection failed']));
        }

        $this->connection->set_charset('utf8mb4');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    // Prevent cloning
    private function __clone() {}
}

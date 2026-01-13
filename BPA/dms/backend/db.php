<?php
/**
 * Database Connection Singleton
 * Returns mysqli connection for the DM system
 */

class DB {
    private static $instance = null;
    private $connection;
    private $env;

    private function __construct() {
        // Detect environment and pick credentials
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
            $this->env = 'prod';
            $host = "pdb1050.atspace.me";
            $username = "4237754_skillswap";
            $password = "PxbBuA1/9ornvsM!";
            $database = "4237754_skillswap";
        } else {
            $this->env = 'local';
            $host = 'localhost';
            $username = 'root';
            $password = 'password';
            $database = 'bpa_skillswap';
        }

        mysqli_report(MYSQLI_REPORT_OFF);

        try {
            $this->connection = @new mysqli($host, $username, $password, $database);
        } catch (Throwable $e) {
            error_log('DB connect throwable env=' . $this->env . ' host=' . $hostHeader . ' server=' . $serverName . ' msg=' . $e->getMessage());
            die(json_encode(['success' => false, 'error' => 'Database connection failed', 'env' => $this->env]));
        }

        if ($this->connection->connect_error) {
            error_log('DB connect error env=' . $this->env . ' host=' . $hostHeader . ' server=' . $serverName . ' msg=' . $this->connection->connect_error);
            die(json_encode(['success' => false, 'error' => 'Database connection failed', 'env' => $this->env]));
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
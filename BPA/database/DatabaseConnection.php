<?php

class DatabaseConnection {
    private $host = "localhost";
    private $username = "root";
    private $password = "password";
    private $database = "bpa_skillswap";

    public $connection;

    public function __construct() {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->connection->connect_error) {
            // Handle connection error, e.g., log it or throw an exception
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

    


    public function closeConnection() {
        if ($this->connection) {
            $this->connection->close();
        }
    }


}
?>
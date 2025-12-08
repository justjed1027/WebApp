    <?php

    class DatabaseConnection {
        private $host;
        private $username;
        private $password;
        private $database;

        public $connection;

        public function __construct() {
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
            }
        }

        


        public function closeConnection() {
            if ($this->connection) {
                $this->connection->close();
            }
        }
        
        
    
    }
    ?>
<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'];
        $this->port = $_ENV['DB_PORT'];
        $this->db_name = $_ENV['DB_DATABASE'];
        $this->username = $_ENV['DB_USERNAME'];
        $this->password = $_ENV['DB_PASSWORD'];
    }

    public function getConnection()
    {
        $this->conn = null;

        try {
            // TiDB is MySQL compatible, so we use mysql driver
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            // Add SSL options if connecting to TiDB Cloud (often requires secure connection)
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // If using TiDB Cloud, you might need this:
            // $options[PDO::MYSQL_ATTR_SSL_CA] = '/path/to/ca-bundle.crt';

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

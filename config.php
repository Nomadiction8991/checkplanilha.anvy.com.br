<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'anvycomb_checkplanilha';
    private $username = 'anvycomb_checkplanilha';
    private $password = 'uGyzaCndm7EDahptkBZd';
    private $conn;

    public function __construct($host = null, $db = null, $user = null, $pass = null) {
        if ($host) $this->host = $host;
        if ($db)   $this->db_name = $db;
        if ($user) $this->username = $user;
        if ($pass) $this->password = $pass;
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $exception) {
            die("Erro de conexão: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>
<?php
require_once __DIR__ . '/../bootstrap.php';
ini_set('display_errors', 0);
error_reporting(0);

class Database {
    private $host = 'anvy.com.br';
    private $db_name = 'anvycomb_checkplanilha';
    private $username = 'anvycomb_checkplanilha';
    private $password = 'uGyzaCndm7EDahptkBZd';
    private $charset = 'utf8mb4';
    
    public $conexao;

    public function getConnection() {
        $this->conexao = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conexao = new PDO($dsn, $this->username, $this->password);
            
            // Configurar opções do PDO
            $this->conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conexao->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch(PDOException $exception) {
            echo "Erro de conexão: " . $exception->getMessage();
        }

        return $this->conexao;
    }
}
// Criar instância da conexão
$database = new Database();
$conexao = $database->getConnection();
?>
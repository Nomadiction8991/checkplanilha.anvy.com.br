<?php
require_once 'config.php';

class PlanilhaProcessor {
    private $conn;
    private $table_name = "planilha";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function limparTabela() {
        $query = "TRUNCATE TABLE " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    public function processarLinha($dados) {
        // Mapear colunas do Excel para o banco
        $codigo = $dados['Código'] ?? '';
        $nome = $dados['Nome'] ?? '';
        $fornecedor = $dados['Fornecedor'] ?? '';
        $localidade = $dados['Localidade'] ?? '';
        $conta = $dados['Conta'] ?? '';
        $numero_documento = $dados['Nº Documento'] ?? '';
        $dependencia = $dados['Dependência'] ?? '';
        $data_aquisicao = $dados['Dt. Aquisição'] ?? '';
        $valor_aquisicao = $dados['Vl. Aquisição'] ?? 0;
        $valor_depreciacao = $dados['Vl. Deprec.'] ?? 0;
        $valor_atual = $dados['Vl. Atual'] ?? 0;
        $status = $dados['Status'] ?? '';

        // Converter data
        if ($data_aquisicao && strtotime($data_aquisicao)) {
            $data_aquisicao = date('Y-m-d', strtotime($data_aquisicao));
        } else {
            $data_aquisicao = null;
        }

        // Inserir no banco
        $query = "INSERT INTO " . $this->table_name . " 
                  (codigo, nome, fornecedor, localidade, conta, numero_documento, 
                   dependencia, data_aquisicao, valor_aquisicao, valor_depreciacao, 
                   valor_atual, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        
        return $stmt->execute([
            $codigo, $nome, $fornecedor, $localidade, $conta, $numero_documento,
            $dependencia, $data_aquisicao, $valor_aquisicao, $valor_depreciacao,
            $valor_atual, $status
        ]);
    }
}
?>
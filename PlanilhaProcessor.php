<?php
class PlanilhaProcessor {
    /** @var PDO */
    public $conn;
    /** @var string */
    public $table_name;

    public function __construct(PDO $conn, string $tableName = 'planilha') {
        $this->conn = $conn;
        $this->table_name = $tableName;
    }

    public function inserirLinha(
        $codigo, $nome, $fornecedor, $localidade, $conta, $numero_documento,
        $dependencia, $data_aquisicao, $valor_aquisicao, $valor_depreciacao,
        $valor_atual, $status
    ) {
        $sql = "INSERT INTO {$this->table_name}
            (codigo, nome, fornecedor, localidade, conta, numero_documento,
             dependencia, data_aquisicao, valor_aquisicao, valor_depreciacao,
             valor_atual, status, checado, data_checagem, usuario_checagem, nome_novo)
            VALUES
            (:codigo, :nome, :fornecedor, :localidade, :conta, :numero_documento,
             :dependencia, :data_aquisicao, :valor_aquisicao, :valor_depreciacao,
             :valor_atual, :status, 0, NULL, NULL, '')";

        $stmt = $this->conn->prepare($sql);

        $stmt->bindValue(':codigo',            $codigo);
        $stmt->bindValue(':nome',              $nome);
        $stmt->bindValue(':fornecedor',        $fornecedor);
        $stmt->bindValue(':localidade',        $localidade);
        $stmt->bindValue(':conta',             $conta);
        $stmt->bindValue(':numero_documento',  $numero_documento);
        $stmt->bindValue(':dependencia',       $dependencia);
        $stmt->bindValue(':data_aquisicao',    $data_aquisicao);

        $stmt->bindValue(':valor_aquisicao',
            $valor_aquisicao,
            $valor_aquisicao === null ? PDO::PARAM_NULL : PDO::PARAM_STR
        );
        $stmt->bindValue(':valor_depreciacao',
            $valor_depreciacao,
            $valor_depreciacao === null ? PDO::PARAM_NULL : PDO::PARAM_STR
        );
        $stmt->bindValue(':valor_atual',
            $valor_atual,
            $valor_atual === null ? PDO::PARAM_NULL : PDO::PARAM_STR
        );

        $stmt->bindValue(':status',            $status);

        return $stmt->execute();
    }

    public function marcarComoChecado($codigo, $nome_novo = null, $usuario = 'Sistema') {
        $sql = "UPDATE {$this->table_name} SET checado = 1, data_checagem = NOW(), usuario_checagem = :usuario";
        if ($nome_novo) {
            $sql .= ", nome_novo = :nome_novo";
        }
        $sql .= " WHERE codigo = :codigo";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':codigo', $codigo);
        $stmt->bindValue(':usuario', $usuario);
        if ($nome_novo) {
            $stmt->bindValue(':nome_novo', $nome_novo);
        }
        return $stmt->execute();
    }

    public function desmarcarComoChecado($codigo, $usuario = 'Sistema') {
        $sql = "UPDATE {$this->table_name} SET checado = 0, data_checagem = NULL, usuario_checagem = :usuario WHERE codigo = :codigo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':codigo', $codigo);
        $stmt->bindValue(':usuario', $usuario);
        return $stmt->execute();
    }

    public function truncateTabela() {
        $this->conn->exec("TRUNCATE TABLE {$this->table_name}");
    }

    public function buscarPorCodigo($codigo) {
        $sql = "SELECT * FROM {$this->table_name} WHERE codigo = :codigo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':codigo', $codigo);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
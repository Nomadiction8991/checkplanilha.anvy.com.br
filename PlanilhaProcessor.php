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
             valor_atual, status)
            VALUES
            (:codigo, :nome, :fornecedor, :localidade, :conta, :numero_documento,
             :dependencia, :data_aquisicao, :valor_aquisicao, :valor_depreciacao,
             :valor_atual, :status)";

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

    public function truncateTabela() {
        $this->conn->exec("TRUNCATE TABLE {$this->table_name}");
    }
}
?>
<?php

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


// ... seu código existente de conexão ...

function processarProdutosCadastros($conexao, $id_planilha) {
    // Primeiro, limpar registros existentes para esta planilha
    $sql_limpar = "DELETE FROM produtos_cadastros WHERE id_produto IN 
                   (SELECT id FROM produtos WHERE id_planilha = :id_planilha)";
    $stmt_limpar = $conexao->prepare($sql_limpar);
    $stmt_limpar->bindValue(':id_planilha', $id_planilha);
    $stmt_limpar->execute();

    // Buscar todos os produtos da planilha
    $sql_produtos = "SELECT id, codigo, nome, localidade, dependencia 
                     FROM produtos 
                     WHERE id_planilha = :id_planilha";
    $stmt_produtos = $conexao->prepare($sql_produtos);
    $stmt_produtos->bindValue(':id_planilha', $id_planilha);
    $stmt_produtos->execute();
    $produtos = $stmt_produtos->fetchAll();

    foreach ($produtos as $produto) {
        $id_produto = $produto['id'];
        $codigo = $produto['codigo'];
        $nome = $produto['nome'];
        $localidade = $produto['localidade'];
        $dependencia = $produto['dependencia'];

        // 1. Processar codigo_comum (apenas números da localidade)
        $codigo_comum = preg_replace('/[^0-9]/', '', $localidade);

        // 2. Processar codigo_produto (valor depois da barra, sem zeros à esquerda)
        $pos_barra = strpos($codigo, '/');
        if ($pos_barra !== false) {
            $codigo_produto = ltrim(substr($codigo, $pos_barra + 1), '0');
        } else {
            $codigo_produto = ltrim($codigo, '0');
        }

        // 3. Processar id_tipo_bem (números antes do primeiro traço no nome)
        $id_tipo_bem = null;
        $pos_traco = strpos($nome, '-');
        if ($pos_traco !== false) {
            $codigo_tipo = trim(substr($nome, 0, $pos_traco));
            
            // Buscar o id na tabela tipos_bens
            $sql_tipo = "SELECT id FROM tipos_bens WHERE codigo = :codigo";
            $stmt_tipo = $conexao->prepare($sql_tipo);
            $stmt_tipo->bindValue(':codigo', (int)$codigo_tipo);
            $stmt_tipo->execute();
            $tipo = $stmt_tipo->fetch();
            
            if ($tipo) {
                $id_tipo_bem = $tipo['id'];
            }
        }

        // 4. Processar complemento
        $complemento = null;
        if ($id_tipo_bem && $pos_traco !== false) {
            // Buscar a descrição do tipo de bem
            $sql_descricao = "SELECT descricao FROM tipos_bens WHERE id = :id";
            $stmt_descricao = $conexao->prepare($sql_descricao);
            $stmt_descricao->bindValue(':id', $id_tipo_bem);
            $stmt_descricao->execute();
            $tipo_desc = $stmt_descricao->fetch();
            
            if ($tipo_desc) {
                $tamanho_descricao = strlen($tipo_desc['descricao']);
                // Pular os caracteres da descrição + espaço após o traço
                $complemento = trim(substr($nome, $pos_traco + $tamanho_descricao + 2));
                
                // Se ainda tiver traços, pegar apenas a primeira parte
                if (strpos($complemento, '-') !== false) {
                    $complemento = substr($complemento, 0, strpos($complemento, '-'));
                }
            }
        }

        // 5. Processar codigo_dependencia - buscar coluna CODIGO
        $codigo_dependencia = null;
        if ($dependencia) {
            // Buscar o CODIGO na tabela dependencias
            $sql_dep = "SELECT codigo FROM dependencias WHERE descricao LIKE :dependencia";
            $stmt_dep = $conexao->prepare($sql_dep);
            $stmt_dep->bindValue(':dependencia', '%' . $dependencia . '%');
            $stmt_dep->execute();
            $dep = $stmt_dep->fetch();
            
            if ($dep) {
                $codigo_dependencia = $dep['codigo'];
            }
        }

        // Inserir na tabela produtos_cadastros
        $sql_insert = "INSERT INTO produtos_cadastros 
                      (id_produto, codigo_comum, codigo_produto, id_tipo_bem, complemento, codigo_dependencia) 
                      VALUES 
                      (:id_produto, :codigo_comum, :codigo_produto, :id_tipo_bem, :complemento, :codigo_dependencia)";
        
        $stmt_insert = $conexao->prepare($sql_insert);
        $stmt_insert->bindValue(':id_produto', $id_produto);
        $stmt_insert->bindValue(':codigo_comum', $codigo_comum ?: null);
        $stmt_insert->bindValue(':codigo_produto', $codigo_produto ?: null);
        $stmt_insert->bindValue(':id_tipo_bem', $id_tipo_bem);
        $stmt_insert->bindValue(':complemento', $complemento);
        $stmt_insert->bindValue(':codigo_dependencia', $codigo_dependencia);
        $stmt_insert->execute();
    }
    
    return true;
}
?>
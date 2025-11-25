<?php
/**
 * Funções para manipulação de dados da tabela COMUMS
 * Extrai código e descrição do formato: "BR 09-0040 - SIBIPIRUNAS"
 */

/**
 * Extrai o código numérico do comum
 * Ex: "BR 09-0040 - SIBIPIRUNAS" retorna 90040
 * Ex: "BR 09-0944 - 3655 - CHÁCARA TALISMÃ" retorna 90944
 * 
 * @param string $comum_text Texto completo do comum
 * @return int Código numérico
 */
function extrair_codigo_comum($comum_text) {
    $comum_text = trim($comum_text);
    
    // Aceita variações como "BR 09-0040", "BR09 0040", "09-0040"
    if (preg_match('/BR\s*(\d{2})\D?(\d{4})/i', $comum_text, $matches)) {
        return (int)($matches[1] . $matches[2]);
    }
    if (preg_match('/(\d{2})\D?(\d{4})/', $comum_text, $matches)) {
        return (int)($matches[1] . $matches[2]);
    }
    
    return 0;
}

/**
 * Extrai a descrição do comum
 * Ex: "BR 09-0040 - SIBIPIRUNAS" retorna "SIBIPIRUNAS"
 * Ex: "BR 09-0944 - 3655 - CHÁCARA TALISMÃ" retorna "CHÁCARA TALISMÃ"
 * 
 * @param string $comum_text Texto completo do comum
 * @return string Descrição
 */
function extrair_descricao_comum($comum_text) {
    $comum_text = trim($comum_text);
    
    // Aceita variações de separador (hífen, barra ou espaço)
    if (preg_match('/BR\s*\d{2}\D?\d{4}\s*[-\/]?\s*(.+)$/i', $comum_text, $matches) ||
        preg_match('/\d{2}\D?\d{4}\s*[-\/]?\s*(.+)$/', $comum_text, $matches)) {
        $descricao = trim($matches[1]);
        
        if (strpos($descricao, '-') !== false) {
            $partes = array_map('trim', explode('-', $descricao));
            $descricao = end($partes);
        }
        
        return $descricao;
    }
    
    return '';
}

/**
 * Cria ou atualiza um comum com todos os dados
 * 
 * @param PDO $conexao Conexão com banco de dados
 * @param string $comum_text Texto do comum (ex: "BR 09-0040 - SIBIPIRUNAS")
 * @param array $dados Array com: cnpj, administracao, cidade, setor (opcional)
 * @return int ID do comum inserido ou existente
 */
function processar_comum($conexao, $comum_text, $dados = []) {
    if (empty($comum_text)) {
        throw new Exception('Comum vazio ou não informado.');
    }
    
    $codigo = extrair_codigo_comum($comum_text);
    $descricao = extrair_descricao_comum($comum_text);
    
    if (empty($codigo) || empty($descricao)) {
        throw new Exception("Formato de comum inválido: '{$comum_text}'.");
    }
    
    try {
        // Verificar se já existe
        $sql_check = "SELECT id FROM comums WHERE codigo = :codigo";
        $stmt_check = $conexao->prepare($sql_check);
        $stmt_check->bindValue(':codigo', $codigo);
        $stmt_check->execute();
        $resultado = $stmt_check->fetch();
        
        if ($resultado) {
            $comum_id = $resultado['id'];
            
            // Se fornecidos dados adicionais, atualizar
            if (!empty($dados)) {
                $sql_update = "UPDATE comums SET ";
                $updates = [];
                $params = [':id' => $comum_id];
                
                if (!empty($dados['cnpj'])) {
                    $updates[] = "cnpj = :cnpj";
                    $params[':cnpj'] = $dados['cnpj'];
                }
                if (!empty($dados['administracao'])) {
                    $updates[] = "administracao = :administracao";
                    $params[':administracao'] = $dados['administracao'];
                }
                if (!empty($dados['cidade'])) {
                    $updates[] = "cidade = :cidade";
                    $params[':cidade'] = $dados['cidade'];
                }
                if (isset($dados['setor'])) {
                    $updates[] = "setor = :setor";
                    $params[':setor'] = $dados['setor'];
                }
                
                if (!empty($updates)) {
                    $sql_update .= implode(', ', $updates) . " WHERE id = :id";
                    $stmt_update = $conexao->prepare($sql_update);
                    foreach ($params as $key => $value) {
                        $stmt_update->bindValue($key, $value);
                    }
                    $stmt_update->execute();
                }
            }
            
            return $comum_id;
        }
        
        // Se não existe, inserir
        $cnpj = $dados['cnpj'] ?? '';
        $administracao = $dados['administracao'] ?? '';
        $cidade = $dados['cidade'] ?? '';
        $setor = $dados['setor'] ?? 0;
        
        // Verificar se já existe pelo CNPJ para evitar erro de chave única
        if (!empty($cnpj)) {
            $sql_cnpj = "SELECT id FROM comums WHERE cnpj = :cnpj";
            $stmt_cnpj = $conexao->prepare($sql_cnpj);
            $stmt_cnpj->bindValue(':cnpj', $cnpj);
            $stmt_cnpj->execute();
            $res_cnpj = $stmt_cnpj->fetch();
            if ($res_cnpj) {
                return (int)$res_cnpj['id'];
            }
        }
        
        $sql_insert = "INSERT INTO comums (codigo, cnpj, descricao, administracao, cidade, setor) 
                       VALUES (:codigo, :cnpj, :descricao, :administracao, :cidade, :setor)";
        $stmt_insert = $conexao->prepare($sql_insert);
        $stmt_insert->bindValue(':codigo', $codigo);
        $stmt_insert->bindValue(':cnpj', $cnpj);
        $stmt_insert->bindValue(':descricao', $descricao);
        $stmt_insert->bindValue(':administracao', $administracao);
        $stmt_insert->bindValue(':cidade', $cidade);
        $stmt_insert->bindValue(':setor', $setor, PDO::PARAM_INT);
        $stmt_insert->execute();
        
        return $conexao->lastInsertId();
        
    } catch (Exception $e) {
        // Tentar capturar duplicidade de CNPJ ou outros detalhes
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate') !== false && !empty($cnpj ?? '')) {
            $stmt_dup = $conexao->prepare("SELECT id FROM comums WHERE cnpj = :cnpj");
            $stmt_dup->bindValue(':cnpj', $cnpj);
            $stmt_dup->execute();
            $dup = $stmt_dup->fetch();
            if ($dup) {
                return (int)$dup['id'];
            }
        }
        error_log("Erro ao processar comum: " . $msg);
        throw new Exception("Erro ao processar comum: " . $msg);
    }
}

/**
 * Obtém todos os comuns cadastrados
 * 
 * @param PDO $conexao Conexão com banco de dados
 * @return array Lista de comuns
 */
function obter_todos_comuns($conexao) {
    try {
        $sql = "SELECT id, codigo, cnpj, descricao, administracao, cidade, setor FROM comums ORDER BY codigo ASC";
        $stmt = $conexao->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao obter comuns: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtém um comum específico pelo ID
 * 
 * @param PDO $conexao Conexão com banco de dados
 * @param int $id ID do comum
 * @return array|null Dados do comum
 */
function obter_comum_por_id($conexao, $id) {
    try {
        $sql = "SELECT id, codigo, cnpj, descricao, administracao, cidade, setor FROM comums WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao obter comum: " . $e->getMessage());
        return null;
    }
}

/**
 * Conta quantas planilhas estão associadas a um comum
 * 
 * @param PDO $conexao Conexão com banco de dados
 * @param int $comum_id ID do comum
 * @return int Quantidade de planilhas
 */
function contar_planilhas_por_comum($conexao, $comum_id) {
    try {
        $sql = "SELECT COUNT(*) as total FROM planilhas WHERE comum_id = :comum_id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch();
        return $resultado['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Erro ao contar planilhas: " . $e->getMessage());
        return 0;
    }
}

/**
 * Conta total de produtos de um comum (todos das planilhas)
 * 
 * @param PDO $conexao Conexão com banco de dados
 * @param int $comum_id ID do comum
 * @return int Quantidade de produtos
 */
function contar_produtos_por_comum($conexao, $comum_id) {
    try {
        $sql = "SELECT COUNT(p.id_produto) as total 
                FROM produtos p 
                INNER JOIN planilhas pl ON p.planilha_id = pl.id 
                WHERE pl.comum_id = :comum_id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch();
        return $resultado['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Erro ao contar produtos: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obtém todas as planilhas de um comum
 * 
 * @param PDO $conexao Conexão com banco de dados
 * @param int $comum_id ID do comum
 * @return array Lista de planilhas
 */
function obter_planilhas_por_comum($conexao, $comum_id) {
    try {
        $sql = "SELECT p.id, p.comum_id, p.data_posicao, p.ativo, 
                       COUNT(pr.id_produto) as total_produtos
                FROM planilhas p
                LEFT JOIN produtos pr ON p.id = pr.planilha_id
                WHERE p.comum_id = :comum_id
                GROUP BY p.id, p.comum_id, p.data_posicao, p.ativo
                ORDER BY p.data_posicao DESC";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao obter planilhas: " . $e->getMessage());
        return [];
    }
}

/**
 * Busca comuns por código (numérico) ou descrição textual.
 * Quando nenhum termo é informado, retorna todos os registros.
 *
 * @param PDO $conexao Conexão com banco de dados
 * @param string $termo Texto informado pelo usuário
 * @return array Lista de comuns filtrada
 */
function buscar_comuns($conexao, $termo = '') {
    $termo = trim($termo);

    if ($termo === '') {
        return obter_todos_comuns($conexao);
    }

    try {
        $sql = "SELECT id, codigo, cnpj, descricao, administracao, cidade, setor
                FROM comums
                WHERE CAST(codigo AS CHAR) LIKE :busca OR descricao LIKE :busca
                ORDER BY codigo ASC";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':busca', '%' . $termo . '%');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao buscar comuns: " . $e->getMessage());
        return [];
    }
}

?>

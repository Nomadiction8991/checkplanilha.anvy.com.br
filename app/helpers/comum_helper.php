<?php

/**
 * Remove tudo que nÇœo for dÇ­gito do CNPJ informado.
 */
function normalizar_cnpj_valor($cnpj_raw) {
    $cnpj = trim((string)$cnpj_raw);
    return preg_replace('/\D+/', '', $cnpj);
}

/**
 * Gera um CNPJ Ç§nico, aplicando placeholder e sufixo quando jÇ­ existe no banco.
 *
 * @param PDO $conexao
 * @param string $cnpj_base Valor informado (pode conter mÇ¸scaras)
 * @param int $codigo CÇüdigo do comum (usado para placeholder/sufixo)
 * @param int|null $ignorar_id ID que pode repetir (para updates)
 * @return string CNPJ pronto para persistir, garantidamente Ç§nico
 */
function gerar_cnpj_unico($conexao, $cnpj_base, $codigo, $ignorar_id = null) {
    $cnpj_limpo = normalizar_cnpj_valor($cnpj_base);
    $base = $cnpj_limpo === '' ? 'SEM-CNPJ-' . $codigo : $cnpj_limpo;
    $cnpj_final = $base;
    $tentativa = 0;

    while (true) {
        $stmt = $conexao->prepare("SELECT id FROM comums WHERE cnpj = :cnpj");
        $stmt->bindValue(':cnpj', $cnpj_final);
        $stmt->execute();
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existente || ($ignorar_id !== null && (int)$existente['id'] === (int)$ignorar_id)) {
            return $cnpj_final;
        }

        $tentativa++;
        $cnpj_final = $base . '-COD-' . $codigo;
        if ($tentativa > 1) {
            $cnpj_final .= '-' . $tentativa;
        }
    }
}

/**
 * Garante que exista um registro de comum pelo codigo informado.
 * Se nao existir, insere com placeholders basicos.
 */
function garantir_comum_por_codigo($conexao, $codigo, $dados = []) {
    $codigo = (int)$codigo;
    if ($codigo <= 0) {
        throw new Exception('Codigo do comum invalido.');
    }

    $stmt = $conexao->prepare("SELECT id, cnpj FROM comums WHERE codigo = :codigo");
    $stmt->bindValue(':codigo', $codigo, PDO::PARAM_INT);
    $stmt->execute();
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existente) {
        // Atualizar dados basicos se enviados
        if (!empty($dados)) {
            $updates = [];
            $params = [':id' => (int)$existente['id']];

            if (!empty($dados['cnpj'])) {
                $novo_cnpj = gerar_cnpj_unico($conexao, $dados['cnpj'], $codigo, (int)$existente['id']);
                if ($novo_cnpj !== $existente['cnpj']) {
                    $updates[] = "cnpj = :cnpj";
                    $params[':cnpj'] = $novo_cnpj;
                }
            }
            if (isset($dados['administracao'])) {
                $updates[] = "administracao = :administracao";
                $params[':administracao'] = $dados['administracao'];
            }
            if (isset($dados['cidade'])) {
                $updates[] = "cidade = :cidade";
                $params[':cidade'] = $dados['cidade'];
            }
            if (isset($dados['setor'])) {
                $updates[] = "setor = :setor";
                $params[':setor'] = $dados['setor'];
            }

            if (!empty($updates)) {
                $sql_update = "UPDATE comums SET " . implode(', ', $updates) . " WHERE id = :id";
                $stmt_up = $conexao->prepare($sql_update);
                foreach ($params as $k => $v) {
                    $stmt_up->bindValue($k, $v);
                }
                $stmt_up->execute();
            }
        }

        return (int)$existente['id'];
    }

    // Inserir novo registro com campos vazios
    $cnpj_final = null;
    $descricao = $dados['descricao'] ?? '';
    $administracao = $dados['administracao'] ?? '';
    $cidade = $dados['cidade'] ?? '';
    $setor = $dados['setor'] ?? null;

    $sql_insert = "INSERT INTO comums (codigo, cnpj, descricao, administracao, cidade, setor)
                   VALUES (:codigo, :cnpj, :descricao, :administracao, :cidade, :setor)";
    $stmt_insert = $conexao->prepare($sql_insert);
    $stmt_insert->bindValue(':codigo', $codigo, PDO::PARAM_INT);
    if ($cnpj_final === null) {
        $stmt_insert->bindValue(':cnpj', null, PDO::PARAM_NULL);
    } else {
        $stmt_insert->bindValue(':cnpj', $cnpj_final);
    }
    $stmt_insert->bindValue(':descricao', $descricao);
    $stmt_insert->bindValue(':administracao', $administracao);
    $stmt_insert->bindValue(':cidade', $cidade);
    if ($setor === null) {
        $stmt_insert->bindValue(':setor', null, PDO::PARAM_NULL);
    } else {
        $stmt_insert->bindValue(':setor', $setor, PDO::PARAM_INT);
    }
    $stmt_insert->execute();

    return (int)$conexao->lastInsertId();
}
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
        throw new Exception('Comum vazio ou nao informado.');
    }
    
    $codigo = extrair_codigo_comum($comum_text);
    $descricao = extrair_descricao_comum($comum_text);
    
    if (empty($codigo) || empty($descricao)) {
        throw new Exception("Formato de comum invalido: '{$comum_text}'.");
    }
    
    $cnpj_final = null;
    $cnpj_informado = $dados['cnpj'] ?? '';
    $administracao = $dados['administracao'] ?? '';
    $cidade = $dados['cidade'] ?? '';
    $setor = $dados['setor'] ?? 0;
    
    try {
        // Verificar se ja existe
        $sql_check = "SELECT id, cnpj FROM comums WHERE codigo = :codigo";
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
                    $cnpj_final = gerar_cnpj_unico($conexao, $dados['cnpj'], $codigo, $comum_id);
                    if ($cnpj_final !== $resultado['cnpj']) {
                        $updates[] = "cnpj = :cnpj";
                        $params[':cnpj'] = $cnpj_final;
                    }
                }
                if (!empty($administracao)) {
                    $updates[] = "administracao = :administracao";
                    $params[':administracao'] = $administracao;
                }
                if (!empty($cidade)) {
                    $updates[] = "cidade = :cidade";
                    $params[':cidade'] = $cidade;
                }
                if (isset($dados['setor'])) {
                    $updates[] = "setor = :setor";
                    $params[':setor'] = $setor;
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
        
        // Se nao existe, inserir
        $cnpj_final = gerar_cnpj_unico($conexao, $cnpj_informado, $codigo);
        
        $sql_insert = "INSERT INTO comums (codigo, cnpj, descricao, administracao, cidade, setor) 
                       VALUES (:codigo, :cnpj, :descricao, :administracao, :cidade, :setor)";
        $stmt_insert = $conexao->prepare($sql_insert);
        $stmt_insert->bindValue(':codigo', $codigo);
        $stmt_insert->bindValue(':cnpj', $cnpj_final);
        $stmt_insert->bindValue(':descricao', $descricao);
        $stmt_insert->bindValue(':administracao', $administracao);
        $stmt_insert->bindValue(':cidade', $cidade);
        $stmt_insert->bindValue(':setor', $setor, PDO::PARAM_INT);
        $stmt_insert->execute();
        
        return $conexao->lastInsertId();
        
    } catch (Exception $e) {
        // Tentar capturar duplicidade de CNPJ ou outros detalhes
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate') !== false && !empty($cnpj_final ?? '')) {
            $stmt_dup = $conexao->prepare("SELECT id FROM comums WHERE cnpj = :cnpj");
            $stmt_dup->bindValue(':cnpj', $cnpj_final);
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
 * Retorna a planilha ativa/mais recente vinculada ao comum.
 */
function obter_planilha_ativa_por_comum(PDO $conexao, int $comum_id): ?array {
    try {
        $sql = "SELECT * FROM planilhas WHERE comum_id = :comum_id ORDER BY data_posicao DESC, id DESC LIMIT 1";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':comum_id', $comum_id, PDO::PARAM_INT);
        $stmt->execute();
        $planilha = $stmt->fetch(PDO::FETCH_ASSOC);
        return $planilha ?: null;
    } catch (Exception $e) {
        error_log("Erro ao obter planilha ativa: " . $e->getMessage());
        return null;
    }
}

/**
 * Resolve o ID da planilha a partir do ID do comum.
 */
function resolver_planilha_id_por_comum(PDO $conexao, int $comum_id): ?int {
    $planilha = obter_planilha_ativa_por_comum($conexao, $comum_id);
    return $planilha ? (int) $planilha['id'] : null;
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
    $termo = trim((string) $termo);

    if ($termo === '') {
        return obter_todos_comuns($conexao);
    }

    $like = '%' . $termo . '%';
    $digits = preg_replace('/\D+/', '', $termo);
    $likeDigits = $digits !== '' ? '%' . $digits . '%' : null;

    try {
        $sql = "SELECT id, codigo, cnpj, descricao, administracao, cidade, setor
                FROM comums
                WHERE CAST(codigo AS CHAR) LIKE :like
                   OR descricao LIKE :like
                   OR administracao LIKE :like
                   OR cidade LIKE :like
                   OR cnpj LIKE :like";

        if ($likeDigits !== null) {
            $sql .= " OR REPLACE(REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '-', ''), '/', ''), ' ', '') LIKE :likeDigits
                     OR CAST(codigo AS CHAR) LIKE :likeDigits";
        }

        $sql .= " ORDER BY codigo ASC";

        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(':like', $like);
        if ($likeDigits !== null) {
            $stmt->bindValue(':likeDigits', $likeDigits);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao buscar comuns: " . $e->getMessage());
        return [];
    }
}

?>

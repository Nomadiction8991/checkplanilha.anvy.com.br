<?php
require_once '../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

// Receber parâmetros
$id_produto = $_GET['id_produto'] ?? null;
$id_planilha = $_GET['id'] ?? null;

// Receber filtros para redirecionamento
$pagina = $_GET['pagina'] ?? 1;
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['filtro_codigo'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Validação
if (!$id_produto || !$id_planilha) {
    $query_string = http_build_query([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status,
        'erro' => 'Parâmetros inválidos'
    ]);
    header('Location: ../planilhas/view-planilha.php?' . $query_string);
    exit;
}

$mensagem = '';
$tipo_mensagem = '';
$novo_nome = '';
$nova_dependencia = '';

// Buscar dados do produto
try {
    $sql_produto = "SELECT * FROM produtos WHERE id = :id_produto AND id_planilha = :id_planilha";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':id_produto', $id_produto);
    $stmt_produto->bindValue(':id_planilha', $id_planilha);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();
    
    if (!$produto) {
        throw new Exception('Produto não encontrado.');
    }
    
    // Buscar dados do check (se existir)
    $sql_check = "SELECT * FROM produtos_check WHERE produto_id = :produto_id";
    $stmt_check = $conexao->prepare($sql_check);
    $stmt_check->bindValue(':produto_id', $produto['id']);
    $stmt_check->execute();
    $check = $stmt_check->fetch();

    // Se existir registro, usar os valores para preencher o formulário
    if ($check) {
        $novo_nome = $check['nome'];
        $nova_dependencia = $check['dependencia'];
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar produto: " . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Buscar opções de dependência para o select
try {
    $sql_dependencias = "SELECT DISTINCT dependencia FROM produtos WHERE id_planilha = :id_planilha ORDER BY dependencia";
    $stmt_dependencias = $conexao->prepare($sql_dependencias);
    $stmt_dependencias->bindValue(':id_planilha', $id_planilha);
    $stmt_dependencias->execute();
    $dependencia_options = $stmt_dependencias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $dependencia_options = [];
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novo_nome = trim($_POST['novo_nome'] ?? '');
    $nova_dependencia = trim($_POST['nova_dependencia'] ?? '');
    
    // Receber filtros do POST também
    $pagina = $_POST['pagina'] ?? 1;
    $filtro_nome = $_POST['nome'] ?? '';
    $filtro_dependencia = $_POST['dependencia'] ?? '';
    $filtro_codigo = $_POST['filtro_codigo'] ?? '';
    $filtro_status = $_POST['status'] ?? '';
    
    try {
        // Verificar se já existe registro na tabela produtos_check
        $sql_verificar = "SELECT COUNT(*) as total FROM produtos_check WHERE produto_id = :produto_id";
        $stmt_verificar = $conexao->prepare($sql_verificar);
        $stmt_verificar->bindValue(':produto_id', $produto['id']);
        $stmt_verificar->execute();
        $existe_registro = $stmt_verificar->fetch()['total'] > 0;

        // Se não houver alterações (campos vazios), redireciona sem salvar
        if (empty($novo_nome) && empty($nova_dependencia)) {
            // Redireciona de volta para a view-planilha sem fazer alterações
            header('Location: ' . getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status));
            exit;
        } else {
            if ($existe_registro) {
                // Atualizar registro existente - apenas se os campos não estiverem vazios
                $sql_update = "UPDATE produtos_check SET imprimir = 1, editado = 1";
                $params = [':produto_id' => $produto['id']];
                
                if (!empty($novo_nome)) {
                    $sql_update .= ", nome = :nome";
                    $params[':nome'] = $novo_nome;
                }
                if (!empty($nova_dependencia)) {
                    $sql_update .= ", dependencia = :dependencia";
                    $params[':dependencia'] = $nova_dependencia;
                }
                
                $sql_update .= " WHERE produto_id = :produto_id";
                $stmt_update = $conexao->prepare($sql_update);
                
                foreach ($params as $key => $value) {
                    $stmt_update->bindValue($key, $value);
                }
                $stmt_update->execute();
            } else {
                // Inserir novo registro - apenas com campos preenchidos
                $campos = ['produto_id', 'imprimir', 'editado'];
                $valores = [':produto_id', '1', '1'];
                $params = [':produto_id' => $produto['id']];
                
                if (!empty($novo_nome)) {
                    $campos[] = 'nome';
                    $valores[] = ':nome';
                    $params[':nome'] = $novo_nome;
                }
                if (!empty($nova_dependencia)) {
                    $campos[] = 'dependencia';
                    $valores[] = ':dependencia';
                    $params[':dependencia'] = $nova_dependencia;
                }
                
                $sql_insert = "INSERT INTO produtos_check (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $valores) . ")";
                $stmt_insert = $conexao->prepare($sql_insert);
                
                foreach ($params as $key => $value) {
                    $stmt_insert->bindValue($key, $value);
                }
                $stmt_insert->execute();
            }

            // Redireciona de volta para a view-planilha após salvar
            header('Location: ' . getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status));
            exit;
        }
        
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar alterações: " . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}

// Função para gerar URL de retorno com filtros
function getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status) {
    $params = [
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'status' => $filtro_status
    ];
    return '../planilhas/view-planilha.php?' . http_build_query($params);
}
?>
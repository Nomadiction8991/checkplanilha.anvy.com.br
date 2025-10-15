<?php
// Incluir arquivo de conexão
require_once '../CRUD/conexao.php';

// Pegar o ID da planilha via GET
$id_planilha = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar dados da planilha para obter o valor da coluna "comum" e "cnpj"
$sql_planilha = "SELECT comum, cnpj FROM planilhas WHERE id = :id_planilha";
$stmt_planilha = $conexao->prepare($sql_planilha);
$stmt_planilha->bindValue(':id_planilha', $id_planilha);
$stmt_planilha->execute();
$planilha = $stmt_planilha->fetch();

$comum_planilha = $planilha ? $planilha['comum'] : '';
$cnpj_planilha = $planilha ? $planilha['cnpj'] : '';

// Extrair número do relatório e casa de oração da coluna "comum"
$numero_relatorio = '';
$casa_oracao = '';

if (!empty($comum_planilha)) {
    $partes = explode('-', $comum_planilha, 2);
    $numero_relatorio = trim($partes[0]);
    if (isset($partes[1])) {
        $casa_oracao = trim($partes[1]);
    }
}

// Consultar produtos que devem imprimir o relatório 14.1
$sql = "SELECT 
            pc.id,
            pc.tipo_ben,
            pc.complemento,
            pc.quantidade,
            pc.descricao_completa,
            pc.possui_nota,
            pc.imprimir_14_1,
            tb.codigo as tipo_codigo,
            tb.descricao as tipo_descricao,
            d.descricao as dependencia_descricao
        FROM produtos_cadastro pc
        LEFT JOIN tipos_bens tb ON pc.id_tipo_ben = tb.id
        LEFT JOIN dependencias d ON pc.id_dependencia = d.id
        WHERE pc.imprimir_14_1 = 1 AND pc.id_planilha = :id_planilha
        ORDER BY pc.id ASC";
$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_planilha', $id_planilha);
$stmt->execute();
$produtos = $stmt->fetchAll();

// As variáveis $produtos, $comum_planilha, $cnpj_planilha, $numero_relatorio e $casa_oracao estarão disponíveis para o HTML
?>
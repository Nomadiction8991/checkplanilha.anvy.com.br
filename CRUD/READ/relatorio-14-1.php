<?php
require_once '../../auth.php'; // Autenticação
// Incluir arquivo de conexão
require_once __DIR__ . '/../conexao.php';

// Pegar o ID da planilha via GET
$id_planilha = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar dados da planilha para obter os valores das colunas necessárias
$sql_planilha = "SELECT comum, cnpj, administracao, cidade FROM planilhas WHERE id = :id_planilha";
$stmt_planilha = $conexao->prepare($sql_planilha);
$stmt_planilha->bindValue(':id_planilha', $id_planilha);
$stmt_planilha->execute();
$planilha = $stmt_planilha->fetch();

$comum_planilha = $planilha ? ($planilha['comum'] ?? '') : '';
$cnpj_planilha = $planilha ? ($planilha['cnpj'] ?? '') : '';
$administracao_planilha = $planilha ? ($planilha['administracao'] ?? '') : '';
$cidade_planilha = $planilha ? ($planilha['cidade'] ?? '') : '';

// Derivar número do relatório e casa de oração a partir de "comum"
// Regra: número do relatório = apenas dígitos antes do segundo '-' ; casa de oração = texto após o segundo '-'
$numero_relatorio_auto = '';
$casa_oracao_auto = '';
if (!empty($comum_planilha)) {
    // Quebrar em partes por '-'
    $partes = array_map('trim', explode('-', $comum_planilha));
    // Número do relatório: capturar somente dígitos da parte antes do segundo '-'
    // Se houver pelo menos 2 partes, usamos a parte 1 (índice 1) ou a concat das duas primeiras? Pedido diz: "só os números antes do segundo '-'"
    // Isso corresponde ao conteúdo acumulado das partes antes do segundo '-', porém comumente a parte imediatamente anterior ao segundo '-' já contém o número.
    // Implementação robusta: juntar tudo até o índice 1 e extrair dígitos
    if (count($partes) >= 2) {
        $antesSegundoHifen = trim($partes[0] . ' ' . $partes[1]);
        if (preg_match_all('/\d+/', $antesSegundoHifen, $m)) {
            $numero_relatorio_auto = implode('', $m[0]);
        }
    } else {
        // fallback: extrair dígitos de toda a string
        if (preg_match_all('/\d+/', $comum_planilha, $m)) {
            $numero_relatorio_auto = implode('', $m[0]);
        }
    }
    // Casa de oração: conteúdo após o segundo '-'
    if (count($partes) >= 3) {
        // Rejuntar tudo a partir da terceira parte para manter possíveis '-' internos após o segundo
        $casa_oracao_auto = trim(implode(' - ', array_slice($partes, 2)));
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

// As variáveis $produtos e $comum_planilha estarão disponíveis para o HTML
// Expor variáveis adicionais: $cnpj_planilha, $numero_relatorio_auto, $casa_oracao_auto
?>
<?php
require_once __DIR__ . '/../../auth.php'; // Autenticação
// Incluir arquivo de conexão
require_once __DIR__ . '/../conexao.php';

// Pegar o ID da planilha via GET
$id_planilha = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar dados da planilha com JOIN na tabela comums
$sql_planilha = "SELECT c.descricao as comum, c.cnpj, c.administracao, c.cidade 
                 FROM planilhas p
                 LEFT JOIN comums c ON p.comum_id = c.id
                 WHERE p.id = :id_planilha";
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
            p.id_produto,
            p.tipo_bem_id,
            p.complemento,
            p.descricao_completa,
            p.condicao_14_1,
            p.nota_numero,
            p.nota_data,
            p.nota_valor,
            p.nota_fornecedor,
            p.imprimir_14_1,
            tb.codigo as tipo_codigo,
            tb.descricao as tipo_descricao,
            d.descricao as dependencia_descricao,
            admin.nome as administrador_nome,
            admin.cpf as administrador_cpf,
            admin.rg as administrador_rg,
            admin.assinatura as administrador_assinatura,
            doador.nome as doador_nome,
            doador.cpf as doador_cpf,
            doador.rg as doador_rg,
            doador.assinatura as doador_assinatura,
            doador.casado as doador_casado,
            doador.nome_conjuge as doador_nome_conjuge,
            doador.cpf_conjuge as doador_cpf_conjuge,
            doador.rg_conjuge as doador_rg_conjuge,
            doador.assinatura_conjuge as doador_assinatura_conjuge,
            doador.endereco_cep as doador_endereco_cep,
            doador.endereco_logradouro as doador_endereco_logradouro,
            doador.endereco_numero as doador_endereco_numero,
            doador.endereco_complemento as doador_endereco_complemento,
            doador.endereco_bairro as doador_endereco_bairro,
            doador.endereco_cidade as doador_endereco_cidade,
            doador.endereco_estado as doador_endereco_estado
        FROM produtos p
        LEFT JOIN tipos_bens tb ON p.tipo_bem_id = tb.id
        LEFT JOIN dependencias d ON p.dependencia_id = d.id
        LEFT JOIN usuarios admin ON p.administrador_acessor_id = admin.id
        LEFT JOIN usuarios doador ON p.doador_conjugue_id = doador.id
        WHERE p.imprimir_14_1 = 1 AND p.planilha_id = :id_planilha
        ORDER BY p.id_produto ASC";
$stmt = $conexao->prepare($sql);
$stmt->bindValue(':id_planilha', $id_planilha);
$stmt->execute();
$produtos = $stmt->fetchAll();

// As variáveis $produtos e $comum_planilha estarão disponíveis para o HTML
// Expor variáveis adicionais: $cnpj_planilha, $numero_relatorio_auto, $casa_oracao_auto
?>
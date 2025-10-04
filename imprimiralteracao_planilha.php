<?php
require_once 'conexao.php';

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: index.php');
    exit;
}

// Buscar dados da planilha
try {
    $sql_planilha = "SELECT * FROM planilhas WHERE id = :id";
    $stmt_planilha = $conexao->prepare($sql_planilha);
    $stmt_planilha->bindValue(':id', $id_planilha);
    $stmt_planilha->execute();
    $planilha = $stmt_planilha->fetch();
    
    if (!$planilha) {
        throw new Exception('Planilha não encontrada.');
    }
} catch (Exception $e) {
    die("Erro ao carregar planilha: " . $e->getMessage());
}

// Filtros do relatório - AGORA SÃO CHECKBOXES PARA SEÇÕES
$mostrar_observacao = isset($_GET['mostrar_observacao']) ? true : true; // Padrão: SIM (sempre mostra)
$mostrar_checados = isset($_GET['mostrar_checados']) ? true : false; // Padrão: NÃO
$mostrar_ambos = isset($_GET['mostrar_ambos']) ? true : false; // Padrão: NÃO
$mostrar_sem_observacao = isset($_GET['mostrar_sem_observacao']) ? true : false; // Padrão: NÃO
$filtro_dependencia = $_GET['dependencia'] ?? ''; // Filtro de dependência

// Buscar TODOS os produtos (sem filtros de tipo)
try {
    $sql_produtos = "SELECT p.*, pc.checado, pc.observacoes 
                     FROM produtos p 
                     LEFT JOIN produtos_check pc ON p.id = pc.produto_id 
                     WHERE p.id_planilha = :id_planilha";
    
    $params = [':id_planilha' => $id_planilha];
    
    // Aplicar apenas filtro de dependência
    if (!empty($filtro_dependencia)) {
        $sql_produtos .= " AND p.dependencia LIKE :dependencia";
        $params[':dependencia'] = '%' . $filtro_dependencia . '%';
    }
    
    $sql_produtos .= " ORDER BY p.codigo";
    
    $stmt_produtos = $conexao->prepare($sql_produtos);
    foreach ($params as $key => $value) {
        $stmt_produtos->bindValue($key, $value);
    }
    $stmt_produtos->execute();
    $todos_produtos = $stmt_produtos->fetchAll();
    
} catch (Exception $e) {
    die("Erro ao carregar produtos: " . $e->getMessage());
}

// Buscar opções de dependência para o filtro
try {
    $sql_dependencias = "SELECT DISTINCT dependencia FROM produtos WHERE id_planilha = :id_planilha ORDER BY dependencia";
    $stmt_dependencias = $conexao->prepare($sql_dependencias);
    $stmt_dependencias->bindValue(':id_planilha', $id_planilha);
    $stmt_dependencias->execute();
    $dependencia_options = $stmt_dependencias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $dependencia_options = [];
}

// Agrupar produtos por tipo (independente dos filtros)
$produtos_com_observacao = [];
$produtos_checados = [];
$produtos_ambos = [];
$produtos_sem_observacao = [];

foreach ($todos_produtos as $produto) {
    $tem_observacao = !empty($produto['observacoes']);
    $esta_checado = $produto['checado'] == 1;
    
    if ($tem_observacao && $esta_checado) {
        $produtos_ambos[] = $produto;
    } elseif ($tem_observacao && !$esta_checado) {
        $produtos_com_observacao[] = $produto;
    } elseif (!$tem_observacao && $esta_checado) {
        $produtos_checados[] = $produto;
    } else {
        $produtos_sem_observacao[] = $produto;
    }
}

// Contar totais
$total_com_observacao = count($produtos_com_observacao);
$total_checados = count($produtos_checados);
$total_ambos = count($produtos_ambos);
$total_sem_observacao = count($produtos_sem_observacao);
$total_geral = count($todos_produtos);

// Calcular totais que serão mostrados baseado nos filtros
$total_mostrar = 0;
if ($mostrar_observacao) $total_mostrar += $total_com_observacao;
if ($mostrar_checados) $total_mostrar += $total_checados;
if ($mostrar_ambos) $total_mostrar += $total_ambos;
if ($mostrar_sem_observacao) $total_mostrar += $total_sem_observacao;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Alterações - <?php echo htmlspecialchars($planilha['descricao']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #000;
            font-size: 12px;
            line-height: 1.4;
        }
        
        @media print {
            body {
                padding: 10px;
                font-size: 10px;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            
            .filtros-info {
                background: #f8f9fa !important;
                color: #000 !important;
                border: 1px solid #000 !important;
            }
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: normal;
        }
        
        .info-planilha {
            margin-bottom: 15px;
            padding: 10px;
            background: #f5f5f5;
            border-left: 4px solid #007bff;
        }
        
        .filtros-info {
            margin-bottom: 20px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 4px;
            border-left: 4px solid #2196f3;
        }
        
        .resumo {
            margin-bottom: 20px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 4px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th {
            background-color: #343a40;
            color: white;
            padding: 8px;
            text-align: left;
            border: 1px solid #000;
            font-size: 11px;
        }
        
        td {
            padding: 6px;
            border: 1px solid #000;
            vertical-align: top;
        }
        
        .observacao-cell {
            background-color: #fff3cd; /* Amarelo claro para observações */
            font-style: italic;
        }
        
        .checado-cell {
            background-color: #d4edda; /* Verde claro para checados */
        }
        
        .ambos-cell {
            background-color: #e6e6fa; /* Roxo claro para ambos */
        }
        
        .secao-titulo {
            background-color: #6c757d;
            color: white;
            padding: 8px;
            margin: 20px 0 10px 0;
            font-weight: bold;
            border-left: 4px solid #495057;
        }
        
        .valor-monetario {
            text-align: right;
            white-space: nowrap;
        }
        
        .btn-print {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .btn-print:hover {
            background: #0056b3;
        }
        
        .sem-registros {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border: 1px dashed #6c757d;
            margin: 10px 0;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 10px;
        }
        
        .filtros-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        
        .filtro-group {
            margin-bottom: 15px;
        }
        
        .filtro-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .filtro-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .filtro-option {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-apply {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-apply:hover {
            background: #218838;
        }
        
        .secao-oculta {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Formulário de Filtros -->
    <div class="filtros-form no-print">
        <h3>Seções do Relatório (Marque quais seções deseja incluir)</h3>
        
        <form method="GET">
            <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
            
            <div class="filtro-group">
                <label>Seções a Incluir:</label>
                <div class="filtro-options">
                    <div class="filtro-option">
                        <input type="checkbox" name="mostrar_observacao" value="1" 
                               <?php echo $mostrar_observacao ? 'checked' : ''; ?>>
                        <label>Produtos com observação apenas (<?php echo $total_com_observacao; ?>)</label>
                    </div>
                    <div class="filtro-option">
                        <input type="checkbox" name="mostrar_checados" value="1" 
                               <?php echo $mostrar_checados ? 'checked' : ''; ?>>
                        <label>Produtos checados (<?php echo $total_checados; ?>)</label>
                    </div>
                    <div class="filtro-option">
                        <input type="checkbox" name="mostrar_ambos" value="1" 
                               <?php echo $mostrar_ambos ? 'checked' : ''; ?>>
                        <label>Produtos com observação e checagem (<?php echo $total_ambos; ?>)</label>
                    </div>
                    <div class="filtro-option">
                        <input type="checkbox" name="mostrar_sem_observacao" value="1" 
                               <?php echo $mostrar_sem_observacao ? 'checked' : ''; ?>>
                        <label>Produtos sem observação (<?php echo $total_sem_observacao; ?>)</label>
                    </div>
                </div>
            </div>
            
            <div class="filtro-group">
                <label for="dependencia">Filtrar por Dependência:</label>
                <select name="dependencia" id="dependencia" style="padding: 5px; width: 300px;">
                    <option value="">Todas as dependências</option>
                    <?php foreach ($dependencia_options as $dep): ?>
                        <option value="<?php echo htmlspecialchars($dep); ?>" 
                                <?php echo $filtro_dependencia === $dep ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn-apply">Aplicar Filtros</button>
        </form>
    </div>

    <!-- Botão de impressão (não aparece na impressão) -->
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button class="btn-print" onclick="window.print()">
            🖨️ Imprimir Relatório
        </button>
        <a href="visualizar_planilha.php?id=<?php echo $id_planilha; ?>" 
           style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; margin-left: 10px;">
            ← Voltar para Planilha
        </a>
    </div>

    <!-- Informações dos filtros aplicados -->
    <div class="filtros-info">
        <strong>Seções Incluídas:</strong><br>
        <?php
        $secoes_texto = [];
        if ($mostrar_observacao) $secoes_texto[] = "Produtos com observação (" . $total_com_observacao . ")";
        if ($mostrar_checados) $secoes_texto[] = "Produtos checados (" . $total_checados . ")";
        if ($mostrar_ambos) $secoes_texto[] = "Produtos com observação e checagem (" . $total_ambos . ")";
        if ($mostrar_sem_observacao) $secoes_texto[] = "Produtos sem observação (" . $total_sem_observacao . ")";
        
        echo empty($secoes_texto) ? "Nenhuma seção selecionada" : implode(" | ", $secoes_texto);
        
        if (!empty($filtro_dependencia)) {
            echo "<br><strong>Dependência:</strong> " . htmlspecialchars($filtro_dependencia);
        }
        ?>
    </div>

    <!-- Cabeçalho do relatório -->
    <div class="header">
        <h1>RELATÓRIO DE ALTERAÇÕES - CONTROLE DE PATRIMÔNIO</h1>
        <h2>Planilha: <?php echo htmlspecialchars($planilha['descricao']); ?></h2>
        <p>Data de geração: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <!-- Informações da planilha -->
    <div class="info-planilha">
        <strong>Status da Planilha:</strong> <?php echo ucfirst($planilha['status']); ?> | 
        <strong>Data de Criação:</strong> <?php echo date('d/m/Y', strtotime($planilha['data_criacao'])); ?>
    </div>

    <!-- Resumo estatístico -->
    <div class="resumo">
        <strong>RESUMO GERAL:</strong><br>
        - Total de produtos na planilha: <?php echo $total_geral; ?><br>
        - Produtos com observação: <?php echo $total_com_observacao; ?><br>
        - Produtos checados: <?php echo $total_checados; ?><br>
        - Produtos com observação e checagem: <?php echo $total_ambos; ?><br>
        - Produtos sem observação: <?php echo $total_sem_observacao; ?><br>
        - <strong>Total a ser impresso: <?php echo $total_mostrar; ?> produtos</strong>
    </div>

    <?php if ($total_geral > 0): ?>
        
        <!-- SEÇÃO 1: Produtos com Observação Apenas -->
        <?php if ($mostrar_observacao && $total_com_observacao > 0): ?>
            <div class="secao-titulo">
                PRODUTOS COM OBSERVAÇÃO (<?php echo $total_com_observacao; ?> itens)
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="15%">Código</th>
                        <th width="30%">Nome</th>
                        <th width="25%">Dependência</th>
                        <th width="10%">Status</th>
                        <th width="20%">Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos_com_observacao as $produto): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                            <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                            <td><?php echo htmlspecialchars($produto['status']); ?></td>
                            <td class="observacao-cell"><?php echo htmlspecialchars($produto['observacoes']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- SEÇÃO 2: Produtos Checados -->
        <?php if ($mostrar_checados && $total_checados > 0): ?>
            <div class="secao-titulo">
                PRODUTOS CHECADOS (<?php echo $total_checados; ?> itens)
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="15%">Código</th>
                        <th width="40%">Nome</th>
                        <th width="30%">Dependência</th>
                        <th width="15%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos_checados as $produto): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
                            <td class="checado-cell"><?php echo htmlspecialchars($produto['nome']); ?></td>
                            <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                            <td><?php echo htmlspecialchars($produto['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- SEÇÃO 3: Produtos com Observação e Checagem -->
        <?php if ($mostrar_ambos && $total_ambos > 0): ?>
            <div class="secao-titulo">
                PRODUTOS COM OBSERVAÇÃO E CHECAGEM (<?php echo $total_ambos; ?> itens)
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="15%">Código</th>
                        <th width="30%">Nome</th>
                        <th width="25%">Dependência</th>
                        <th width="10%">Status</th>
                        <th width="20%">Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos_ambos as $produto): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
                            <td class="ambos-cell"><?php echo htmlspecialchars($produto['nome']); ?></td>
                            <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                            <td><?php echo htmlspecialchars($produto['status']); ?></td>
                            <td class="ambos-cell"><?php echo htmlspecialchars($produto['observacoes']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- SEÇÃO 4: Produtos sem Observação -->
        <?php if ($mostrar_sem_observacao && $total_sem_observacao > 0): ?>
            <div class="secao-titulo">
                PRODUTOS SEM OBSERVAÇÃO (<?php echo $total_sem_observacao; ?> itens)
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="15%">Código</th>
                        <th width="40%">Nome</th>
                        <th width="30%">Dependência</th>
                        <th width="15%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos_sem_observacao as $produto): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                            <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                            <td><?php echo htmlspecialchars($produto['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($total_mostrar === 0): ?>
            <div class="sem-registros" style="text-align: center; padding: 40px;">
                <h3>Nenhuma seção selecionada</h3>
                <p>Marque pelo menos uma seção para visualizar o relatório.</p>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="sem-registros" style="text-align: center; padding: 40px;">
            <h3>Nenhum produto encontrado</h3>
            <p>Não há produtos cadastrados nesta planilha ou não correspondem ao filtro de dependência.</p>
        </div>
    <?php endif; ?>

    <!-- Rodapé -->
    <div class="footer">
        Relatório gerado em <?php echo date('d/m/Y \à\s H:i:s'); ?> | 
        Sistema de Controle de Patrimônio
    </div>

    <script>
        window.onbeforeprint = function() {
            document.title = "Relatório Alterações - <?php echo htmlspecialchars($planilha['descricao']); ?>";
        };
    </script>
</body>
</html>
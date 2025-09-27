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

// Buscar produtos com observações (não checados)
try {
    $sql_com_observacoes = "SELECT p.*, pc.observacoes 
                           FROM produtos p 
                           LEFT JOIN produtos_check pc ON p.id = pc.produto_id 
                           WHERE p.id_planilha = :id_planilha 
                           AND pc.observacoes IS NOT NULL 
                           AND pc.observacoes != '' 
                           AND COALESCE(pc.checado, 0) = 0
                           ORDER BY p.codigo";
    
    $stmt_com_observacoes = $conexao->prepare($sql_com_observacoes);
    $stmt_com_observacoes->bindValue(':id_planilha', $id_planilha);
    $stmt_com_observacoes->execute();
    $produtos_com_observacoes = $stmt_com_observacoes->fetchAll();
    
    // Buscar produtos sem observações (não checados)
    $sql_sem_observacoes = "SELECT p.*, pc.observacoes 
                           FROM produtos p 
                           LEFT JOIN produtos_check pc ON p.id = pc.produto_id 
                           WHERE p.id_planilha = :id_planilha 
                           AND (pc.observacoes IS NULL OR pc.observacoes = '')
                           AND COALESCE(pc.checado, 0) = 0
                           ORDER BY p.codigo";
    
    $stmt_sem_observacoes = $conexao->prepare($sql_sem_observacoes);
    $stmt_sem_observacoes->bindValue(':id_planilha', $id_planilha);
    $stmt_sem_observacoes->execute();
    $produtos_sem_observacoes = $stmt_sem_observacoes->fetchAll();
    
} catch (Exception $e) {
    die("Erro ao carregar produtos: " . $e->getMessage());
}

// Contar totais
$total_com_observacoes = count($produtos_com_observacoes);
$total_sem_observacoes = count($produtos_sem_observacoes);
$total_geral = $total_com_observacoes + $total_sem_observacoes;
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
            background-color: #fff3cd;
            font-style: italic;
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
    </style>
</head>
<body>
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
        <strong>RESUMO:</strong><br>
        - Produtos com observações: <?php echo $total_com_observacoes; ?><br>
        - Produtos sem observações: <?php echo $total_sem_observacoes; ?><br>
        - Total de produtos no relatório: <?php echo $total_geral; ?><br>
        - <em>Produtos marcados como "checados" não aparecem neste relatório</em>
    </div>

    <?php if ($total_geral > 0): ?>
        
        <!-- SEÇÃO 1: Produtos com Observações -->
        <?php if ($total_com_observacoes > 0): ?>
            <div class="secao-titulo">
                PRODUTOS COM OBSERVAÇÕES (<?php echo $total_com_observacoes; ?> itens)
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="8%">Código</th>
                        <th width="20%">Nome</th>
                        <th width="12%">Fornecedor</th>
                        <th width="10%">Localidade</th>
                        <th width="8%">Conta</th>
                        <th width="10%">Nº Documento</th>
                        <th width="12%">Dependência</th>
                        <th width="8%">Data Aquisição</th>
                        <th width="8%">Valor Atual</th>
                        <th width="8%">Status</th>
                        <th width="20%">Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos_com_observacoes as $produto): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                            <td><?php echo htmlspecialchars($produto['fornecedor']); ?></td>
                            <td><?php echo htmlspecialchars($produto['localidade']); ?></td>
                            <td><?php echo htmlspecialchars($produto['conta']); ?></td>
                            <td><?php echo htmlspecialchars($produto['numero_documento']); ?></td>
                            <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                            <td><?php echo htmlspecialchars($produto['data_aquisicao']); ?></td>
                            <td class="valor-monetario">R$ <?php echo number_format($produto['valor_atual'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($produto['status']); ?></td>
                            <td class="observacao-cell"><?php echo htmlspecialchars($produto['observacoes']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="sem-registros">
                Nenhum produto com observações encontrado.
            </div>
        <?php endif; ?>

        <!-- Quebra de página entre seções -->
        <div class="page-break"></div>

        <!-- SEÇÃO 2: Produtos sem Observações -->
        <?php if ($total_sem_observacoes > 0): ?>
            <div class="secao-titulo">
                PRODUTOS SEM OBSERVAÇÕES (<?php echo $total_sem_observacoes; ?> itens)
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="10%">Código</th>
                        <th width="22%">Nome</th>
                        <th width="14%">Fornecedor</th>
                        <th width="10%">Localidade</th>
                        <th width="8%">Conta</th>
                        <th width="10%">Nº Documento</th>
                        <th width="14%">Dependência</th>
                        <th width="8%">Data Aquisição</th>
                        <th width="8%">Valor Atual</th>
                        <th width="8%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos_sem_observacoes as $produto): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                            <td><?php echo htmlspecialchars($produto['fornecedor']); ?></td>
                            <td><?php echo htmlspecialchars($produto['localidade']); ?></td>
                            <td><?php echo htmlspecialchars($produto['conta']); ?></td>
                            <td><?php echo htmlspecialchars($produto['numero_documento']); ?></td>
                            <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                            <td><?php echo htmlspecialchars($produto['data_aquisicao']); ?></td>
                            <td class="valor-monetario">R$ <?php echo number_format($produto['valor_atual'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($produto['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="sem-registros">
                Nenhum produto sem observações encontrado.
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="sem-registros" style="text-align: center; padding: 40px;">
            <h3>Nenhum produto para exibir no relatório</h3>
            <p>Todos os produtos estão marcados como "checados" ou não existem registros para esta planilha.</p>
        </div>
    <?php endif; ?>

    <!-- Rodapé -->
    <div class="footer">
        Relatório gerado em <?php echo date('d/m/Y \à\s H:i:s'); ?> | 
        Sistema de Controle de Patrimônio
    </div>

    <script>
        // Configurações para melhorar a experiência de impressão
        window.onbeforeprint = function() {
            // Adiciona informações extras antes da impressão
            document.title = "Relatório Alterações - <?php echo htmlspecialchars($planilha['descricao']); ?>";
        };
        
        // Auto-print opcional (descomente se quiser que imprima automaticamente)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // };
    </script>
</body>
</html>
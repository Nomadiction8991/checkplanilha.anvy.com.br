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

// Buscar produtos marcados para impressão
try {
    $sql_produtos = "SELECT p.codigo 
                     FROM produtos p 
                     INNER JOIN produtos_check pc ON p.id = pc.produto_id 
                     WHERE p.id_planilha = :id_planilha AND pc.imprimir = 1 
                     ORDER BY p.codigo";
    $stmt_produtos = $conexao->prepare($sql_produtos);
    $stmt_produtos->bindValue(':id_planilha', $id_planilha);
    $stmt_produtos->execute();
    $produtos = $stmt_produtos->fetchAll(PDO::FETCH_COLUMN);
    
    $codigos = implode(', ', $produtos);
} catch (Exception $e) {
    $codigos = '';
    $mensagem = "Erro ao carregar produtos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copiar Etiquetas - <?php echo htmlspecialchars($planilha['descricao']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
        }

        header {
            background: #007bff;
            padding: 5px 10px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 50px;
        }

        .header-title {
            width: 50%;
            font-size: 16px;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .header-actions {
            width: 50%;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .header-btn {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
            text-decoration: none;
        }

        .header-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .info-card h2 {
            margin-top: 0;
            color: #007bff;
            font-size: 18px;
        }

        .codigos-container {
            position: relative;
            margin: 20px 0;
        }

        .codigos-field {
            width: 100%;
            padding: 15px;
            border: 2px solid #007bff;
            border-radius: 8px;
            background: #fff;
            font-family: monospace;
            font-size: 14px;
            line-height: 1.4;
            resize: vertical;
            min-height: 150px;
            box-sizing: border-box;
        }

        .codigos-field:focus {
            outline: none;
            border-color: #0056b3;
        }

        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #007bff;
            color: white;
            border: none;
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.2s;
        }

        .copy-btn:hover {
            background: #0056b3;
        }

        .copy-btn.copied {
            background: #28a745;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
<header>
    <a href="visualizar_planilha.php?id=<?php echo $id_planilha; ?>" class="header-btn" title="Fechar">❌</a>
    <h1 class="header-title">Copiar Etiquetas</h1>
</header>

    <div class="container">
        <?php if (!empty($mensagem)): ?>
            <div class="message error">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <div class="info-card">
            <h2>🏷️ Códigos para Impressão de Etiquetas</h2>
            <p>Esta lista contém todos os produtos marcados com 🏷️ "Para Imprimir" na planilha.</p>
            
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($produtos); ?></div>
                    <div class="stat-label">Produtos para Imprimir</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count(array_unique($produtos)); ?></div>
                    <div class="stat-label">Códigos Únicos</div>
                </div>
            </div>

            <?php if (!empty($produtos)): ?>
                <div class="codigos-container">
                    <textarea 
                        id="codigosField" 
                        class="codigos-field" 
                        readonly
                        onclick="this.select()"
                    ><?php echo htmlspecialchars($codigos); ?></textarea>
                    <button class="copy-btn" onclick="copiarCodigos()" title="Copiar para área de transferência">
                        📋
                    </button>
                </div>
                
                <p style="text-align: center; color: #6c757d; font-size: 14px;">
                    Clique no campo acima para selecionar todos os códigos, ou use o botão "Copiar".
                </p>
            <?php else: ?>
                <div class="message warning">
                    <strong>Nenhum produto marcado para impressão!</strong><br>
                    Volte para a planilha e marque alguns produtos com o ícone 🏷️ para vê-los aqui.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copiarCodigos() {
            const codigosField = document.getElementById('codigosField');
            const copyBtn = document.querySelector('.copy-btn');
            
            codigosField.select();
            codigosField.setSelectionRange(0, 99999); // Para mobile
            
            try {
                navigator.clipboard.writeText(codigosField.value).then(() => {
                    copyBtn.textContent = '✅';
                    copyBtn.classList.add('copied');
                    
                    setTimeout(() => {
                        copyBtn.textContent = '📋';
                        copyBtn.classList.remove('copied');
                    }, 2000);
                });
            } catch (err) {
                // Fallback para navegadores mais antigos
                document.execCommand('copy');
                copyBtn.textContent = '✅';
                copyBtn.classList.add('copied');
                
                setTimeout(() => {
                    copyBtn.textContent = '📋';
                    copyBtn.classList.remove('copied');
                }, 2000);
            }
        }

        // Selecionar automaticamente ao focar no campo
        document.getElementById('codigosField').addEventListener('focus', function() {
            this.select();
        });
    </script>
</body>
</html>
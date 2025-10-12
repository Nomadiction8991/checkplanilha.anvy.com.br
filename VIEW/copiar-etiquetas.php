<?php
require_once '../CRUD/conexao.php';

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

// Buscar dependências disponíveis
try {
    $sql_dependencias = "SELECT DISTINCT dependencia 
                         FROM produtos 
                         WHERE id_planilha = :id_planilha 
                         ORDER BY dependencia";
    $stmt_dependencias = $conexao->prepare($sql_dependencias);
    $stmt_dependencias->bindValue(':id_planilha', $id_planilha);
    $stmt_dependencias->execute();
    $dependencias = $stmt_dependencias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $dependencias = [];
}

// Obter dependência selecionada (se houver)
$dependencia_selecionada = $_GET['dependencia'] ?? '';

// Buscar produtos marcados para impressão
try {
    $sql_produtos = "SELECT p.codigo, p.dependencia 
                     FROM produtos p 
                     INNER JOIN produtos_check pc ON p.id = pc.produto_id 
                     WHERE p.id_planilha = :id_planilha AND pc.imprimir = 1";
    
    // Adicionar filtro por dependência se selecionado
    if (!empty($dependencia_selecionada)) {
        $sql_produtos .= " AND p.dependencia = :dependencia";
    }
    
    $sql_produtos .= " ORDER BY p.codigo";
    
    $stmt_produtos = $conexao->prepare($sql_produtos);
    $stmt_produtos->bindValue(':id_planilha', $id_planilha);
    
    if (!empty($dependencia_selecionada)) {
        $stmt_produtos->bindValue(':dependencia', $dependencia_selecionada);
    }
    
    $stmt_produtos->execute();
    $produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);
    
    // Extrair apenas os códigos
    $codigos = array_column($produtos, 'codigo');
    
    // Remover espaços dos códigos
    $produtos_sem_espacos = array_map(function($codigo) {
        return str_replace(' ', '', $codigo);
    }, $codigos);
    
    // Juntar os códigos sem espaços após a vírgula
    $codigos_concatenados = implode(',', $produtos_sem_espacos);
} catch (Exception $e) {
    $codigos_concatenados = '';
    $produtos = [];
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
        :root {
            --primary-color: #007bff;
            --primary-dark: #0056b3;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --gray-color: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            padding: 0 20px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header-title {
            font-size: 18px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
            text-align: center;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            text-decoration: none;
            width: 40px;
            height: 40px;
        }

        .header-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .container {
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--box-shadow);
            border: none;
        }

        .card h2 {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h2 svg {
            fill: var(--primary-color);
        }

        .filtro-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filtro-label {
            font-weight: 600;
            color: var(--dark-color);
        }

        .filtro-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            background-color: white;
            font-size: 15px;
            min-width: 200px;
            transition: var(--transition);
        }

        .filtro-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .codigos-container {
            position: relative;
            margin: 20px 0;
        }

        .codigos-field {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            background: #f8f9fa;
            font-family: 'Courier New', monospace;
            font-size: 15px;
            line-height: 1.5;
            resize: vertical;
            min-height: 150px;
            transition: var(--transition);
        }

        .codigos-field:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
        }

        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px;
            border-radius: var(--border-radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .copy-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .copy-btn.copied {
            background: var(--success-color);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-item {
            background: var(--light-color);
            padding: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .stat-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--gray-color);
        }

        .message {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: var(--border-radius);
            text-align: center;
            font-weight: 500;
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

        .info-text {
            text-align: center;
            color: var(--gray-color);
            font-size: 14px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .card {
                padding: 20px;
            }
            
            .filtro-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filtro-select {
                width: 100%;
            }
            
            .header-title {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
<header>
    <a href="visualizar_planilha.php?id=<?php echo $id_planilha; ?>" class="header-btn" title="Voltar">
        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
            <path d="m313-440 224 224-57 56-320-320 320-320 57 56-224 224h487v80H313Z"/>
        </svg>
    </a>
    <h1 class="header-title">Copiar Etiquetas - <?php echo htmlspecialchars($planilha['descricao']); ?></h1>
</header>

    <div class="container">
        <?php if (!empty($mensagem)): ?>
            <div class="message error">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5985E1">
                    <path d="M120-220v-80h80v80h-80Zm0-140v-80h80v80h-80Zm0-140v-80h80v80h-80ZM260-80v-80h80v80h-80Zm100-160q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480Zm40 240v-80h80v80h-80Zm-200 0q-33 0-56.5-23.5T120-160h80v80Zm340 0v-80h80q0 33-23.5 56.5T540-80ZM120-640q0-33 23.5-56.5T200-720v80h-80Zm420 80Z"/>
                </svg>
                Códigos para Impressão de Etiquetas
            </h2>
            <p>Esta lista contém todos os produtos marcados com "Para Imprimir" na planilha.</p>
            
            <!-- Filtro por dependência -->
            <?php if (!empty($dependencias)): ?>
            <div class="filtro-container">
                <span class="filtro-label">Filtrar por dependência:</span>
                <select class="filtro-select" id="filtroDependencia" onchange="filtrarPorDependencia()">
                    <option value="">Todas as dependências</option>
                    <?php foreach ($dependencias as $dependencia): ?>
                        <option value="<?php echo htmlspecialchars($dependencia); ?>" 
                            <?php echo ($dependencia_selecionada === $dependencia) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dependencia); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($produtos); ?></div>
                    <div class="stat-label">Produtos para Imprimir</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count(array_unique($produtos_sem_espacos ?? [])); ?></div>
                    <div class="stat-label">Códigos Únicos</div>
                </div>
                <?php if (!empty($dependencia_selecionada)): ?>
                <div class="stat-item">
                    <div class="stat-number"><?php echo htmlspecialchars($dependencia_selecionada); ?></div>
                    <div class="stat-label">Dependência Selecionada</div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($produtos)): ?>
                <div class="codigos-container">
                    <textarea 
                        id="codigosField" 
                        class="codigos-field" 
                        readonly
                        onclick="this.select()"
                    ><?php echo htmlspecialchars($codigos_concatenados); ?></textarea>
                    <button class="copy-btn" onclick="copiarCodigos()" title="Copiar para área de transferência">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                            <path d="M120-220v-80h80v80h-80Zm0-140v-80h80v80h-80Zm0-140v-80h80v80h-80ZM260-80v-80h80v80h-80Zm100-160q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480Zm40 240v-80h80v80h-80Zm-200 0q-33 0-56.5-23.5T120-160h80v80Zm340 0v-80h80q0 33-23.5 56.5T540-80ZM120-640q0-33 23.5-56.5T200-720v80h-80Zm420 80Z"/>
                        </svg>
                    </button>
                </div>
                
                <p class="info-text">
                    Clique no campo acima para selecionar todos os códigos, ou use o botão "Copiar".
                </p>
            <?php else: ?>
                <div class="message warning">
                    <strong>Nenhum produto marcado para impressão!</strong><br>
                    <?php if (!empty($dependencia_selecionada)): ?>
                        Não há produtos marcados para impressão na dependência "<?php echo htmlspecialchars($dependencia_selecionada); ?>".
                    <?php else: ?>
                        Volte para a planilha e marque alguns produtos com o ícone 🏷️ para vê-los aqui.
                    <?php endif; ?>
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
                    copyBtn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                            <path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/>
                        </svg>
                    `;
                    copyBtn.classList.add('copied');
                    
                    setTimeout(() => {
                        copyBtn.innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                                <path d="M120-220v-80h80v80h-80Zm0-140v-80h80v80h-80Zm0-140v-80h80v80h-80ZM260-80v-80h80v80h-80Zm100-160q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480Zm40 240v-80h80v80h-80Zm-200 0q-33 0-56.5-23.5T120-160h80v80Zm340 0v-80h80q0 33-23.5 56.5T540-80ZM120-640q0-33 23.5-56.5T200-720v80h-80Zm420 80Z"/>
                            </svg>
                        `;
                        copyBtn.classList.remove('copied');
                    }, 2000);
                });
            } catch (err) {
                // Fallback para navegadores mais antigos
                document.execCommand('copy');
                copyBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                        <path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/>
                    </svg>
                `;
                copyBtn.classList.add('copied');
                
                setTimeout(() => {
                    copyBtn.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                            <path d="M120-220v-80h80v80h-80Zm0-140v-80h80v80h-80Zm0-140v-80h80v80h-80ZM260-80v-80h80v80h-80Zm100-160q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480Zm40 240v-80h80v80h-80Zm-200 0q-33 0-56.5-23.5T120-160h80v80Zm340 0v-80h80q0 33-23.5 56.5T540-80ZM120-640q0-33 23.5-56.5T200-720v80h-80Zm420 80Z"/>
                        </svg>
                    `;
                    copyBtn.classList.remove('copied');
                }, 2000);
            }
        }

        // Selecionar automaticamente ao focar no campo
        document.getElementById('codigosField').addEventListener('focus', function() {
            this.select();
        });

        // Filtrar por dependência
        function filtrarPorDependencia() {
            const dependencia = document.getElementById('filtroDependencia').value;
            const url = new URL(window.location);
            
            if (dependencia) {
                url.searchParams.set('dependencia', dependencia);
            } else {
                url.searchParams.delete('dependencia');
            }
            
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
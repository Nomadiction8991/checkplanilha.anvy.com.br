<?php
$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu de Impressões</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
        }

        header {
            background: #007bff;
            padding: 15px 20px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-btn {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
            text-decoration: none;
        }

        .header-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        h1 {
            font-size: 20px;
            font-weight: normal;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .menu-item {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .menu-icon {
            font-size: 48px;
            color: #007bff;
            margin-bottom: 15px;
        }

        .menu-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .menu-description {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 15px;
            }

            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="visualizar_planilha.php?id=<?php echo $id_planilha; ?>" class="header-btn" title="Voltar">✖</a>
        <h1>Menu de Impressões</h1>
    </header>

    <div class="container">
        <div class="menu-grid">
            <a href="imprimiralteracao_planilha.php?id=<?php echo $id_planilha; ?>" class="menu-item">
                <div class="menu-icon">📋</div>
                <div class="menu-title">Relatório de Alterações</div>
                <div class="menu-description">
                    Imprime o relatório completo com todas as alterações, observações e produtos marcados da planilha
                </div>
            </a>

            <a href="imprimir_termo_doacao_manual.php?id=<?php echo $id_planilha; ?>" class="menu-item">
                <div class="menu-icon">📄</div>
                <div class="menu-title">Termo de Doação Manual</div>
                <div class="menu-description">
                    Preencha e imprima o formulário de declaração de doação de bens móveis
                </div>
            </a>
        </div>
    </div>
</body>
</html>
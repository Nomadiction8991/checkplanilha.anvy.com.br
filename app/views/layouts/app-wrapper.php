<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Anvy - Gestão de Planilhas'; ?></title>
    
    <!-- PWA - Progressive Web App -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CheckPlanilha">
    <link rel="apple-touch-icon" href="/logo.png">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        /* ===== LAYOUT MOBILE 400px CENTRALIZADO ===== */
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        
        /* Container principal centralizado */
        .app-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 20px 10px;
        }
        
        /* Wrapper mobile de 400px */
        .mobile-wrapper {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            min-height: calc(100vh - 40px);
            display: flex;
            flex-direction: column;
        }
        
        /* Header fixo */
        .app-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        
        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .app-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
        }
        
        .header-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-header-action {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-header-action:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        /* Botão PWA com animação de pulso */
        #installPwaBtn {
            position: relative;
            animation: pulsePwa 2s infinite;
        }
        
        @keyframes pulsePwa {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(255, 255, 255, 0);
            }
        }
        
        #installPwaBtn:hover {
            animation: none;
            background: rgba(255, 255, 255, 0.4) !important;
        }
        
        /* Conteúdo principal */
        .app-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        /* Cards Bootstrap personalizados */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 16px;
            transition: all 0.3s;
        }
        
        .card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            padding: 12px 16px;
        }
        
        /* Botões personalizados */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        /* Exceção: botões dentro de input-group não devem se mover */
        .input-group .btn:hover,
        .input-group .btn:focus,
        .input-group .btn:active {
            transform: none !important;
        }
        
        /* Tabelas responsivas */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        
        table {
            margin-bottom: 0;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        thead th {
            border: none;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 8px;
        }
        
        tbody tr {
            transition: all 0.2s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        tbody td {
            padding: 12px 8px;
            vertical-align: middle;
            font-size: 14px;
        }
        
        /* Badges personalizados */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 11px;
        }
        
        /* Forms */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 10px 12px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #495057;
            margin-bottom: 6px;
        }
        
        /* Loading */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        
        /* Paginação */
        .pagination {
            gap: 4px;
        }
        
        .page-link {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            color: #667eea;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        /* Responsividade extra para < 400px */
        @media (max-width: 420px) {
            .app-container {
                padding: 0;
            }
            
            .mobile-wrapper {
                border-radius: 0;
                min-height: 100vh;
            }
        }
        
        /* Utilitários */
        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .shadow-sm-custom {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
        }
        
        /* Animações */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
    
    <?php if (isset($customCss)): ?>
        <style><?php echo $customCss; ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="app-container">
        <div class="mobile-wrapper">
            <!-- Header -->
            <header class="app-header">
                <div class="header-left">
                    <?php if (isset($backUrl)): ?>
                        <a href="<?php echo $backUrl; ?>" class="btn-back">
                            <i class="bi bi-arrow-left fs-5"></i>
                        </a>
                    <?php endif; ?>
                    <h1 class="app-title"><?php echo $pageTitle ?? 'Anvy'; ?></h1>
                </div>
                <div class="header-actions">
                    <?php if (isset($headerActions)): ?>
                        <?php echo $headerActions; ?>
                    <?php endif; ?>
                </div>
            </header>
            
            <!-- Content -->
            <main class="app-content fade-in">
                <?php if (isset($contentFile)): ?>
                    <?php include $contentFile; ?>
                <?php else: ?>
                    <!-- Conteúdo padrão aqui -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Conteúdo não definido
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => console.log('SW registrado:', registration.scope))
                    .catch(err => console.log('SW falhou:', err));
            });
        }
    </script>
    
    <?php if (isset($customJs)): ?>
        <script><?php echo $customJs; ?></script>
    <?php endif; ?>
</body>
</html>

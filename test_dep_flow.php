<?php
// Script de teste rápido para incluir a view de listagem simulando sessão admin
session_start();

$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_tipo'] = 'Administrador/Acessor';
$_SESSION['usuario_nome'] = 'Teste';

// Forçar GET
$_GET['pagina'] = 1;

// Capturar include
ob_start();
try {
    include PROJECT_ROOT . '/app/views/dependencias/read-dependencia.php';
    $html = ob_get_clean();
    echo "INCLUDE_OK\n";
    // Verificar se aparece texto de erro no HTML
    if (stripos($html, 'Conteúdo não definido') !== false || stripos($html, 'Warning') !== false || stripos($html, 'Notice') !== false) {
        echo "POSSÍVEL_ERRO_VISUAL\n";
        // salvar amostra
        file_put_contents(PROJECT_ROOT . '/var/test_dep_output.html', $html);
    } else {
        echo "HTML_OK\n";
    }
} catch (Throwable $e) {
    echo 'INCLUDE_FAIL: ' . $e->getMessage() . PHP_EOL;
}

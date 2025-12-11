<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../conexao.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    try {
        if ($descricao === '') {
            throw new Exception('A descricao e obrigatoria.');
        }

        // Se codigo informado, validar unicidade
        if ($codigo !== '') {
            $check = $conexao->prepare('SELECT id FROM dependencias WHERE codigo = :codigo');
            $check->bindValue(':codigo', $codigo);
            $check->execute();
            if ($check->fetch()) {
                throw new Exception('Este codigo ja esta cadastrado.');
            }
        }

        // Montar insert dinamico conforme presenca de codigo
        $fields = ['descricao'];
        $placeholders = [':descricao'];
        $params = [':descricao' => $descricao];

        if ($codigo !== '') {
            $fields[] = 'codigo';
            $placeholders[] = ':codigo';
            $params[':codigo'] = $codigo;
        }

        $sql = 'INSERT INTO dependencias (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $conexao->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        header('Location: read-dependencia.php?success=1');
        exit;
    } catch (Throwable $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

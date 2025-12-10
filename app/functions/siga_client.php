<?php
require_once PROJECT_ROOT . '/config.php';

// Constantes centrais da integraçao SIGA
if (!defined('SIGA_BASE_URL')) {
    define('SIGA_BASE_URL', 'https://siga.congregacao.org.br');
}

/**
 * Tenta descobrir o endpoint de login válido do SIGA.
 */
function siga_detect_login_endpoint(): string
{
    if (!function_exists('curl_init')) {
        return '/login';
    }

    $candidates = [
        '/login',
        '/Login',
        '/account/login',
        '/Account/Login',
        '/usuario/login',
        '/Usuario/Login',
        '/',
    ];

    foreach ($candidates as $path) {
        $url = rtrim(SIGA_BASE_URL, '/') . $path;
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'checkplanilha-siga-endpoint/1.0',
            ]);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status >= 200 && $status < 400 && is_string($body) && trim($body) !== '') {
                return $path;
            }
        } catch (Throwable $e) {
            // tenta o próximo
        }
    }

    // fallback conservador
    return '/login';
}

/**
 * Detecta automaticamente qual parâmetro de retorno o SIGA usa no login.
 * Procura por nomes comuns no HTML (returnUrl, redirect, continue, next).
 */
function siga_detect_redirect_param(string $loginPath): string
{
    if (!function_exists('curl_init')) {
        return 'returnUrl';
    }

    $candidates = ['returnUrl', 'ReturnUrl', 'redirect', 'redirectUrl', 'Redirect', 'continue', 'next'];
    $loginUrl = rtrim(SIGA_BASE_URL, '/') . $loginPath;

    try {
        $ch = curl_init($loginUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'checkplanilha-siga-integration/1.0',
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        if (!is_string($html) || trim($html) === '') {
            return $candidates[0];
        }

        $lower = strtolower($html);
        foreach ($candidates as $candidate) {
            if (strpos($lower, strtolower($candidate)) !== false) {
                return $candidate;
            }
        }
    } catch (Throwable $e) {
        // Falhou a detecção; usa padrão.
    }

    return $candidates[0];
}

/**
 * Monta a URL de login do SIGA com callback já incluído.
 */
function siga_build_login_url(string $callbackUrl): string
{
    // Alguns ambientes do SIGA ignoram parâmetros na rota /login.
    // Usamos a raiz com ReturnUrl explícito (mais aceito em apps ASP.NET)
    $baseLogin = rtrim(SIGA_BASE_URL, '/') . '/';
    $params = [
        'ReturnUrl' => $callbackUrl,
    ];
    return $baseLogin . '?' . http_build_query($params);
}

/**
 * Encaminha uma requisição para o SIGA reutilizando o cookie do navegador.
 * Retorna array com status_code, headers, body.
 */
function siga_proxy_request(string $targetUrl, string $method = 'GET', ?string $payload = null, array $headers = [], ?string $cookieHeader = null): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Extensao cURL nao disponivel no servidor.');
    }

    $ch = curl_init($targetUrl);

    $defaultHeaders = [
        'Accept: */*',
        'Accept-Language: pt-BR,pt;q=0.9',
        'Connection: keep-alive',
    ];

    if ($cookieHeader) {
        $defaultHeaders[] = 'Cookie: ' . $cookieHeader;
    }

    foreach ($headers as $header) {
        $defaultHeaders[] = $header;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'checkplanilha-siga-proxy/1.0',
        CURLOPT_HTTPHEADER => $defaultHeaders,
    ]);

    $method = strtoupper($method);
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Falha ao contatar o SIGA: ' . $error);
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $rawHeaders = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    $parsedHeaders = [];
    foreach (explode("\r\n", $rawHeaders) as $line) {
        if (strpos($line, ':') !== false) {
            [$key, $value] = explode(':', $line, 2);
            $parsedHeaders[trim($key)] = trim($value);
        }
    }

    return [
        'status_code' => $statusCode,
        'headers' => $parsedHeaders,
        'body' => $body,
    ];
}

/**
 * Extrai campos da tela de preferências do SIGA.
 * Heurística defensiva: procura por ids/names e também por textos próximos.
 */
function siga_parse_preferencias_html(string $html): array
{
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $encoded = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
    $dom->loadHTML($encoded);
    $xpath = new DOMXPath($dom);

    $getInputValue = function (array $queries) use ($xpath) {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes && $nodes->length > 0) {
                $value = trim((string)$nodes->item(0)->getAttribute('value'));
                if ($value !== '') {
                    return $value;
                }
            }
        }
        return null;
    };

    $getSelectValue = function (array $queries) use ($xpath) {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes && $nodes->length > 0) {
                /** @var DOMElement $select */
                $select = $nodes->item(0);
                foreach ($select->getElementsByTagName('option') as $opt) {
                    if ($opt->hasAttribute('selected') || $opt->getAttribute('value') === $select->getAttribute('value')) {
                        $text = trim($opt->nodeValue);
                        $value = trim($opt->getAttribute('value'));
                        return $text !== '' ? $text : $value;
                    }
                }
            }
        }
        return null;
    };

    $data = [
        'siga_login' => $getInputValue([
            "//input[contains(translate(@name,'LOGIN','login'),'login')]",
            "//input[contains(translate(@id,'LOGIN','login'),'login')]",
        ]),
        'nome' => $getInputValue([
            "//input[contains(translate(@name,'NOME','nome'),'nome')]",
            "//input[contains(translate(@id,'NOME','nome'),'nome')]",
        ]),
        'email' => $getInputValue([
            "//input[contains(translate(@name,'EMAIL','email'),'email')]",
            "//input[contains(translate(@id,'EMAIL','email'),'email')]",
        ]),
        'ddd' => $getInputValue([
            "//input[contains(translate(@name,'DDD','ddd'),'ddd')]",
            "//input[contains(translate(@id,'DDD','ddd'),'ddd')]",
        ]),
        'telefone' => $getInputValue([
            "//input[contains(translate(@name,'TEL','tel'))]",
            "//input[contains(translate(@id,'TEL','tel'))]",
            "//input[contains(translate(@name,'FONE','fone'))]",
            "//input[contains(translate(@id,'FONE','fone'))]",
        ]),
        'operadora' => $getInputValue([
            "//input[contains(translate(@name,'OPER','oper'))]",
            "//input[contains(translate(@id,'OPER','oper'))]",
        ]) ?: $getSelectValue([
            "//select[contains(translate(@name,'OPER','oper'))]",
            "//select[contains(translate(@id,'OPER','oper'))]",
        ]),
        'idioma' => $getSelectValue([
            "//select[contains(translate(@name,'IDIOMA','idioma'),'idioma')]",
            "//select[contains(translate(@id,'IDIOMA','idioma'),'idioma')]",
        ]),
        'registros_por_pagina' => $getSelectValue([
            "//select[contains(translate(@name,'REGIST','regist'),'regist')]",
            "//select[contains(translate(@id,'REGIST','regist'),'regist')]",
        ]),
        'tema' => $getInputValue([
            "//input[@type='color']",
            "//input[contains(translate(@name,'TEMA','tema'),'tema')]",
            "//input[contains(translate(@id,'TEMA','tema'),'tema')]",
        ]) ?: $getSelectValue([
            "//select[contains(translate(@name,'TEMA','tema'),'tema')]",
            "//select[contains(translate(@id,'TEMA','tema'),'tema')]",
        ]),
        'tempo_limite_sessao' => $getSelectValue([
            "//select[contains(translate(@name,'SESSAO','sessao'),'sessao')]",
            "//select[contains(translate(@name,'SESSION','session'),'session')]",
            "//select[contains(translate(@id,'SESSAO','sessao'),'sessao')]",
        ]),
    ];

    return $data;
}

/**
 * Garante a existência da tabela siga_usuarios.
 */
function siga_ensure_table(PDO $pdo): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS `siga_usuarios` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `siga_login` VARCHAR(150) NOT NULL,
            `nome` VARCHAR(255) NULL,
            `email` VARCHAR(255) NULL,
            `ddd` VARCHAR(10) NULL,
            `telefone` VARCHAR(50) NULL,
            `operadora` VARCHAR(100) NULL,
            `idioma` VARCHAR(100) NULL,
            `registros_por_pagina` VARCHAR(50) NULL,
            `tema` VARCHAR(50) NULL,
            `tempo_limite_sessao` VARCHAR(50) NULL,
            `preferencia_hash` VARCHAR(64) NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_siga_login` (`siga_login`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($sql);
}

/**
 * Upsert dos dados em siga_usuarios. Retorna id.
 */
function siga_upsert_usuario(PDO $pdo, array $data): int
{
    if (empty($data['siga_login'])) {
        throw new InvalidArgumentException('Login do SIGA não encontrado para salvar.');
    }

    $hashSource = json_encode($data, JSON_UNESCAPED_UNICODE);
    $hash = hash('sha256', $hashSource);

    $sql = "
        INSERT INTO siga_usuarios
            (siga_login, nome, email, ddd, telefone, operadora, idioma, registros_por_pagina, tema, tempo_limite_sessao, preferencia_hash)
        VALUES
            (:siga_login, :nome, :email, :ddd, :telefone, :operadora, :idioma, :registros_por_pagina, :tema, :tempo_limite_sessao, :preferencia_hash)
        ON DUPLICATE KEY UPDATE
            nome = VALUES(nome),
            email = VALUES(email),
            ddd = VALUES(ddd),
            telefone = VALUES(telefone),
            operadora = VALUES(operadora),
            idioma = VALUES(idioma),
            registros_por_pagina = VALUES(registros_por_pagina),
            tema = VALUES(tema),
            tempo_limite_sessao = VALUES(tempo_limite_sessao),
            preferencia_hash = VALUES(preferencia_hash),
            updated_at = CURRENT_TIMESTAMP
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':siga_login' => $data['siga_login'],
        ':nome' => $data['nome'] ?? null,
        ':email' => $data['email'] ?? null,
        ':ddd' => $data['ddd'] ?? null,
        ':telefone' => $data['telefone'] ?? null,
        ':operadora' => $data['operadora'] ?? null,
        ':idioma' => $data['idioma'] ?? null,
        ':registros_por_pagina' => $data['registros_por_pagina'] ?? null,
        ':tema' => $data['tema'] ?? null,
        ':tempo_limite_sessao' => $data['tempo_limite_sessao'] ?? null,
        ':preferencia_hash' => $hash,
    ]);

    if ($pdo->lastInsertId()) {
        return (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare('SELECT id FROM siga_usuarios WHERE siga_login = :login LIMIT 1');
    $stmt->execute([':login' => $data['siga_login']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : 0;
}

/**
 * Garante um usuário na tabela usuarios e retorna seu id.
 */
function siga_sync_local_usuario(PDO $pdo, array $data): int
{
    $login = $data['siga_login'] ?? null;
    $email = $data['email'] ?? null;
    $nome = $data['nome'] ?? ($login ?: 'Usuário SIGA');

    // Fallback de email único se não vier do SIGA
    if (empty($email) && !empty($login)) {
        $email = $login . '@siga.local';
    } elseif (empty($email)) {
        $email = 'siga_user_' . bin2hex(random_bytes(4)) . '@siga.local';
    }

    // Tenta achar usuário existente por email
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Atualiza nome se necessário
        $update = $pdo->prepare('UPDATE usuarios SET nome = :nome, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $update->execute([':nome' => $nome, ':id' => (int)$row['id']]);
        return (int)$row['id'];
    }

    // Cria novo usuário interno com senha aleatória
    $senhaHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $insert = $pdo->prepare("
        INSERT INTO usuarios (nome, email, senha, ativo, tipo, created_at, updated_at)
        VALUES (:nome, :email, :senha, 1, 'SIGA', NOW(), NOW())
    ");
    $insert->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senhaHash,
    ]);

    return (int)$pdo->lastInsertId();
}

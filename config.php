<?php
// Configuração central de URLs e ambiente
// Uso: base_url('caminho/para/pagina.php')

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Detectar prefixo do ambiente no caminho (dev/prod) a partir da URL atual
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $segments = array_values(array_filter(explode('/', parse_url($requestUri, PHP_URL_PATH))));

        $prefix = '';
        if (!empty($segments)) {
            // Se o primeiro segmento for 'dev' ou 'prod', usar como prefixo
            if (in_array($segments[0], ['dev','prod'], true)) {
                $prefix = '/' . $segments[0];
            }
        }

        // Permitir forçar via variável de ambiente ANVY_ENV
        $forcedEnv = getenv('ANVY_ENV');
        if ($forcedEnv && in_array($forcedEnv, ['dev','prod'], true)) {
            $prefix = '/' . $forcedEnv;
        }

        $base = $scheme . '://' . $host . $prefix;
        if ($path === '' || $path === '/') {
            return $base . '/';
        }
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('current_url')) {
    function current_url(): string {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }
}

<?php
function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require BASE_PATH . '/config/app.php';
    }
    $keys  = explode('.', $key);
    $value = $config;
    foreach ($keys as $k) {
        if (!isset($value[$k])) return $default;
        $value = $value[$k];
    }
    return $value;
}

function dd(mixed ...$vars): never
{
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:20px;font-size:13px;overflow:auto">';
    foreach ($vars as $var) { var_dump($var); }
    echo '</pre>';
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url, array $flash = []): never
{
    foreach ($flash as $key => $value) { flash($key, $value); }
    header('Location: ' . $url);
    exit;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_verify(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

function auth(): ?array
{
    return $_SESSION['user'] ?? null;
}

function guest(): bool
{
    return !isset($_SESSION['user']);
}

// FIX: asset() now uses the actual request host instead of APP_URL.
// This means CSS/JS links work from any IP or hostname without touching .env.
function asset(string $path): string
{
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/' . ltrim($path, '/');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function format_money(float $amount, string $symbol = '৳'): string
{
    return $symbol . number_format($amount, 2);
}

function format_date(string $date): string
{
    return date('d M Y', strtotime($date));
}

function generate_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function old(string $key, string $default = ''): string
{
    $value = $_SESSION['_old_input'][$key] ?? $default;
    unset($_SESSION['_old_input'][$key]);
    return e($value);
}

function set_old(array $data): void
{
    $_SESSION['_old_input'] = $data;
}

function activePage(string $page): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = trim($uri, '/');
    return ($uri === $page || str_starts_with($uri, $page . '/')) ? 'active' : '';
}
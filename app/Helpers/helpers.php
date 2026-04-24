<?php
// =============================================================================
// app/Helpers/helpers.php — Global utility functions
// =============================================================================
// These are simple functions you'll call everywhere in the app.
// They're loaded once at startup (in index.php) and then available globally.
// =============================================================================

// -----------------------------------------------------------------------------
// config() — Read a value from config/app.php
// Usage: config('db.host')  or  config('app.name')
// The dot (.) lets you go one level deep into the array.
// -----------------------------------------------------------------------------
function config(string $key, mixed $default = null): mixed
{
    static $config = null;

    // Load config file once, cache it in $config
    if ($config === null) {
        $config = require BASE_PATH . '/config/app.php';
    }

    // Support dot notation: 'db.host' → $config['db']['host']
    $keys  = explode('.', $key);
    $value = $config;

    foreach ($keys as $k) {
        if (!isset($value[$k])) return $default;
        $value = $value[$k];
    }

    return $value;
}

// -----------------------------------------------------------------------------
// dd() — "Dump and Die" — for debugging ONLY
// Usage: dd($someVariable);   ← shows the value and stops execution
// Remove all dd() calls before going live!
// -----------------------------------------------------------------------------
function dd(mixed ...$vars): never
{
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:20px;font-size:13px;overflow:auto">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit;
}

// -----------------------------------------------------------------------------
// e() — Escape output to prevent XSS attacks
// ALWAYS use e() when printing user-supplied data in HTML
// Usage: echo e($user['name']);
// -----------------------------------------------------------------------------
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// -----------------------------------------------------------------------------
// redirect() — Redirect to another URL
// Usage: redirect('/dashboard');   or   redirect('/login', ['error' => 'Wrong password'])
// -----------------------------------------------------------------------------
function redirect(string $url, array $flash = []): never
{
    foreach ($flash as $key => $value) {
        flash($key, $value);
    }
    header('Location: ' . $url);
    exit;
}

// -----------------------------------------------------------------------------
// flash() — Store or retrieve a one-time message (survives ONE redirect)
// Set:  flash('error', 'Wrong password')
// Get:  $msg = flash('error')   ← returns the message AND deletes it
// -----------------------------------------------------------------------------
function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        // Store message
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    // Retrieve and delete message
    $msg = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $msg;
}

// -----------------------------------------------------------------------------
// csrf_token() — Generate or return the CSRF token for this session
// Used in forms to prevent Cross-Site Request Forgery attacks
// Usage in HTML: <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
// -----------------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

// -----------------------------------------------------------------------------
// csrf_verify() — Check that a submitted form has a valid CSRF token
// Call this at the start of any POST handler
// -----------------------------------------------------------------------------
function csrf_verify(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

// -----------------------------------------------------------------------------
// auth() — Get the currently logged-in user (or null if not logged in)
// Usage: $user = auth();   then   $user['name'], $user['email'], etc.
// -----------------------------------------------------------------------------
function auth(): ?array
{
    return $_SESSION['user'] ?? null;
}

// -----------------------------------------------------------------------------
// guest() — Returns true if nobody is logged in
// Usage: if (guest()) redirect('/login');
// -----------------------------------------------------------------------------
function guest(): bool
{
    return !isset($_SESSION['user']);
}

// -----------------------------------------------------------------------------
// asset() — Get the full URL to a CSS/JS/image file in /public
// Usage: asset('css/app.css')  →  https://yourdomain.com/css/app.css
// -----------------------------------------------------------------------------
function asset(string $path): string
{
    return rtrim(config('url'), '/') . '/' . ltrim($path, '/');
}

// -----------------------------------------------------------------------------
// now() — Current datetime in MySQL format
// Usage: $created_at = now();   →   "2025-04-22 14:30:00"
// -----------------------------------------------------------------------------
function now(): string
{
    return date('Y-m-d H:i:s');
}

// -----------------------------------------------------------------------------
// slugify() — Convert a string to URL-safe format
// Usage: slugify('My Book 2025')  →  "my-book-2025"
// -----------------------------------------------------------------------------
function slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// -----------------------------------------------------------------------------
// format_money() — Format a number as currency
// Usage: format_money(12500)  →  "৳12,500.00"
// -----------------------------------------------------------------------------
function format_money(float $amount, string $symbol = '৳'): string
{
    return $symbol . number_format($amount, 2);
}

// -----------------------------------------------------------------------------
// format_date() — Format a date nicely
// Usage: format_date('2025-04-22')  →  "22 Apr 2025"
// -----------------------------------------------------------------------------
function format_date(string $date): string
{
    return date('d M Y', strtotime($date));
}

// -----------------------------------------------------------------------------
// generate_token() — Create a random token (for email verification, password reset)
// Usage: $token = generate_token();
// -----------------------------------------------------------------------------
function generate_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

// -----------------------------------------------------------------------------
// json_response() — Send a JSON API response and exit
// Usage: json_response(['success' => true, 'data' => $user]);
// -----------------------------------------------------------------------------
function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------------------------------------------------------
// old() — Repopulate a form field after a failed submission
// Usage in HTML: <input value="<?= old('email') ?>">
// Set with:  $_SESSION['_old_input'] = $_POST;  before redirecting
// -----------------------------------------------------------------------------
function old(string $key, string $default = ''): string
{
    $value = $_SESSION['_old_input'][$key] ?? $default;
    unset($_SESSION['_old_input'][$key]);  // only use it once
    return e($value);
}

// -----------------------------------------------------------------------------
// set_old() — Save POST data for repopulating forms after errors
// Call this before redirecting back with errors
// -----------------------------------------------------------------------------
function set_old(array $data): void
{
    $_SESSION['_old_input'] = $data;
}

// -----------------------------------------------------------------------------
// activePage() — Returns 'active' CSS class if current URL matches
// Usage in nav: class="nav-item <?= activePage('dashboard') ?>"
// -----------------------------------------------------------------------------
function activePage(string $page): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = trim($uri, '/');
    return ($uri === $page || str_starts_with($uri, $page . '/')) ? 'active' : '';
}

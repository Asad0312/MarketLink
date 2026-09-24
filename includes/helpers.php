<?php

declare(strict_types=1);

/** Escape output for HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Return the directory name where MarketLink is installed. */
function base_path_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');
    $base = $directory === '.' ? '' : $directory;
    return $base;
}

/** Build an internal application URL. */
function url(string $page = 'home', array $params = []): string
{
    $query = array_merge(['page' => $page], $params);
    return base_path_url() . '/index.php?' . http_build_query($query);
}

/** Build a public asset URL. */
function asset(string $path): string
{
    return base_path_url() . '/' . ltrim($path, '/');
}

/** Redirect and stop execution. */
function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

/** Redirect to an application page. */
function redirect_to(string $page = 'home', array $params = []): never
{
    redirect(url($page, $params));
}

/** Read a request value from POST or GET. */
function request_value(string $key, mixed $default = null): mixed
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

function post_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function post_int(string $key, int $default = 0): int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    return $value === false || $value === null ? $default : $value;
}

function get_int(string $key, int $default = 0): int
{
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
    return $value === false || $value === null ? $default : $value;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Store a one-time message in the session. */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** Return and clear all flash messages. */
function pull_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($flashes) ? $flashes : [];
}

/** Generate or return the session CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page, and try again.');
    }
}

/** Preserve a submitted value for one redirect. */
function remember_old(array $values): void
{
    $_SESSION['_old'] = $values;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function remember_errors(array $errors): void
{
    $_SESSION['_errors'] = array_values(array_filter(array_map('strval', $errors)));
}

function pull_errors(): array
{
    $errors = $_SESSION['_errors'] ?? [];
    unset($_SESSION['_errors']);
    return is_array($errors) ? $errors : [];
}

function account_can_access(array $user): bool
{
    return match ($user['role'] ?? '') {
        'admin', 'customer' => ($user['status'] ?? '') === 'active',
        'farmer' => in_array($user['status'] ?? '', ['pending', 'approved'], true),
        default => false,
    };
}

/** Return the authenticated user, if any. */
function current_user(): ?array
{
    static $loaded = false;
    static $user = null;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId < 1) {
        return null;
    }

    $statement = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $statement->execute([$userId]);
    $record = $statement->fetch();
    if (!$record || !account_can_access($record)) {
        unset($_SESSION['user_id']);
        return null;
    }

    unset($record['password_hash']);
    $user = $record;
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please sign in to continue.');
        $next = rawurlencode($_SERVER['REQUEST_URI'] ?? url('home'));
        redirect(url('login', ['next' => $next]));
    }
    return $user;
}

function require_role(string|array $roles): array
{
    $user = require_auth();
    $allowed = (array) $roles;
    if (!in_array($user['role'], $allowed, true)) {
        http_response_code(403);
        $page = '403';
        require BASE_PATH . '/views/errors.php';
        exit;
    }
    return $user;
}

function require_approved_farmer(): array
{
    $user = require_role('farmer');
    if ($user['status'] !== 'approved') {
        flash('warning', 'Your farmer account is awaiting admin approval.');
        redirect_to('dashboard');
    }
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    flash('success', 'Welcome back, ' . $user['name'] . '!');
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

/** Number of products in the current customer's cart. */
function cart_count(): int
{
    $user = current_user();
    if (!$user || $user['role'] !== 'customer') {
        return 0;
    }
    $statement = db()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
    $statement->execute([(int) $user['id']]);
    return (int) $statement->fetchColumn();
}

function is_favorite(string $type, int $itemId): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $allowed = ['farmer', 'product', 'market'];
    if (!in_array($type, $allowed, true)) {
        return false;
    }
    $statement = db()->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND item_type = ? AND item_id = ? LIMIT 1');
    $statement->execute([(int) $user['id'], $type, $itemId]);
    return (bool) $statement->fetchColumn();
}

function money(float|int|string $amount): string
{
    return 'Rs ' . number_format((float) $amount, 2);
}

function format_date(?string $date, string $format = 'M j, Y'): string
{
    if (!$date) {
        return '—';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '—';
}

function date_time_label(?string $date): string
{
    return format_date($date, 'M j, Y · g:i A');
}

function time_ago(string $date): string
{
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return '';
    }
    $seconds = max(0, time() - $timestamp);
    if ($seconds < 60) return 'just now';
    if ($seconds < 3600) return floor($seconds / 60) . 'm ago';
    if ($seconds < 86400) return floor($seconds / 3600) . 'h ago';
    if ($seconds < 604800) return floor($seconds / 86400) . 'd ago';
    return date('M j', $timestamp);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
    }
    return strtoupper($letters ?: 'ML');
}

function status_pill(string $status): string
{
    $classes = [
        'placed' => 'pill-blue',
        'accepted' => 'pill-orange',
        'ready' => 'pill-lime',
        'completed' => 'pill',
        'declined' => 'pill-red',
        'cancelled' => 'pill-red',
        'pending' => 'pill-orange',
        'approved' => 'pill',
        'suspended' => 'pill-red',
        'deactivated' => 'pill-red',
        'active' => 'pill',
        'inactive' => 'pill-gray',
        'sold_out' => 'pill-red',
        'hidden' => 'pill-gray',
    ];
    $label = str_replace('_', ' ', ucfirst(strtolower($status)));
    return '<span class="pill ' . ($classes[strtolower($status)] ?? 'pill-gray') . '">' . e($label) . '</span>';
}

function star_display(float $rating, bool $showNumber = true): string
{
    $rating = max(0, min(5, $rating));
    $full = (int) floor($rating);
    $half = $rating - $full >= 0.5;
    $html = '<span class="rating" aria-label="' . e(number_format($rating, 1)) . ' out of 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full) {
            $html .= '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.46L12 17.31l-5.8 3.05 1.1-6.46-4.69-4.58 6.49-.94L12 2.5Z"/></svg>';
        } elseif ($i === $full + 1 && $half) {
            $html .= '<svg viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="half"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="#dce8e1"/></linearGradient></defs><path fill="url(#half)" d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.46L12 17.31l-5.8 3.05 1.1-6.46-4.69-4.58 6.49-.94L12 2.5Z"/></svg>';
        } else {
            $html .= '<svg viewBox="0 0 24 24" aria-hidden="true" style="opacity:.25"><path d="m12 2.5 2.9 5.88 6.49.94-4.7 4.58 1.11 6.46L12 17.31l-5.8 3.05 1.1-6.46-4.69-4.58 6.49-.94L12 2.5Z"/></svg>';
        }
    }
    if ($showNumber) {
        $html .= '<span class="sr-only">Rating:</span><span>' . e(number_format($rating, 1)) . '</span>';
    }
    return $html . '</span>';
}

function product_placeholder(string $category): string
{
    $map = [
        'vegetables' => '🥬',
        'fruits' => '🍎',
        'dairy' => '🥛',
        'bakery' => '🥖',
        'eggs' => '🥚',
        'pantry' => '🫙',
        'herbs' => '🌿',
        'honey' => '🍯',
    ];
    return $map[strtolower($category)] ?? '🧺';
}

function random_order_number(): string
{
    return 'ML' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
}

/** Validate and store an optional product image upload. */
function upload_product_image(array $file, ?string $existing = null): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload did not complete.');
    }
    if ((int) $file['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Product image must be smaller than 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Please upload a valid JPG, PNG, WEBP, or GIF image.');
    }
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $filename)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    return $filename;
}

function delete_uploaded_image(?string $filename): void
{
    if (!$filename || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|gif)$/i', $filename)) {
        return;
    }
    $path = UPLOAD_PATH . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

function product_image_url(?string $filename): ?string
{
    if (!$filename) {
        return null;
    }
    if (preg_match('#^https?://#i', $filename)) {
        return $filename;
    }
    if (is_file(UPLOAD_PATH . '/' . $filename)) {
        return asset(UPLOAD_URL . '/' . rawurlencode($filename));
    }
    return null;
}

function safe_return_path(?string $path): string
{
    if (!$path || !str_starts_with($path, base_path_url() . '/')) {
        return url('home');
    }
    return $path;
}

function require_post(): void
{
    if (!is_post()) {
        http_response_code(405);
        exit('Method not allowed.');
    }
    verify_csrf();
}

function page_title(string $title = ''): string
{
    return $title ? $title . ' · ' . APP_NAME : APP_NAME . ' — ' . APP_TAGLINE;
}

function notify_user(int $userId, string $type, string $title, string $message, ?string $link = null): void
{
    $statement = db()->prepare('INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)');
    $statement->execute([$userId, $type, $title, $message, $link]);
}

function audit_log(?int $actorId, string $action, string $entityType, ?int $entityId = null, string $details = ''): void
{
    $statement = db()->prepare('INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)');
    $statement->execute([$actorId, $action, $entityType, $entityId, $details]);
}

/** Return a small inline SVG icon. */
function icon(string $name, string $class = ''): string
{
    $paths = [
        'search' => '<circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.6-3.6"></path>',
        'location' => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.5"></circle>',
        'cart' => '<circle cx="9" cy="20" r="1"></circle><circle cx="19" cy="20" r="1"></circle><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 8H6"></path>',
        'user' => '<circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"></path>',
        'arrow-left' => '<path d="m19 12H5M11 18l-6-6 6-6"></path>',
        'check' => '<path d="m5 12 4 4L19 6"></path>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path><path d="m9 12 2 2 4-4"></path>',
        'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 4.8 19 2 19 2c1 8-3.6 15-10 15"></path><path d="M2 21c0-3 1.85-5.36 5.08-6.94C9.25 13 12 12 16 11"></path>',
        'clock' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path>',
        'store' => '<path d="M3 9l2-5h14l2 5"></path><path d="M5 13v8h14v-8M9 21v-6h6v6"></path><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"></path>',
        'map' => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"></path><path d="M9 3v15M15 6v15"></path>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"></path>',
        'package' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"></path><path d="m3 8 9 5 9-5v8l-9 5-9-5V8Z"></path><path d="M12 13v8"></path>',
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect>',
        'orders' => '<path d="M6 2h12l2 5H4l2-5Z"></path><path d="M5 7v14h14V7M9 11h6"></path>',
        'chart' => '<path d="M3 3v18h18"></path><path d="m7 16 4-5 4 3 5-7"></path>',
        'settings' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06a1.7 1.7 0 0 0-1.88-.34 1.7 1.7 0 0 0-1.03 1.56V21h-4v-.09A1.7 1.7 0 0 0 9 19.37a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.63 15 1.7 1.7 0 0 0 3.09 14H3v-4h.09A1.7 1.7 0 0 0 4.63 9a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.63h.01A1.7 1.7 0 0 0 10 3.09V3h4v.09A1.7 1.7 0 0 0 15 4.63a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.37 9v.01A1.7 1.7 0 0 0 20.91 10H21v4h-.09A1.7 1.7 0 0 0 19.4 15Z"></path>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path>',
        'edit' => '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z"></path>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v5M14 11v5"></path>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle>',
        'upload' => '<path d="M12 16V4M7 9l5-5 5 5"></path><path d="M20 16v4H4v-4"></path>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><path d="m16 17 5-5-5-5M21 12H9"></path>',
        'plus' => '<path d="M12 5v14M5 12h14"></path>',
        'minus' => '<path d="M5 12h14"></path>',
        'info' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 11v5M12 8h.01"></path>',
        'message' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"></path>',
        'filter' => '<path d="M4 5h16M7 12h10M10 19h4"></path>',
    ];

    $path = $paths[$name] ?? $paths['leaf'];
    $classAttribute = $class !== '' ? ' class="' . e($class) . '"' : '';
    return '<svg' . $classAttribute . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/permissions.php';

// Authenticates against the `users` table that sor-system owns (same shared
// database). Account creation/activation happens in sor-system — this app
// only ever reads from `users`, it never creates or activates accounts.

function is_logged_in(): bool
{
    return !empty($_SESSION['pm_logged_in']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        $uri = $_SERVER['REQUEST_URI'];
        if ($uri === '/' || $uri === '/index.php') {
            redirect(APP_URL . '/login.php');
        } else {
            redirect(APP_URL . '/login.php?next=' . urlencode($uri));
        }
    }
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_rate_limited(string $ip): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
          WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
    );
    $stmt->execute([$ip]);
    return (int)$stmt->fetchColumn() >= 10;
}

function record_failed_attempt(string $ip): void
{
    db()->prepare('INSERT INTO login_attempts (ip) VALUES (?)')->execute([$ip]);
}

function clear_attempts(string $ip): void
{
    db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
}

/** @param array<string,mixed> $user */
function establish_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['pm_logged_in'] = true;
    $_SESSION['pm_user']      = (string)($user['display_name'] ?: $user['username']);
    $_SESSION['user_id']      = (int)$user['id'];
    $_SESSION['username']     = (string)$user['username'];
    $_SESSION['is_admin']     = is_admin_username((string)$user['username']);
}

/**
 * Returns true on success, false on bad credentials or an account that has
 * no password set yet (those need to activate via sor-system first).
 */
function attempt_login(string $user, string $pass): bool
{
    $db   = db();
    $stmt = $db->prepare(
        'SELECT id, username, password_hash, display_name
         FROM users WHERE username = ? AND is_active = 1 LIMIT 1'
    );
    $stmt->execute([$user]);
    $row = $stmt->fetch();

    $hash  = $row && !empty($row['password_hash'])
        ? $row['password_hash']
        : '$2y$12$invalidsaltinvalidsaltinvalidsaltinvalidsaltinvalidsa';
    $valid = password_verify($pass, $hash);

    if ($row && $valid && !empty($row['password_hash'])) {
        establish_session($row);
        return true;
    }
    return false;
}

function get_current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

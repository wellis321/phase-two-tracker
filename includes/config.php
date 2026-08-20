<?php
declare(strict_types=1);

// Load .env from project root (one level up from /includes/)
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        if (!isset($_ENV[$key]) && !isset($_SERVER[$key])) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

define('APP_ENV',        getenv('APP_ENV')       ?: 'local');
define('APP_URL',        rtrim(getenv('APP_URL') ?: '', '/'));

// Points at the SAME database sor-system uses, so both apps authenticate
// against one shared `users` table — that's what makes login interoperable.
define('DB_HOST',        getenv('DB_HOST')        ?: 'localhost');
define('DB_PORT',        getenv('DB_PORT')        ?: '3306');
define('DB_NAME',        getenv('DB_NAME')        ?: 'sor_management');
define('DB_USER',        getenv('DB_USER')        ?: 'root');
define('DB_PASS',        getenv('DB_PASS')        ?: '');

define('SESSION_SECRET', getenv('SESSION_SECRET') ?: 'changeme');

// Comma-separated sor-system usernames who get admin (create/edit) rights
// in this app. Anyone else with a valid shared login is view-only.
define('PM_ADMIN_USERNAMES', getenv('PM_ADMIN_USERNAMES') ?: '');

// Cross-links to the other apps in the suite — same URLs/pattern sor-system
// itself links out to in its footer.
define('SOR_SYSTEM_URL', rtrim(getenv('SOR_SYSTEM_URL') ?: '', '/'));
define('ERC_SITE_URL',     rtrim(getenv('ERC_SITE_URL')     ?: 'https://aqua-quetzal-992173.hostingersite.com', '/'));
define('ASIS_SITE_URL',    rtrim(getenv('ASIS_SITE_URL')    ?: 'https://slategray-cat-335719.hostingersite.com', '/'));
define('METRICS_SITE_URL', rtrim(getenv('METRICS_SITE_URL') ?: 'https://dodgerblue-wombat-806788.hostingersite.com', '/'));

define('ITEMS_PER_PAGE', 50);

// ── Security headers ──────────────────────────────────────────
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Permitted-Cross-Domain-Policies: none');
if (APP_ENV !== 'local') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ── Hardened session cookie ───────────────────────────────────
// Distinct session name/cookie from sor-system's 'sor_session' — the two
// apps share a *credential store*, not a live session, so logging into one
// does not automatically log you into the other.
if (session_status() === PHP_SESSION_NONE) {
    session_name('phase2_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (APP_ENV !== 'local'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Binary authorization for this app: is this shared-login username one of
 * the configured admins? Deliberately NOT based on sor-system's
 * users.app_role — that column governs SOR rates permissions and coupling
 * it here would mean a SOR role change silently changes PM-tool access.
 */
function is_admin_username(string $username): bool
{
    $admins = array_filter(array_map('trim', explode(',', PM_ADMIN_USERNAMES)));
    return in_array($username, $admins, true);
}

function is_admin(): bool
{
    return !empty($_SESSION['is_admin']);
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        flash('error', 'You do not have permission to do that — this tool is view-only for your account.');
        redirect(APP_URL . '/index.php');
    }
}

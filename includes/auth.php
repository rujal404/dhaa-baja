<?php
/**
 * Dhaa Baja - Auth helpers (used by both public site and admin)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_session_handler.php';

if (session_status() === PHP_SESSION_NONE) {
    // Store sessions in the database rather than local disk — see
    // db_session_handler.php for why this matters on serverless hosts.
    session_set_save_handler(new DbSessionHandler(getDB()), true);
    session_start();
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return isset($_SESSION['user']);
}

function is_admin(): bool {
    return is_logged_in() && $_SESSION['user']['role'] === 'admin';
}

function require_login(string $redirectTo = 'login'): void {
    if (!is_logged_in()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: login');
        exit;
    }
}

function login_user(array $userRow): void {
    $_SESSION['user'] = [
        'id'         => $userRow['id'],
        'first_name' => $userRow['first_name'],
        'last_name'  => $userRow['last_name'],
        'email'      => $userRow['email'],
        'role'       => $userRow['role'],
    ];
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(): bool {
    return isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function flash(string $key, ?string $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Builds a reusable SQL fragment (+ bound params) that restricts a rhythm/category
 * query to only what the CURRENT visitor is allowed to see:
 *   - Admins bypass every restriction (they see everything, enabled or not).
 *   - Everyone else only sees enabled categories/rhythms, and for anything marked
 *     "restricted" they must have an explicit access_table row for their user id.
 *
 * Expects the query to alias rhythms as $rhythmAlias and categories as $catAlias
 * (both must be joined already). Pass null for $rhythmAlias to only filter categories.
 */
/**
 * Builds a reusable SQL fragment (+ bound params) that restricts a rhythm/category
 * query to only what the CURRENT visitor is allowed to see:
 *   - Admins bypass every restriction (they see everything, enabled or not).
 *   - The globally enabled/disabled flag always applies to everyone.
 *   - Per-user overrides (category_access / rhythm_access, keyed by user + item)
 *     can force something ON (enabled=1) or OFF (enabled=0) for one specific
 *     user, regardless of whether the item's default visibility is public or
 *     restricted. No override row = fall back to the default visibility.
 *
 * Expects the query to alias rhythms as $rhythmAlias and categories as $catAlias
 * (both must be joined already). Pass null for $rhythmAlias to only filter categories.
 */
function visibility_sql(string $catAlias = 'c', ?string $rhythmAlias = 'r'): array {
    if (is_admin()) {
        return ['sql' => '1=1', 'params' => []];
    }

    $uid = current_user()['id'] ?? 0;
    $params = [];

    // Uncategorized rhythms (category_id IS NULL, so $catAlias.id is NULL via the LEFT JOIN)
    // are treated as always category-visible; only the rhythm's own flags apply to them.
    $params[':cat_uid_on'] = $uid;
    $params[':cat_uid_off'] = $uid;
    $sql = "($catAlias.id IS NULL OR ($catAlias.is_enabled = 1
        AND (
            EXISTS (SELECT 1 FROM category_access ca WHERE ca.category_id = $catAlias.id AND ca.user_id = :cat_uid_on AND ca.enabled = 1)
            OR (
                $catAlias.visibility = 'public'
                AND NOT EXISTS (SELECT 1 FROM category_access ca2 WHERE ca2.category_id = $catAlias.id AND ca2.user_id = :cat_uid_off AND ca2.enabled = 0)
            )
        )))";

    if ($rhythmAlias !== null) {
        $params[':rhy_uid_on'] = $uid;
        $params[':rhy_uid_off'] = $uid;
        $sql .= " AND $rhythmAlias.is_enabled = 1
        AND (
            EXISTS (SELECT 1 FROM rhythm_access ra WHERE ra.rhythm_id = $rhythmAlias.id AND ra.user_id = :rhy_uid_on AND ra.enabled = 1)
            OR (
                $rhythmAlias.visibility = 'public'
                AND NOT EXISTS (SELECT 1 FROM rhythm_access ra2 WHERE ra2.rhythm_id = $rhythmAlias.id AND ra2.user_id = :rhy_uid_off AND ra2.enabled = 0)
            )
        )";
    }

    return ['sql' => $sql, 'params' => $params];
}

/**
 * Server-side gate for a single rhythm (e.g. before streaming a download).
 * Mirrors visibility_sql() but as a straight boolean check, so endpoints like
 * api/download.php can't be bypassed just because the UI hid the button.
 */
function user_can_access_rhythm(PDO $db, int $rhythmId): bool {
    if (is_admin()) {
        return true;
    }

    $stmt = $db->prepare('
        SELECT r.is_enabled AS r_enabled, r.visibility AS r_visibility,
               c.id AS cat_id, c.is_enabled AS c_enabled, c.visibility AS c_visibility
        FROM rhythms r
        LEFT JOIN categories c ON c.id = r.category_id
        WHERE r.id = :id
    ');
    $stmt->execute([':id' => $rhythmId]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }

    if ((int)$row['r_enabled'] !== 1) {
        return false;
    }

    $uid = current_user()['id'] ?? 0;

    // Rhythm-level per-user override
    $chk = $db->prepare('SELECT enabled FROM rhythm_access WHERE rhythm_id = :r AND user_id = :u');
    $chk->execute([':r' => $rhythmId, ':u' => $uid]);
    $override = $chk->fetch();
    if ($override !== false) {
        if ((int)$override['enabled'] !== 1) {
            return false; // explicitly disabled for this user
        }
        // explicitly enabled for this user - skip the default visibility check below
    } elseif ($row['r_visibility'] === 'restricted') {
        return false; // restricted by default and no override grants access
    }

    if ($row['cat_id'] !== null) {
        if ((int)$row['c_enabled'] !== 1) {
            return false;
        }

        $chk = $db->prepare('SELECT enabled FROM category_access WHERE category_id = :c AND user_id = :u');
        $chk->execute([':c' => $row['cat_id'], ':u' => $uid]);
        $catOverride = $chk->fetch();
        if ($catOverride !== false) {
            if ((int)$catOverride['enabled'] !== 1) {
                return false; // explicitly disabled for this user
            }
        } elseif ($row['c_visibility'] === 'restricted') {
            return false; // restricted by default and no override grants access
        }
    }

    return true;
}

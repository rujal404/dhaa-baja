<?php
/**
 * Vercel front-controller.
 *
 * vercel.json routes every request (except /assets/*) through this single
 * PHP file, which is declared as the one Vercel Function for the whole app.
 * That keeps the deployment to 1 function total instead of ~25 (one per
 * page), which matters because Vercel's Hobby plan caps a deployment at 12
 * functions.
 *
 * This script just maps the clean URL (e.g. /library, /admin/rhythms) back
 * to the real .php file already living in the project (library.php,
 * admin/rhythms.php, ...) and requires it — the exact same files used for a
 * traditional Apache deployment, completely unmodified. __DIR__ inside those
 * files still correctly resolves to their own directory, so every
 * require_once/include of includes/, admin/includes/, etc. keeps working
 * exactly as it does locally or on Apache.
 */

$root = __DIR__ . '/..';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = $uri === false || $uri === null ? '/' : $uri;

// Canonicalize: if a literal .php URL is requested, redirect to the clean
// version, matching the behavior of the .htaccess rules used on Apache.
if (preg_match('#^/(.+)\.php$#', $uri, $m)) {
    header('Location: /' . $m[1], true, 301);
    exit;
}

// Root -> index.php
if ($uri === '/' || $uri === '') {
    require $root . '/index.php';
    exit;
}

// Try an exact file match: /library -> library.php, /admin/rhythms -> admin/rhythms.php,
// /api/download -> api/download.php (the real handler files sitting next to this one).
$candidate = $root . rtrim($uri, '/') . '.php';
if (is_file($candidate) && realpath($candidate) !== __FILE__) {
    require $candidate;
    exit;
}

// Directory index fallback: /admin or /admin/ -> admin/index.php
$indexCandidate = $root . rtrim($uri, '/') . '/index.php';
if (is_file($indexCandidate) && realpath($indexCandidate) !== __FILE__) {
    require $indexCandidate;
    exit;
}

http_response_code(404);
echo '404 Not Found';

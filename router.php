<?php
/**
 * Dev-server router — ONLY used when running via PHP's built-in server, e.g.:
 *   php -S localhost:8000 router.php
 *
 * Apache/Nginx deployments ignore this file entirely; they use .htaccess
 * (Apache) instead. This script exists purely so clean URLs like /library
 * also work during local development without a real web server.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Canonicalize: redirect example.com/library.php -> example.com/library
// (must run before the "serve real files as-is" check below, since the
// requested .php file genuinely exists on disk and would otherwise be
// served directly instead of redirected).
if (preg_match('#^/(.+)\.php$#', $uri, $m)) {
    header('Location: /' . $m[1], true, 301);
    exit;
}

// Let real non-PHP files (css, js, images, uploaded sheets, etc.) be served as-is.
$requested = __DIR__ . $uri;
if ($uri !== '/' && file_exists($requested) && !is_dir($requested)) {
    return false;
}

// Try to resolve /library -> library.php, /admin/rhythms -> admin/rhythms.php, etc.
$candidate = __DIR__ . rtrim($uri, '/') . '.php';
if (is_file($candidate)) {
    require $candidate;
    return true;
}

// Fall back to directory index (e.g. / -> index.php, /admin/ -> admin/index.php)
$indexCandidate = __DIR__ . rtrim($uri, '/') . '/index.php';
if (is_file($indexCandidate)) {
    require $indexCandidate;
    return true;
}

return false;

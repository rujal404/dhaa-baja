<?php
/**
 * Dhaa Baja - Database Configuration
 *
 * Reads connection details from environment variables first (so this same
 * file works unmodified on Vercel — set these in Project Settings > Environment
 * Variables — or any other host that injects env vars), falling back to local
 * defaults for plain local development where no env vars are set.
 *
 * Required env vars for a hosted MySQL-compatible database (e.g. PlanetScale,
 * TiDB Cloud, Aiven, Railway): DB_HOST, DB_NAME, DB_USER, DB_PASS.
 * Optional: DB_PORT (default 3306), DB_SSL (set to "true" to connect over TLS,
 * which most hosted MySQL providers require).
 */
function env(string $key, ?string $default = null): ?string {
    $value = getenv($key);
    return $value !== false && $value !== '' ? $value : $default;
}

$dbHost = env('DB_HOST', 'localhost');
// The literal string "localhost" makes MySQL client libraries try a local Unix
// socket file instead of a TCP connection, regardless of platform. That's fine
// on a traditional server with MySQL installed alongside PHP, but on a
// serverless host like Vercel there's no local socket, which produces a
// confusing "SQLSTATE[HY000] [2002] No such file or directory" error even
// when DB_HOST *looks* correctly configured. Force TCP always by swapping
// the literal hostname for its loopback IP.
if ($dbHost === 'localhost') {
    $dbHost = '127.0.0.1';
}

define('DB_HOST', $dbHost);
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'dhaa_baja'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_SSL',  filter_var(env('DB_SSL', 'false'), FILTER_VALIDATE_BOOLEAN));
define('DB_CHARSET', 'utf8mb4');

// Base URL of the site (no trailing slash) - used for redirects/links
define('BASE_URL', env('BASE_URL', ''));

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if (DB_SSL) {
            // Encrypt the connection, which most hosted MySQL providers require
            // over the public internet. We don't pin a specific CA bundle here
            // since providers vary; if your provider hands you a CA cert, set
            // PDO::MYSQL_ATTR_SSL_CA to its path for stricter verification.
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $onVercel = getenv('VERCEL') !== false;
            $hint = $onVercel
                ? ' — on Vercel, this almost always means DB_HOST (and DB_NAME/DB_USER/DB_PASS) '
                  . 'aren\'t set in Project Settings > Environment Variables, or point at the wrong '
                  . 'host. Double-check they match your hosted MySQL provider exactly, then redeploy.'
                : ' — check that your MySQL/MariaDB server is running and that DB_HOST/DB_PORT in '
                  . 'includes/config.php (or your environment variables) are correct.';
            die('Database connection failed: ' . htmlspecialchars($e->getMessage()) . $hint);
        }
    }
    return $pdo;
}

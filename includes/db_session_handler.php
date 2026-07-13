<?php
/**
 * Database-backed PHP session handler.
 *
 * PHP's default session handler writes files to local disk (usually /tmp).
 * That's fine on a traditional server, but breaks on serverless platforms
 * like Vercel: each request can be routed to a different, short-lived
 * instance with its own ephemeral filesystem, so a session file written
 * during login might not exist anymore by the very next request — silently
 * logging people out or invalidating CSRF tokens.
 *
 * Storing session data as a row in the `sessions` table instead means every
 * instance reads/writes the same source of truth, so sessions work correctly
 * regardless of how many instances are running or how requests get routed.
 * This also happens to work identically on a normal single-server host, so
 * there's no environment-specific branching needed anywhere else.
 */
class DbSessionHandler implements SessionHandlerInterface
{
    private PDO $db;
    private int $maxLifetime;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->maxLifetime = (int)ini_get('session.gc_maxlifetime') ?: 1440;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->db->prepare('SELECT data FROM sessions WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sessions (id, data, last_access) VALUES (:id, :data, :ts)
             ON DUPLICATE KEY UPDATE data = VALUES(data), last_access = VALUES(last_access)'
        );
        return $stmt->execute([':id' => $id, ':data' => $data, ':ts' => time()]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $cutoff = time() - $max_lifetime;
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE last_access < :cutoff');
        $stmt->execute([':cutoff' => $cutoff]);
        return $stmt->rowCount();
    }
}

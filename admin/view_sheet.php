<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$db = getDB();

$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT sheet_data, sheet_mime, sheet_original_name FROM rhythms WHERE id = :id');
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

if (!$row || $row['sheet_data'] === null) {
    http_response_code(404);
    echo 'No sheet music found for this rhythm.';
    exit;
}

header('Content-Type: ' . ($row['sheet_mime'] ?: 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . basename($row['sheet_original_name'] ?: 'sheet-music') . '"');
header('Content-Length: ' . strlen($row['sheet_data']));
header('Cache-Control: no-cache, must-revalidate');
echo $row['sheet_data'];
exit;

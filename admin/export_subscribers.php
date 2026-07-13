<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$db = getDB();

$subs = $db->query("SELECT email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC")->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="dhaa-baja-subscribers.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Email', 'Subscribed At']);
foreach ($subs as $s) {
    fputcsv($out, [$s['email'], $s['subscribed_at']]);
}
fclose($out);
exit;

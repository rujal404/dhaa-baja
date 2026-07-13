<?php
require_once __DIR__ . '/../includes/auth.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $email = trim($_POST['email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $db->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (:e)');
        $stmt->execute([':e' => $email]);
        flash('purchase_msg', 'Thanks for subscribing to the newsletter!');
    }
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../index'));
exit;

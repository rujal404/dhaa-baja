<?php
require_once __DIR__ . '/../includes/auth.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $eventId = (int)($_POST['event_id'] ?? 0);
    $user = current_user();
    $name = $user ? $user['first_name'] . ' ' . $user['last_name'] : trim($_POST['name'] ?? '');
    $email = $user ? $user['email'] : trim($_POST['email'] ?? '');

    if ($eventId && $name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $db->prepare('SELECT * FROM events WHERE id = :id');
        $stmt->execute([':id' => $eventId]);
        $event = $stmt->fetch();

        if ($event) {
            $ins = $db->prepare('INSERT INTO rsvps (event_id, user_id, name, email, status) VALUES (:ev, :u, :n, :e, "confirmed")');
            $ins->execute([
                ':ev' => $eventId,
                ':u' => $user['id'] ?? null,
                ':n' => $name,
                ':e' => $email,
            ]);
            flash('rsvp_msg', 'You\'re confirmed for "' . $event['title'] . '". See you there!');
        }
    } else {
        flash('rsvp_msg', 'Please provide your name and a valid email address.');
    }
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../events'));
exit;

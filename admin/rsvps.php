<?php
$pageTitle = 'RSVPs | Admin';
$activeAdmin = 'rsvps';
include __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $rid = (int)$_POST['id'];
    if (($_POST['action'] ?? '') === 'cancel') {
        $db->prepare("UPDATE rsvps SET status='cancelled' WHERE id=:id")->execute([':id'=>$rid]);
        flash('admin_msg', 'RSVP cancelled.');
    } elseif (($_POST['action'] ?? '') === 'confirm') {
        $db->prepare("UPDATE rsvps SET status='confirmed' WHERE id=:id")->execute([':id'=>$rid]);
        flash('admin_msg', 'RSVP confirmed.');
    }
    header('Location: rsvps');
    exit;
}

$rsvps = $db->query("
    SELECT rs.*, ev.title AS event_title, ev.event_date FROM rsvps rs
    JOIN events ev ON ev.id = rs.event_id
    ORDER BY rs.created_at DESC
")->fetchAll();
$msg = flash('admin_msg');
?>
<h1 class="font-headline text-3xl text-primary mb-1">Event RSVPs</h1>
<p class="text-on-surface-variant mb-8">Everyone registered for upcoming gatherings.</p>

<?php if ($msg): ?><div class="mb-6 bg-secondary-container/40 px-4 py-3 rounded text-sm"><?= e($msg) ?></div><?php endif; ?>

<div class="bg-surface border border-outline-variant rounded-xl overflow-x-auto">
  <table class="w-full text-sm min-w-[750px]">
    <thead>
      <tr class="text-left border-b border-outline-variant text-xs uppercase tracking-wider text-on-surface-variant">
        <th class="px-6 py-4">Event</th>
        <th class="px-6 py-4">Date</th>
        <th class="px-6 py-4">Guest</th>
        <th class="px-6 py-4">Email</th>
        <th class="px-6 py-4">Status</th>
        <th class="px-6 py-4 text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rsvps as $r): ?>
      <tr class="border-b border-outline-variant/50 last:border-0">
        <td class="px-6 py-4 font-medium"><?= e($r['event_title']) ?></td>
        <td class="px-6 py-4"><?= (new DateTime($r['event_date']))->format('M d, Y') ?></td>
        <td class="px-6 py-4"><?= e($r['name']) ?></td>
        <td class="px-6 py-4"><?= e($r['email']) ?></td>
        <td class="px-6 py-4">
          <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $r['status']==='confirmed'?'bg-green-100 text-green-700':($r['status']==='cancelled'?'bg-red-100 text-red-700':'bg-yellow-100 text-yellow-700') ?>"><?= e($r['status']) ?></span>
        </td>
        <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
          <?php if ($r['status'] !== 'confirmed'): ?>
            <form method="post" class="inline">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="confirm">
              <button class="text-secondary hover:underline" type="submit">Confirm</button>
            </form>
          <?php endif; ?>
          <?php if ($r['status'] !== 'cancelled'): ?>
            <form method="post" class="inline">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="cancel">
              <button class="text-red-600 hover:underline" type="submit">Cancel</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rsvps): ?>
        <tr><td colspan="6" class="px-6 py-8 text-center text-on-surface-variant">No RSVPs yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

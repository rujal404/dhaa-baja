<?php
$pageTitle = 'Subscribers | Admin';
$activeAdmin = 'subscribers';
include __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && csrf_check()) {
    $db->prepare('DELETE FROM newsletter_subscribers WHERE id = :id')->execute([':id' => (int)$_POST['id']]);
    flash('admin_msg', 'Subscriber removed.');
    header('Location: subscribers');
    exit;
}

$subs = $db->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC")->fetchAll();
$msg = flash('admin_msg');
?>
<div class="flex justify-between items-center mb-8">
  <div>
    <h1 class="font-headline text-3xl text-primary mb-1">Newsletter Subscribers</h1>
    <p class="text-on-surface-variant">Everyone who signed up via the site footer.</p>
  </div>
  <a href="export_subscribers" class="border border-outline-variant px-5 py-3 rounded-lg text-sm font-bold hover:bg-surface-container-high">Export CSV</a>
</div>

<?php if ($msg): ?><div class="mb-6 bg-secondary-container/40 px-4 py-3 rounded text-sm"><?= e($msg) ?></div><?php endif; ?>

<div class="bg-surface border border-outline-variant rounded-xl overflow-x-auto">
  <table class="w-full text-sm">
    <thead>
      <tr class="text-left border-b border-outline-variant text-xs uppercase tracking-wider text-on-surface-variant">
        <th class="px-6 py-4">Email</th>
        <th class="px-6 py-4">Subscribed On</th>
        <th class="px-6 py-4 text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($subs as $s): ?>
      <tr class="border-b border-outline-variant/50 last:border-0">
        <td class="px-6 py-4"><?= e($s['email']) ?></td>
        <td class="px-6 py-4"><?= (new DateTime($s['subscribed_at']))->format('M d, Y') ?></td>
        <td class="px-6 py-4 text-right">
          <form method="post" class="inline" data-confirm="Remove this subscriber?">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button class="text-red-600 hover:underline" type="submit">Remove</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$subs): ?>
        <tr><td colspan="3" class="px-6 py-8 text-center text-on-surface-variant">No subscribers yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

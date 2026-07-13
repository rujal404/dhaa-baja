<?php
$pageTitle = 'Events | Admin';
$activeAdmin = 'events';
include __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && csrf_check()) {
    $db->prepare('DELETE FROM events WHERE id = :id')->execute([':id' => (int)$_POST['id']]);
    flash('admin_msg', 'Event deleted.');
    header('Location: events');
    exit;
}

$events = $db->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll();
$msg = flash('admin_msg');
?>
<div class="flex justify-between items-center mb-8">
  <div>
    <h1 class="font-headline text-3xl text-primary mb-1">Events</h1>
    <p class="text-on-surface-variant">Manage gatherings, workshops, and performances.</p>
  </div>
  <a href="event_form" class="bg-primary text-on-primary px-5 py-3 rounded-lg text-sm font-bold flex items-center gap-2 hover:opacity-90">
    <span class="material-symbols-outlined text-lg">add</span> New Event
  </a>
</div>

<?php if ($msg): ?><div class="mb-6 bg-secondary-container/40 px-4 py-3 rounded text-sm"><?= e($msg) ?></div><?php endif; ?>

<div class="bg-surface border border-outline-variant rounded-xl overflow-x-auto">
  <table class="w-full text-sm min-w-[750px]">
    <thead>
      <tr class="text-left border-b border-outline-variant text-xs uppercase tracking-wider text-on-surface-variant">
        <th class="px-6 py-4">Title</th>
        <th class="px-6 py-4">Category</th>
        <th class="px-6 py-4">Date</th>
        <th class="px-6 py-4">Location</th>
        <th class="px-6 py-4">Price</th>
        <th class="px-6 py-4 text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($events as $ev): ?>
      <tr class="border-b border-outline-variant/50 last:border-0">
        <td class="px-6 py-4 font-medium"><?= e($ev['title']) ?></td>
        <td class="px-6 py-4"><?= e($ev['category']) ?></td>
        <td class="px-6 py-4"><?= (new DateTime($ev['event_date']))->format('M d, Y') ?></td>
        <td class="px-6 py-4"><?= e($ev['location']) ?></td>
        <td class="px-6 py-4"><?= $ev['is_free'] ? 'Free' : '$' . number_format($ev['price'], 2) ?></td>
        <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
          <a href="event_form?id=<?= (int)$ev['id'] ?>" class="text-secondary hover:underline">Edit</a>
          <form method="post" class="inline" data-confirm="Delete this event?">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>">
            <button class="text-red-600 hover:underline" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$events): ?>
        <tr><td colspan="6" class="px-6 py-8 text-center text-on-surface-variant">No events yet — add the first one.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

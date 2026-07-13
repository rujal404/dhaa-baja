<?php
$pageTitle = 'Purchases | Admin';
$activeAdmin = 'purchases';
include __DIR__ . '/includes/header.php';

$purchases = $db->query("
    SELECT p.*, r.title AS rhythm_title FROM purchases p
    JOIN rhythms r ON r.id = p.rhythm_id
    ORDER BY p.created_at DESC
")->fetchAll();

$total = $db->query("SELECT COALESCE(SUM(amount),0) FROM purchases")->fetchColumn();
?>
<div class="flex justify-between items-end mb-8">
  <div>
    <h1 class="font-headline text-3xl text-primary mb-1">Downloads &amp; Purchases</h1>
    <p class="text-on-surface-variant">Rhythm downloads sold through the library.</p>
  </div>
  <div class="text-right">
    <p class="text-xs uppercase tracking-widest text-on-surface-variant">Total Revenue</p>
    <p class="font-headline text-3xl text-primary">$<?= number_format((float)$total, 2) ?></p>
  </div>
</div>

<div class="bg-surface border border-outline-variant rounded-xl overflow-x-auto">
  <table class="w-full text-sm">
    <thead>
      <tr class="text-left border-b border-outline-variant text-xs uppercase tracking-wider text-on-surface-variant">
        <th class="px-6 py-4">Rhythm</th>
        <th class="px-6 py-4">Buyer Email</th>
        <th class="px-6 py-4">Amount</th>
        <th class="px-6 py-4">Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($purchases as $p): ?>
      <tr class="border-b border-outline-variant/50 last:border-0">
        <td class="px-6 py-4 font-medium"><?= e($p['rhythm_title']) ?></td>
        <td class="px-6 py-4"><?= e($p['email']) ?></td>
        <td class="px-6 py-4">$<?= number_format($p['amount'], 2) ?></td>
        <td class="px-6 py-4"><?= (new DateTime($p['created_at']))->format('M d, Y g:i A') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$purchases): ?>
        <tr><td colspan="4" class="px-6 py-8 text-center text-on-surface-variant">No purchases yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

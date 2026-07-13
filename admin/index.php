<?php
$pageTitle = 'Dashboard | Admin';
$activeAdmin = 'dashboard';
include __DIR__ . '/includes/header.php';

$stats = [
    'users'       => $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
    'rhythms'     => $db->query("SELECT COUNT(*) FROM rhythms")->fetchColumn(),
    'events'      => $db->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn(),
    'rsvps'       => $db->query("SELECT COUNT(*) FROM rsvps")->fetchColumn(),
    'purchases'   => $db->query("SELECT COUNT(*) FROM purchases")->fetchColumn(),
    'revenue'     => $db->query("SELECT COALESCE(SUM(amount),0) FROM purchases")->fetchColumn(),
    'subscribers' => $db->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn(),
];

$recentPurchases = $db->query("
    SELECT p.*, r.title AS rhythm_title FROM purchases p
    JOIN rhythms r ON r.id = p.rhythm_id
    ORDER BY p.created_at DESC LIMIT 5
")->fetchAll();

$recentRsvps = $db->query("
    SELECT rs.*, ev.title AS event_title FROM rsvps rs
    JOIN events ev ON ev.id = rs.event_id
    ORDER BY rs.created_at DESC LIMIT 5
")->fetchAll();
?>
<h1 class="font-headline text-3xl text-primary mb-1">Dashboard</h1>
<p class="text-on-surface-variant mb-8">Overview of Dhaa Baja activity.</p>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
  <div class="bg-surface border border-outline-variant rounded-xl p-6">
    <p class="text-xs uppercase tracking-widest text-on-surface-variant mb-2">Members</p>
    <p class="font-headline text-3xl text-primary"><?= (int)$stats['users'] ?></p>
  </div>
  <div class="bg-surface border border-outline-variant rounded-xl p-6">
    <p class="text-xs uppercase tracking-widest text-on-surface-variant mb-2">Rhythms</p>
    <p class="font-headline text-3xl text-primary"><?= (int)$stats['rhythms'] ?></p>
  </div>
  <div class="bg-surface border border-outline-variant rounded-xl p-6">
    <p class="text-xs uppercase tracking-widest text-on-surface-variant mb-2">Upcoming Events</p>
    <p class="font-headline text-3xl text-primary"><?= (int)$stats['events'] ?></p>
  </div>
  <div class="bg-surface border border-outline-variant rounded-xl p-6">
    <p class="text-xs uppercase tracking-widest text-on-surface-variant mb-2">Total Revenue</p>
    <p class="font-headline text-3xl text-primary">$<?= number_format((float)$stats['revenue'], 2) ?></p>
  </div>
  <div class="bg-surface border border-outline-variant rounded-xl p-6">
    <p class="text-xs uppercase tracking-widest text-on-surface-variant mb-2">RSVPs</p>
    <p class="font-headline text-3xl text-primary"><?= (int)$stats['rsvps'] ?></p>
  </div>
  <div class="bg-surface border border-outline-variant rounded-xl p-6">
    <p class="text-xs uppercase tracking-widest text-on-surface-variant mb-2">Downloads Sold</p>
    <p class="font-headline text-3xl text-primary"><?= (int)$stats['purchases'] ?></p>
  </div>
  <div class="bg-surface border border-outline-variant rounded-xl p-6 col-span-2 md:col-span-1">
    <p class="text-xs uppercase tracking-widest text-on-surface-variant mb-2">Newsletter Subscribers</p>
    <p class="font-headline text-3xl text-primary"><?= (int)$stats['subscribers'] ?></p>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
  <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden">
    <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
      <h2 class="font-headline text-lg text-primary">Recent Downloads</h2>
      <a href="purchases" class="text-xs text-secondary hover:underline">View all</a>
    </div>
    <table class="w-full text-sm">
      <?php foreach ($recentPurchases as $p): ?>
      <tr class="border-b border-outline-variant/50 last:border-0">
        <td class="px-6 py-3"><?= e($p['rhythm_title']) ?></td>
        <td class="px-6 py-3 text-on-surface-variant"><?= e($p['email']) ?></td>
        <td class="px-6 py-3 text-right">$<?= number_format($p['amount'], 2) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$recentPurchases): ?>
        <tr><td class="px-6 py-6 text-on-surface-variant text-center" colspan="3">No downloads yet.</td></tr>
      <?php endif; ?>
    </table>
  </div>

  <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden">
    <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
      <h2 class="font-headline text-lg text-primary">Recent RSVPs</h2>
      <a href="rsvps" class="text-xs text-secondary hover:underline">View all</a>
    </div>
    <table class="w-full text-sm">
      <?php foreach ($recentRsvps as $r): ?>
      <tr class="border-b border-outline-variant/50 last:border-0">
        <td class="px-6 py-3"><?= e($r['event_title']) ?></td>
        <td class="px-6 py-3 text-on-surface-variant"><?= e($r['name']) ?></td>
        <td class="px-6 py-3 text-right"><?= e(ucfirst($r['status'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$recentRsvps): ?>
        <tr><td class="px-6 py-6 text-on-surface-variant text-center" colspan="3">No RSVPs yet.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

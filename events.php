<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$catFilter = $_GET['category'] ?? '';
$q = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM events WHERE 1=1";
$params = [];
if ($catFilter !== '' && in_array($catFilter, ['Workshop','Live Performance','Community Circle','Exhibition'], true)) {
    $sql .= " AND category = :cat";
    $params[':cat'] = $catFilter;
}
if ($q !== '') {
    $sql .= " AND (title LIKE :q1 OR description LIKE :q2 OR location LIKE :q3)";
    $params[':q1'] = "%$q%";
    $params[':q2'] = "%$q%";
    $params[':q3'] = "%$q%";
}
$sql .= " ORDER BY event_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$pageTitle = 'Events | Dhaa Baja';
$activePage = 'events';
include __DIR__ . '/includes/head.php';
?>
<body class="font-body-md text-body-md">
<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="max-w-container-max mx-auto px-gutter py-section-gap">
  <header class="mb-16 max-w-2xl">
    <span class="font-label-md text-label-md text-secondary uppercase tracking-widest mb-4 block">Cultural Calendar</span>
    <h1 class="font-display-lg text-display-lg text-primary mb-6">Gatherings &amp; Echoes</h1>
    <p class="font-body-lg text-body-lg text-on-surface-variant">
      A journal of the workshops, performances, and community circles that have shaped our story.
    </p>
  </header>

  <form method="get" class="mb-8">
    <div class="relative max-w-md">
      <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search gatherings by title, description, or location..." class="w-full pl-10 pr-4 py-3 rounded-full border border-outline-variant bg-surface-container-low text-sm focus:outline-none focus:border-primary">
    </div>
  </form>

  <form method="get" class="flex flex-wrap gap-3 mb-12">
    <input type="hidden" name="q" value="<?= e($q) ?>">
    <?php foreach (['' => 'All Gatherings', 'Workshop' => 'Workshops', 'Live Performance' => 'Live Performance', 'Community Circle' => 'Community Circles', 'Exhibition' => 'Exhibitions'] as $val => $label): ?>
      <button name="category" value="<?= e($val) ?>" class="px-6 py-2 rounded-full font-label-md text-label-md border transition-colors <?= $catFilter === $val ? 'bg-secondary-container text-on-secondary-container border-transparent' : 'bg-surface-container text-on-surface-variant border-outline-variant hover:bg-surface-container-high' ?>"><?= e($label) ?></button>
    <?php endforeach; ?>
  </form>

  <?php if ($q !== ''): ?>
    <p class="text-sm text-on-surface-variant mb-6">Showing results for &ldquo;<?= e($q) ?>&rdquo;</p>
  <?php endif; ?>

  <?php if (!$events): ?>
    <p class="text-on-surface-variant"><?= $q !== '' ? 'No gatherings match your search.' : 'No gatherings in this category yet — check back soon.' ?></p>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach ($events as $ev): $d = new DateTime($ev['event_date']); ?>
    <article id="event-<?= (int)$ev['id'] ?>" class="parchment-card flex flex-col h-full rounded-xl overflow-hidden border border-outline-variant">
      <div class="p-8 flex flex-col flex-grow">
        <div class="flex items-center justify-between mb-4">
          <span class="bg-primary text-on-primary px-3 py-1 rounded font-label-md text-xs uppercase tracking-wider"><?= e($ev['category']) ?></span>
          <span class="font-label-md text-label-md text-secondary"><?= $d->format('M d, Y') ?></span>
        </div>
        <h3 class="font-headline-md text-headline-md text-primary mb-4 leading-tight"><?= e($ev['title']) ?></h3>
        <p class="text-on-surface-variant flex-grow"><?= e($ev['description']) ?></p>
        <div class="flex items-center gap-2 text-on-surface-variant mt-6 pt-6 border-t border-outline-variant/30">
          <span class="material-symbols-outlined text-sm">location_on</span>
          <span class="font-label-md text-sm"><?= e($ev['location']) ?></span>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

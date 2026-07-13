<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$q = trim($_GET['q'] ?? '');

$rhythms = [];
$categories = [];
$events = [];

if ($q !== '') {
    $like = "%$q%";

    // Rhythms — respecting the same visibility rules as the Library page
    $vis = visibility_sql('c', 'r');
    $rSql = "SELECT r.id, r.title, r.slug, r.category_id, r.description, r.duration_seconds,
                r.image_url, r.audio_url, r.is_featured, r.is_enabled, r.visibility,
                r.play_count, r.created_at, c.name AS category_name
             FROM rhythms r
             LEFT JOIN categories c ON c.id = r.category_id
             WHERE ({$vis['sql']}) AND (r.title LIKE :q1 OR r.description LIKE :q2 OR c.name LIKE :q3)
             ORDER BY r.is_featured DESC, r.created_at DESC";
    $stmt = $db->prepare($rSql);
    $stmt->execute($vis['params'] + [':q1' => $like, ':q2' => $like, ':q3' => $like]);
    $rhythms = $stmt->fetchAll();

    // Categories — respecting visibility rules
    $catVis = visibility_sql('c', null);
    $cSql = "SELECT c.*, (SELECT COUNT(*) FROM rhythms r2 WHERE r2.category_id = c.id) AS rhythm_count
             FROM categories c
             WHERE ({$catVis['sql']}) AND (c.name LIKE :q1 OR c.description LIKE :q2)
             ORDER BY c.name ASC";
    $stmt = $db->prepare($cSql);
    $stmt->execute($catVis['params'] + [':q1' => $like, ':q2' => $like]);
    $categories = $stmt->fetchAll();

    // Events — public, no access control on this content type
    $eSql = "SELECT * FROM events
             WHERE title LIKE :q1 OR description LIKE :q2 OR location LIKE :q3 OR category LIKE :q4
             ORDER BY event_date DESC";
    $stmt = $db->prepare($eSql);
    $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like]);
    $events = $stmt->fetchAll();
}

$totalResults = count($rhythms) + count($categories) + count($events);

$pageTitle = 'Search | Dhaa Baja';
$activePage = 'search';
include __DIR__ . '/includes/head.php';
?>
<body class="font-body-md text-on-surface">
<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="max-w-container-max mx-auto px-gutter py-section-gap">
  <div class="mb-12">
    <span class="font-label-md text-label-md text-secondary uppercase tracking-widest mb-4 block">Search</span>
    <h1 class="font-display-lg text-display-lg text-primary mb-6">
      <?= $q !== '' ? 'Results for &ldquo;' . e($q) . '&rdquo;' : 'Search Dhaa Baja' ?>
    </h1>

    <form method="get" class="max-w-xl">
      <div class="relative">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">search</span>
        <input type="text" name="q" value="<?= e($q) ?>" autofocus placeholder="Search rhythms, categories, and events..." class="w-full pl-12 pr-4 py-4 rounded-full border border-outline-variant bg-surface-container-low text-body-md focus:outline-none focus:border-primary">
      </div>
    </form>
  </div>

  <?php if ($q === ''): ?>
    <p class="text-on-surface-variant">Type something above to search across the rhythm library, categories, and upcoming gatherings.</p>
  <?php elseif ($totalResults === 0): ?>
    <p class="text-on-surface-variant">No matches for &ldquo;<?= e($q) ?>&rdquo;. Try a different word or check your spelling.</p>
  <?php else: ?>
    <p class="text-sm text-on-surface-variant mb-10"><?= (int)$totalResults ?> result<?= $totalResults === 1 ? '' : 's' ?> found.</p>

    <?php if ($categories): ?>
    <section class="mb-14">
      <h2 class="font-headline-md text-headline-md text-primary mb-6 flex items-center gap-2">
        <span class="material-symbols-outlined">category</span> Categories <span class="text-sm text-on-surface-variant font-normal">(<?= count($categories) ?>)</span>
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($categories as $c): ?>
        <a href="library?category=<?= (int)$c['id'] ?>" class="parchment-card border border-outline-variant/30 rounded-xl p-6 block hover:-translate-y-1 transition-transform">
          <h3 class="font-headline-md text-lg text-primary mb-2"><?= e($c['name']) ?></h3>
          <?php if ($c['description']): ?><p class="text-sm text-on-surface-variant mb-3"><?= e($c['description']) ?></p><?php endif; ?>
          <span class="text-xs text-secondary font-label-md uppercase tracking-widest"><?= (int)$c['rhythm_count'] ?> rhythm<?= (int)$c['rhythm_count'] === 1 ? '' : 's' ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($rhythms): ?>
    <section class="mb-14">
      <h2 class="font-headline-md text-headline-md text-primary mb-6 flex items-center gap-2">
        <span class="material-symbols-outlined">library_music</span> Rhythms <span class="text-sm text-on-surface-variant font-normal">(<?= count($rhythms) ?>)</span>
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($rhythms as $r): ?>
        <a href="library#rhythm-<?= (int)$r['id'] ?>" class="parchment-card border border-outline-variant/30 rounded-xl p-6 block hover:-translate-y-1 transition-transform">
          <span class="bg-secondary/10 text-secondary px-2 py-0.5 font-label-md text-xs rounded-full uppercase"><?= e($r['category_name'] ?? 'Uncategorized') ?></span>
          <h3 class="font-headline-md text-lg text-primary mt-3 mb-2"><?= e($r['title']) ?></h3>
          <p class="text-sm text-on-surface-variant line-clamp-2"><?= e($r['description']) ?></p>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($events): ?>
    <section class="mb-14">
      <h2 class="font-headline-md text-headline-md text-primary mb-6 flex items-center gap-2">
        <span class="material-symbols-outlined">event</span> Events <span class="text-sm text-on-surface-variant font-normal">(<?= count($events) ?>)</span>
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($events as $ev): $d = new DateTime($ev['event_date']); ?>
        <a href="events#event-<?= (int)$ev['id'] ?>" class="parchment-card border border-outline-variant/30 rounded-xl p-6 block hover:-translate-y-1 transition-transform">
          <div class="flex justify-between items-start mb-2">
            <span class="bg-primary text-on-primary px-2 py-0.5 font-label-md text-xs rounded uppercase"><?= e($ev['category']) ?></span>
            <span class="text-xs text-secondary font-label-md"><?= $d->format('M d, Y') ?></span>
          </div>
          <h3 class="font-headline-md text-lg text-primary mb-2"><?= e($ev['title']) ?></h3>
          <p class="text-sm text-on-surface-variant line-clamp-2 mb-2"><?= e($ev['description']) ?></p>
          <span class="text-xs text-on-surface-variant flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">location_on</span> <?= e($ev['location']) ?>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

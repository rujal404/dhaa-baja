<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$q = trim($_GET['q'] ?? '');
$categoryId = $_GET['category'] ?? '';

$vis = visibility_sql('c', 'r');

$sql = "SELECT r.id, r.title, r.slug, r.category_id, r.description, r.duration_seconds,
            r.image_url, r.audio_url, r.sheet_original_name, r.is_featured, r.is_enabled,
            r.visibility, r.play_count, r.created_at,
            c.name AS category_name
        FROM rhythms r
        LEFT JOIN categories c ON c.id = r.category_id
        WHERE ({$vis['sql']})";
$params = $vis['params'];

if ($q !== '') {
    $sql .= " AND (r.title LIKE :q1 OR r.description LIKE :q2 OR c.name LIKE :q3)";
    $params[':q1'] = "%$q%";
    $params[':q2'] = "%$q%";
    $params[':q3'] = "%$q%";
}
if ($categoryId !== '' && ctype_digit((string)$categoryId)) {
    $sql .= " AND r.category_id = :cat";
    $params[':cat'] = (int)$categoryId;
}
$sql .= " ORDER BY r.is_featured DESC, r.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rhythms = $stmt->fetchAll();

// Categories available to this visitor, for the filter bar
$catVis = visibility_sql('c', null);
$catSql = "SELECT c.* FROM categories c WHERE ({$catVis['sql']}) ORDER BY c.name ASC";
$catStmt = $db->prepare($catSql);
$catStmt->execute($catVis['params']);
$categories = $catStmt->fetchAll();

$downloadMsg = flash('download_msg');
$pageTitle = 'Library | Dhaa Baja';
$activePage = 'library';
include __DIR__ . '/includes/head.php';
?>
<body class="font-body-md text-on-surface">
<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="max-w-container-max mx-auto px-gutter py-section-gap">
  <div class="mb-16 text-center">
    <h1 class="font-display-lg text-display-lg text-primary mb-4">The Rhythm Library</h1>
    <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto">
      Explore a curated collection of ancestral beats, transcribed for the modern soul.
    </p>
  </div>

  <?php if ($downloadMsg): ?>
    <div class="mb-8 bg-secondary-container/40 border border-secondary text-on-secondary-container px-6 py-4 rounded text-center"><?= e($downloadMsg) ?></div>
  <?php endif; ?>

  <!-- Filters -->
  <form method="get" class="flex flex-wrap gap-3 mb-12 justify-center">
    <input type="hidden" name="q" value="<?= e($q) ?>">
    <button name="category" value="" class="px-6 py-2 rounded-full font-label-md text-label-md border transition-colors <?= $categoryId === '' ? 'bg-secondary-container text-on-secondary-container border-transparent' : 'bg-surface-container text-on-surface-variant border-outline-variant hover:bg-surface-container-high' ?>">All Rhythms</button>
    <?php foreach ($categories as $c): ?>
      <button name="category" value="<?= (int)$c['id'] ?>" class="px-6 py-2 rounded-full font-label-md text-label-md border transition-colors <?= (string)$categoryId === (string)$c['id'] ? 'bg-secondary-container text-on-secondary-container border-transparent' : 'bg-surface-container text-on-surface-variant border-outline-variant hover:bg-surface-container-high' ?>"><?= e($c['name']) ?></button>
    <?php endforeach; ?>
  </form>

  <?php if (!$rhythms): ?>
    <p class="text-center text-on-surface-variant">No rhythms match your search yet.</p>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <?php foreach ($rhythms as $r): ?>
    <div id="rhythm-<?= (int)$r['id'] ?>" class="parchment-card border border-outline-variant/30 p-8 flex flex-col rounded-xl">
      <div class="flex justify-between items-start mb-6">
        <span class="bg-secondary/10 text-secondary px-3 py-1 font-label-md text-label-md rounded-full uppercase"><?= e($r['category_name'] ?? 'Uncategorized') ?></span>
        <span class="font-headline-md text-headline-md text-primary/30">#<?= (int)$r['id'] ?></span>
      </div>
      <h2 class="font-headline-lg text-[26px] text-primary mb-3 leading-tight"><?= e($r['title']) ?></h2>
      <p class="font-body-md text-body-md text-on-surface-variant mb-6 italic flex-grow"><?= e($r['description']) ?></p>
      <div class="flex items-center justify-between mt-auto pt-6 border-t border-outline-variant/50 mb-4">
        <span class="font-label-md text-label-md text-outline"><?= gmdate('i:s', (int)$r['duration_seconds']) ?> &middot; <?= (int)$r['play_count'] ?> downloads</span>
      </div>
      <?php if (!empty($r['sheet_original_name'])): ?>
        <a href="api/download?rhythm_id=<?= (int)$r['id'] ?>" class="w-full inline-flex items-center justify-center gap-2 bg-primary text-on-primary py-3 rounded-lg font-label-md text-label-md hover:opacity-90 transition-all">
          <span class="material-symbols-outlined text-lg">download</span> Download Sheet Music
        </a>
      <?php else: ?>
        <span class="w-full inline-flex items-center justify-center gap-2 bg-surface-container-high text-on-surface-variant py-3 rounded-lg font-label-md text-label-md cursor-not-allowed">
          <span class="material-symbols-outlined text-lg">hourglass_empty</span> Sheet Music Coming Soon
        </span>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

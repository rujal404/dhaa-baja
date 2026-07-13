<?php
$pageTitle = 'Rhythms | Admin';
$activeAdmin = 'rhythms';
include __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $rid = (int)$_POST['id'];
    $action = $_POST['action'];
    if ($action === 'delete') {
        $db->prepare('DELETE FROM rhythms WHERE id = :id')->execute([':id' => $rid]);
        flash('admin_msg', 'Rhythm deleted.');
    } elseif ($action === 'toggle_enabled') {
        $db->prepare("UPDATE rhythms SET is_enabled = IF(is_enabled=1,0,1) WHERE id = :id")->execute([':id' => $rid]);
        flash('admin_msg', 'Rhythm visibility updated.');
    }
    header('Location: rhythms');
    exit;
}

$rhythms = $db->query("
    SELECT r.id, r.title, r.slug, r.category_id, r.description, r.duration_seconds,
        r.image_url, r.audio_url, r.sheet_original_name, r.is_featured, r.is_enabled,
        r.visibility, r.play_count, r.created_at,
        c.name AS category_name, c.is_enabled AS category_enabled,
        (SELECT COUNT(*) FROM rhythm_access ra WHERE ra.rhythm_id = r.id) AS access_count
    FROM rhythms r
    LEFT JOIN categories c ON c.id = r.category_id
    ORDER BY r.created_at DESC
")->fetchAll();
$msg = flash('admin_msg');
?>
<div class="flex justify-between items-center mb-8">
  <div>
    <h1 class="font-headline text-3xl text-primary mb-1">Rhythms</h1>
    <p class="text-on-surface-variant">Manage the rhythm library catalogue.</p>
  </div>
  <a href="rhythm_form" class="bg-primary text-on-primary px-5 py-3 rounded-lg text-sm font-bold flex items-center gap-2 hover:opacity-90">
    <span class="material-symbols-outlined text-lg">add</span> New Rhythm
  </a>
</div>

<?php if ($msg): ?><div class="mb-6 bg-secondary-container/40 px-4 py-3 rounded text-sm"><?= e($msg) ?></div><?php endif; ?>

<div class="bg-surface border border-outline-variant rounded-xl overflow-x-auto">
  <table class="w-full text-sm min-w-[900px]">
    <thead>
      <tr class="text-left border-b border-outline-variant text-xs uppercase tracking-wider text-on-surface-variant">
        <th class="px-6 py-4">Title</th>
        <th class="px-6 py-4">Category</th>
        <th class="px-6 py-4">Sheet Music</th>
        <th class="px-6 py-4">Status</th>
        <th class="px-6 py-4">Visibility</th>
        <th class="px-6 py-4">Featured</th>
        <th class="px-6 py-4 text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rhythms as $r): ?>
      <tr class="border-b border-outline-variant/50 last:border-0">
        <td class="px-6 py-4 font-medium"><?= e($r['title']) ?></td>
        <td class="px-6 py-4">
          <?php if ($r['category_name']): ?>
            <?= e($r['category_name']) ?><?php if (!$r['category_enabled']): ?><span class="text-xs text-red-600 block">(category disabled)</span><?php endif; ?>
          <?php else: ?>
            <span class="text-on-surface-variant text-xs italic">Uncategorized</span>
          <?php endif; ?>
        </td>
        <td class="px-6 py-4">
          <?php if (!empty($r['sheet_original_name'])): ?>
            <a href="view_sheet?id=<?= (int)$r['id'] ?>" target="_blank" class="text-secondary hover:underline flex items-center gap-1">
              <span class="material-symbols-outlined text-sm">description</span> <?= e($r['sheet_original_name']) ?>
            </a>
          <?php else: ?>
            <span class="text-on-surface-variant text-xs italic">Not uploaded</span>
          <?php endif; ?>
        </td>
        <td class="px-6 py-4">
          <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $r['is_enabled'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= $r['is_enabled'] ? 'Enabled' : 'Disabled' ?></span>
        </td>
        <td class="px-6 py-4">
          <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $r['visibility'] === 'restricted' ? 'bg-yellow-100 text-yellow-700' : 'bg-surface-container text-on-surface-variant' ?>"><?= e($r['visibility']) ?></span>
          <?php if ((int)$r['access_count'] > 0): ?>
            <span class="text-xs text-on-surface-variant block mt-1"><?= (int)$r['access_count'] ?> user override(s)</span>
          <?php endif; ?>
        </td>
        <td class="px-6 py-4"><?= $r['is_featured'] ? '★ Yes' : '—' ?></td>
        <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
          <a href="rhythm_form?id=<?= (int)$r['id'] ?>" class="text-secondary hover:underline">Edit</a>
          <form method="post" class="inline">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="toggle_enabled">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="text-yellow-700 hover:underline" type="submit"><?= $r['is_enabled'] ? 'Disable' : 'Enable' ?></button>
          </form>
          <form method="post" class="inline" data-confirm="Delete this rhythm?">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="text-red-600 hover:underline" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rhythms): ?>
        <tr><td colspan="7" class="px-6 py-8 text-center text-on-surface-variant">No rhythms yet — add the first one.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

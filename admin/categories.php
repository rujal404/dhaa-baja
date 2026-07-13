<?php
$pageTitle = 'Categories | Admin';
$activeAdmin = 'categories';
include __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $catId = (int)$_POST['id'];
    $action = $_POST['action'];

    if ($action === 'toggle_enabled') {
        $db->prepare("UPDATE categories SET is_enabled = IF(is_enabled=1,0,1) WHERE id = :id")->execute([':id' => $catId]);
        flash('admin_msg', 'Category visibility updated.');
    } elseif ($action === 'delete') {
        // Rhythms in this category keep existing (category_id becomes NULL via FK ON DELETE SET NULL)
        $db->prepare('DELETE FROM categories WHERE id = :id')->execute([':id' => $catId]);
        flash('admin_msg', 'Category deleted. Rhythms in it are now uncategorized.');
    }
    header('Location: categories');
    exit;
}

$categories = $db->query("
    SELECT c.*, COUNT(r.id) AS rhythm_count,
        (SELECT COUNT(*) FROM category_access ca WHERE ca.category_id = c.id) AS access_count
    FROM categories c
    LEFT JOIN rhythms r ON r.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();
$msg = flash('admin_msg');
?>
<div class="flex justify-between items-center mb-8">
  <div>
    <h1 class="font-headline text-3xl text-primary mb-1">Categories</h1>
    <p class="text-on-surface-variant">Organize the rhythm library and control who can see each category.</p>
  </div>
  <a href="category_form" class="bg-primary text-on-primary px-5 py-3 rounded-lg text-sm font-bold flex items-center gap-2 hover:opacity-90">
    <span class="material-symbols-outlined text-lg">add</span> New Category
  </a>
</div>

<?php if ($msg): ?><div class="mb-6 bg-secondary-container/40 px-4 py-3 rounded text-sm"><?= e($msg) ?></div><?php endif; ?>

<div class="bg-surface border border-outline-variant rounded-xl overflow-x-auto">
  <table class="w-full text-sm min-w-[750px]">
    <thead>
      <tr class="text-left border-b border-outline-variant text-xs uppercase tracking-wider text-on-surface-variant">
        <th class="px-6 py-4">Name</th>
        <th class="px-6 py-4">Rhythms</th>
        <th class="px-6 py-4">Status</th>
        <th class="px-6 py-4">Visibility</th>
        <th class="px-6 py-4 text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $c): ?>
      <tr class="border-b border-outline-variant/50 last:border-0">
        <td class="px-6 py-4 font-medium"><?= e($c['name']) ?><?php if ($c['description']): ?><br><span class="text-xs text-on-surface-variant font-normal"><?= e($c['description']) ?></span><?php endif; ?></td>
        <td class="px-6 py-4"><?= (int)$c['rhythm_count'] ?></td>
        <td class="px-6 py-4">
          <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $c['is_enabled'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= $c['is_enabled'] ? 'Enabled' : 'Disabled' ?></span>
        </td>
        <td class="px-6 py-4">
          <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $c['visibility'] === 'restricted' ? 'bg-yellow-100 text-yellow-700' : 'bg-surface-container text-on-surface-variant' ?>"><?= e($c['visibility']) ?></span>
          <?php if ((int)$c['access_count'] > 0): ?>
            <span class="text-xs text-on-surface-variant block mt-1"><?= (int)$c['access_count'] ?> user override(s)</span>
          <?php endif; ?>
        </td>
        <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
          <a href="category_form?id=<?= (int)$c['id'] ?>" class="text-secondary hover:underline">Edit</a>
          <form method="post" class="inline">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="toggle_enabled">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="text-yellow-700 hover:underline" type="submit"><?= $c['is_enabled'] ? 'Disable' : 'Enable' ?></button>
          </form>
          <form method="post" class="inline" data-confirm="Delete this category? Rhythms inside it will become uncategorized.">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="text-red-600 hover:underline" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$categories): ?>
        <tr><td colspan="5" class="px-6 py-8 text-center text-on-surface-variant">No categories yet — add the first one.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

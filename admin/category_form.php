<?php
$pageTitle = 'Category Form | Admin';
$activeAdmin = 'categories';
include __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$category = ['name'=>'','description'=>'','is_enabled'=>1,'visibility'=>'public'];
$overrides = []; // user_id => 1 (force enabled) or 0 (force disabled)

if ($id) {
    $stmt = $db->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $found = $stmt->fetch();
    if ($found) $category = $found;

    $stmt = $db->prepare('SELECT user_id, enabled FROM category_access WHERE category_id = :id');
    $stmt->execute([':id' => $id]);
    foreach ($stmt->fetchAll() as $row) {
        $overrides[(int)$row['user_id']] = (int)$row['enabled'];
    }
}

$allUsers = $db->query("SELECT id, first_name, last_name, email, role FROM users ORDER BY first_name ASC")->fetchAll();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expired, please try again.';
    } else {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
        $visibility = $_POST['visibility'] === 'restricted' ? 'restricted' : 'public';
        $userOverrides = $_POST['user_override'] ?? []; // user_id => 'default'|'enabled'|'disabled'
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));

        if ($name === '') {
            $error = 'Category name is required.';
        } else {
            if ($id) {
                $stmt = $db->prepare('UPDATE categories SET name=:n, slug=:s, description=:d, is_enabled=:e, visibility=:v WHERE id=:id');
                $stmt->execute([':n'=>$name, ':s'=>$slug, ':d'=>$description, ':e'=>$isEnabled, ':v'=>$visibility, ':id'=>$id]);
            } else {
                $stmt = $db->prepare('INSERT INTO categories (name, slug, description, is_enabled, visibility) VALUES (:n,:s,:d,:e,:v)');
                $stmt->execute([':n'=>$name, ':s'=>$slug . '-' . substr(md5((string)time()),0,5), ':d'=>$description, ':e'=>$isEnabled, ':v'=>$visibility]);
                $id = (int)$db->lastInsertId();
            }

            // Rebuild the per-user override rows to match the submitted selections
            $db->prepare('DELETE FROM category_access WHERE category_id = :id')->execute([':id' => $id]);
            $ins = $db->prepare('INSERT INTO category_access (category_id, user_id, enabled) VALUES (:c, :u, :e)');
            foreach ($userOverrides as $uid => $choice) {
                $uid = (int)$uid;
                if ($choice === 'enabled') {
                    $ins->execute([':c' => $id, ':u' => $uid, ':e' => 1]);
                } elseif ($choice === 'disabled') {
                    $ins->execute([':c' => $id, ':u' => $uid, ':e' => 0]);
                }
                // 'default' -> no row, falls back to the category's default visibility
            }

            flash('admin_msg', 'Category saved.');
            header('Location: categories'); exit;
        }
    }
}
?>
<h1 class="font-headline text-3xl text-primary mb-1"><?= $id ? 'Edit Category' : 'New Category' ?></h1>
<p class="text-on-surface-variant mb-8">Categories organize the rhythm library and can be hidden or restricted.</p>

<?php if ($error): ?><div class="mb-6 bg-red-50 text-red-700 px-4 py-3 rounded text-sm"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="bg-surface border border-outline-variant rounded-xl p-8 max-w-2xl space-y-6">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Name</label>
    <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="name" required value="<?= e($category['name']) ?>">
  </div>

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Description (optional)</label>
    <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="description" value="<?= e($category['description']) ?>">
  </div>

  <div class="flex items-center gap-2">
    <input type="checkbox" name="is_enabled" id="is_enabled" <?= $category['is_enabled'] ? 'checked' : '' ?>>
    <label for="is_enabled" class="text-sm">Category is enabled (master on/off switch — disabling hides it from everyone, including overrides)</label>
  </div>

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-2">Default Visibility</label>
    <div class="flex gap-6">
      <label class="flex items-center gap-2">
        <input type="radio" name="visibility" value="public" <?= $category['visibility'] === 'public' ? 'checked' : '' ?>>
        <span class="text-sm">Public — visible to everyone by default</span>
      </label>
      <label class="flex items-center gap-2">
        <input type="radio" name="visibility" value="restricted" <?= $category['visibility'] === 'restricted' ? 'checked' : '' ?>>
        <span class="text-sm">Restricted — hidden from everyone by default</span>
      </label>
    </div>
  </div>

  <div class="bg-surface-container rounded-lg p-4 max-h-80 overflow-y-auto">
    <p class="text-xs uppercase tracking-widest text-on-surface-variant mb-1">Per-user overrides</p>
    <p class="text-xs text-on-surface-variant mb-3">Set a specific user to <strong>Enabled</strong> or <strong>Disabled</strong> to override the default above just for them. Leave on <strong>Default</strong> to use the setting above.</p>
    <?php foreach ($allUsers as $u): $current = $overrides[$u['id']] ?? null; ?>
      <div class="flex items-center justify-between gap-4 py-2 border-b border-outline-variant/30 last:border-0">
        <span class="text-sm"><?= e($u['first_name'] . ' ' . $u['last_name']) ?> <span class="text-on-surface-variant text-xs">(<?= e($u['email']) ?> &middot; <?= e($u['role']) ?>)</span></span>
        <select name="user_override[<?= (int)$u['id'] ?>]" class="border border-outline-variant rounded px-2 py-1 text-sm">
          <option value="default" <?= $current === null ? 'selected' : '' ?>>Default</option>
          <option value="enabled" <?= $current === 1 ? 'selected' : '' ?>>Enabled</option>
          <option value="disabled" <?= $current === 0 ? 'selected' : '' ?>>Disabled</option>
        </select>
      </div>
    <?php endforeach; ?>
    <?php if (!$allUsers): ?><p class="text-xs text-on-surface-variant">No registered users yet.</p><?php endif; ?>
  </div>

  <div class="flex gap-3 pt-2">
    <button class="bg-primary text-on-primary px-6 py-3 rounded-lg font-bold text-sm hover:opacity-90" type="submit">Save Category</button>
    <a href="categories" class="px-6 py-3 rounded-lg font-bold text-sm border border-outline-variant">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>

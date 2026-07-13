<?php
$pageTitle = 'Users | Admin';
$activeAdmin = 'users';
include __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $targetId = (int)$_POST['id'];
    $action = $_POST['action'];

    if ($action === 'toggle_status' && $targetId !== (int)$admin['id']) {
        $db->prepare("UPDATE users SET status = IF(status='active','suspended','active') WHERE id = :id")->execute([':id' => $targetId]);
        flash('admin_msg', 'User status updated.');
    } elseif ($action === 'toggle_role' && $targetId !== (int)$admin['id']) {
        $db->prepare("UPDATE users SET role = IF(role='admin','user','admin') WHERE id = :id")->execute([':id' => $targetId]);
        flash('admin_msg', 'User role updated.');
    } elseif ($action === 'delete' && $targetId !== (int)$admin['id']) {
        $db->prepare('DELETE FROM users WHERE id = :id')->execute([':id' => $targetId]);
        flash('admin_msg', 'User deleted.');
    }
    header('Location: users');
    exit;
}

$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$msg = flash('admin_msg');
?>
<h1 class="font-headline text-3xl text-primary mb-1">Users</h1>
<p class="text-on-surface-variant mb-8">Manage member and admin accounts.</p>

<?php if ($msg): ?><div class="mb-6 bg-secondary-container/40 px-4 py-3 rounded text-sm"><?= e($msg) ?></div><?php endif; ?>

<div class="bg-surface border border-outline-variant rounded-xl overflow-x-auto">
  <table class="w-full text-sm min-w-[700px]">
    <thead>
      <tr class="text-left border-b border-outline-variant text-xs uppercase tracking-wider text-on-surface-variant">
        <th class="px-6 py-4">Name</th>
        <th class="px-6 py-4">Email</th>
        <th class="px-6 py-4">Role</th>
        <th class="px-6 py-4">Status</th>
        <th class="px-6 py-4">Joined</th>
        <th class="px-6 py-4 text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr class="border-b border-outline-variant/50 last:border-0">
        <td class="px-6 py-4 font-medium"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
        <td class="px-6 py-4"><?= e($u['email']) ?></td>
        <td class="px-6 py-4">
          <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $u['role'] === 'admin' ? 'bg-primary/10 text-primary' : 'bg-surface-container text-on-surface-variant' ?>"><?= e($u['role']) ?></span>
        </td>
        <td class="px-6 py-4">
          <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $u['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= e($u['status']) ?></span>
        </td>
        <td class="px-6 py-4"><?= (new DateTime($u['created_at']))->format('M d, Y') ?></td>
        <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
          <?php if ((int)$u['id'] !== (int)$admin['id']): ?>
            <form method="post" class="inline">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <input type="hidden" name="action" value="toggle_role">
              <button class="text-secondary hover:underline" type="submit">Make <?= $u['role'] === 'admin' ? 'User' : 'Admin' ?></button>
            </form>
            <form method="post" class="inline">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <input type="hidden" name="action" value="toggle_status">
              <button class="text-yellow-700 hover:underline" type="submit"><?= $u['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button>
            </form>
            <form method="post" class="inline" data-confirm="Delete this user?">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button class="text-red-600 hover:underline" type="submit">Delete</button>
            </form>
          <?php else: ?>
            <span class="text-xs text-on-surface-variant italic">This is you</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

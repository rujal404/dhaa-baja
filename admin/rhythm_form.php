<?php
$pageTitle = 'Rhythm Form | Admin';
$activeAdmin = 'rhythms';
include __DIR__ . '/includes/header.php';

define('SHEET_ALLOWED_EXT', ['pdf', 'jpg', 'jpeg', 'png', 'gif']);
define('SHEET_MAX_BYTES', 10 * 1024 * 1024); // 10 MB
define('SHEET_MIME_MAP', [
    'pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png', 'gif' => 'image/gif',
]);

$id = (int)($_GET['id'] ?? 0);
// Sheet music is stored as a BLOB in the database (not on local disk) so it
// survives on serverless hosts like Vercel, where the filesystem is read-only
// / ephemeral. We select sheet_original_name here (cheap) to know whether a
// file exists without pulling the whole BLOB into this page's memory.
$rhythm = ['title'=>'','category_id'=>null,'description'=>'','duration_seconds'=>0,'image_url'=>'','audio_url'=>'','sheet_original_name'=>null,'is_featured'=>0,'is_enabled'=>1,'visibility'=>'public'];
$hasSheet = false;
$overrides = []; // user_id => 1 (force enabled) or 0 (force disabled)

if ($id) {
    $stmt = $db->prepare('SELECT id, title, category_id, description, duration_seconds, image_url, audio_url, sheet_original_name, is_featured, is_enabled, visibility FROM rhythms WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $found = $stmt->fetch();
    if ($found) {
        $rhythm = $found;
        $hasSheet = !empty($found['sheet_original_name']);
    }

    $stmt = $db->prepare('SELECT user_id, enabled FROM rhythm_access WHERE rhythm_id = :id');
    $stmt->execute([':id' => $id]);
    foreach ($stmt->fetchAll() as $row) {
        $overrides[(int)$row['user_id']] = (int)$row['enabled'];
    }
}

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$allUsers = $db->query("SELECT id, first_name, last_name, email, role FROM users ORDER BY first_name ASC")->fetchAll();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expired, please try again.';
    } else {
        $title = trim($_POST['title']);
        $categoryId = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
        $description = trim($_POST['description']);
        $duration = (int)$_POST['duration_seconds'];
        $imageUrl = trim($_POST['image_url']);
        $audioUrl = trim($_POST['audio_url']);
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
        $visibility = $_POST['visibility'] === 'restricted' ? 'restricted' : 'public';
        $userOverrides = $_POST['user_override'] ?? []; // user_id => 'default'|'enabled'|'disabled'
        $removeSheet = isset($_POST['remove_sheet']);
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));

        // Sheet music: null = "leave unchanged" (only meaningful on UPDATE),
        // false = "clear it", or [data, mime, name] = "set/replace it".
        $sheetAction = null;

        if ($removeSheet) {
            $sheetAction = false;
        }

        // A new upload takes priority over the remove checkbox.
        if (!empty($_FILES['sheet_file']['name']) && $_FILES['sheet_file']['error'] === UPLOAD_ERR_OK) {
            $originalName = basename($_FILES['sheet_file']['name']);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($ext, SHEET_ALLOWED_EXT, true)) {
                $error = 'Sheet music must be a PDF, JPG, PNG, or GIF file.';
            } elseif ($_FILES['sheet_file']['size'] > SHEET_MAX_BYTES) {
                $error = 'File is too large (max 10MB).';
            } else {
                $contents = file_get_contents($_FILES['sheet_file']['tmp_name']);
                if ($contents === false) {
                    $error = 'Could not read the uploaded file. Please try again.';
                } else {
                    $sheetAction = [$contents, SHEET_MIME_MAP[$ext] ?? 'application/octet-stream', $originalName];
                }
            }
        }

        if (!$error && $title === '') {
            $error = 'Title is required.';
        }

        if (!$error) {
            // Build the sheet-music portion of the query only when it's actually changing,
            // so editing other fields never touches (or re-uploads) the BLOB unnecessarily.
            $sheetSql = '';
            $sheetParams = [];
            if ($sheetAction === false) {
                $sheetSql = ', sheet_data=NULL, sheet_mime=NULL, sheet_original_name=NULL';
            } elseif (is_array($sheetAction)) {
                $sheetSql = ', sheet_data=:sd, sheet_mime=:sm, sheet_original_name=:sn';
                $sheetParams = [':sd' => $sheetAction[0], ':sm' => $sheetAction[1], ':sn' => $sheetAction[2]];
            }

            if ($id) {
                $stmt = $db->prepare("UPDATE rhythms SET title=:t, slug=:s, category_id=:cat, description=:d, duration_seconds=:dur, image_url=:img, audio_url=:aud, is_featured=:f, is_enabled=:en, visibility=:vis $sheetSql WHERE id=:id");
                $stmt->execute([':t'=>$title, ':s'=>$slug, ':cat'=>$categoryId, ':d'=>$description, ':dur'=>$duration, ':img'=>$imageUrl, ':aud'=>$audioUrl, ':f'=>$featured, ':en'=>$isEnabled, ':vis'=>$visibility, ':id'=>$id] + $sheetParams);
            } else {
                $stmt = $db->prepare("INSERT INTO rhythms (title, slug, category_id, description, duration_seconds, image_url, audio_url, is_featured, is_enabled, visibility" . (is_array($sheetAction) ? ', sheet_data, sheet_mime, sheet_original_name' : '') . ") VALUES (:t,:s,:cat,:d,:dur,:img,:aud,:f,:en,:vis" . (is_array($sheetAction) ? ',:sd,:sm,:sn' : '') . ")");
                $stmt->execute([':t'=>$title, ':s'=>$slug . '-' . substr(md5((string)time()),0,5), ':cat'=>$categoryId, ':d'=>$description, ':dur'=>$duration, ':img'=>$imageUrl, ':aud'=>$audioUrl, ':f'=>$featured, ':en'=>$isEnabled, ':vis'=>$visibility] + $sheetParams);
                $id = (int)$db->lastInsertId();
            }

            // Rebuild the per-user override rows to match the submitted selections
            $db->prepare('DELETE FROM rhythm_access WHERE rhythm_id = :id')->execute([':id' => $id]);
            $ins = $db->prepare('INSERT INTO rhythm_access (rhythm_id, user_id, enabled) VALUES (:r, :u, :e)');
            foreach ($userOverrides as $uid => $choice) {
                $uid = (int)$uid;
                if ($choice === 'enabled') {
                    $ins->execute([':r' => $id, ':u' => $uid, ':e' => 1]);
                } elseif ($choice === 'disabled') {
                    $ins->execute([':r' => $id, ':u' => $uid, ':e' => 0]);
                }
                // 'default' -> no row, falls back to the rhythm's default visibility
            }

            flash('admin_msg', $id ? 'Rhythm saved.' : 'Rhythm created.');
            header('Location: rhythms'); exit;
        }

        // repopulate on error
        $rhythm = compact('title','description') + ['category_id'=>$categoryId,'duration_seconds'=>$duration,'image_url'=>$imageUrl,'audio_url'=>$audioUrl,'sheet_original_name'=>is_array($sheetAction) ? $sheetAction[2] : ($sheetAction === false ? null : $rhythm['sheet_original_name']),'is_featured'=>$featured,'is_enabled'=>$isEnabled,'visibility'=>$visibility];
        $hasSheet = !empty($rhythm['sheet_original_name']);
        $overrides = [];
        foreach ($userOverrides as $uid => $choice) {
            if ($choice === 'enabled') $overrides[(int)$uid] = 1;
            elseif ($choice === 'disabled') $overrides[(int)$uid] = 0;
        }
    }
}
?>
<h1 class="font-headline text-3xl text-primary mb-1"><?= $id ? 'Edit Rhythm' : 'New Rhythm' ?></h1>
<p class="text-on-surface-variant mb-8">Fill in the details for this rhythm.</p>

<?php if ($error): ?><div class="mb-6 bg-red-50 text-red-700 px-4 py-3 rounded text-sm"><?= e($error) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="bg-surface border border-outline-variant rounded-xl p-8 max-w-2xl space-y-6">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Title</label>
    <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="title" required value="<?= e($rhythm['title']) ?>">
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Category</label>
      <select class="w-full border border-outline-variant rounded-lg px-4 py-2" name="category_id">
        <option value="">— Uncategorized —</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)($rhythm['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
            <?= e($c['name']) ?><?= $c['is_enabled'] ? '' : ' (disabled)' ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (!$categories): ?>
        <p class="text-xs text-on-surface-variant mt-1">No categories yet — <a href="categories" class="text-secondary hover:underline">create one first</a>.</p>
      <?php endif; ?>
    </div>
    <div>
      <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Duration (seconds)</label>
      <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="duration_seconds" type="number" min="0" value="<?= e((string)$rhythm['duration_seconds']) ?>">
    </div>
  </div>

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Description</label>
    <textarea class="w-full border border-outline-variant rounded-lg px-4 py-2" name="description" rows="4"><?= e($rhythm['description']) ?></textarea>
  </div>

  <div class="flex items-center gap-2">
    <input type="checkbox" name="is_featured" id="is_featured" <?= $rhythm['is_featured'] ? 'checked' : '' ?>>
    <label for="is_featured" class="text-sm">Feature on homepage</label>
  </div>

  <div class="flex items-center gap-2">
    <input type="checkbox" name="is_enabled" id="is_enabled" <?= $rhythm['is_enabled'] ? 'checked' : '' ?>>
    <label for="is_enabled" class="text-sm">Rhythm is enabled (master on/off switch — disabling hides it from everyone, including overrides)</label>
  </div>

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-2">Default Visibility</label>
    <div class="flex gap-6">
      <label class="flex items-center gap-2">
        <input type="radio" name="visibility" value="public" <?= $rhythm['visibility'] === 'public' ? 'checked' : '' ?>>
        <span class="text-sm">Public — visible to everyone by default</span>
      </label>
      <label class="flex items-center gap-2">
        <input type="radio" name="visibility" value="restricted" <?= $rhythm['visibility'] === 'restricted' ? 'checked' : '' ?>>
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

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Image URL (optional)</label>
    <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="image_url" value="<?= e($rhythm['image_url']) ?>">
  </div>
  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Audio URL (optional)</label>
    <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="audio_url" value="<?= e($rhythm['audio_url']) ?>">
  </div>

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-2">Music Sheet File</label>

    <?php if ($hasSheet && $id): ?>
      <div class="flex items-center justify-between bg-surface-container rounded-lg px-4 py-3 mb-3">
        <a href="view_sheet?id=<?= (int)$id ?>" target="_blank" class="text-secondary hover:underline flex items-center gap-2 text-sm">
          <span class="material-symbols-outlined text-lg">description</span> <?= e($rhythm['sheet_original_name']) ?>
        </a>
        <label class="flex items-center gap-2 text-xs text-red-600">
          <input type="checkbox" name="remove_sheet"> Remove file
        </label>
      </div>
    <?php endif; ?>

    <div class="upload-dropzone">
      <input type="file" id="sheet_file" name="sheet_file" accept=".pdf,.jpg,.jpeg,.png,.gif" class="block mx-auto text-sm">
      <p id="sheet_file_label" class="text-xs text-on-surface-variant mt-2">Drag a PDF or image here, or click to browse (max 10MB) — stored in the database</p>
    </div>
  </div>

  <div class="flex gap-3 pt-2">
    <button class="bg-primary text-on-primary px-6 py-3 rounded-lg font-bold text-sm hover:opacity-90" type="submit">Save Rhythm</button>
    <a href="rhythms" class="px-6 py-3 rounded-lg font-bold text-sm border border-outline-variant">Cancel</a>
  </div>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
$pageTitle = 'Event Form | Admin';
$activeAdmin = 'events';
include __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$event = ['title'=>'','category'=>'Workshop','description'=>'','event_date'=>date('Y-m-d'),'event_time'=>'18:00','location'=>'','price'=>0,'is_free'=>0,'capacity'=>'','image_url'=>''];
if ($id) {
    $stmt = $db->prepare('SELECT * FROM events WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $found = $stmt->fetch();
    if ($found) $event = $found;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Session expired, please try again.';
    } else {
        $title = trim($_POST['title']);
        $category = $_POST['category'];
        $description = trim($_POST['description']);
        $eventDate = $_POST['event_date'];
        $eventTime = $_POST['event_time'] ?: null;
        $location = trim($_POST['location']);
        $price = (float)$_POST['price'];
        $isFree = isset($_POST['is_free']) ? 1 : 0;
        $capacity = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;
        $imageUrl = trim($_POST['image_url']);

        if ($title === '' || $eventDate === '') {
            $error = 'Title and date are required.';
        } elseif ($id) {
            $stmt = $db->prepare('UPDATE events SET title=:t, category=:c, description=:d, event_date=:ed, event_time=:et, location=:loc, price=:p, is_free=:f, capacity=:cap, image_url=:img WHERE id=:id');
            $stmt->execute([':t'=>$title, ':c'=>$category, ':d'=>$description, ':ed'=>$eventDate, ':et'=>$eventTime, ':loc'=>$location, ':p'=>$price, ':f'=>$isFree, ':cap'=>$capacity, ':img'=>$imageUrl, ':id'=>$id]);
            flash('admin_msg', 'Event updated.');
            header('Location: events'); exit;
        } else {
            $stmt = $db->prepare('INSERT INTO events (title, category, description, event_date, event_time, location, price, is_free, capacity, image_url) VALUES (:t,:c,:d,:ed,:et,:loc,:p,:f,:cap,:img)');
            $stmt->execute([':t'=>$title, ':c'=>$category, ':d'=>$description, ':ed'=>$eventDate, ':et'=>$eventTime, ':loc'=>$location, ':p'=>$price, ':f'=>$isFree, ':cap'=>$capacity, ':img'=>$imageUrl]);
            flash('admin_msg', 'Event created.');
            header('Location: events'); exit;
        }
    }
}
?>
<h1 class="font-headline text-3xl text-primary mb-1"><?= $id ? 'Edit Event' : 'New Event' ?></h1>
<p class="text-on-surface-variant mb-8">Fill in the details for this gathering.</p>

<?php if ($error): ?><div class="mb-6 bg-red-50 text-red-700 px-4 py-3 rounded text-sm"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="bg-surface border border-outline-variant rounded-xl p-8 max-w-2xl space-y-6">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Title</label>
    <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="title" required value="<?= e($event['title']) ?>">
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Category</label>
      <select class="w-full border border-outline-variant rounded-lg px-4 py-2" name="category">
        <?php foreach (['Workshop','Live Performance','Community Circle','Exhibition'] as $c): ?>
          <option value="<?= e($c) ?>" <?= $event['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Location</label>
      <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="location" value="<?= e($event['location']) ?>">
    </div>
  </div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Date</label>
      <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="event_date" type="date" required value="<?= e($event['event_date']) ?>">
    </div>
    <div>
      <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Time</label>
      <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="event_time" type="time" value="<?= e(substr($event['event_time'] ?? '18:00', 0, 5)) ?>">
    </div>
  </div>

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Description</label>
    <textarea class="w-full border border-outline-variant rounded-lg px-4 py-2" name="description" rows="4"><?= e($event['description']) ?></textarea>
  </div>

  <div class="grid grid-cols-3 gap-4 items-end">
    <div>
      <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Price (USD)</label>
      <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="price" type="number" step="0.01" min="0" value="<?= e((string)$event['price']) ?>">
    </div>
    <div>
      <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Capacity</label>
      <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="capacity" type="number" min="0" value="<?= e((string)($event['capacity'] ?? '')) ?>">
    </div>
    <label class="flex items-center gap-2 pb-2">
      <input type="checkbox" name="is_free" <?= $event['is_free'] ? 'checked' : '' ?>>
      <span class="text-sm">This event is free</span>
    </label>
  </div>

  <div>
    <label class="block text-xs uppercase tracking-widest text-on-surface-variant mb-1">Image URL (optional)</label>
    <input class="w-full border border-outline-variant rounded-lg px-4 py-2" name="image_url" value="<?= e($event['image_url']) ?>">
  </div>

  <div class="flex gap-3 pt-2">
    <button class="bg-primary text-on-primary px-6 py-3 rounded-lg font-bold text-sm hover:opacity-90" type="submit">Save Event</button>
    <a href="events" class="px-6 py-3 rounded-lg font-bold text-sm border border-outline-variant">Cancel</a>
  </div>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

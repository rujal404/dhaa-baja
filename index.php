<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$vis = visibility_sql('c', 'r');

$RHYTHM_COLS = "r.id, r.title, r.slug, r.category_id, r.description, r.duration_seconds,
    r.image_url, r.audio_url, r.is_featured, r.is_enabled, r.visibility, r.play_count, r.created_at";

$featuredStmt = $db->prepare("
    SELECT $RHYTHM_COLS, c.name AS category_name FROM rhythms r
    LEFT JOIN categories c ON c.id = r.category_id
    WHERE r.is_featured = 1 AND ({$vis['sql']})
    ORDER BY r.created_at DESC LIMIT 1
");
$featuredStmt->execute($vis['params']);
$featured = $featuredStmt->fetch();

$othersStmt = $db->prepare("
    SELECT $RHYTHM_COLS, c.name AS category_name FROM rhythms r
    LEFT JOIN categories c ON c.id = r.category_id
    WHERE ({$vis['sql']})
    ORDER BY r.is_featured DESC, r.created_at DESC LIMIT 4
");
$othersStmt->execute($vis['params']);
$others = $othersStmt->fetchAll();

$nextEvents = $db->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3")->fetchAll();

$pageTitle = 'Dhaa Baja | Ancestral Rhythms';
$activePage = 'home';
include __DIR__ . '/includes/head.php';
?>
<body class="font-body-md text-on-surface">
<?php include __DIR__ . '/includes/nav.php'; ?>

<!-- Hero -->
<section class="relative min-h-[640px] flex items-center overflow-hidden bg-cover bg-center" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuD-quB-7usSAQDZr507QajHbr2tMlKa-IA0FLT7SmmJ7KjaQkn_ImUHsIipl_4MEE1rQsXgVBFgJkrD6xiEROkArE6641_LLHL9ZOu-euCkJLDYjuux4qJ9gwnApghsMU6FKaU3fexdmNOeTx4R5iQzqM7oThTAVVqn6hJ6Z_Dic-xa-PgjZ3bABWou76KitA2aU87SbERjqwmb9mkI-dCYnzKJGOTdDzo4CaGwYUoU-re-onjXMgD_yPobWhhU6odqiyqur5-broA');">
  <div class="absolute inset-0 bg-gradient-to-r from-[#1b0503]/90 via-[#1b0503]/60 to-[#1b0503]/20"></div>
  <div class="max-w-container-max mx-auto px-gutter relative z-10 py-section-gap w-full">
    <div class="max-w-xl">
      <span class="text-secondary-fixed font-label-md text-label-md uppercase tracking-[0.2em] mb-4 block">Ancestral Resonance</span>
      <h1 class="font-display-lg text-display-lg text-white mb-6">The Pulse of <br/> Heritage.</h1>
      <p class="font-body-lg text-body-lg text-white/85 max-w-lg mb-8 italic">
        "Every beat of the Dhaa Baja tells a story of a thousand years, a rhythmic dialogue between the earth and the soul."
      </p>
      <div class="flex gap-4">
        <a href="library" class="bg-primary text-on-primary px-8 py-4 rounded-DEFAULT font-label-md text-label-md hover:opacity-90 transition-opacity">Explore Library</a>
        <a href="about" class="border border-white/40 text-white px-8 py-4 rounded-DEFAULT font-label-md text-label-md hover:bg-white/10 transition-colors">Learn Our History</a>
      </div>
    </div>
  </div>
</section>

<!-- Editor's Pick (live from DB) -->
<section class="py-section-gap max-w-container-max mx-auto px-gutter">
  <div class="flex justify-between items-end mb-12">
    <div>
      <h2 class="font-headline-lg text-headline-lg text-primary">Editor's Pick</h2>
      <p class="font-body-md text-body-md text-on-surface-variant">The most resonant rhythms from our master percussionists.</p>
    </div>
    <a href="library" class="flex items-center gap-2 text-secondary font-label-md text-label-md">View All Rhythms <span class="material-symbols-outlined">arrow_right_alt</span></a>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <?php if ($featured): ?>
    <div class="md:col-span-2 parchment-card bg-surface border border-outline-variant p-8 flex flex-col justify-between">
      <div>
        <div class="flex items-center gap-2 mb-4">
          <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-xs font-bold uppercase"><?= e($featured['category_name'] ?? 'Uncategorized') ?></span>
          <span class="text-outline text-xs"><?= gmdate('i:s', $featured['duration_seconds']) ?></span>
        </div>
        <h3 class="font-headline-md text-headline-md text-primary mb-4"><?= e($featured['title']) ?></h3>
        <p class="font-body-md text-body-md text-on-surface-variant line-clamp-3"><?= e($featured['description']) ?></p>
      </div>
      <div class="mt-8 flex justify-between items-center">
        <div class="flex items-center gap-3">
          <span class="w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center">
            <span class="material-symbols-outlined">play_arrow</span>
          </span>
          <span class="font-label-md text-label-md"><?= (int)$featured['play_count'] ?> downloads</span>
        </div>
        <a href="library#rhythm-<?= (int)$featured['id'] ?>" class="text-primary font-label-md text-xs">VIEW</a>
      </div>
    </div>
    <?php endif; ?>

    <?php foreach ($others as $r): if ($featured && $r['id'] == $featured['id']) continue; ?>
    <div class="md:col-span-1 parchment-card bg-surface border border-outline-variant p-6 flex flex-col justify-between">
      <span class="px-2 py-0.5 bg-surface-container-highest rounded text-[10px] text-on-surface-variant font-bold uppercase w-max mb-2"><?= e($r['category_name'] ?? 'Uncategorized') ?></span>
      <h4 class="font-headline-md text-[18px] text-primary mb-2"><?= e($r['title']) ?></h4>
      <p class="font-label-md text-xs text-on-surface-variant mb-4"><?= (int)$r['play_count'] ?> downloads</p>
      <a href="library#rhythm-<?= (int)$r['id'] ?>" class="mt-auto text-primary font-label-md text-xs flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">play_circle</span> PLAY
      </a>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Upcoming Events preview -->
<section class="py-section-gap max-w-container-max mx-auto px-gutter">
  <div class="flex justify-between items-end mb-12">
    <div>
      <h2 class="font-headline-lg text-headline-lg text-primary">Gatherings &amp; Echoes</h2>
      <p class="font-body-md text-on-surface-variant max-w-md">Join us in person to experience the vibration that unites generations.</p>
    </div>
    <a href="events" class="flex items-center gap-2 text-secondary font-label-md text-label-md">View All Events <span class="material-symbols-outlined">arrow_right_alt</span></a>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <?php foreach ($nextEvents as $ev): $d = new DateTime($ev['event_date']); ?>
    <div class="parchment-card bg-surface border border-outline-variant p-0 overflow-hidden relative">
      <div class="p-8">
        <div class="flex justify-between items-start mb-4">
          <div>
            <p class="text-secondary font-label-md text-xs font-bold uppercase tracking-widest"><?= e($ev['category']) ?></p>
            <h4 class="font-headline-md text-[22px] text-primary mt-1"><?= e($ev['title']) ?></h4>
          </div>
          <div class="text-right">
            <p class="font-display-lg text-2xl text-primary leading-none"><?= $d->format('d') ?></p>
            <p class="font-label-md text-[10px] uppercase opacity-60"><?= $d->format('M') ?></p>
          </div>
        </div>
        <div class="flex items-center gap-2 text-on-surface-variant text-sm mb-6">
          <span class="material-symbols-outlined text-xs">location_on</span>
          <span><?= e($ev['location']) ?></span>
        </div>
        <a href="events#event-<?= (int)$ev['id'] ?>" class="block w-full text-center py-3 bg-surface-container-high text-primary font-bold text-xs uppercase tracking-widest hover:bg-primary hover:text-on-primary transition-colors">
          Read More
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

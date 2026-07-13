<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();
$stats = $db->query("SELECT (SELECT COUNT(*) FROM rhythms) AS rhythm_count, (SELECT COUNT(*) FROM events) AS event_count")->fetch();

$pageTitle = 'About | Dhaa Baja';
$activePage = 'about';
include __DIR__ . '/includes/head.php';
?>
<body class="font-body-md text-body-md">
<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="pt-16 pb-section-gap">
  <header class="max-w-container-max mx-auto px-gutter mb-24 text-center">
    <span class="font-label-md text-label-md text-primary tracking-[0.2em] uppercase mb-4 block">Our Heritage</span>
    <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg text-on-background mb-8 max-w-4xl mx-auto">Resonating Through the Centuries</h1>
    <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto italic">
      "The Dhaa Baja is not merely a drum; it is the heartbeat of the Newar civilization, a hollowed log that speaks the language of the ancestors."
    </p>
  </header>

  <section class="max-w-container-max mx-auto px-gutter mb-section-gap">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-12 items-center">
      <div class="md:col-span-7">
        <img class="w-full h-[420px] object-cover shadow-xl rounded" alt="Craftsman with drum" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDHClpuPiLtSEqg0JQQjXtNbVyQ94r38p0y3FrlOTr5lUdt-DblJqEDRtia7s58pLXLilmVvmNoZvMuudKzX5iAVxDL0BshWbng1PbT2Kj4A9ira8T22A2T_nJBBM-7Atki9wokNMHyPvq2HI_qmwZYoIZy2mYJzWEg7_cDmzTvO9hZd7J25zntr0oeHkrf3sYgxh9kReqzL4akLDAAKPahr8AcKiCk7KdT9xr6l4Ue1I7RQN81IyTiqwJXO3vtLseA-laijXGlrEw"/>
      </div>
      <div class="md:col-span-5">
        <h2 class="font-headline-lg text-headline-lg text-primary mb-6">History of the Instrument</h2>
        <div class="space-y-6 text-on-surface-variant font-body-md text-body-md">
          <p>Dating back over a thousand years to the Licchavi period, the Dhaa Baja emerged as the rhythmic cornerstone of the Kathmandu Valley. Traditionally played during festivals and religious processions, its deep, resonant tone was believed to bridge the physical and spiritual realms.</p>
          <p>Each rhythm carried by the Dhaa is a coded record of history &mdash; a sonic map of migrations, celestial alignments, and communal celebrations.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-surface-container py-section-gap">
    <div class="max-w-container-max mx-auto px-gutter">
      <div class="text-center mb-16">
        <h2 class="font-headline-lg text-headline-lg text-primary mb-4">The Craftsmanship</h2>
        <p class="max-w-xl mx-auto text-on-surface-variant">Forged from wood, hide, and copper. A testament to patience and the precision of the master maker.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="parchment-card p-8 flex flex-col items-center text-center rounded-xl">
          <span class="material-symbols-outlined text-4xl text-primary mb-6">forest</span>
          <h3 class="font-headline-md text-headline-md mb-4">The Khari Wood</h3>
          <p class="text-on-surface-variant">Selected from seasoned Khari logs, the shell is hollowed by hand to create a perfect acoustic chamber.</p>
        </div>
        <div class="parchment-card p-8 flex flex-col items-center text-center rounded-xl">
          <span class="material-symbols-outlined text-4xl text-primary mb-6">layers</span>
          <h3 class="font-headline-md text-headline-md mb-4">Buffalo Parchment</h3>
          <p class="text-on-surface-variant">Drumheads made from specially treated buffalo hide, tensioned by hand for micro-tonal adjustment.</p>
        </div>
        <div class="parchment-card p-8 flex flex-col items-center text-center rounded-xl">
          <span class="material-symbols-outlined text-4xl text-primary mb-6">hardware</span>
          <h3 class="font-headline-md text-headline-md mb-4">Aged Copper</h3>
          <p class="text-on-surface-variant">Hand-hammered copper accents provide structural integrity and a signature visual brilliance.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="max-w-container-max mx-auto px-gutter py-section-gap">
    <div class="max-w-2xl bg-surface p-12 shadow-2xl rounded-xl border border-outline-variant">
      <h2 class="font-headline-lg text-headline-lg text-primary mb-6">Our Mission</h2>
      <p class="font-body-lg text-body-lg text-on-surface mb-8">
        Dhaa Baja was founded to ensure that the ancient rhythms of our valley are not lost to the silence of time.
      </p>
      <div class="grid grid-cols-2 gap-8">
        <div>
          <div class="text-3xl font-headline-md text-primary mb-2"><?= (int)$stats['rhythm_count'] ?></div>
          <div class="font-label-md text-label-md text-outline uppercase tracking-wider">Rhythms Cataloged</div>
        </div>
        <div>
          <div class="text-3xl font-headline-md text-primary mb-2"><?= (int)$stats['event_count'] ?></div>
          <div class="font-label-md text-label-md text-outline uppercase tracking-wider">Gatherings Hosted</div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

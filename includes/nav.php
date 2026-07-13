<?php
/** Shared top nav bar. Expects $activePage to be set (e.g. 'library'). */
$activePage = $activePage ?? '';
$user = current_user();
function navLink(string $href, string $label, string $key, string $active): void {
    $isActive = $active === $key;
    $classes = $isActive
        ? 'text-primary font-bold border-b-2 border-primary pb-1'
        : 'text-on-surface-variant hover:text-primary transition-colors duration-300';
    echo '<a class="' . $classes . ' font-label-md text-label-md" href="' . e($href) . '">' . e($label) . '</a>';
}
?>
<nav class="bg-surface sticky top-0 z-50 shadow-sm h-20 flex items-center">
  <div class="flex justify-between items-center w-full px-gutter max-w-container-max mx-auto h-20">
    <a class="font-headline-md text-headline-md text-primary tracking-tight" href="index">Dhaa Baja</a>
    <div class="hidden md:flex gap-8 items-center">
      <?php navLink('library', 'Library', 'library', $activePage); ?>
      <?php navLink('about', 'About', 'about', $activePage); ?>
      <?php navLink('events', 'Events', 'events', $activePage); ?>
      <form action="search" method="get" class="relative flex items-center">
        <span class="material-symbols-outlined absolute left-3 text-outline text-lg">search</span>
        <input name="q" value="<?= e($_GET['q'] ?? '') ?>" class="pl-10 pr-4 py-2 border-none focus:ring-0 bg-surface-container-low text-label-md w-48 rounded" placeholder="Search rhythms, events..." type="text"/>
      </form>
    </div>
    <div class="flex items-center gap-4">
      <?php if ($user): ?>
        <span class="hidden sm:inline font-label-md text-label-md text-on-surface-variant">Hi, <?= e($user['first_name']) ?></span>
        <a href="logout" class="bg-primary text-on-primary px-6 py-2.5 rounded-DEFAULT font-label-md text-label-md hover:opacity-90 transition">Sign Out</a>
      <?php else: ?>
        <a href="login" class="bg-primary text-on-primary px-6 py-2.5 rounded-DEFAULT font-label-md text-label-md hover:opacity-90 transition">Sign In</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

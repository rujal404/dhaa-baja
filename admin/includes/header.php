<?php
/**
 * Shared admin layout head + sidebar.
 * Expects $pageTitle and $activeAdmin (e.g. 'dashboard','rhythms','events','users','subscribers') to be set.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_admin();
$db = getDB();
$admin = current_user();
$activeAdmin = $activeAdmin ?? '';

function adminLink(string $href, string $icon, string $label, string $key, string $active): void {
    $isActive = $active === $key;
    $classes = $isActive
        ? 'bg-primary text-on-primary'
        : 'text-on-surface-variant hover:bg-surface-container-high';
    echo '<a href="' . e($href) . '" class="flex items-center gap-3 px-4 py-3 rounded-lg font-label-md text-label-md transition-colors ' . $classes . '">'
       . '<span class="material-symbols-outlined text-lg">' . e($icon) . '</span>' . e($label) . '</a>';
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= e($pageTitle ?? 'Admin | Dhaa Baja') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@500;600;700&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
  tailwind.config = {
    theme: { extend: { colors: {
      primary: '#5c0d09', 'primary-container':'#7b241c', secondary:'#7d5800',
      'secondary-container':'#fec660', background:'#fcf9f8', surface:'#ffffff',
      'surface-container':'#f0eded', 'surface-container-high':'#eae7e7',
      'on-surface-variant':'#56423f', 'outline-variant':'#dcc0bc', 'on-primary':'#ffffff'
    }, fontFamily: { headline:['EB Garamond','serif'], body:['Manrope','sans-serif'] } } }
  }
</script>
<link href="../assets/css/admin.css" rel="stylesheet"/>
</head>
<body class="text-on-background">
<div class="flex min-h-screen">
  <aside class="w-64 bg-surface border-r border-outline-variant flex-shrink-0 hidden md:flex flex-col">
    <div class="h-20 flex items-center px-6 font-headline text-2xl text-primary border-b border-outline-variant">Dhaa Baja <span class="text-xs align-top text-secondary ml-1">ADMIN</span></div>
    <nav class="flex-1 p-4 space-y-1">
      <?php adminLink('index', 'space_dashboard', 'Dashboard', 'dashboard', $activeAdmin); ?>
      <?php adminLink('rhythms', 'library_music', 'Rhythms', 'rhythms', $activeAdmin); ?>
      <?php adminLink('categories', 'category', 'Categories', 'categories', $activeAdmin); ?>
      <?php adminLink('events', 'event', 'Events', 'events', $activeAdmin); ?>
      <?php adminLink('rsvps', 'confirmation_number', 'RSVPs', 'rsvps', $activeAdmin); ?>
      <?php adminLink('purchases', 'shopping_bag', 'Purchases', 'purchases', $activeAdmin); ?>
      <?php adminLink('users', 'group', 'Users', 'users', $activeAdmin); ?>
      <?php adminLink('subscribers', 'mail', 'Subscribers', 'subscribers', $activeAdmin); ?>
    </nav>
    <div class="p-4 border-t border-outline-variant">
      <p class="font-label-md text-xs text-on-surface-variant mb-2">Signed in as<br><strong><?= e($admin['first_name'] . ' ' . $admin['last_name']) ?></strong></p>
      <a href="../logout" class="flex items-center gap-2 text-sm text-primary hover:underline"><span class="material-symbols-outlined text-base">logout</span> Sign Out</a>
      <a href="../index" class="flex items-center gap-2 text-sm text-on-surface-variant hover:underline mt-2"><span class="material-symbols-outlined text-base">arrow_back</span> View Site</a>
    </div>
  </aside>

  <main class="flex-1 p-6 md:p-10 max-w-6xl mx-auto w-full">

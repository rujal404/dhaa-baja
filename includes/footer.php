<footer class="bg-surface-container border-t border-outline-variant">
  <div class="flex flex-col md:flex-row justify-between items-start gap-base w-full px-gutter py-section-gap max-w-container-max mx-auto">
    <div class="max-w-sm">
      <div class="font-headline-md text-headline-md text-primary mb-4">Dhaa Baja</div>
      <p class="font-body-md text-body-md text-on-surface-variant mb-8 italic">
        Preserving the intangible heritage of percussive arts through meticulous digital archival and community exploration.
      </p>
    </div>
    <div class="grid grid-cols-2 gap-16">
      <div>
        <p class="font-label-md text-label-md text-primary font-bold mb-4">Foundation</p>
        <ul class="space-y-3">
          <li><a class="text-on-surface-variant hover:text-secondary transition-colors" href="about">Craftsmanship</a></li>
          <li><a class="text-on-surface-variant hover:text-secondary transition-colors" href="about">Mission</a></li>
          <li><a class="text-on-surface-variant hover:text-secondary transition-colors" href="about">History</a></li>
        </ul>
      </div>
      <div>
        <p class="font-label-md text-label-md text-primary font-bold mb-4">Connect</p>
        <ul class="space-y-3">
          <li>
            <form action="api/newsletter" method="post" class="flex flex-col gap-1">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="email" name="email" required placeholder="you@example.com" class="bg-surface-container-low text-sm px-2 py-1 rounded border border-outline-variant">
              <button class="text-left text-on-surface-variant hover:text-secondary transition-colors" type="submit">Newsletter Sign Up</button>
            </form>
          </li>
          <li><a class="text-on-surface-variant hover:text-secondary transition-colors" href="#">Terms</a></li>
          <li><a class="text-on-surface-variant hover:text-secondary transition-colors" href="#">Contact</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="max-w-container-max mx-auto px-gutter pb-12">
    <p class="text-on-surface-variant opacity-60 text-xs text-center md:text-left border-t border-outline-variant/30 pt-8">
      &copy; <?= date('Y') ?> Dhaa Baja. Honoring Ancestral Rhythms.
    </p>
  </div>
</footer>
<script src="assets/js/site.js"></script>
</body>
</html>

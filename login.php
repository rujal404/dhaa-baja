<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();
$error = null;

if (is_logged_in()) {
    header('Location: index');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Your session expired, please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $userRow = $stmt->fetch();

        if ($userRow && password_verify($password, $userRow['password_hash'])) {
            if ($userRow['status'] === 'suspended') {
                $error = 'This account has been suspended.';
            } else {
                login_user($userRow);
                header('Location: ' . ($userRow['role'] === 'admin' ? 'admin/index' : 'index'));
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Sign In | Dhaa Baja';
$activePage = 'login';
include __DIR__ . '/includes/head.php';
?>
<body class="bg-background min-h-screen flex flex-col">
<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="flex-grow flex items-center justify-center py-16 px-margin-mobile">
  <div class="w-full max-w-md bg-surface-container-lowest rounded-xl overflow-hidden shadow-xl p-10 md:p-14">
    <h1 class="font-headline-lg text-headline-lg text-primary mb-2">Welcome Back</h1>
    <p class="font-body-md text-body-md text-on-surface-variant mb-8">Resume your journey through ancestral rhythms.</p>

    <?php if ($error): ?>
      <div class="mb-6 bg-error-container text-on-error-container px-4 py-3 rounded text-sm"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-6">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div>
        <label class="block text-label-md font-label-md text-outline uppercase tracking-widest mb-1">Email Address</label>
        <input class="input-field" name="email" placeholder="percussionist@heritage.com" type="email" required>
      </div>
      <div>
        <label class="block text-label-md font-label-md text-outline uppercase tracking-widest mb-1">Password</label>
        <input class="input-field" name="password" placeholder="••••••••" type="password" required>
      </div>
      <button class="w-full bg-primary text-on-primary py-4 rounded-full font-label-md text-label-md font-bold uppercase tracking-widest hover:bg-primary-container transition-colors shadow-md" type="submit">
        Sign In
      </button>
    </form>

    <p class="mt-8 text-center font-body-md text-body-md text-on-surface-variant">
      New to the community? <a class="text-primary font-bold" href="signup">Sign up</a>
    </p>
    <p class="mt-4 text-center text-xs text-outline">Demo admin: admin@dhaabaja.com / Admin@12345</p>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

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
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($first === '' || $last === '' || $email === '' || strlen($password) < 8) {
            $error = 'Please fill all fields; password must be at least 8 characters.';
        } else {
            $check = $db->prepare('SELECT id FROM users WHERE email = :email');
            $check->execute([':email' => $email]);
            if ($check->fetch()) {
                $error = 'An account with that email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (:f, :l, :e, :p, "user")');
                $stmt->execute([':f' => $first, ':l' => $last, ':e' => $email, ':p' => $hash]);

                $userId = (int)$db->lastInsertId();
                login_user([
                    'id' => $userId, 'first_name' => $first, 'last_name' => $last,
                    'email' => $email, 'role' => 'user',
                ]);
                header('Location: index');
                exit;
            }
        }
    }
}

$pageTitle = 'Join the Community | Dhaa Baja';
$activePage = 'signup';
include __DIR__ . '/includes/head.php';
?>
<body class="bg-background min-h-screen flex flex-col">
<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="flex-grow flex items-center justify-center py-16 px-margin-mobile">
  <div class="w-full max-w-md bg-surface-container-lowest rounded-xl overflow-hidden shadow-xl p-10 md:p-14">
    <h2 class="font-headline-lg text-headline-lg text-tertiary mb-2">Join the Community</h2>
    <p class="font-body-md text-body-md text-on-surface-variant mb-8">Preserve, learn, and perform the beats of history.</p>

    <?php if ($error): ?>
      <div class="mb-6 bg-error-container text-on-error-container px-4 py-3 rounded text-sm"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-6">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-label-md font-label-md text-outline uppercase tracking-widest mb-1">First Name</label>
          <input class="input-field" name="first_name" type="text" required value="<?= e($_POST['first_name'] ?? '') ?>">
        </div>
        <div>
          <label class="block text-label-md font-label-md text-outline uppercase tracking-widest mb-1">Last Name</label>
          <input class="input-field" name="last_name" type="text" required value="<?= e($_POST['last_name'] ?? '') ?>">
        </div>
      </div>
      <div>
        <label class="block text-label-md font-label-md text-outline uppercase tracking-widest mb-1">Email Address</label>
        <input class="input-field" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div>
        <label class="block text-label-md font-label-md text-outline uppercase tracking-widest mb-1">Password</label>
        <input class="input-field" name="password" type="password" required minlength="8">
      </div>
      <button class="w-full bg-tertiary text-on-tertiary py-4 rounded-full font-label-md text-label-md font-bold uppercase tracking-widest hover:bg-tertiary-container transition-colors shadow-md" type="submit">
        Create Account
      </button>
    </form>

    <p class="mt-8 text-center font-body-md text-body-md text-on-surface-variant">
      Already a member? <a class="text-primary font-bold" href="login">Login here</a>
    </p>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

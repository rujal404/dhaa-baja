<?php
require_once __DIR__ . '/../includes/auth.php';
$db = getDB();
$error = null;

if (is_admin()) {
    header('Location: index');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $error = 'Your session expired, please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email AND role = "admin" LIMIT 1');
        $stmt->execute([':email' => $email]);
        $userRow = $stmt->fetch();

        if ($userRow && password_verify($password, $userRow['password_hash'])) {
            login_user($userRow);
            header('Location: index');
            exit;
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Login | Dhaa Baja</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@600;700&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet"/>
<link href="../assets/css/admin.css" rel="stylesheet"/>
</head>
<body class="min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-sm bg-white rounded-xl shadow-xl p-10 border border-[#dcc0bc]">
    <h1 class="font-headline text-3xl text-[#5c0d09] mb-1">Dhaa Baja</h1>
    <p class="text-sm text-[#56423f] mb-8">Admin Dashboard Sign In</p>

    <?php if ($error): ?>
      <div class="mb-6 bg-red-50 text-red-700 px-4 py-3 rounded text-sm"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-5">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div>
        <label class="block text-xs uppercase tracking-widest text-[#89726e] mb-1">Email</label>
        <input class="w-full border-b border-[#dcc0bc] focus:border-[#5c0d09] focus:outline-none py-2" name="email" type="email" required>
      </div>
      <div>
        <label class="block text-xs uppercase tracking-widest text-[#89726e] mb-1">Password</label>
        <input class="w-full border-b border-[#dcc0bc] focus:border-[#5c0d09] focus:outline-none py-2" name="password" type="password" required>
      </div>
      <button class="w-full bg-[#5c0d09] text-white py-3 rounded-full font-bold uppercase text-xs tracking-widest hover:opacity-90 transition" type="submit">Sign In</button>
    </form>
    <p class="mt-6 text-center text-xs text-[#89726e]">Demo: admin@dhaabaja.com / Admin@12345</p>
  </div>
</body>
</html>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];

        $classesRaw = $user['class'] ?? '';
        $classesArr = array_filter(array_map('trim', explode(',', $classesRaw)));

        $normalized = [];
        foreach ($classesArr as $c) {
            $c = preg_replace('/\s+/', ' ', $c);
            $c = preg_replace('/class(\d+)/i', 'class $1', $c);
            $normalized[] = trim($c);
        }

        $_SESSION['classes'] = $normalized;
        $_SESSION['class'] = $normalized[0] ?? '';
        $_SESSION['session_active'] = true;
        $_SESSION['login_at'] = time();

        $success = true;
    } else {
        $error = 'Invalid credentials. Please verify your username and password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Secure Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/app.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js" defer></script>
</head>
<body class="auth-page">
<div class="bg-particles" aria-hidden="true"></div>

<div class="skeleton-card" id="skeletonCard" aria-hidden="true">
  <div class="skeleton-line lg"></div>
  <div class="skeleton-line"></div>
  <div class="skeleton-line"></div>
  <div class="skeleton-line btn"></div>
</div>

<main class="login-wrap hidden" id="loginWrap">
  <section class="login-card fade-in" id="loginCard">
    <div class="secure-badge">🔒 Secure Login</div>
    <div class="card-border-glow"></div>

    <div id="lockLottie" class="lock-lottie" aria-hidden="true"></div>
    <h1>Welcome Back</h1>
    <p class="muted">Sign in to your admin workspace</p>

    <?php if ($error): ?>
      <div class="error-msg shake"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="auth-form" id="authForm">
      <label class="field">
        <span>Username</span>
        <input class="input" name="username" placeholder="Enter username" required autocomplete="off">
      </label>

      <label class="field pwd-wrap">
        <span>Password</span>
        <input class="input" type="password" name="password" id="password" placeholder="Enter password" required>
        <button type="button" class="eye-btn ripple" id="togglePwd" aria-label="Toggle password visibility">👁</button>
      </label>

      <button class="btn-primary ripple" type="submit">Sign In</button>

      <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
    </form>

    <div class="warning-note">⚠️ Password reset is limited to 3 attempts. Additional resets unlock again after 5 days.</div>
  </section>
</main>

<div class="modal-backdrop" id="successModal" role="dialog" aria-modal="true" aria-hidden="true">
  <div class="modal-card zoom-in">
    <h3>Login Successful</h3>
    <p>Secure session established. Redirecting to dashboard…</p>
    <button class="btn-primary ripple" id="closeModal">Continue now</button>
  </div>
</div>

<div class="mute-toggle">
  <button class="icon-btn ripple" id="muteToggle" aria-label="Toggle sound">🔊</button>
</div>

<canvas id="confettiCanvas" aria-hidden="true"></canvas>

<script>
window.APP_LOGIN_STATE = {
  success: <?= $success ? 'true' : 'false' ?>,
  redirectUrl: 'dashboard.php'
};
</script>
<script src="assets/app.js" defer></script>
</body>
</html>

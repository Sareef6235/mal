<?php
require __DIR__ . '/lib.php';


error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'user'; // always customer

    if ($name === '' || $email === '' || $password === '') {
        flash('error', 'Please fill all required fields.');
        redirect('/qwe1/register.php');
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        flash('error', 'Email already registered.');
        redirect('/qwe1/register.php');
    }

    $insert = db()->prepare('INSERT INTO users (name, email, password_hash, phone, role) VALUES (:name, :email, :password_hash, :phone, :role)');
    $insert->execute([
    'name' => $name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'phone' => $phone !== '' ? $phone : null,
    'role' => $role, // always customer
]);

    flash('success', 'Account created successfully. Please login.');
    redirect('/qwe1/login.php');
}

$flash = getFlash();
renderHead('Register', 'Create a customer account for premium ticket support and chat access.');
?>
<body>
    
    <div id="cursor-core"></div>
<div id="cursor-ring"></div>

         <?php renderFlash(); ?>  

<div class="page-shell auth-page">
    <div class="ambient ambient-b"></div>
    <div class="ambient ambient-c"></div>
    <header class="topbar glass compact-topbar" aria-label="Registration header">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Modern account creation for premium support access</p>
            </div>
        </div>
        <nav class="menu" aria-label="Registration navigation">
            <a href="/qwe1/">Home</a>
            <a href="/qwe1/login.php">Login</a>
        </nav>
      <style>  
      /* HIDE DEFAULT CURSOR */
* {
    cursor: none !important;
}

/* MAIN CURSOR DOT */
#cursor-core {
    position: fixed;
    width: 10px;
    height: 10px;
    background: #67e8f9;
    border-radius: 50%;
    pointer-events: none;
    z-index: 9999;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 18px rgba(103,232,249,.9), 0 0 28px rgba(34,211,238,.7);
}

/* OUTER RING */
#cursor-ring {
    position: fixed;
    width: 34px;
    height: 34px;
    border: 1px solid rgba(103,232,249,.9);
    border-radius: 50%;
    pointer-events: none;
    z-index: 9999;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 24px rgba(34,211,238,.45);
}

/* TRAIL EFFECT */
.cursor-trail {
    position: fixed;
    width: 8px;
    height: 8px;
    background: rgba(34,211,238,.45);
    border-radius: 50%;
    pointer-events: none;
    z-index: 9998;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 14px rgba(34,211,238,.55);
    transition: transform .18s linear, opacity .25s ease;
}

/* CLICK RIPPLE */
.cursor-ripple {
    position: fixed;
    border: 1px solid rgba(103,232,249,.65);
    border-radius: 50%;
    pointer-events: none;
    transform: translate(-50%, -50%);
    animation: ripple .6s ease-out forwards;
    z-index: 9998;
}

@keyframes ripple {
    from { width:0; height:0; opacity:.7; }
    to { width:90px; height:90px; opacity:0; }
}
     /* ROOT THEME */
:root {
    --bg: #07111f;
    --bg-soft: #0e1b30;
    --surface: rgba(10, 22, 40, 0.78);
    --surface-strong: #10213a;
    --surface-muted: rgba(255, 255, 255, 0.06);
    --card-border: rgba(255, 255, 255, 0.1);
    --text: #f5f7fb;
    --text-muted: #9fb1cc;
    --heading: #ffffff;
    --primary: #6d8cff;
    --accent: #9a7cff;
    --success: #6ce3a8;
    --danger: #ff6b81;
    --radius-lg: 20px;
}

/* RESET */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', 'Noto Sans Malayalam', sans-serif;
    background: var(--bg);
    color: var(--text);
}

/* LAYOUT */
.page-shell {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
}

/* CARD */
.card {
    background: var(--surface);
    border: 1px solid var(--card-border);
    border-radius: var(--radius-lg);
    padding: 20px;
    backdrop-filter: blur(20px);
}

/* TOPBAR */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.brand {
    display: flex;
    gap: 10px;
    align-items: center;
}

.brand-mark {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg,#3b82f6,#9333ea);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}

/* MENU */
.menu a {
    margin-left: 12px;
    text-decoration: none;
    color: var(--text-muted);
    padding: 6px 10px;
    border-radius: 8px;
    transition: 0.2s;
}

.menu a:hover,
.menu a.active {
    background: linear-gradient(135deg,#3b82f6,#9333ea);
    color: white;
}

/* HERO */
.page-hero {
    margin-bottom: 20px;
}

.eyebrow {
    font-size: 12px;
    color: var(--text-muted);
}

.lead {
    color: var(--text-muted);
}

/* BUTTON */
.primary-btn {
    background: linear-gradient(135deg,#3b82f6,#9333ea);
    border: none;
    padding: 10px 16px;
    border-radius: 10px;
    color: white;
    cursor: pointer;
}

.secondary-btn {
    background: var(--surface-muted);
    border: none;
    padding: 10px;
    border-radius: 10px;
    color: white;
}

/* FORM */
.stack-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.field-label input,
.field-label textarea,
.field-label select {
    width: 100%;
    padding: 10px;
    border-radius: 10px;
    border: none;
    background: var(--surface-muted);
    color: white;
}

/* FLASH POPUP 🔥 */
.flash-popup {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--surface);
    border-left: 4px solid var(--primary);
    padding: 12px 16px;
    border-radius: 10px;
    z-index: 9999;
    animation: slideIn 0.3s ease;
}

.flash-popup.success { border-color: var(--success); }
.flash-popup.error { border-color: var(--danger); }

@keyframes slideIn {
    from {transform: translateX(100%);}
    to {transform: translateX(0);}
}

/* AUTH GRID */
.auth-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 900px) {
    .auth-grid {
        grid-template-columns: 1fr;
    }
}

/* PREVIEW GRID */
.preview-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 20px;
}

@media (max-width: 900px) {
    .preview-grid {
        grid-template-columns: 1fr;
    }
}

/* CHAT */
.demo-chat {
    flex: 1;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.chat-bubble {
    padding: 10px;
    border-radius: 12px;
    max-width: 75%;
}

.customer {
    background: #2563eb;
    align-self: flex-end;
}

.agent {
    background: rgba(255,255,255,0.08);
}

/* FOOTER */
.site-footer {
    margin-top: 30px;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 20px;
}

.footer-bottom {
    margin-top: 20px;
    font-size: 12px;
    opacity: 0.7;
}

@media (max-width: 900px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
    }
} 
</style>     
        
        
        
        
    </header>

    <main class="auth-shell auth-grid">
        <section class="auth-hero card">
            <span class="eyebrow">Create account</span>
            <h1>Start secure messaging with a premium support experience</h1>
            <p class="lead">Create your customer profile to submit tickets, track updates, and continue conversation history in a clean chat UI.</p>
            <div class="feature-list compact-list">
                <div class="feature-list-item">Order linked messaging</div>
                <div class="feature-list-item">Mobile-first support workspace</div>
                <div class="feature-list-item">Email and WhatsApp ready workflow</div>
            </div>
        </section>

        <section class="auth-card card" aria-labelledby="register-title">
            <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
            <span class="eyebrow">Customer registration</span>
            <h2 id="register-title">Create your account</h2>
            <form class="stack-form" method="post" aria-label="Registration form">
                <label class="field-label">Full Name
                    <input name="name" placeholder="Full Name" required aria-label="Full name">
                </label>
                <label class="field-label">Email Address
                    <input name="email" type="email" placeholder="Email Address" required aria-label="Email address">
                </label>
                <label class="field-label">WhatsApp Number
                    <input name="phone" placeholder="WhatsApp Number" aria-label="WhatsApp number">
                </label>
                <label class="field-label">Password
                    <input name="password" type="password" placeholder="Password" required aria-label="Password">
                </label>
                <button class="primary-btn" type="submit">Create Account</button>
            </form>
            <p class="muted">Already registered? <a href="/qwe1/login.php">Login now</a></p>
        </section>
    </main>



    <?php renderFooter(); ?>
<script src="/qwe1/assets/cursor.js"></script>
</body>
</html>
    
    
</div>
</body>
</html>

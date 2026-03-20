<?php
require __DIR__ . '/lib.php';
$user = currentUser();
$flash = getFlash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Support Desk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Malayalam:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="page-shell">
    <header class="topbar glass">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>24/7 WhatsApp-style customer care portal</p>
            </div>
        </div>
        <nav class="menu">
            <a href="#features">Features</a>
            <a href="#workflow">Workflow</a>
            <?php if ($user): ?>
                <a href="/dashboard.php">Dashboard</a>
                <?php if ($user['role'] === 'admin'): ?><a href="/admin.php">Admin</a><?php endif; ?>
                <a href="/logout.php">Logout</a>
            <?php else: ?>
                <a href="/login.php">Login</a>
                <a class="menu-btn" href="/register.php">Get Started</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-b"></div>

    <?php if ($flash): ?>
        <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <section class="hero grid-2">
        <div>
            <span class="eyebrow">Support ticket + chat + notification system</span>
            <h1>പ്രൊഫഷണൽ support system with premium header, responsive menu, and full ticket workflow.</h1>
            <p class="lead">MySQL database save, admin reply system, email alerts, WhatsApp queue integration, order linking, and secure admin login — all in one clean UI.</p>
            <div class="hero-actions">
                <a class="primary-btn" href="<?= $user ? '/new-ticket.php' : '/register.php' ?>">Create Ticket</a>
                <a class="secondary-btn" href="/login.php">Open Chat Panel</a>
            </div>
            <div class="trust-row">
                <span>Live ticket tracking</span>
                <span>Premium admin console</span>
                <span>Responsive mobile design</span>
            </div>
            <div class="stats-row">
                <div><strong>MySQL</strong><span>Database-ready schema</span></div>
                <div><strong>Chat UI</strong><span>WhatsApp-style replies</span></div>
                <div><strong>Alerts</strong><span>Email + WhatsApp queue</span></div>
            </div>
        </div>
        <div class="hero-card card premium-card">
            <div class="mini-topbar">
                <span></span><span></span><span></span>
            </div>
            <div class="support-widget">
                <div class="ticket-pill">Ticket #TKT-DEMO91 · Urgent</div>
                <div class="widget-toolbar">
                    <span class="soft-chip">Admin online</span>
                    <span class="soft-chip">Response SLA: 5 min</span>
                </div>
                <div class="chat-bubble customer">Order #ORD-2026-1102 payment updated. Please confirm delivery slot.</div>
                <div class="chat-bubble agent">Hi Arjun 👋 Delivery is scheduled for today. Invoice + WhatsApp update sent.</div>
                <div class="chat-bubble customer">Perfect, നന്ദി.</div>
                <div class="widget-footer">
                    <span>Email queued</span>
                    <span>WhatsApp ready</span>
                    <span>Admin secured</span>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="section-grid">
        <article class="card info-card"><h3>Database Save</h3><p>Users, tickets, replies, and notification history are stored in MySQL with a ready-to-run SQL script.</p></article>
        <article class="card info-card"><h3>Admin Reply</h3><p>Dedicated admin panel with WhatsApp-style chat interface, ticket status control, and linked order details.</p></article>
        <article class="card info-card"><h3>Email Notification</h3><p>Gmail SMTP-ready config placeholders and server-side email queue logging for ticket updates.</p></article>
        <article class="card info-card"><h3>WhatsApp Integration</h3><p>Prepared API request handler to push messages when credentials are enabled in config.</p></article>
    </section>

    <section class="showcase-grid">
        <article class="card showcase-card">
            <span class="eyebrow">Customer journey</span>
            <h3>Single flow from contact to resolution</h3>
            <ul class="feature-list">
                <li>Login-based secure access</li>
                <li>Order reference mapped on each ticket</li>
                <li>Reply history with timestamps</li>
                <li>Status badges for Open / Pending / Closed</li>
            </ul>
        </article>
        <article class="card showcase-card highlight-card">
            <span class="eyebrow">Premium visuals</span>
            <h3>Modern gradients, hover effects, clean spacing</h3>
            <p>Every page uses the same premium theme so the landing page, forms, dashboard, and admin reply panels all feel like one polished product.</p>
        </article>
        <article class="card showcase-card">
            <span class="eyebrow">Notification layer</span>
            <h3>Ready for Gmail SMTP and WhatsApp API</h3>
            <ul class="feature-list">
                <li>Email notifications logged in DB</li>
                <li>WhatsApp payload queue saved per ticket</li>
                <li>Config-driven activation later</li>
                <li>Perfect base for real production credentials</li>
            </ul>
        </article>
    </section>

    <section id="workflow" class="card workflow">
        <div>
            <span class="eyebrow">How it works</span>
            <h2>Ready pages included</h2>
        </div>
        <div class="workflow-grid">
            <div><strong>01</strong><p>Register / login as customer or admin.</p></div>
            <div><strong>02</strong><p>Create tickets with subject, category, priority, and order reference.</p></div>
            <div><strong>03</strong><p>Continue conversation in a premium responsive chat window.</p></div>
            <div><strong>04</strong><p>Admin replies, updates status, and tracks notifications.</p></div>
        </div>
    </section>

    <section class="cta-banner card">
        <div>
            <span class="eyebrow">Ready to launch</span>
            <h2>Contact page മുതൽ full support system വരെ complete setup</h2>
            <p class="muted">Responsive header, modern menu, hover effects, premium colors, support chat, admin login, database save, and future-ready integrations are now connected in one project structure.</p>
        </div>
        <div class="hero-actions">
            <a class="primary-btn" href="<?= $user ? '/dashboard.php' : '/register.php' ?>">Open Workspace</a>
            <a class="secondary-btn" href="/admin.php">Admin Preview</a>
        </div>
    </section>
</div>
</body>
</html>

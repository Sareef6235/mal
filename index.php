<?php
require __DIR__ . '/lib.php';
$user = currentUser();
$flash = getFlash();
renderHead(
    'Premium Support System',
    'Premium customer support system with ticket, chat, WhatsApp and email integration.'
);
?>
<body>
<div class="page-shell marketing-page">
    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-b"></div>
    <div class="ambient ambient-c"></div>

    <header class="topbar glass" aria-label="Primary header">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Modern customer care workspace with premium visual design</p>
            </div>
        </div>
        <?php renderPrimaryMenu($user, 'home'); ?>
    </header>

    <main>
        <?php if ($flash): ?>
            <div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <section class="hero hero-grid" aria-labelledby="hero-title">
            <article class="hero-copy">
                <span class="eyebrow">Premium SaaS support platform</span>
                <h1 id="hero-title">Professional PHP support system with premium UI, sticky navigation, and modern customer communication.</h1>
                <p class="lead">Built for tickets, admin replies, WhatsApp-style messaging, email updates, and linked orders — all wrapped in a modern, mobile-first experience.</p>
                <div class="hero-actions">
                    <a class="primary-btn" href="<?= $user ? '/new-ticket.php' : '/register.php' ?>" aria-label="Create a new support ticket">Create Ticket</a>
                    <a class="secondary-btn" href="<?= $user ? '/dashboard.php' : '/login.php' ?>" aria-label="Open customer support dashboard">Open Workspace</a>
                </div>
                <div class="trust-row" aria-label="Highlights">
                    <span>Ticket + Chat UI</span>
                    <span>Admin secured</span>
                    <span>Responsive premium design</span>
                </div>
                <div class="stats-row">
                    <div><strong>24/7</strong><span>Support-ready interface</span></div>
                    <div><strong>MySQL</strong><span>Structured DB workflow</span></div>
                    <div><strong>Fast</strong><span>Lightweight PHP architecture</span></div>
                </div>
            </article>
            <aside class="hero-card card premium-card" aria-label="Preview card">
                <div class="mini-topbar"><span></span><span></span><span></span></div>
                <div class="support-widget">
                    <div class="ticket-pill">Ticket #TKT-DEMO91 · Urgent</div>
                    <div class="widget-toolbar">
                        <span class="soft-chip">Admin online</span>
                        <span class="soft-chip">Avg reply 05 min</span>
                    </div>
                    <div class="chat-bubble customer">Order #ORD-2026-1102 updated. Please confirm delivery schedule.</div>
                    <div class="chat-bubble agent">Hi Arjun 👋 Delivery is confirmed. Invoice and ticket update have been queued for email and WhatsApp.</div>
                    <div class="chat-bubble customer">Great, നന്ദി.</div>
                    <div class="widget-footer">
                        <span>Email ready</span>
                        <span>WhatsApp queue</span>
                        <span>Admin notes</span>
                    </div>
                    <div class="hero-actions preview-actions">
                        <a class="primary-btn" href="/preview.php">Open Live Preview</a>
                    </div>
                </div>
            </aside>
        </section>

        <section class="section-shell">
            <div class="section-intro">
                <span class="eyebrow">Feature highlights</span>
                <h2>Designed like a premium product, structured like a real support desk</h2>
            </div>
            <div class="section-grid">
                <article class="card info-card"><h3>Professional Ticket Flow</h3><p>Create, manage, and resolve support requests with organized ticket codes, priorities, and customer-linked order references.</p></article>
                <article class="card info-card"><h3>WhatsApp-style Reply Experience</h3><p>Customers and admins communicate inside a modern chat-style timeline with clean message grouping and clear status visibility.</p></article>
                <article class="card info-card"><h3>Email + WhatsApp Ready</h3><p>Notification hooks are already connected so you can activate delivery later with live SMTP and WhatsApp credentials.</p></article>
                <article class="card info-card"><h3>Responsive Admin Control</h3><p>The interface adapts from desktop to mobile while preserving premium card layouts, hierarchy, and quick actions.</p></article>
            </div>
            <div class="hero-actions">
                <a class="secondary-btn" href="/features.php">View all features</a>
            </div>
        </section>

        <section class="showcase-grid">
            <article class="card showcase-card">
                <span class="eyebrow">Branding</span>
                <h3>Stripe / Notion / Linear inspired clarity</h3>
                <ul class="feature-list">
                    <li>Sticky glassmorphism header</li>
                    <li>Premium color hierarchy</li>
                    <li>Refined CTA placement</li>
                    <li>Improved readability and contrast</li>
                </ul>
            </article>
            <article class="card showcase-card highlight-card">
                <span class="eyebrow">Why teams love it</span>
                <h3>Purpose-built for premium support operations</h3>
                <p>Clear sections, strong visual hierarchy, polished interactions, and consistent layouts make the whole support experience feel more professional for both staff and customers.</p>
            </article>
            <article class="card showcase-card">
                <span class="eyebrow">Performance</span>
                <h3>Modern CSS with lightweight structure</h3>
                <ul class="feature-list">
                    <li>Grid + flexbox layout</li>
                    <li>Reduced heavy decoration</li>
                    <li>Smooth transitions and hover polish</li>
                    <li>Fast-loading visual system</li>
                </ul>
            </article>
        </section>

        <section class="card workflow">
            <div class="section-intro">
                <span class="eyebrow">Workflow</span>
                <h2>From customer request to admin resolution</h2>
            </div>
            <div class="workflow-grid">
                <article><strong>01</strong><p>Customer creates account or logs in securely.</p></article>
                <article><strong>02</strong><p>Support request is saved with order reference and priority.</p></article>
                <article><strong>03</strong><p>Admin replies inside WhatsApp-style chat layout.</p></article>
                <article><strong>04</strong><p>Status, email, and WhatsApp actions are tracked in one place.</p></article>
            </div>
            <div class="hero-actions">
                <a class="secondary-btn" href="/workflow.php">See full workflow</a>
            </div>
        </section>

        <section class="cta-banner card" aria-label="Call to action section">
            <div>
                <span class="eyebrow">Launch ready</span>
                <h2>Contact page മുതൽ full support application വരെ polished premium experience</h2>
                <p class="muted">This interface now looks like a complete SaaS product with better hierarchy, mobile responsiveness, smoother transitions, and dedicated pages for every primary menu item.</p>
            </div>
            <div class="hero-actions">
                <a class="primary-btn" href="<?= $user ? '/dashboard.php' : '/register.php' ?>">Open Workspace</a>
                <a class="secondary-btn" href="/preview.php">Try live preview</a>
            </div>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
</body>
</html>

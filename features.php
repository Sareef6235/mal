<?php
require __DIR__ . '/lib.php';
$user = currentUser();
renderHead('Features', 'Premium support system feature overview with ticketing, chat, admin tools, and communication workflow.');
?>
<body>
<div class="page-shell marketing-page">
    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-b"></div>
    <header class="topbar glass" aria-label="Features header">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Full feature overview for your support product</p>
            </div>
        </div>
        <?php renderPrimaryMenu($user, 'features'); ?>
    </header>

    <main>
        <section class="page-hero card">
            <div class="section-intro">
                <span class="eyebrow">Features</span>
                <h1>Everything needed for a professional support workspace.</h1>
                <p class="lead">From ticket creation to admin replies and customer follow-up, every important piece of the workflow has a polished premium interface.</p>
            </div>
        </section>

        <section class="detail-grid">
            <article class="card info-card"><h2>Smart Ticket Management</h2><p>Create support tickets with subject, category, priority, and optional order reference so every issue stays organized and easy to track.</p></article>
            <article class="card info-card"><h2>WhatsApp-style Chat</h2><p>Conversation screens are styled like a modern messaging app so both customers and admins can reply comfortably.</p></article>
            <article class="card info-card"><h2>Admin Workspace</h2><p>Admins can review ticket lists, open conversations, update statuses, and continue replies from one dedicated control room.</p></article>
            <article class="card info-card"><h2>Notification Hooks</h2><p>Email and WhatsApp integration points are already connected in the workflow for future live sending.</p></article>
        </section>

        <section class="cta-banner card">
            <div>
                <span class="eyebrow">Next step</span>
                <h2>Open the live preview and try the messaging experience.</h2>
            </div>
            <div class="hero-actions">
                <a class="primary-btn" href="/preview.php">Live Preview</a>
                <a class="secondary-btn" href="/workflow.php">Workflow</a>
            </div>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
</body>
</html>

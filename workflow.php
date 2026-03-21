<?php
require __DIR__ . '/lib.php';
$user = currentUser();
renderHead('Workflow', 'Workflow page showing the premium support journey from customer message to admin resolution.');
?>
<body>
<div class="page-shell marketing-page">
    <div class="ambient ambient-b"></div>
    <div class="ambient ambient-c"></div>
    <header class="topbar glass" aria-label="Workflow header">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>How tickets move through your support pipeline</p>
            </div>
        </div>
        <?php renderPrimaryMenu($user, 'workflow'); ?>
    </header>

    <main>
        <section class="page-hero card">
            <div class="section-intro">
                <span class="eyebrow">Workflow</span>
                <h1>Clear journey from message creation to final resolution.</h1>
                <p class="lead">This page explains how the customer enters the system, how admins respond, and how updates are tracked in the premium interface.</p>
            </div>
        </section>

        <section class="workflow-grid">
            <article class="card"><strong>01</strong><p>User signs in or registers and opens a new support request.</p></article>
            <article class="card"><strong>02</strong><p>The message is saved with ticket code, priority, and order reference.</p></article>
            <article class="card"><strong>03</strong><p>Admin views the request inside the premium dashboard and replies in chat format.</p></article>
            <article class="card"><strong>04</strong><p>Status and communication logs stay visible until the issue is resolved.</p></article>
        </section>

        <section class="cta-banner card">
            <div>
                <span class="eyebrow">Explore more</span>
                <h2>See the same workflow in an interactive preview screen.</h2>
            </div>
            <div class="hero-actions">
                <a class="primary-btn" href="/preview.php">Try Messaging</a>
                <a class="secondary-btn" href="/features.php">Back to Features</a>
            </div>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
</body>
</html>

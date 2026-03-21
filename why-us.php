<?php
require __DIR__ . '/lib.php';
$user = currentUser();
renderHead('Why Us', 'Why this premium support desk design feels more professional, modern, and customer-friendly.');
?>
<body>
<div class="page-shell marketing-page">
    <div class="ambient ambient-a"></div>
    <div class="ambient ambient-c"></div>
    <header class="topbar glass" aria-label="Why us header">
        <div class="brand">
            <div class="brand-mark">PS</div>
            <div>
                <strong>Premium Support Desk</strong>
                <p>Why this UI feels polished and production-ready</p>
            </div>
        </div>
        <?php renderPrimaryMenu($user, 'why-us'); ?>
    </header>

    <main>
        <section class="page-hero card">
            <div class="section-intro">
                <span class="eyebrow">Why Us</span>
                <h1>Premium styling that helps customers trust your support process.</h1>
                <p class="lead">Clean spacing, strong contrast, glassmorphism layers, and modern interaction feedback make the product feel more professional from the first screen.</p>
            </div>
        </section>

        <section class="showcase-grid">
            <article class="card showcase-card"><h2>Premium Visual Trust</h2><p>Rounded surfaces, layered gradients, and better spacing make the system feel serious and reliable.</p></article>
            <article class="card showcase-card highlight-card"><h2>Better Communication Flow</h2><p>The app keeps information readable and structured so customers instantly understand what is happening.</p></article>
            <article class="card showcase-card"><h2>Responsive by Default</h2><p>Each major screen is styled to stay usable on desktop, tablet, and mobile without losing hierarchy.</p></article>
        </section>

        <section class="cta-banner card">
            <div>
                <span class="eyebrow">Preview</span>
                <h2>Open the demo conversation page and test the premium chat view.</h2>
            </div>
            <div class="hero-actions">
                <a class="primary-btn" href="/preview.php">Open Preview</a>
                <a class="secondary-btn" href="/login.php">Login</a>
            </div>
        </section>
    </main>

    <?php renderFooter(); ?>
</div>
</body>
</html>

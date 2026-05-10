<?php
require __DIR__ . '/config.php';
$userId = require_auth();
$user = get_user($userId);
$data = dashboard_data($userId);
$avatar = $user['avatar'] ? UPLOAD_URL . '/avatars/' . e($user['avatar']) : 'https://api.dicebear.com/8.x/notionists/svg?seed=' . rawurlencode($user['username']);
$cover = $user['cover_photo'] ? UPLOAD_URL . '/covers/' . e($user['cover_photo']) : '';
$storagePercent = min(100, round(((float) $user['storage_used_mb'] / max(1, (float) $user['storage_limit_mb'])) * 100));
$profileFields = ['full_name','username','email','phone','avatar','cover_photo','bio','address','gender','date_of_birth','website','twitter','linkedin','github'];
$complete = 0;
foreach ($profileFields as $field) {
    $complete += !empty($user[$field]) ? 1 : 0;
}
$completion = (int) round(($complete / count($profileFields)) * 100);
$avgScore = count($data['quiz_results']) ? round(array_sum(array_map(static fn ($r) => (float) $r['score'], $data['quiz_results'])) / count($data['quiz_results'])) : 0;
?>
<!doctype html>
<html lang="en" data-theme="<?= e($data['settings']['theme'] ?? 'dark') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="theme-color" content="#070b16">
    <link rel="manifest" href="manifest.webmanifest">
    <title><?= e(APP_NAME) ?> — Premium Profile Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js" defer></script>
    <script src="assets/js/dashboard.js" defer></script>
    <script>if ('serviceWorker' in navigator) window.addEventListener('load', () => navigator.serviceWorker.register('service-worker.js').catch(() => {}));</script>
</head>
<body>
    <div class="cursor-dot" aria-hidden="true"></div>
    <div class="mouse-glow" aria-hidden="true"></div>
    <div class="ambient-bg" aria-hidden="true"><span></span><span></span><span></span><i></i></div>

    <aside class="sidebar glass-panel" aria-label="Primary navigation">
        <a class="brand" href="profile.php"><span class="brand-mark">N</span><span><strong>MAL Nexus</strong><small>AI Learning OS</small></span></a>
        <nav class="side-nav">
            <?php foreach (['Dashboard'=>'⌁','Profile'=>'◉','My Materials'=>'▣','Downloads'=>'⇩','Quiz Results'=>'◌','Achievements'=>'✦','Notifications'=>'◈','Security'=>'盾','Settings'=>'⚙','Support'=>'?','Logout'=>'⎋'] as $label => $icon): ?>
                <a href="#<?= e(strtolower(str_replace(' ', '-', $label))) ?>" class="<?= $label === 'Profile' ? 'active' : '' ?>" data-nav="<?= e($label) ?>"><span><?= e($icon) ?></span><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="plan-mini">
            <p>Membership</p>
            <strong><?= e($user['membership_plan']) ?></strong>
            <div class="mini-progress"><span style="width: <?= $storagePercent ?>%"></span></div>
        </div>
    </aside>

    <header class="topbar glass-panel">
        <button class="icon-btn menu-toggle" aria-label="Open menu">☰</button>
        <div class="search-wrap"><span>⌕</span><input type="search" placeholder="Search materials, quizzes, analytics…" aria-label="Search dashboard"></div>
        <div class="top-actions">
            <button class="theme-toggle" type="button" aria-label="Toggle theme"><span>☾</span><b>Theme</b></button>
            <button class="icon-btn" data-toast="Notifications synced" aria-label="Notifications">🔔<em>3</em></button>
            <div class="user-menu">
                <button class="avatar-chip" type="button"><img src="<?= $avatar ?>" alt="<?= e($user['full_name']) ?> avatar"><span><?= e($user['full_name']) ?></span></button>
                <div class="dropdown glass-panel"><a href="#profile">View Profile</a><a href="#settings">Settings</a><a href="#security">Security</a><button data-modal="logoutModal">Logout</button></div>
            </div>
        </div>
    </header>

    <main class="app-shell">
        <section class="hero-card glass-panel reveal" id="dashboard" style="--cover:url('<?= $cover ?>')">
            <div class="cover-gradient"></div>
            <button class="cover-upload upload-trigger" data-target="cover" type="button">Change Cover</button>
            <div class="hero-content">
                <div class="avatar-xl upload-trigger" data-target="avatar"><img src="<?= $avatar ?>" alt="Profile photo"><span class="online"></span><b>Upload</b></div>
                <div>
                    <p class="eyebrow"><span class="pulse-dot"></span><span id="greeting">Welcome back</span> • <span id="liveClock">--:--</span></p>
                    <h1><?= e($user['full_name']) ?> <?= (int) $user['is_verified'] === 1 ? '<span class="verified">✓ Verified</span>' : '' ?></h1>
                    <p class="muted">@<?= e($user['username']) ?> • <?= e($user['rank_title']) ?> • <?= e($user['status']) ?> account</p>
                    <div class="hero-actions"><button class="primary-btn" data-modal="editProfileModal">Edit Profile</button><button class="ghost-btn" data-modal="passwordModal">Change Password</button><button class="danger-btn" data-modal="deleteModal">Delete Account</button></div>
                </div>
            </div>
        </section>

        <section class="stats-grid" aria-label="Profile statistics">
            <article class="stat-card glass-panel reveal"><span>Profile Completion</span><strong data-counter="<?= $completion ?>">0</strong><small>% optimized</small><div class="progress"><i style="width:<?= $completion ?>%"></i></div></article>
            <article class="stat-card glass-panel reveal"><span>Quiz Average</span><strong data-counter="<?= $avgScore ?>">0</strong><small>% performance</small><div class="progress cyan"><i style="width:<?= $avgScore ?>%"></i></div></article>
            <article class="stat-card glass-panel reveal"><span>Favorites</span><strong data-counter="<?= count($data['favorites']) ?>">0</strong><small>saved materials</small><div class="sparkline"></div></article>
            <article class="stat-card glass-panel reveal"><span>Security Score</span><strong data-counter="<?= $user['two_factor_enabled'] ? 94 : 72 ?>">0</strong><small><?= $user['two_factor_enabled'] ? '2FA enabled' : 'Enable 2FA' ?></small><div class="progress green"><i style="width:<?= $user['two_factor_enabled'] ? 94 : 72 ?>%"></i></div></article>
        </section>

        <section class="content-grid">
            <article class="profile-card glass-panel reveal" id="profile">
                <div class="section-head"><div><p class="eyebrow">Overview</p><h2>User Profile</h2></div><span class="status-pill"><i></i> Online now</span></div>
                <p class="bio"><?= e($user['bio']) ?></p>
                <div class="info-grid">
                    <div><small>Email</small><strong><?= e($user['email']) ?></strong></div>
                    <div><small>Phone</small><strong><?= e($user['phone']) ?></strong></div>
                    <div><small>Address</small><strong><?= e($user['address']) ?></strong></div>
                    <div><small>Gender</small><strong><?= e($user['gender']) ?></strong></div>
                    <div><small>Date of Birth</small><strong><?= e($user['date_of_birth']) ?></strong></div>
                    <div><small>Last Login</small><strong><?= e($user['last_login_at']) ?></strong></div>
                </div>
                <div class="social-row"><a href="<?= e($user['website']) ?>">Website</a><a href="<?= e($user['twitter']) ?>">X/Twitter</a><a href="<?= e($user['linkedin']) ?>">LinkedIn</a><a href="<?= e($user['github']) ?>">GitHub</a></div>
            </article>

            <article class="rank-card glass-panel reveal" id="achievements">
                <div class="ring" style="--value:<?= $completion ?>"><span><?= $completion ?>%</span></div>
                <h2><?= e($user['rank_title']) ?></h2>
                <p class="muted">Ranked in the top 3% of active learners this month.</p>
                <div class="badges">
                    <?php foreach ($data['achievements'] as $achievement): ?>
                        <span title="<?= e($achievement['description']) ?>"><?= e($achievement['icon']) ?> <?= e($achievement['title']) ?></span>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="widget-grid">
            <article class="glass-panel widget reveal" id="security"><div class="section-head"><h3>Security Settings</h3><button class="ghost-btn small" data-modal="verifyModal">Verify</button></div><label class="switch-row">Two Factor Authentication <input type="checkbox" <?= $user['two_factor_enabled'] ? 'checked' : '' ?>><span></span></label><label class="switch-row">Login Alerts <input type="checkbox" checked><span></span></label><label class="switch-row">Device Trust <input type="checkbox" checked><span></span></label><div class="rate-limit">Rate limit: <b>5 sensitive actions / minute</b></div></article>
            <article class="glass-panel widget reveal"><div class="section-head"><h3>Storage Usage</h3><strong><?= $storagePercent ?>%</strong></div><div class="storage-bar"><i style="width:<?= $storagePercent ?>%"></i></div><p class="muted"><?= number_format((float) $user['storage_used_mb'] / 1024, 1) ?> GB used of <?= number_format((float) $user['storage_limit_mb'] / 1024, 0) ?> GB</p></article>
            <article class="glass-panel widget reveal"><div class="section-head"><h3>Membership Plan</h3><span class="verified">Pro</span></div><p class="plan-name"><?= e($user['membership_plan']) ?></p><p class="muted">Priority analytics, unlimited saves, premium support.</p><button class="primary-btn small">Upgrade</button></article>
            <article class="glass-panel widget reveal"><h3>AI Assistant</h3><p class="muted">Ask for study suggestions, profile improvements, or security tips.</p><div class="ai-box">“Focus on MySQL optimization next — your quiz trend is rising.”</div></article>
            <article class="glass-panel widget reveal"><h3>Motivation</h3><blockquote id="quote">Small improvements compound into extraordinary systems.</blockquote><p class="weather">Weather widget placeholder • 72°F • Clear</p></article>
            <article class="glass-panel widget reveal"><h3>Performance Analytics</h3><canvas id="performanceChart" height="160" aria-label="Performance chart"></canvas></article>
        </section>

        <section class="tables-grid">
            <article class="glass-panel table-card reveal" id="downloads"><h3>Download History</h3><div class="responsive-table"><table><thead><tr><th>Material</th><th>Date</th></tr></thead><tbody><?php foreach ($data['downloads'] as $row): ?><tr><td><?= e($row['title']) ?></td><td><?= e($row['downloaded_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></article>
            <article class="glass-panel table-card reveal" id="quiz-results"><h3>Quiz History</h3><div class="responsive-table"><table><thead><tr><th>Quiz</th><th>Score</th><th>Date</th></tr></thead><tbody><?php foreach ($data['quiz_results'] as $row): ?><tr><td><?= e($row['quiz_title']) ?></td><td><?= e((string) $row['score']) ?>%</td><td><?= e($row['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></div></article>
        </section>

        <section class="content-grid bottom-section">
            <article class="glass-panel reveal"><h3>Login Device History</h3><div class="timeline"><?php foreach ($data['login_history'] as $row): ?><div><span></span><strong><?= e($row['device_name']) ?></strong><p><?= e($row['location']) ?> • <?= e($row['ip_address']) ?> • <?= e($row['logged_in_at']) ?></p></div><?php endforeach; ?></div></article>
            <article class="glass-panel reveal" id="notifications"><h3>Activity Timeline</h3><div class="timeline"><?php foreach ($data['notifications'] as $row): ?><div><span></span><strong><?= e($row['title']) ?></strong><p><?= e($row['body']) ?> • <?= e($row['created_at']) ?></p></div><?php endforeach; ?></div></article>
        </section>

        <section class="settings-panel glass-panel reveal" id="settings">
            <div class="section-head"><div><p class="eyebrow">Preferences</p><h2>Notification, Privacy & Language</h2></div><button class="primary-btn" id="saveSettings">Save Settings</button></div>
            <div class="settings-grid">
                <label>Language<select id="language"><option value="en">English</option><option value="es">Spanish</option><option value="fr">French</option><option value="bn">Bangla</option></select></label>
                <label>Profile Visibility<select id="privacy"><option>members</option><option>public</option><option>private</option></select></label>
                <label class="switch-row">Email Notifications <input type="checkbox" checked><span></span></label>
                <label class="switch-row">Push Notifications <input type="checkbox" checked><span></span></label>
                <label class="switch-row">Show Online Status <input type="checkbox" checked><span></span></label>
            </div>
        </section>
    </main>

    <nav class="mobile-nav glass-panel" aria-label="Mobile navigation"><a href="#dashboard">⌁<span>Home</span></a><a href="#profile">◉<span>Profile</span></a><a href="#quiz-results">◌<span>Quiz</span></a><a href="#security">盾<span>Secure</span></a></nav>

    <div class="fab-stack"><button class="fab" data-toast="Support chat opening…">💬</button><button class="fab" data-modal="editProfileModal">✎</button><button class="fab top-link">↑</button></div>
    <div id="toastHost" class="toast-host" aria-live="polite"></div>

    <template id="skeletonTemplate"><div class="skeleton"></div></template>

    <?php include __DIR__ . '/partials_modals.php'; ?>
</body>
</html>

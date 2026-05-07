<?php
$pageTitle = 'Mal Quiz Vault';
$questions = [
    ['class' => 'Class IX', 'type' => 'MCQ', 'difficulty' => 'Advanced', 'title' => 'Neural algebra checkpoint', 'answer' => 'Vector transformation'],
    ['class' => 'Class X', 'type' => 'Image', 'difficulty' => 'Medium', 'title' => 'Identify the secure media protocol', 'answer' => 'Signed URL delivery'],
    ['class' => 'Class XII', 'type' => 'Theory', 'difficulty' => 'Expert', 'title' => 'Explain zero-trust exam mode', 'answer' => 'Continuous verification'],
];
$media = [
    ['type' => 'image', 'name' => 'Encrypted Diagram Pack', 'meta' => '12 PNG • protected'],
    ['type' => 'video', 'name' => 'Premium Lecture Vault', 'meta' => '4K MP4 • expiring link'],
    ['type' => 'audio', 'name' => 'Listening Practice Set', 'meta' => 'AAC • watermark enabled'],
    ['type' => 'file', 'name' => 'Secure Question Archive', 'meta' => 'PDF • password required'],
];
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#020617">
    <title><?= htmlspecialchars($pageTitle) ?> — Premium Quiz Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/premium-ui.css">
</head>
<body>
    <div class="ambient-scene" aria-hidden="true">
        <span class="blob blob-cyan"></span>
        <span class="blob blob-violet"></span>
        <span class="blob blob-emerald"></span>
        <span class="particle p1"></span><span class="particle p2"></span><span class="particle p3"></span><span class="particle p4"></span>
    </div>

    <aside class="sidebar glass-panel" id="sidebar" aria-label="Dashboard navigation">
        <a class="brand" href="#dashboard" aria-label="Mal Quiz Vault home">
            <span class="brand-mark"><i class="bi bi-stars"></i></span>
            <span><strong>Mal Vault</strong><small>Secure Quiz Cloud</small></span>
        </a>
        <nav class="nav-stack">
            <?php foreach ([['dashboard','grid-1x2-fill','Dashboard'],['questions','collection-fill','Questions'],['quiz','shield-lock-fill','Quiz'],['results','graph-up-arrow','Results'],['gallery','images','Media Gallery'],['settings','sliders','Settings'],['profile','person-badge','Profile']] as $item): ?>
                <a href="#<?= $item[0] ?>" class="nav-link <?= $item[0] === 'dashboard' ? 'active' : '' ?>" data-nav-link><i class="bi bi-<?= $item[1] ?>"></i><span><?= $item[2] ?></span></a>
            <?php endforeach; ?>
        </nav>
        <button class="nav-link logout"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button>
    </aside>

    <div class="app-shell">
        <header class="topbar glass-panel">
            <button class="icon-btn hamburger" id="menuToggle" aria-expanded="false" aria-controls="sidebar"><span></span><span></span><span></span></button>
            <div class="topbar-title"><p>Secure learning workspace</p><h1>Quiz + Questions Dashboard</h1></div>
            <div class="topbar-actions">
                <label class="search-pill" aria-label="Global search"><i class="bi bi-search"></i><input id="globalSearch" placeholder="Search questions, media, results…"></label>
                <button class="icon-btn" id="themeToggle" aria-label="Toggle theme"><i class="bi bi-moon-stars"></i></button>
                <button class="btn btn-premium" data-open-modal="premiumModal"><i class="bi bi-gem"></i> Upgrade</button>
            </div>
        </header>

        <main>
            <section id="dashboard" class="hero-grid section-reveal">
                <article class="hero-card glass-panel animated-border">
                    <span class="eyebrow"><i class="bi bi-broadcast-pin"></i> Live protected session</span>
                    <h2>Ultra-premium exam control center for secure quizzes and media delivery.</h2>
                    <p>Monitor active quizzes, manage question banks, protect premium content, and keep every learner flow fast, polished, and mobile-ready.</p>
                    <div class="hero-actions">
                        <button class="btn btn-primary ripple" data-open-modal="submitModal"><i class="bi bi-play-circle-fill"></i> Start secure quiz</button>
                        <a class="btn btn-ghost" href="#gallery"><i class="bi bi-cloud-lock-fill"></i> Open vault</a>
                    </div>
                </article>
                <aside class="session-card glass-panel">
                    <div class="timer-orb" id="timerOrb"><span id="quizTimer">24:59</span><small>remaining</small></div>
                    <div class="autosave"><span></span> Auto-save active</div>
                    <div class="progress-wrap"><div class="progress-label"><span>Quiz progress</span><strong id="progressText">68%</strong></div><div class="progress"><span style="--value:68%"></span></div></div>
                </aside>
            </section>

            <section class="stats-grid section-reveal" aria-label="Dashboard statistics">
                <?php foreach ([['Questions','1,284','collection','#22d3ee'],['Active exams','37','activity','#8b5cf6'],['Secure files','218','lock','#10b981'],['Avg. score','91%','trophy','#f59e0b']] as $stat): ?>
                    <article class="stat-card glass-panel tilt-card"><i class="bi bi-<?= $stat[2] ?>" style="--accent:<?= $stat[3] ?>"></i><span><?= $stat[0] ?></span><strong class="counter" data-count="<?= preg_replace('/\D/','',$stat[1]) ?>"><?= $stat[1] ?></strong></article>
                <?php endforeach; ?>
            </section>

            <section id="quiz" class="content-grid section-reveal">
                <div class="quiz-panel glass-panel">
                    <div class="section-heading"><span class="eyebrow">Secure exam mode</span><h2>Immersive quiz experience</h2><p>Fullscreen-ready question cards with animated answers, difficulty badges, countdown states, and polished transitions.</p></div>
                    <div class="question-card animated-border" data-question-card>
                        <div class="question-top"><span class="badge danger">Expert</span><span class="badge"><i class="bi bi-shield-check"></i> Locked</span></div>
                        <h3>Which delivery strategy best protects paid downloadable media?</h3>
                        <div class="option-list">
                            <button class="option ripple">Public static folder</button>
                            <button class="option selected ripple">Short-lived signed URL with audit trail</button>
                            <button class="option ripple">Hidden HTML link</button>
                            <button class="option ripple">Renamed file extension only</button>
                        </div>
                        <div class="quiz-footer"><button class="btn btn-ghost"><i class="bi bi-arrow-left"></i> Previous</button><button class="btn btn-primary ripple" data-next-question>Next question <i class="bi bi-arrow-right"></i></button></div>
                    </div>
                </div>
                <aside class="glass-panel exam-controls">
                    <h3>Exam controls</h3>
                    <button class="btn btn-soft"><i class="bi bi-fullscreen"></i> Fullscreen mode</button>
                    <button class="btn btn-soft" data-open-modal="exitModal"><i class="bi bi-exclamation-triangle"></i> Exit warning</button>
                    <button class="btn btn-soft" data-open-modal="timeModal"><i class="bi bi-alarm"></i> Time-up preview</button>
                    <div class="skeleton-card"><span></span><span></span><span></span></div>
                </aside>
            </section>

            <section id="questions" class="section-reveal">
                <div class="section-heading"><span class="eyebrow">Question command center</span><h2>Premium questions dashboard</h2></div>
                <div class="filter-bar glass-panel">
                    <label><i class="bi bi-search"></i><input id="questionSearch" placeholder="Realtime search questions…"></label>
                    <select><option>All classes</option><option>Class IX</option><option>Class X</option><option>Class XII</option></select>
                    <select><option>All question types</option><option>MCQ</option><option>Image</option><option>Theory</option></select>
                </div>
                <div class="question-grid" id="questionGrid">
                    <?php foreach ($questions as $question): ?>
                        <article class="mcq-card glass-panel tilt-card" data-title="<?= strtolower($question['title']) ?> <?= strtolower($question['class']) ?> <?= strtolower($question['type']) ?>">
                            <div><span class="badge"><?= $question['class'] ?></span><span class="badge purple"><?= $question['type'] ?></span></div>
                            <h3><?= htmlspecialchars($question['title']) ?></h3>
                            <p>Correct answer: <strong><?= htmlspecialchars($question['answer']) ?></strong></p>
                            <span class="correct"><i class="bi bi-check2-circle"></i> Verified answer highlighted</span>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="empty-state glass-panel" id="emptyState" hidden><i class="bi bi-stars"></i><h3>No matching questions</h3><p>Try a different class, keyword, or type filter.</p></div>
            </section>

            <section id="gallery" class="section-reveal">
                <div class="section-heading"><span class="eyebrow">Secure media vault</span><h2>Protected gallery and download center</h2></div>
                <div class="masonry-gallery">
                    <?php foreach ($media as $index => $item): ?>
                        <article class="media-card glass-panel <?= $index % 2 ? 'tall' : '' ?>">
                            <div class="media-preview"><i class="bi bi-<?= ['image'=>'image','video'=>'play-btn','audio'=>'soundwave','file'=>'file-earmark-lock'][$item['type']] ?>"></i><span class="vault-ring"></span></div>
                            <div class="media-info"><span class="badge emerald">Encrypted</span><h3><?= htmlspecialchars($item['name']) ?></h3><p><?= htmlspecialchars($item['meta']) ?></p></div>
                            <div class="media-overlay"><button class="btn btn-primary ripple" data-open-modal="downloadModal"><i class="bi bi-download"></i> Secure download</button></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>

    <nav class="mobile-bottom glass-panel" aria-label="Mobile navigation">
        <?php foreach ([['dashboard','house'],['questions','collection'],['quiz','shield-lock'],['gallery','images']] as $item): ?>
            <a href="#<?= $item[0] ?>"><i class="bi bi-<?= $item[1] ?>"></i><span><?= ucfirst($item[0]) ?></span></a>
        <?php endforeach; ?>
    </nav>

    <div class="toast-stack" id="toastStack" aria-live="polite"></div>

    <div class="modal-layer" id="premiumModal" role="dialog" aria-modal="true" aria-labelledby="premiumTitle">
        <div class="premium-modal glass-panel animated-border">
            <button class="icon-btn modal-close" data-close-modal aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
            <span class="eyebrow"><i class="bi bi-gem"></i> Premium access</span>
            <h2 id="premiumTitle">Unlock secure cloud exams, smart analytics, and protected media delivery.</h2>
            <div class="feature-list"><span><i class="bi bi-lightning-charge"></i> Neon quiz flows</span><span><i class="bi bi-lock"></i> Vault downloads</span><span><i class="bi bi-cpu"></i> Admin insights</span></div>
            <div class="hero-actions"><button class="btn btn-primary ripple">Subscribe now</button><button class="btn btn-ghost" data-close-modal>Cancel</button></div>
        </div>
    </div>
    <?php foreach ([['submitModal','Submit quiz?','Your responses are auto-saved. Confirm when you are ready to submit securely.'],['timeModal','Time is up','The exam session is locked and responses are being submitted.'],['exitModal','Exit secure mode?','Leaving may trigger a session audit and pause the timer.'],['downloadModal','Protected download','Enter your password to unlock a short-lived encrypted download link.']] as $modal): ?>
        <div class="modal-layer" id="<?= $modal[0] ?>" role="dialog" aria-modal="true" aria-labelledby="<?= $modal[0] ?>Title">
            <div class="premium-modal glass-panel">
                <button class="icon-btn modal-close" data-close-modal aria-label="Close modal"><i class="bi bi-x-lg"></i></button>
                <h2 id="<?= $modal[0] ?>Title"><?= $modal[1] ?></h2><p><?= $modal[2] ?></p>
                <?php if ($modal[0] === 'downloadModal'): ?><label class="secure-input"><i class="bi bi-key"></i><input type="password" placeholder="Vault password"></label><div class="countdown">Link expires in <strong>00:30</strong></div><?php endif; ?>
                <div class="hero-actions"><button class="btn btn-primary ripple" data-close-modal>Confirm</button><button class="btn btn-ghost" data-close-modal>Cancel</button></div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="assets/js/premium-ui.js" defer></script>
</body>
</html>

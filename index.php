<?php
$config = require __DIR__ . '/config/mailboxes.php';
$defaultEmail = htmlspecialchars($config['default'], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1a73e8">
    <title>MMHNU Premium Webmail</title>
    <link rel="manifest" href="manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
    <div class="app-shell" data-account="<?= $defaultEmail ?>">
        <aside class="sidebar glass-panel" aria-label="Mailbox folders">
            <div class="brand">
                <div class="brand-mark">M</div>
                <div>
                    <strong>MMHNU Mail</strong>
                    <span>Premium Webmail</span>
                </div>
            </div>
            <button class="compose compose-desktop" data-compose>+ Compose</button>
            <nav class="folder-nav">
                <button class="active" data-folder="inbox"><span>📥 Inbox</span><b data-count="inbox">0</b></button>
                <button data-folder="starred"><span>⭐ Starred</span><b>VIP</b></button>
                <button data-folder="sent"><span>📤 Sent</span><b data-count="sent">0</b></button>
                <button data-folder="drafts"><span>📝 Drafts</span><b>4</b></button>
                <button data-folder="spam"><span>🛡 Spam</span><b>2</b></button>
                <button data-folder="trash"><span>🗑 Trash</span><b>12</b></button>
                <button data-open-admin><span>⚙️ Settings</span><b>Admin</b></button>
            </nav>
            <div class="account-card">
                <span class="status-dot online"></span>
                <div>
                    <strong><?= $defaultEmail ?></strong>
                    <small>cPanel IMAP connected</small>
                </div>
            </div>
        </aside>

        <main class="main-panel">
            <header class="topbar glass-panel">
                <button class="icon-btn mobile-menu" aria-label="Open folders">☰</button>
                <label class="search-wrap">
                    <span>🔍</span>
                    <input id="searchInput" type="search" placeholder="Search inbox, sender, subject..." autocomplete="off">
                </label>
                <button class="icon-btn notification" data-notify aria-label="Enable notifications">🔔<span id="badgeCounter">0</span></button>
                <select id="accountSwitch" aria-label="Switch mailbox">
                    <option value="<?= $defaultEmail ?>"><?= $defaultEmail ?></option>
                </select>
                <div class="avatar" title="<?= $defaultEmail ?>">H</div>
            </header>

            <section class="hero-grid">
                <article class="stat-card blue"><span>Total mail</span><strong id="totalMails">—</strong><small>Live IMAP mailbox</small></article>
                <article class="stat-card green"><span>Unread</span><strong id="unreadMails">—</strong><small>Needs attention</small></article>
                <article class="stat-card yellow"><span>Sent</span><strong id="sentMails">—</strong><small>Outbound replies</small></article>
                <article class="stat-card navy"><span>Last sync</span><strong id="lastSync">—</strong><small>Auto refresh 25s</small></article>
            </section>

            <section class="workspace glass-panel">
                <div class="mail-toolbar">
                    <div>
                        <h1 id="folderTitle">Inbox</h1>
                        <p>Fast AJAX inbox with lazy pagination, live badges, and premium actions.</p>
                    </div>
                    <div class="toolbar-actions">
                        <button class="ghost" id="bulkSelect">Select</button>
                        <button class="ghost" id="refreshButton">Refresh</button>
                    </div>
                </div>
                <div id="skeleton" class="skeleton-list" hidden>
                    <i></i><i></i><i></i><i></i>
                </div>
                <div id="mailList" class="mail-list" aria-live="polite"></div>
                <button id="loadMore" class="load-more" hidden>Load 20 more</button>
            </section>
        </main>

        <button class="compose floating" data-compose aria-label="Compose email">✎</button>

        <nav class="bottom-nav" aria-label="Mobile navigation">
            <button class="active" data-folder="inbox">📥<span>Inbox</span></button>
            <button data-folder="starred">⭐<span>Starred</span></button>
            <button data-compose>✎<span>Compose</span></button>
            <button data-open-admin>📊<span>Admin</span></button>
        </nav>
    </div>

    <div id="viewerBackdrop" class="modal-backdrop" hidden>
        <article class="mail-viewer" role="dialog" aria-modal="true" aria-labelledby="viewerSubject">
            <button class="close-btn" data-close-viewer>×</button>
            <div id="viewerContent"></div>
        </article>
    </div>

    <div id="composeBackdrop" class="modal-backdrop" hidden>
        <article class="compose-modal" role="dialog" aria-modal="true" aria-labelledby="composeTitle">
            <button class="close-btn" data-close-compose>×</button>
            <h2 id="composeTitle">New message</h2>
            <input placeholder="To" aria-label="Recipient">
            <input placeholder="Subject" aria-label="Subject">
            <textarea placeholder="Write a polished reply..." aria-label="Message body"></textarea>
            <footer><button class="ghost" data-close-compose>Cancel</button><button class="compose">Send message</button></footer>
        </article>
    </div>

    <div id="adminBackdrop" class="modal-backdrop" hidden>
        <article class="admin-modal" role="dialog" aria-modal="true" aria-labelledby="adminTitle">
            <button class="close-btn" data-close-admin>×</button>
            <h2 id="adminTitle">Admin Command Center</h2>
            <div class="admin-grid">
                <section><h3>Multi mailbox</h3><p>Switch accounts, monitor status, and add more cPanel IMAP mailboxes from configuration.</p><div id="accountList"></div></section>
                <section><h3>Activity timeline</h3><ol class="timeline"><li>Inbox synced through AJAX</li><li>Unread badge updated</li><li>PWA shell cached offline</li><li>IMAP actions logged by endpoint</li></ol></section>
                <section><h3>Logs</h3><code>api.php?action=messages · 20 per page · cache shell active</code></section>
            </div>
        </article>
    </div>

    <template id="emptyState">
        <div class="empty-state"><strong>No messages found</strong><span>Try another folder or search term.</span></div>
    </template>

    <script src="assets/app.js" defer></script>
</body>
</html>

<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';

if (isset($_GET['lang'])) {
    setLanguage((string) $_GET['lang']);
    redirect('/new-ticket.php');
}

$user = requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['_csrf'] ?? '');
    if (!validateCsrfToken('ticket_create', $token)) {
        flash('error', 'Invalid CSRF token. Refresh and try again.');
        redirect('/new-ticket.php');
    }

    try {
        $ticketId = createTicket($user, $_POST, $_FILES);
        flash('success', __('ticket.created_success'));
        redirect('/admin-ticket.php?id=' . $ticketId);
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('/new-ticket.php');
    }
}

$csrf = generateCsrfToken('ticket_create');
$flash = getFlash();
renderHead(__('ticket.create_title'), 'SaaS support portal ticket submission form.');
?>
<body>
<style>
:root {
    --bg: #060b16;
    --surface: rgba(17, 25, 40, 0.65);
    --text: #e6ecff;
    --muted: #93a4c7;
    --border: rgba(255,255,255,.16);
    --brand1: #5f7cff;
    --brand2: #8f4fff;
}
body.light {
    --bg: #f3f6ff;
    --surface: rgba(255,255,255,.75);
    --text: #111827;
    --muted: #4b5563;
    --border: rgba(17,24,39,.12);
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Inter,system-ui,sans-serif}
.page{max-width:1100px;margin:24px auto;padding:0 16px}
.topbar,.card{backdrop-filter:blur(18px);background:var(--surface);border:1px solid var(--border);border-radius:18px}
.topbar{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;position:sticky;top:10px;z-index:2}
.links a,.lang a{color:var(--muted);text-decoration:none;margin-left:12px}
.card{margin-top:20px;padding:22px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
label{display:flex;flex-direction:column;gap:7px;color:var(--muted);font-size:14px}
input,select,textarea{padding:12px;border-radius:10px;border:1px solid transparent;background:rgba(148,163,184,.12);color:var(--text)}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--brand1)}
button{border:none;border-radius:12px;padding:12px 16px;color:#fff;background:linear-gradient(135deg,var(--brand1),var(--brand2));cursor:pointer}
.flash{padding:12px 14px;border-radius:10px;margin-bottom:12px}
.flash.error{background:#dc2626}.flash.success{background:#16a34a}
.loading{display:none;font-size:13px;color:var(--muted)}
@media (max-width: 760px){.grid{grid-template-columns:1fr}.topbar{flex-direction:column;gap:10px;align-items:flex-start}}
</style>

<div class="page">
    <div class="topbar">
        <strong>Support SaaS</strong>
        <div>
            <span class="lang">
                <a href="?lang=en">EN</a>
                <a href="?lang=ml">ML</a>
            </span>
            <span class="links">
                <a href="/admin-dashboard.php">Dashboard</a>
                <a href="/logout.php">Logout</a>
            </span>
            <button id="themeToggle" type="button">🌗</button>
        </div>
    </div>

    <section class="card">
        <?php if ($flash): ?>
            <div class="flash <?= e((string) $flash['type']) ?>"><?= e((string) $flash['message']) ?></div>
        <?php endif; ?>

        <h1><?= e(__('ticket.create_header')) ?></h1>

        <form method="post" enctype="multipart/form-data" id="ticketForm">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

            <label><?= e(__('ticket.subject')) ?>
                <input name="subject" required maxlength="180">
            </label>

            <div class="grid">
                <label>Order Reference
                    <input name="order_reference" maxlength="100">
                </label>
                <label><?= e(__('ticket.priority')) ?>
                    <select name="priority">
                        <option>Low</option>
                        <option selected>Normal</option>
                        <option>High</option>
                        <option>Urgent</option>
                    </select>
                </label>
            </div>

            <div class="grid">
                <label><?= e(__('ticket.category')) ?>
                    <input name="category" value="General Support" maxlength="100">
                </label>
                <label><?= e(__('ticket.tags')) ?>
                    <input name="tags" placeholder="payment, bug, urgent">
                </label>
            </div>

            <label><?= e(__('ticket.message')) ?>
                <textarea name="message" rows="8" required></textarea>
            </label>

            <label><?= e(__('ticket.attachments')) ?>
                <input type="file" name="attachments[]" multiple accept=".png,.jpg,.jpeg,.pdf,.txt">
            </label>

            <button type="submit" id="submitBtn">Create Ticket</button>
            <span class="loading" id="loadingTxt">Submitting ticket...</span>
        </form>
    </section>
</div>

<script>
(() => {
    const body = document.body;
    const key = 'support-theme';
    const saved = localStorage.getItem(key);
    if (saved === 'light') body.classList.add('light');

    document.getElementById('themeToggle').addEventListener('click', () => {
        body.classList.toggle('light');
        localStorage.setItem(key, body.classList.contains('light') ? 'light' : 'dark');
    });

    const form = document.getElementById('ticketForm');
    form.addEventListener('submit', () => {
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('loadingTxt').style.display = 'inline';
    });
})();
</script>
</body>
<?php renderFooter(); ?>

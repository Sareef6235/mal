<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
$token = csrf_token();
$dbError = '';
try {
    $rules = db()->query('SELECT * FROM regex_rules ORDER BY sort_order ASC, id ASC')->fetchAll();
} catch (Throwable $e) {
    $rules = [];
    $dbError = $e->getMessage();
}
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h($token) ?>">
    <title>Regex Settings · <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="index.php"><span class="brand-mark">OCR</span><span><strong>Text Extractor</strong><small>Regex Admin</small></span></a>
        <nav class="nav-menu"><a href="index.php#dashboard">Dashboard</a><a href="index.php#upload">Upload File</a><a href="index.php#extracted">Extracted Text</a><a class="active" href="settings.php">Regex Settings</a><a href="index.php#history">History</a></nav>
        <div class="sidebar-card"><span class="pulse"></span><strong>Admin panel</strong><p>Add, test, enable, disable, and order regex cleanup rules without code changes.</p></div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="icon-button mobile-only" type="button" data-toggle-sidebar>☰</button>
            <label class="search-wrap"><span>⌕</span><input type="search" id="settingsSearch" placeholder="Search regex rules..."></label>
            <button class="theme-toggle" type="button" data-theme-toggle><span>☾</span> Dark</button>
            <div class="profile"><span>Admin</span><strong>A</strong></div>
        </header>

        <?php if ($dbError): ?><section class="alert danger"><strong>Database connection failed.</strong><p><?= h($dbError) ?></p></section><?php endif; ?>

        <section class="hero compact">
            <div>
                <span class="eyebrow">Dynamic regex engine</span>
                <h1>Regex Settings</h1>
                <p>Create reusable cleanup rules that are applied to every OCR extraction in sort order.</p>
            </div>
            <button class="gradient-button" type="button" data-open-rule-modal>Add regex rule</button>
        </section>

        <section class="grid two-col">
            <article class="glass-card wide-card">
                <div class="section-title"><span>01</span><div><h2>Rules Library</h2><p>Enabled rules are applied automatically after extraction.</p></div></div>
                <div class="table-wrap">
                    <table class="rules-table" id="rulesTable">
                        <thead><tr><th>Order</th><th>Name</th><th>Pattern</th><th>Replacement</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($rules as $rule): ?>
                            <tr data-rule='<?= h(json_encode($rule, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
                                <td><?= (int) $rule['sort_order'] ?></td>
                                <td><strong><?= h($rule['name']) ?></strong></td>
                                <td><code><?= h($rule['pattern']) ?></code></td>
                                <td><?php $replacementPreview = (string) $rule['replacement']; echo h(strlen($replacementPreview) > 40 ? substr($replacementPreview, 0, 40) . '…' : $replacementPreview); ?></td>
                                <td><span class="status <?= $rule['enabled'] ? 'ok' : 'off' ?>"><?= $rule['enabled'] ? 'Enabled' : 'Disabled' ?></span></td>
                                <td class="table-actions"><button class="ghost-button small" data-edit-rule>Edit</button><button class="danger-button small" data-delete-rule="<?= (int) $rule['id'] ?>">Delete</button></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$rules): ?><tr><td colspan="6" class="muted">No rules yet. Add your first cleanup rule.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="glass-card">
                <div class="section-title"><span>02</span><div><h2>Test Regex</h2><p>Validate a rule before saving it.</p></div></div>
                <label class="field"><span>Pattern</span><input id="testPattern" placeholder="Example: \\s{2,}"></label>
                <label class="field"><span>Replacement</span><input id="testReplacement" placeholder="Example: single space"></label>
                <label class="field"><span>Flags</span><input id="testFlags" value="u"></label>
                <label class="field"><span>Sample text</span><textarea id="testSample" rows="6">Paste noisy OCR text   here.</textarea></label>
                <button class="gradient-button" id="testRegex" type="button">Test regex</button>
                <pre class="preview-box" id="testOutput">Result will appear here.</pre>
            </article>
        </section>
    </main>
</div>

<div class="modal" id="ruleModal" aria-hidden="true">
    <form class="modal-card" id="ruleForm">
        <button class="modal-close" type="button" data-close-modal>&times;</button>
        <h2 id="ruleModalTitle">Add regex rule</h2>
        <input type="hidden" name="csrf_token" value="<?= h($token) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="ruleId">
        <label class="field"><span>Name</span><input name="name" id="ruleName" required maxlength="160" placeholder="Normalize whitespace"></label>
        <label class="field"><span>Pattern</span><input name="pattern" id="rulePattern" required maxlength="500" placeholder="\\s{2,}"></label>
        <label class="field"><span>Replacement</span><textarea name="replacement" id="ruleReplacement" rows="3" placeholder=" "></textarea></label>
        <div class="form-grid"><label class="field"><span>Flags</span><input name="flags" id="ruleFlags" value="u" maxlength="20"></label><label class="field"><span>Sort order</span><input name="sort_order" id="ruleSort" type="number" value="100"></label></div>
        <label class="switch"><input type="checkbox" name="enabled" id="ruleEnabled" checked><span></span> Enabled</label>
        <button class="gradient-button full" type="submit">Save rule</button>
    </form>
</div>
<div class="toast-stack" id="toastStack"></div>
<script src="assets/js/app.js"></script>
</body>
</html>

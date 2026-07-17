<?php
declare(strict_types=1);

/**
 * Document automation dashboard data adapter.
 * It uses the host application's db() PDO helper when available and gracefully
 * degrades to empty states while the document tables are being installed.
 */
$functions = __DIR__ . '/../includes/functions.php';
if (is_file($functions)) {
    require_once $functions;
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (function_exists('require_login')) {
    require_login();
}
if (empty($_SESSION['document_csrf_token'])) {
    $_SESSION['document_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['document_csrf_token'];
$currentUser = $_SESSION['user'] ?? [];
$currentUserName = (string) ($currentUser['name'] ?? $currentUser['full_name'] ?? $_SESSION['username'] ?? 'Administrator');
$currentUserInitials = strtoupper(implode('', array_map(static fn(string $word): string => $word[0], array_slice(preg_split('/\s+/', trim($currentUserName)) ?: ['A'], 0, 2))));

function doc_h(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function doc_db(): ?PDO { try { return function_exists('db') ? db() : null; } catch (Throwable) { return null; } }
function doc_table_exists(PDO $pdo, string $table): bool { $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'); $q->execute([$table]); return (bool) $q->fetchColumn(); }
function doc_count(PDO $pdo, string $sql, array $params=[]): int { $q=$pdo->prepare($sql); $q->execute($params); return (int) $q->fetchColumn(); }

$pdo = doc_db();
$dashboard = ['templates'=>0, 'generated'=>0, 'today'=>0, 'attendance'=>0, 'storage'=>0.0];
$recentTemplates = [];
$recentActivity = [];
if ($pdo) {
    // These checks keep this page compatible with the provided attendance schema
    // as well as optional document_automation tables when they are introduced.
    if (doc_table_exists($pdo, 'document_templates')) {
        $dashboard['templates'] = doc_count($pdo, 'SELECT COUNT(*) FROM document_templates WHERE COALESCE(status, "active") != "archived"');
        $recentTemplates = $pdo->query('SELECT id, name, category, version, updated_at FROM document_templates ORDER BY updated_at DESC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    if (doc_table_exists($pdo, 'generated_documents')) {
        $dashboard['generated'] = doc_count($pdo, 'SELECT COUNT(*) FROM generated_documents');
        $dashboard['today'] = doc_count($pdo, 'SELECT COUNT(*) FROM generated_documents WHERE DATE(created_at)=CURDATE()');
        $dashboard['storage'] = (float) doc_count($pdo, 'SELECT COALESCE(SUM(file_size),0) FROM generated_documents') / 1048576;
    }
    if (doc_table_exists($pdo, 'attendance_logs')) {
        $dashboard['attendance'] = doc_count($pdo, 'SELECT COUNT(*) FROM attendance_logs WHERE DATE(scan_time)=CURDATE()');
        $recentActivity = $pdo->query("SELECT l.scan_time, l.member_id, l.status, COALESCE(m.full_name, 'Unknown member') AS full_name FROM attendance_logs l LEFT JOIN members m ON m.member_uid=l.member_id ORDER BY l.scan_time DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
if (($_GET['ajax'] ?? '') === 'document_automation') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, (string) $token)) { http_response_code(419); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>http_response_code() !== 419, 'dashboard'=>$dashboard, 'templates'=>$recentTemplates, 'activity'=>$recentActivity], JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FlowDocs — Document Automation</title>
  <meta name="csrf-token" content="<?= doc_h($csrfToken) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="#"><span class="brand-mark"><i class="bi bi-file-earmark-richtext-fill"></i></span><span class="brand-name">Flow<span>Docs</span></span><i class="bi bi-chevron-left collapse-icon"></i></a>
    <div class="workspace"><span class="workspace-icon">AC</span><div><small>WORKSPACE</small><strong>Acme Corporation</strong></div><i class="bi bi-chevron-expand"></i></div>
    <nav class="side-nav">
      <p>WORKSPACE</p><a href="#"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>
      <p>ATTENDANCE</p><a href="#"><i class="bi bi-calendar2-check"></i><span>Attendance</span></a><a href="#"><i class="bi bi-qr-code-scan"></i><span>QR Attendance</span></a><a href="#"><i class="bi bi-person-bounding-box"></i><span>Face Attendance</span></a><a href="#"><i class="bi bi-geo-alt"></i><span>GPS Attendance</span></a>
      <p>DOCUMENTS</p><a class="active" href="#"><i class="bi bi-magic"></i><span>Document Automation</span><b>New</b></a><a href="#"><i class="bi bi-file-earmark-word"></i><span>Word Templates</span></a><a href="#"><i class="bi bi-diagram-3"></i><span>Auto Mapping</span></a><a href="#"><i class="bi bi-braces"></i><span>Placeholder Manager</span></a><a href="#"><i class="bi bi-files"></i><span>Generated Documents</span></a><a href="#"><i class="bi bi-clock-history"></i><span>Document History</span></a>
      <p>OPERATIONS</p><a href="#"><i class="bi bi-layers"></i><span>Bulk Generate</span></a><a href="#"><i class="bi bi-award"></i><span>Certificates</span></a><a href="#"><i class="bi bi-person-vcard"></i><span>ID Cards</span></a><a href="#"><i class="bi bi-bar-chart"></i><span>Reports</span></a><a href="#"><i class="bi bi-box-arrow-up-right"></i><span>Exports</span></a>
    </nav>
    <div class="sidebar-footer"><a href="#"><i class="bi bi-gear"></i><span>Settings</span></a><div class="admin-card"><span class="avatar mini">JS</span><div><b>Jordan Smith</b><small>Administrator</small></div><i class="bi bi-three-dots"></i></div></div>
  </aside>
  <main class="main-content">
    <header class="topbar"><button class="icon-btn d-lg-none" id="menuBtn"><i class="bi bi-list"></i></button><div class="search"><i class="bi bi-search"></i><input placeholder="Search templates, documents, members..."><kbd>⌘ K</kbd></div><div class="top-actions"><button class="icon-btn"><i class="bi bi-moon-stars"></i></button><button class="icon-btn notification"><i class="bi bi-bell"></i><em></em></button><div class="date-pill"><i class="bi bi-calendar3"></i><span><?= doc_h(date("l, d M")) ?></span><b><?= doc_h(date("h:i A")) ?></b></div><span class="avatar"><?= doc_h($currentUserInitials) ?></span></div></header>
    <div class="content-wrap">
      <div class="breadcrumb-line"><span>Workspace</span><i class="bi bi-chevron-right"></i><span>Documents</span><i class="bi bi-chevron-right"></i><strong>Automation</strong><div class="ms-auto"><button class="help-btn"><i class="bi bi-question-circle"></i> Help center</button></div></div>
      <section class="hero-card"><div class="hero-copy"><div class="eyebrow"><span class="live-dot"></span> AUTOMATION HUB</div><h1>Documents, <span>beautifully</span><br>automated.</h1><p>Turn your Word templates into intelligent, data-driven documents in minutes — not hours.</p><div class="hero-actions"><button class="btn btn-primary-premium" data-bs-toggle="modal" data-bs-target="#uploadModal"><i class="bi bi-cloud-arrow-up"></i> Upload template</button><button class="btn btn-glass"><i class="bi bi-play-circle"></i> See how it works</button></div></div><div class="hero-visual"><div class="float-card card-a"><span class="doc-icon word"><i class="bi bi-file-earmark-word-fill"></i></span><div><b>Attendance Register</b><small>Ready to generate</small></div><i class="bi bi-check-circle-fill"></i></div><div class="document-preview"><div class="doc-head"><span class="m-word">W</span><i class="bi bi-three-dots"></i></div><b>Certificate of Attendance</b><div class="doc-line long"></div><div class="doc-line"></div><div class="doc-highlight">{{full_name}}</div><div class="doc-line mid"></div><div class="doc-sign">✓</div></div><div class="float-card card-b"><span class="sparkle">✦</span><div><b>24 fields mapped</b><small>100% confidence</small></div></div></div></section>
      <section class="metrics row g-3"><div class="col-sm-6 col-xl-3"><article class="metric"><span class="metric-icon purple"><i class="bi bi-file-earmark-text"></i></span><div><small>ACTIVE TEMPLATES</small><h2><?= $dashboard['templates'] ?> <b>active</b></h2></div><i class="bi bi-three-dots-vertical dots"></i></article></div><div class="col-sm-6 col-xl-3"><article class="metric"><span class="metric-icon blue"><i class="bi bi-files"></i></span><div><small>DOCUMENTS GENERATED</small><h2><?= $dashboard['generated'] ?> <b class="up">generated</b></h2></div><i class="bi bi-three-dots-vertical dots"></i></article></div><div class="col-sm-6 col-xl-3"><article class="metric"><span class="metric-icon amber"><i class="bi bi-calendar-check"></i></span><div><small>TODAY'S ATTENDANCE</small><h2><?= $dashboard['attendance'] ?> <b>live records</b></h2></div><i class="bi bi-three-dots-vertical dots"></i></article></div><div class="col-sm-6 col-xl-3"><article class="metric"><span class="metric-icon green"><i class="bi bi-hdd-stack"></i></span><div><small>STORAGE USED</small><h2><?= number_format($dashboard['storage'], 1) ?> <small>MB</small><b>stored</b></h2></div><i class="bi bi-three-dots-vertical dots"></i><div class="storage"><span></span></div></article></div></section>
      <section class="work-grid"><div class="panel templates-panel"><div class="panel-head"><div><span class="eyebrow plain">TEMPLATE LIBRARY</span><h3>Your recent templates</h3></div><a href="#">View all <i class="bi bi-arrow-right"></i></a></div><div class="template-list">
<?php if ($recentTemplates): foreach ($recentTemplates as $template): ?>
  <div class="template-row"><span class="doc-icon word"><i class="bi bi-file-earmark-word-fill"></i></span><div class="template-name"><b><?= doc_h($template['name']) ?></b><small><i class="bi bi-clock"></i> Updated <?= doc_h(date('M j', strtotime((string) $template['updated_at']))) ?> <span>•</span> v<?= doc_h($template['version'] ?? '1.0') ?></small></div><span class="tag blue-tag"><?= doc_h($template['category'] ?? 'General') ?></span><button class="more" aria-label="Template actions"><i class="bi bi-three-dots"></i></button></div>
<?php endforeach; else: ?>
  <div class="empty-state"><i class="bi bi-file-earmark-plus"></i><div><b>No document templates yet</b><small>Upload a DOCX template to begin auto-mapping your live data.</small></div></div>
<?php endif; ?>
</div><button class="create-template"><span><i class="bi bi-plus-lg"></i></span><div><b>Create a new template</b><small>Upload a DOCX or start from a blank canvas</small></div><i class="bi bi-arrow-right ms-auto"></i></button></div>
      <div class="panel quick-panel"><div class="panel-head"><div><span class="eyebrow plain">QUICK ACTIONS</span><h3>Get started</h3></div></div><button class="quick-card" data-bs-toggle="modal" data-bs-target="#uploadModal"><span class="quick-icon violet"><i class="bi bi-cloud-arrow-up"></i></span><div><b>Upload a template</b><small>DOCX, DOTX up to 25 MB</small></div><i class="bi bi-arrow-up-right"></i></button><button class="quick-card generate"><span class="quick-icon aqua"><i class="bi bi-lightning-charge"></i></span><div><b>Generate document</b><small>Choose a template & data source</small></div><i class="bi bi-arrow-up-right"></i></button><button class="quick-card"><span class="quick-icon orange-q"><i class="bi bi-diagram-3"></i></span><div><b>Review field mappings</b><small><strong>2</strong> fields need your attention</small></div><i class="bi bi-arrow-up-right"></i></button></div></section>
      <section class="lower-grid"><div class="panel activity"><div class="panel-head"><div><span class="eyebrow plain">ACTIVITY</span><h3>Recent activity</h3></div><button class="filter-btn">This week <i class="bi bi-chevron-down"></i></button></div><div class="activity-feed">
<?php if ($recentActivity): foreach ($recentActivity as $activity): ?>
  <div class="activity-line"><span class="activity-icon green"><i class="bi bi-check-lg"></i></span><p><b><?= doc_h($activity['full_name']) ?></b> attendance was recorded <small><?= doc_h(date('M j, h:i A', strtotime((string) $activity['scan_time']))) ?> · <?= doc_h(ucfirst((string) $activity['status'])) ?></small></p><a href="#">Open</a></div>
<?php endforeach; else: ?>
  <div class="empty-state"><i class="bi bi-activity"></i><div><b>No attendance activity today</b><small>Recent scans from <code>attendance_logs</code> will appear here.</small></div></div>
<?php endif; ?>
</div></div><div class="panel processing"><div class="panel-head"><div><span class="eyebrow plain">SYSTEM STATUS</span><h3>Automation health</h3></div><span class="status-ok"><i class="bi bi-check-circle-fill"></i> All systems operational</span></div><div class="health-row"><div><span>Template processing</span><small><?= $dashboard['templates'] ?> templates</small></div><div class="progress"><div style="width:96%"></div></div><b>96%</b></div><div class="health-row"><div><span>Field mapping accuracy</span><small>Last 30 days</small></div><div class="progress"><div style="width:98%"></div></div><b>98%</b></div><div class="health-row"><div><span>Document delivery</span><small>Last 24 hours</small></div><div class="progress"><div style="width:100%"></div></div><b>100%</b></div><div class="tip"><i class="bi bi-stars"></i><p><b>AI Mapping is learning</b><br>It's now 12% more accurate than last month.</p></div></div></section>
    </div>
  </main>
</div>
<div class="modal fade" id="uploadModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content upload-modal"><button class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button><div class="modal-icon"><i class="bi bi-cloud-arrow-up"></i></div><h3>Add a Word template</h3><p>We’ll scan its placeholders and map your data automatically.</p><label class="drop-zone" for="templateFile"><input id="templateFile" name="template" type="file" accept=".docx" hidden><i class="bi bi-file-earmark-arrow-up"></i><b>Drop your file here, or <span>browse</span></b><small>DOCX or DOTX · up to 25 MB</small></label><button class="btn btn-primary-premium w-100 mt-3" id="uploadButton"><i class="bi bi-magic"></i> Upload & auto-map</button></div></div></div>
<div class="toast-container position-fixed bottom-0 end-0 p-4"><div id="successToast" class="toast success-toast border-0" role="alert"><div class="toast-body"><i class="bi bi-check-circle-fill"></i><div><b>Template uploaded successfully</b><span>24 fields were mapped automatically.</span></div><button data-bs-dismiss="toast"><i class="bi bi-x"></i></button></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="assets/js/app.js"></script>
</body></html>

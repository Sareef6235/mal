<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$functionsFile = __DIR__ . '/../includes/functions.php';
if (is_file($functionsFile)) {
    require_once $functionsFile;
}

$title = 'Attendance Register Form Designer';
$headerFile = __DIR__ . '/../includes/header.php';
if (is_file($headerFile)) {
    require_once $headerFile;
}
if (function_exists('require_login')) {
    require_login();
}

$currentUser = $_SESSION['user'] ?? [];
$currentUserId = (int)($currentUser['id'] ?? $_SESSION['user_id'] ?? 0);
$currentUserName = (string)($currentUser['name'] ?? $currentUser['full_name'] ?? $_SESSION['username'] ?? 'Administrator');
$currentUserRole = strtolower((string)($currentUser['role'] ?? $_SESSION['role'] ?? 'administrator'));
$isAdministrator = in_array($currentUserRole, ['admin', 'administrator', 'super_admin', 'super administrator'], true);

if (empty($_SESSION['attendance_csrf_token'])) {
    $_SESSION['attendance_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['attendance_csrf_token'];

const AFD_TEMPLATE_FILE = __DIR__ . '/attendance-form-template.json';
const AFD_UPLOAD_DIR = __DIR__ . '/../uploads/attendance-form-designer';
const AFD_UPLOAD_URL = '/idd/uploads/attendance-form-designer';

function attendance_h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function attendance_db(): ?PDO
{
    try {
        if (function_exists('db')) {
            return db();
        }
    } catch (Throwable $e) {
        return null;
    }

    return null;
}

function attendance_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    exit;
}

function attendance_assert_ajax(string $csrfToken): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, (string)$token)) {
        attendance_json(['ok' => false, 'message' => 'Invalid security token.'], 419);
    }
}

function attendance_table_exists(PDO $pdo, string $table): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $statement->execute([$table]);

    return (int)$statement->fetchColumn() > 0;
}

function attendance_column_exists(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $statement->execute([$table, $column]);

    return (int)$statement->fetchColumn() > 0;
}

function attendance_seed_events(): array
{
    return [
        ['id' => 1, 'name' => 'Attendance', 'icon' => 'bi-person-check', 'color' => '#2563eb', 'status' => 'active'],
        ['id' => 2, 'name' => 'Class Attendance', 'icon' => 'bi-mortarboard', 'color' => '#4f46e5', 'status' => 'active'],
        ['id' => 3, 'name' => 'Exam Attendance', 'icon' => 'bi-clipboard-check', 'color' => '#7c3aed', 'status' => 'active'],
        ['id' => 4, 'name' => 'Prayer Attendance', 'icon' => 'bi-moon-stars', 'color' => '#0891b2', 'status' => 'active'],
        ['id' => 5, 'name' => 'Meeting Attendance', 'icon' => 'bi-people', 'color' => '#0f766e', 'status' => 'active'],
        ['id' => 6, 'name' => 'Visitor Entry', 'icon' => 'bi-person-badge', 'color' => '#ea580c', 'status' => 'active'],
        ['id' => 7, 'name' => 'Library Entry', 'icon' => 'bi-journal-bookmark', 'color' => '#9333ea', 'status' => 'active'],
        ['id' => 8, 'name' => 'Food Distribution', 'icon' => 'bi-cup-hot', 'color' => '#16a34a', 'status' => 'active'],
        ['id' => 9, 'name' => 'Certificate Collection', 'icon' => 'bi-award', 'color' => '#ca8a04', 'status' => 'active'],
        ['id' => 10, 'name' => 'Custom Event', 'icon' => 'bi-stars', 'color' => '#dc2626', 'status' => 'active'],
    ];
}

function attendance_events(?PDO $pdo = null): array
{
    if (!$pdo || !attendance_table_exists($pdo, 'attendance_events')) {
        return attendance_seed_events();
    }

    $statement = $pdo->query("SELECT id,name,icon,color,status FROM attendance_events ORDER BY FIELD(status,'active','default','disabled'),name");
    if (!$statement) {
        return attendance_seed_events();
    }

    $events = $statement->fetchAll(PDO::FETCH_ASSOC);

    return $events ?: attendance_seed_events();
}

function attendance_departments(PDO $pdo = null): array
{
    if (!$pdo || !attendance_table_exists($pdo, 'departments')) {
        return [];
    }

    $stmt = $pdo->query("SELECT id,name FROM departments WHERE status='active' ORDER BY name");

    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function attendance_classes(PDO $pdo = null): array
{
    if (!$pdo || !attendance_table_exists($pdo, 'classes')) {
        return [];
    }

    $stmt = $pdo->query('SELECT id,class_name FROM classes ORDER BY class_name');

    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function attendance_recent(PDO $pdo = null, array $filters = []): array
{
    if (!$pdo || !attendance_table_exists($pdo, 'attendance_logs')) {
        return [];
    }

    $where = [];
    $params = [];
    $search = trim((string)($filters['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(m.full_name LIKE ? OR l.member_id LIKE ? OR l.verify_token LIKE ? OR d.name LIKE ? OR c.class_name LIKE ? OR l.operator LIKE ? OR l.status LIKE ?)';
        $term = '%' . $search . '%';
        array_push($params, $term, $term, $term, $term, $term, $term, $term);
    }

    $range = (string)($filters['range'] ?? 'today');
    if ($range === 'yesterday') {
        $where[] = 'DATE(l.scan_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
    } elseif ($range === 'week') {
        $where[] = 'l.scan_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    } elseif ($range === 'month') {
        $where[] = 'l.scan_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
    } elseif ($range === 'all') {
        $where[] = '1=1';
    } elseif ($range === 'custom' && !empty($filters['date'])) {
        $where[] = 'DATE(l.scan_time) = ?';
        $params[] = $filters['date'];
    } else {
        $where[] = 'DATE(l.scan_time) = CURDATE()';
    }

    foreach (['department' => 'd.name', 'class' => 'c.class_name', 'status' => 'l.status', 'type' => 'e.name'] as $key => $column) {
        if (!empty($filters[$key])) {
            $where[] = "$column = ?";
            $params[] = $filters[$key];
        }
    }

    $signatureSelect = attendance_column_exists($pdo, 'members', 'signature') ? "COALESCE(m.signature, '')" : "''";
    $mobileSelect = attendance_column_exists($pdo, 'members', 'mobile') ? "COALESCE(m.mobile, '')" : (attendance_column_exists($pdo, 'members', 'phone') ? "COALESCE(m.phone, '')" : "''");
    $msrSelect = attendance_column_exists($pdo, 'members', 'msr_number') ? "COALESCE(m.msr_number, m.member_uid)" : "COALESCE(m.member_uid, '')";
    $madrassaSelect = attendance_column_exists($pdo, 'members', 'madrassa') ? "COALESCE(m.madrassa, '')" : "''";

    $sql = "SELECT l.id, l.member_id, l.verify_token, l.scan_time, l.device, l.operator, l.status, l.remarks,
                   COALESCE(m.full_name, 'Unknown Member') full_name, COALESCE(m.photo, '') photo,
                   {$signatureSelect} signature,
                   {$mobileSelect} mobile,
                   {$msrSelect} msr_number,
                   {$madrassaSelect} madrassa,
                   COALESCE(d.name, 'Unassigned') AS department,
                   COALESCE(c.class_name, '-') AS class,
                   COALESCE(e.name, 'Attendance') event_name, COALESCE(e.icon, 'bi-person-check') event_icon, COALESCE(e.color, '#2563eb') event_color
            FROM attendance_logs l
            LEFT JOIN members m ON m.member_uid = l.member_id
            LEFT JOIN departments d ON d.id = m.department_id
            LEFT JOIN classes c ON c.id = m.class_id
            LEFT JOIN attendance_events e ON e.id = l.event_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY l.scan_time DESC, l.id DESC LIMIT 500";
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}


function attendance_form_default_columns(): array
{
    return [
        ['key' => 'photo', 'label' => 'Photo', 'enabled' => true, 'width' => 9, 'align' => 'center'],
        ['key' => 'msr_number', 'label' => 'MSR Number', 'enabled' => true, 'width' => 12, 'align' => 'center'],
        ['key' => 'member_id', 'label' => 'Member ID', 'enabled' => true, 'width' => 13, 'align' => 'center'],
        ['key' => 'full_name', 'label' => 'Name', 'enabled' => true, 'width' => 20, 'align' => 'left'],
        ['key' => 'mobile', 'label' => 'Mobile', 'enabled' => true, 'width' => 14, 'align' => 'center'],
        ['key' => 'department', 'label' => 'Department', 'enabled' => true, 'width' => 15, 'align' => 'center'],
        ['key' => 'class', 'label' => 'Class', 'enabled' => true, 'width' => 10, 'align' => 'center'],
        ['key' => 'madrassa', 'label' => 'Madrassa', 'enabled' => true, 'width' => 14, 'align' => 'center'],
        ['key' => 'event_name', 'label' => 'Attendance Type', 'enabled' => true, 'width' => 15, 'align' => 'center'],
        ['key' => 'attendance_time', 'label' => 'Time', 'enabled' => true, 'width' => 12, 'align' => 'center'],
        ['key' => 'attendance_date', 'label' => 'Date', 'enabled' => true, 'width' => 14, 'align' => 'center'],
        ['key' => 'remarks', 'label' => 'Remarks', 'enabled' => true, 'width' => 18, 'align' => 'left'],
        ['key' => 'signature', 'label' => 'Signature', 'enabled' => true, 'width' => 18, 'align' => 'center'],
    ];
}

function attendance_form_columns(array $template): array
{
    $decoded = json_decode((string)($template['columns_json'] ?? ''), true);
    $columns = is_array($decoded) ? $decoded : attendance_form_default_columns();
    return array_values(array_filter(array_map(static function ($column): array {
        $key = preg_replace('/[^a-z0-9_]/i', '', (string)($column['key'] ?? 'custom')) ?: 'custom';
        return [
            'key' => $key,
            'label' => trim((string)($column['label'] ?? $key)) ?: $key,
            'enabled' => filter_var($column['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'width' => max(6, min(40, (float)($column['width'] ?? 12))),
            'align' => in_array(($column['align'] ?? 'center'), ['left', 'center', 'right'], true) ? $column['align'] : 'center',
        ];
    }, $columns), static fn ($column): bool => (bool)$column['enabled']));
}

function attendance_form_column_value(array $row, string $key): string
{
    $time = strtotime((string)($row['scan_time'] ?? 'now')) ?: time();
    return match ($key) {
        'attendance_time' => date('H:i:s', $time),
        'attendance_date' => date('Y-m-d', $time),
        'msr_number' => (string)($row['msr_number'] ?? $row['member_id'] ?? ''),
        'madrassa' => (string)($row['madrassa'] ?? $row['department'] ?? ''),
        'remarks' => (string)($row['remarks'] ?? $row['status'] ?? 'present'),
        default => (string)($row[$key] ?? ''),
    };
}

function attendance_form_default_template(): array
{
    return [
        'paper_size' => 'A4', 'orientation' => 'portrait', 'margin_top' => 12, 'margin_right' => 10, 'margin_bottom' => 12, 'margin_left' => 10,
        'header_height' => 31, 'footer_height' => 14, 'font_family' => 'Inter, Arial, sans-serif', 'font_size' => 9, 'text_color' => '#0f172a',
        'primary_color' => '#2563eb', 'border_color' => '#cbd5e1', 'background_color' => '#ffffff', 'table_header_bg' => '#eff6ff',
        'row_height' => 16, 'photo_width' => 14, 'photo_height' => 13, 'signature_width' => 27, 'signature_height' => 11,
        'logo_url' => '', 'right_logo_url' => '', 'seal_url' => '', 'header' => 'Institution Name', 'title' => 'Common Attendance Register',
        'institution_address' => 'Institution address', 'institution_phone' => '+91 00000 00000', 'institution_email' => '',
        'subtitle' => 'Automatically populated after successful attendance scanning', 'footer' => 'Generated from attendance.php scanner records',
        'watermark' => 'ATTENDANCE', 'range_name' => 'Main Range', 'meeting_name' => 'Daily Attendance', 'meeting_number' => '001', 'meeting_date' => date('Y-m-d'), 'meeting_place' => 'Main Campus',
        'header_bg' => '#ffffff', 'header_border' => '#0f172a', 'header_align' => 'center', 'header_font_weight' => '700', 'header_gap' => 4, 'logo_size' => 18, 'seal_size' => 18,
        'logo_x' => 0, 'logo_y' => 0, 'seal_x' => 0, 'seal_y' => 0, 'right_logo_x' => 0, 'right_logo_y' => 0,
        'show_remarks' => '1', 'show_page_number' => '1', 'active' => '1', 'updated_by' => '', 'updated_at' => gmdate('c'),
        'table_width' => 100, 'table_align' => 'center', 'table_x' => 0, 'table_border_radius' => 0, 'table_shadow' => '0',
        'cell_padding' => 3, 'cell_spacing' => 0, 'border_thickness' => 1, 'header_text_color' => '#0f172a', 'row_color' => '#ffffff',
        'alternate_row_color' => '#f8fafc', 'table_text_align' => 'center', 'show_grid' => '1', 'show_margin_guide' => '1', 'show_ruler' => '1',
        'columns_json' => json_encode(attendance_form_default_columns(), JSON_UNESCAPED_SLASHES),
    ];
}

function attendance_form_template(): array
{
    $default = attendance_form_default_template();
    if (is_file(AFD_TEMPLATE_FILE)) {
        $loaded = json_decode((string)file_get_contents(AFD_TEMPLATE_FILE), true);
        if (is_array($loaded)) {
            return array_merge($default, $loaded, ['active' => '1']);
        }
    }

    return $default;
}

function attendance_form_upload(string $field): string
{
    if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return '';
    }

    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
    $mime = mime_content_type($_FILES[$field]['tmp_name']) ?: '';
    if (!isset($allowed[$mime])) {
        return '';
    }

    if (!is_dir(AFD_UPLOAD_DIR)) {
        mkdir(AFD_UPLOAD_DIR, 0775, true);
    }

    $name = $field . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    if (move_uploaded_file($_FILES[$field]['tmp_name'], AFD_UPLOAD_DIR . '/' . $name)) {
        return AFD_UPLOAD_URL . '/' . $name;
    }

    return '';
}

function attendance_form_save(string $currentUserName): array
{
    $template = attendance_form_template();
    foreach (attendance_form_default_template() as $key => $value) {
        if (array_key_exists($key, $_POST)) {
            $template[$key] = trim((string)$_POST[$key]);
        }
    }
    if (isset($_POST['columns_json'])) {
        $decodedColumns = json_decode((string)$_POST['columns_json'], true);
        if (is_array($decodedColumns)) {
            $template['columns_json'] = json_encode($decodedColumns, JSON_UNESCAPED_SLASHES);
        }
    }

    foreach (['logo_url' => 'logo_upload', 'right_logo_url' => 'right_logo_upload', 'seal_url' => 'seal_upload'] as $target => $field) {
        $uploaded = attendance_form_upload($field);
        if ($uploaded !== '') {
            $template[$target] = $uploaded;
        }
    }

    $template['active'] = '1';
    $template['updated_by'] = $currentUserName;
    $template['updated_at'] = gmdate('c');
    file_put_contents(AFD_TEMPLATE_FILE, json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $template;
}

function attendance_form_asset(?string $path, string $memberId = '', string $type = 'profile'): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/idd/')) {
        return $path;
    }
    if (str_starts_with($path, 'uploads/')) {
        return '/idd/' . $path;
    }
    if (str_starts_with($path, '/uploads/')) {
        return '/idd' . $path;
    }
    if ($memberId !== '') {
        return '/idd/uploads/members/' . rawurlencode($memberId) . '/' . $type . '/' . rawurlencode($path);
    }

    return $path;
}

if (($_GET['ajax'] ?? '') === 'attendance_form') {
    attendance_assert_ajax($csrfToken);
    $action = $_POST['action'] ?? $_GET['action'] ?? 'bootstrap';
    $pdo = attendance_db();

    if ($action === 'save_template') {
        if (!$isAdministrator) {
            attendance_json(['ok' => false, 'message' => 'Administrator permission required.'], 403);
        }
        attendance_json(['ok' => true, 'message' => 'Template saved and activated.', 'template' => attendance_form_save($currentUserName)]);
    }

    if ($action === 'search') {
        attendance_json(['ok' => true, 'recent' => attendance_recent($pdo, $_GET + $_POST)]);
    }

    attendance_json(['ok' => false, 'message' => 'Unknown action.'], 400);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    attendance_assert_ajax($csrfToken);
    if (!$isAdministrator) {
        attendance_json(['ok' => false, 'message' => 'Administrator permission required.'], 403);
    }
    attendance_form_save($currentUserName);
    $_SESSION['attendance_form_flash'] = 'Template saved and activated successfully.';
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$pdo = attendance_db();
$events = attendance_events($pdo);
$departments = attendance_departments($pdo);
$classes = attendance_classes($pdo);
$recentScans = attendance_recent($pdo, $_GET + ['range' => $_GET['range'] ?? 'today']);
$template = attendance_form_template();
$flash = $_SESSION['attendance_form_flash'] ?? '';
unset($_SESSION['attendance_form_flash']);

$sidebarFile = __DIR__ . '/../includes/sidebar.php';
if (is_file($sidebarFile)) {
    require_once $sidebarFile;
}

function attendance_form_render_register(array $template, array $rows): void
{
    $paperClass = strtolower((string)$template['paper_size']) . ' ' . ($template['orientation'] === 'landscape' ? 'landscape' : '');
    $available = ($template['orientation'] === 'landscape' ? 150 : 238) - (float)$template['header_height'] - (float)$template['footer_height'];
    $perPage = max(5, (int)floor($available / max(9, (float)$template['row_height'])));
    $pages = array_chunk($rows ?: [], $perPage) ?: [[]];
    foreach ($pages as $pageIndex => $pageRows): ?>
        <article class="afd-paper <?= attendance_h($paperClass) ?> <?= $pageIndex + 1 < count($pages) ? 'afd-page-break' : '' ?>" style="--afd-font:<?= attendance_h($template['font_family']) ?>;--afd-size:<?= attendance_h($template['font_size']) ?>pt;--afd-text:<?= attendance_h($template['text_color']) ?>;--afd-primary:<?= attendance_h($template['primary_color']) ?>;--afd-border:<?= attendance_h($template['border_color']) ?>;--afd-thead:<?= attendance_h($template['table_header_bg']) ?>;--afd-bg:<?= attendance_h($template['background_color']) ?>;--afd-mt:<?= attendance_h($template['margin_top']) ?>mm;--afd-mr:<?= attendance_h($template['margin_right']) ?>mm;--afd-mb:<?= attendance_h($template['margin_bottom']) ?>mm;--afd-ml:<?= attendance_h($template['margin_left']) ?>mm;--afd-header:<?= attendance_h($template['header_height']) ?>mm;--afd-footer:<?= attendance_h($template['footer_height']) ?>mm;--afd-row:<?= attendance_h($template['row_height']) ?>mm;--afd-photo-w:<?= attendance_h($template['photo_width']) ?>mm;--afd-photo-h:<?= attendance_h($template['photo_height']) ?>mm;--afd-sign-w:<?= attendance_h($template['signature_width']) ?>mm;--afd-sign-h:<?= attendance_h($template['signature_height']) ?>mm;">
            <div class="afd-watermark"><?= attendance_h($template['watermark']) ?></div>
            <header class="afd-register-header" style="background:<?= attendance_h($template['header_bg']) ?>;border-color:<?= attendance_h($template['header_border']) ?>;gap:<?= attendance_h($template['header_gap']) ?>mm;text-align:<?= attendance_h($template['header_align']) ?>;font-weight:<?= attendance_h($template['header_font_weight']) ?>;">
                <section class="afd-header-left">
                    <div class="afd-logo-box afd-draggable" data-x-field="logo_x" data-y-field="logo_y" style="width:<?= attendance_h($template['logo_size']) ?>mm;height:<?= attendance_h($template['logo_size']) ?>mm;transform:translate(<?= attendance_h($template['logo_x']) ?>mm,<?= attendance_h($template['logo_y']) ?>mm);"><?= $template['logo_url'] ? '<img src="' . attendance_h($template['logo_url']) . '" alt="Institution Logo">' : '<i class="bi bi-building"></i>' ?></div>
                    <div><div class="fw-bold afd-header-text"><?= attendance_h($template['header']) ?></div><div class="small"><?= attendance_h($template['institution_address']) ?></div><div class="small"><?= attendance_h($template['institution_phone']) ?><?= $template['institution_email'] ? ' • ' . attendance_h($template['institution_email']) : '' ?></div></div>
                </section>
                <section class="afd-header-center">
                    <h2 class="afd-title"><?= attendance_h($template['title']) ?></h2>
                    <div class="afd-official-lines"><span>Range Name : <strong><?= attendance_h($template['range_name']) ?></strong></span><span>Meeting : <strong><?= attendance_h($template['meeting_name']) ?></strong></span><span>Meeting No : <strong><?= attendance_h($template['meeting_number']) ?></strong></span><span>Meeting Date : <strong><?= attendance_h($template['meeting_date']) ?></strong></span><span>Meeting Place : <strong><?= attendance_h($template['meeting_place']) ?></strong></span></div>
                </section>
                <section class="afd-header-right">
                    <div class="afd-logo-box afd-draggable" data-x-field="seal_x" data-y-field="seal_y" style="width:<?= attendance_h($template['seal_size']) ?>mm;height:<?= attendance_h($template['seal_size']) ?>mm;transform:translate(<?= attendance_h($template['seal_x']) ?>mm,<?= attendance_h($template['seal_y']) ?>mm);"><?= $template['seal_url'] ? '<img src="' . attendance_h($template['seal_url']) . '" alt="Institution Seal">' : '<i class="bi bi-patch-check"></i>' ?></div>
                    <div class="afd-logo-box afd-draggable" data-x-field="right_logo_x" data-y-field="right_logo_y" style="width:<?= attendance_h($template['logo_size']) ?>mm;height:<?= attendance_h($template['logo_size']) ?>mm;transform:translate(<?= attendance_h($template['right_logo_x']) ?>mm,<?= attendance_h($template['right_logo_y']) ?>mm);"><?= $template['right_logo_url'] ? '<img src="' . attendance_h($template['right_logo_url']) . '" alt="Organization Logo">' : '<i class="bi bi-mortarboard"></i>' ?></div>
                </section>
            </header>
            <div class="afd-info-block"><span>Range Name : <strong><?= attendance_h($template['range_name']) ?></strong></span><span>Meeting : <strong><?= attendance_h($template['meeting_name']) ?></strong></span><span>Meeting Date : <strong><?= attendance_h($template['meeting_date']) ?></strong></span><span>Meeting Place : <strong><?= attendance_h($template['meeting_place']) ?></strong></span></div>
            <?php $columns = attendance_form_columns($template); ?>
            <table class="afd-register-table" style="width:<?= attendance_h($template['table_width']) ?>%;margin-left:<?= $template['table_align'] === 'left' ? '0' : ($template['table_align'] === 'right' ? 'auto' : 'auto') ?>;margin-right:<?= $template['table_align'] === 'right' ? '0' : ($template['table_align'] === 'left' ? 'auto' : 'auto') ?>;border-spacing:<?= attendance_h($template['cell_spacing']) ?>mm;border-collapse:<?= (float)$template['cell_spacing'] > 0 ? 'separate' : 'collapse' ?>;box-shadow:<?= $template['table_shadow'] === '1' ? '0 14px 35px rgba(15,23,42,.12)' : 'none' ?>;">
                <thead><tr><?php foreach ($columns as $column): ?><th style="width:<?= attendance_h($column['width']) ?>%;text-align:<?= attendance_h($column['align']) ?>;"><?= attendance_h($column['label']) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                <?php if (!$pageRows): ?><tr><td colspan="<?= count($columns) ?: 1 ?>" class="text-center py-5 text-muted"><i class="bi bi-database d-block fs-2 mb-2"></i>No attendance scans found for the selected filters.</td></tr><?php endif; ?>
                <?php foreach ($pageRows as $row): $photo = attendance_form_asset($row['photo'] ?? '', (string)($row['member_id'] ?? ''), 'profile'); $signature = attendance_form_asset($row['signature'] ?? '', (string)($row['member_id'] ?? ''), 'signature'); ?>
                    <tr><?php foreach ($columns as $column): ?><td style="text-align:<?= attendance_h($column['align']) ?>;padding:<?= attendance_h($template['cell_padding']) ?>mm;border-width:<?= attendance_h($template['border_thickness']) ?>px;">
                        <?php if ($column['key'] === 'photo'): ?><div class="afd-photo-cell"><?= $photo ? '<img src="' . attendance_h($photo) . '" alt="Photo">' : '<i class="bi bi-person text-secondary"></i>' ?></div>
                        <?php elseif ($column['key'] === 'signature'): ?><div class="afd-signature-cell"><?= $signature ? '<img src="' . attendance_h($signature) . '" alt="Signature">' : '<span>Stored signature</span>' ?></div>
                        <?php else: ?><?= attendance_h(attendance_form_column_value($row, $column['key'])) ?><?php endif; ?>
                    </td><?php endforeach; ?></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <footer class="afd-register-footer"><span><?= attendance_h($template['footer']) ?></span><span><?= $template['show_page_number'] === '1' ? 'Page ' . ($pageIndex + 1) . ' of ' . count($pages) : '' ?></span></footer>
        </article>
    <?php endforeach;
}
?>
<div class="attendance-form-designer attendance-enterprise" data-csrf="<?= attendance_h($csrfToken) ?>">
    <style>
        :root{--afd-primary:#2563eb;--afd-indigo:#4f46e5;--afd-emerald:#10b981;--afd-slate:#0f172a;--afd-muted:#64748b;--afd-glass:rgba(255,255,255,.78);--afd-shadow:0 24px 70px rgba(15,23,42,.14);--afd-radius:24px}.attendance-form-designer{min-height:100vh;padding:24px;background:radial-gradient(circle at top left,rgba(37,99,235,.2),transparent 36%),linear-gradient(135deg,#f8fbff,#eef4ff 52%,#f8fafc);color:var(--afd-slate)}.afd-glass{background:var(--afd-glass);border:1px solid rgba(255,255,255,.75);box-shadow:var(--afd-shadow);backdrop-filter:blur(18px);border-radius:var(--afd-radius)}.afd-topbar{position:sticky;top:0;z-index:20}.afd-brand{width:48px;height:48px;border-radius:16px;background:linear-gradient(135deg,var(--afd-primary),var(--afd-indigo));display:grid;place-items:center;color:#fff}.afd-btn{border:0;border-radius:16px;background:linear-gradient(135deg,var(--afd-primary),var(--afd-indigo));color:#fff;box-shadow:0 14px 28px rgba(37,99,235,.25)}.afd-btn:hover{color:#fff;transform:translateY(-1px)}.afd-grid{display:grid;grid-template-columns:410px minmax(0,1fr);gap:22px}.afd-panel{max-height:calc(100vh - 145px);overflow:auto}.form-control,.form-select{border-radius:14px}.afd-stage{overflow:auto;padding:28px;background:linear-gradient(135deg,rgba(15,23,42,.04),rgba(37,99,235,.06));border-radius:24px}.afd-paper{width:210mm;min-height:297mm;margin:0 auto 24px;background:var(--afd-bg);color:var(--afd-text);font-family:var(--afd-font);font-size:var(--afd-size);padding:var(--afd-mt) var(--afd-mr) var(--afd-mb) var(--afd-ml);box-shadow:0 30px 80px rgba(15,23,42,.22);position:relative;transform-origin:top center}.afd-paper.letter{width:216mm;min-height:279mm}.afd-paper.legal{width:216mm;min-height:356mm}.afd-paper.landscape{width:297mm;min-height:210mm}.afd-paper.letter.landscape{width:279mm;min-height:216mm}.afd-paper.legal.landscape{width:356mm;min-height:216mm}.afd-watermark{position:absolute;inset:0;display:grid;place-items:center;font-size:68px;font-weight:900;color:rgba(37,99,235,.055);letter-spacing:8px;transform:rotate(-30deg);pointer-events:none}.afd-register-header{min-height:var(--afd-header);display:grid;grid-template-columns:1.15fr 1.45fr .9fr;align-items:center;border:2px solid var(--afd-primary);padding:6px 8px;position:relative;z-index:1}.afd-header-left,.afd-header-center,.afd-header-right{height:100%;display:flex;align-items:center;gap:8px}.afd-header-center{flex-direction:column;justify-content:center;border-left:1px solid var(--afd-border);border-right:1px solid var(--afd-border);padding-inline:8px}.afd-header-right{justify-content:center}.afd-title{font-size:20px;font-weight:900;margin:3px 0}.afd-official-lines{display:grid;grid-template-columns:1fr 1fr;gap:2px 14px;font-size:10px;text-align:left}.afd-info-block{display:grid;grid-template-columns:repeat(4,1fr);gap:4px;border:1px solid var(--afd-border);border-top:0;padding:5px 8px;font-size:10px;position:relative;z-index:1;background:rgba(255,255,255,.72)}.afd-info-block span,.afd-official-lines span{border-bottom:1px dotted #64748b;white-space:nowrap}.afd-logo-box{width:58px;height:58px;border-radius:14px;border:1px dashed var(--afd-border);display:grid;place-items:center;overflow:hidden;color:#94a3b8;background:#fff}.afd-draggable{cursor:move}.afd-logo-box img,.afd-photo-cell img,.afd-signature-cell img{width:100%;height:100%;object-fit:contain}.afd-register-table{width:100%;border-collapse:collapse;margin-top:8px;position:relative;z-index:1}.afd-register-table th{background:var(--afd-thead);border:1px solid var(--afd-border);padding:5px;text-align:center}.afd-register-table td{border:1px solid var(--afd-border);padding:3px;height:var(--afd-row);vertical-align:middle}.afd-photo-cell{width:var(--afd-photo-w);height:var(--afd-photo-h);border-radius:8px;background:#f1f5f9;display:grid;place-items:center;margin:auto;overflow:hidden}.afd-signature-cell{width:var(--afd-sign-w);height:var(--afd-sign-h);border-bottom:1px solid #334155;margin:auto;display:grid;place-items:center;font-family:cursive;color:#64748b}.afd-register-footer{min-height:var(--afd-footer);display:flex;justify-content:space-between;gap:16px;align-items:center;border-top:1px solid var(--afd-border);margin-top:8px;padding-top:6px;color:#64748b;position:relative;z-index:1}.afd-page-break{break-after:page;page-break-after:always}.afd-guide .afd-paper{background-image:linear-gradient(rgba(37,99,235,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(37,99,235,.08) 1px,transparent 1px);background-size:10mm 10mm}.afd-ruler{height:28px;border-radius:12px;background:repeating-linear-gradient(90deg,#e2e8f0 0,#e2e8f0 1px,transparent 1px,transparent 10mm);font-size:11px;color:#64748b;padding:6px 10px}.afd-column-row{cursor:grab;border:1px solid #e2e8f0;border-radius:16px;padding:10px;background:rgba(255,255,255,.75)}.afd-column-row.dragging{opacity:.5}.afd-toast{position:fixed;right:22px;bottom:22px;z-index:1080}.modal-content.afd-modal{border:1px solid rgba(255,255,255,.6);border-radius:28px;background:rgba(255,255,255,.9);backdrop-filter:blur(22px)}@media(max-width:1100px){.afd-grid{grid-template-columns:1fr}.afd-panel{max-height:none}.afd-register-header{grid-template-columns:1fr}.afd-header-center{border:0;border-top:1px solid var(--afd-border);border-bottom:1px solid var(--afd-border)}.afd-info-block{grid-template-columns:1fr 1fr}}@media(max-width:700px){.attendance-form-designer{padding:12px}.afd-stage{padding:10px}.afd-paper{transform:scale(.42);margin-left:-61mm;margin-bottom:-166mm}.afd-actions{display:grid!important;grid-template-columns:1fr 1fr}}@media print{body{background:#fff!important}.afd-no-print,.afd-panel,.modal{display:none!important}.attendance-form-designer{padding:0;background:#fff}.afd-grid{display:block}.afd-stage{padding:0;overflow:visible;background:#fff}.afd-paper{box-shadow:none;margin:0;transform:none!important}.afd-page-break{margin:0}@page{size:A4 portrait;margin:0}}
    </style>
    <?php if ($flash): ?><div class="alert alert-success afd-glass afd-no-print"><i class="bi bi-check-circle-fill me-2"></i><?= attendance_h($flash) ?></div><?php endif; ?>
    <div class="afd-topbar afd-glass afd-no-print p-3 mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-3"><div class="afd-brand"><i class="bi bi-ui-checks-grid fs-4"></i></div><div><div class="small text-primary fw-bold text-uppercase">Attendance Management</div><h1 class="h4 mb-0 fw-bold">Common Attendance Register Form Designer</h1><div class="small text-muted">Reuses attendance.php database, CSRF, login, members, departments, classes, events and attendance_logs.</div></div></div>
        <div class="afd-actions d-flex gap-2 flex-wrap"><a href="attendance.php" class="btn btn-light rounded-pill"><i class="bi bi-qr-code-scan me-1"></i>Scanner</a><button class="btn btn-outline-primary rounded-pill" id="afdFitWidth">Fit Width</button><button class="btn btn-outline-primary rounded-pill" id="afdFitPage">Fit Page</button><button class="btn btn-outline-dark rounded-pill" data-bs-toggle="modal" data-bs-target="#afdPreviewModal">Preview</button><button class="btn btn-outline-success rounded-pill" id="afdPrint">Print</button><button class="btn btn-outline-danger rounded-pill" id="afdPdf">PDF</button><button class="btn afd-btn px-4" form="afdTemplateForm"><i class="bi bi-cloud-check me-1"></i>Save & Activate</button></div>
    </div>
    <div class="afd-grid">
        <aside class="afd-panel afd-glass afd-no-print p-4">
            <form id="afdFilterForm" class="mb-4">
                <h2 class="h6 fw-bold text-uppercase text-muted">Register Data</h2>
                <div class="row g-2"><div class="col-12"><input class="form-control" name="search" id="attendanceSearch" placeholder="Search member, ID, department, class, operator"></div><div class="col-6"><select class="form-select" name="range" id="rangeFilter"><option value="today">Today</option><option value="yesterday">Yesterday</option><option value="week">Last 7 days</option><option value="month">Last 30 days</option><option value="all">All records</option><option value="custom">Custom date</option></select></div><div class="col-6"><input type="date" class="form-control d-none" name="date" id="customDate"></div><div class="col-6"><select class="form-select" name="department" id="departmentFilter"><option value="">All departments</option><?php foreach ($departments as $department): ?><option><?= attendance_h($department['name']) ?></option><?php endforeach; ?></select></div><div class="col-6"><select class="form-select" name="class" id="classFilter"><option value="">All classes</option><?php foreach ($classes as $class): ?><option><?= attendance_h($class['class_name']) ?></option><?php endforeach; ?></select></div><div class="col-6"><select class="form-select" name="type" id="eventFilter"><option value="">All events</option><?php foreach ($events as $event): ?><option><?= attendance_h($event['name']) ?></option><?php endforeach; ?></select></div><div class="col-6"><select class="form-select" name="status" id="statusFilter"><option value="">All status</option><option value="present">Present</option><option value="late">Late</option></select></div></div>
            </form>
            <form id="afdTemplateForm" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= attendance_h($csrfToken) ?>">
                <h2 class="h6 fw-bold text-uppercase text-muted">Template Designer</h2>
                <div class="row g-3">
                    <div class="col-6"><label class="form-label">Paper</label><select class="form-select afd-live" name="paper_size"><?php foreach (['A4','Letter','Legal'] as $v): ?><option <?= $template['paper_size'] === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                    <div class="col-6"><label class="form-label">Orientation</label><select class="form-select afd-live" name="orientation"><?php foreach (['portrait','landscape'] as $v): ?><option <?= $template['orientation'] === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label">Left Institution Logo</label><input class="form-control" type="file" name="logo_upload" accept="image/*"><input class="form-control mt-2 afd-live" name="logo_url" value="<?= attendance_h($template['logo_url']) ?>" placeholder="Left logo URL"></div>
                    <div class="col-12"><label class="form-label">Right Organization Logo</label><input class="form-control" type="file" name="right_logo_upload" accept="image/*"><input class="form-control mt-2 afd-live" name="right_logo_url" value="<?= attendance_h($template['right_logo_url']) ?>" placeholder="Right logo URL"></div>
                    <div class="col-12"><label class="form-label">Institution Seal</label><input class="form-control" type="file" name="seal_upload" accept="image/*"><input class="form-control mt-2 afd-live" name="seal_url" value="<?= attendance_h($template['seal_url']) ?>" placeholder="Seal URL"></div>
                    <?php foreach ([['header','Institution Name'],['institution_address','Address'],['institution_phone','Phone'],['institution_email','Email'],['title','Register Title'],['subtitle','Subtitle'],['footer','Footer'],['watermark','Watermark'],['range_name','Range Name'],['meeting_name','Meeting Name'],['meeting_number','Meeting Number'],['meeting_date','Meeting Date'],['meeting_place','Meeting Place']] as $field): ?><div class="col-12"><label class="form-label"><?= $field[1] ?></label><input class="form-control afd-live" name="<?= $field[0] ?>" value="<?= attendance_h($template[$field[0]]) ?>"></div><?php endforeach; ?>
                    <?php foreach ([['margin_top','Top margin'],['margin_right','Right margin'],['margin_bottom','Bottom margin'],['margin_left','Left margin'],['header_height','Header height'],['footer_height','Footer height'],['font_size','Font size'],['header_gap','Header spacing'],['logo_size','Logo size'],['seal_size','Seal size'],['logo_x','Left logo X'],['logo_y','Left logo Y'],['seal_x','Seal X'],['seal_y','Seal Y'],['right_logo_x','Right logo X'],['right_logo_y','Right logo Y'],['row_height','Row height'],['photo_width','Photo width'],['photo_height','Photo height'],['signature_width','Signature width'],['signature_height','Signature height']] as $field): ?><div class="col-6"><label class="form-label"><?= $field[1] ?> mm</label><input type="number" step="1" class="form-control afd-live" name="<?= $field[0] ?>" value="<?= attendance_h($template[$field[0]]) ?>"></div><?php endforeach; ?>
                    <div class="col-12"><label class="form-label">Font family</label><input class="form-control afd-live" name="font_family" value="<?= attendance_h($template['font_family']) ?>"></div>
                    <?php foreach ([['text_color','Text'],['primary_color','Primary'],['border_color','Border'],['background_color','Background'],['table_header_bg','Table header'],['header_bg','Header bg'],['header_border','Header border']] as $field): ?><div class="col"><label class="form-label"><?= $field[1] ?></label><input type="color" class="form-control form-control-color afd-live w-100" name="<?= $field[0] ?>" value="<?= attendance_h($template[$field[0]]) ?>"></div><?php endforeach; ?>
                    <div class="col-6"><label class="form-label">Header alignment</label><select class="form-select afd-live" name="header_align"><?php foreach (['left','center','right'] as $v): ?><option <?= $template['header_align'] === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                    <div class="col-6"><label class="form-label">Header font weight</label><select class="form-select afd-live" name="header_font_weight"><?php foreach (['400','500','600','700','800','900'] as $v): ?><option <?= (string)$template['header_font_weight'] === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                    <?php foreach ([['table_width','Table width %'],['cell_padding','Cell padding mm'],['cell_spacing','Cell spacing mm'],['border_thickness','Border px']] as $field): ?><div class="col-6"><label class="form-label"><?= $field[1] ?></label><input type="number" step="1" class="form-control afd-live" name="<?= $field[0] ?>" value="<?= attendance_h($template[$field[0]]) ?>"></div><?php endforeach; ?>
                    <div class="col-6"><label class="form-label">Table align</label><select class="form-select afd-live" name="table_align"><?php foreach (['center','left','right'] as $v): ?><option <?= $template['table_align'] === $v ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                    <div class="col-6"><label class="form-label">Table shadow</label><select class="form-select afd-live" name="table_shadow"><option value="0" <?= $template['table_shadow'] === '0' ? 'selected' : '' ?>>Off</option><option value="1" <?= $template['table_shadow'] === '1' ? 'selected' : '' ?>>On</option></select></div>
                    <div class="col-12"><div class="d-flex justify-content-between align-items-center mb-2"><label class="form-label fw-bold mb-0">Drag & Drop Column Manager</label><button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="afdAddColumn"><i class="bi bi-plus-lg"></i> Add Column</button></div><input type="hidden" name="columns_json" id="afdColumnsJson" value="<?= attendance_h($template['columns_json']) ?>"><div id="afdColumnManager" class="d-grid gap-2"></div><div class="form-text">Drag rows to reorder, rename labels, resize widths, align cells, and hide/show register columns.</div></div>
                    <div class="col-6"><label class="form-label">Remarks</label><select class="form-select afd-live" name="show_remarks"><option value="1" <?= $template['show_remarks'] === '1' ? 'selected' : '' ?>>Show</option><option value="0" <?= $template['show_remarks'] === '0' ? 'selected' : '' ?>>Hide</option></select></div>
                    <div class="col-6"><label class="form-label">Page number</label><select class="form-select afd-live" name="show_page_number"><option value="1" <?= $template['show_page_number'] === '1' ? 'selected' : '' ?>>Show</option><option value="0" <?= $template['show_page_number'] === '0' ? 'selected' : '' ?>>Hide</option></select></div>
                    <div class="col-12 d-grid"><button class="btn afd-btn btn-lg"><i class="bi bi-check2-circle me-2"></i>Save Active Template</button></div>
                </div>
            </form>
        </aside>
        <section class="afd-stage" id="afdStage"><div class="afd-ruler afd-no-print mb-3">Ruler / safe print area guide</div><?php attendance_form_render_register($template, $recentScans); ?></section>
    </div>
</div>
<div class="modal fade" id="afdPreviewModal" tabindex="-1"><div class="modal-dialog modal-fullscreen-xl-down modal-xl modal-dialog-centered"><div class="modal-content afd-modal"><div class="modal-header border-0"><div><div class="small text-primary fw-bold text-uppercase">Premium Preview</div><h3 class="modal-title fw-bold">Full Attendance Register Preview</h3></div><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="afd-stage" id="afdModalPreview"></div></div><div class="modal-footer border-0"><button class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Close</button><button class="btn btn-outline-success rounded-pill" id="afdModalPrint">Print</button><button class="btn afd-btn" id="afdModalPdf">Download PDF</button></div></div></div></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
(() => {
  const root = document.querySelector('.attendance-form-designer');
  const csrf = root.dataset.csrf;
  const stage = document.getElementById('afdStage');
  const form = document.getElementById('afdTemplateForm');
  const esc = value => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
  const asset = (path, member, type='profile') => { path = String(path || '').trim(); if (!path) return ''; if (/^https?:\/\//.test(path) || path.startsWith('/idd/')) return path; if (path.startsWith('uploads/')) return '/idd/' + path; if (path.startsWith('/uploads/')) return '/idd' + path; return member ? `/idd/uploads/members/${encodeURIComponent(member)}/${type}/${encodeURIComponent(path)}` : path; };
  const api = async (data, method = 'POST') => { const options = { method, headers: {'X-CSRF-Token': csrf} }; if (method === 'POST') options.body = new URLSearchParams(data); const query = method === 'GET' ? '&' + new URLSearchParams(data) : ''; const response = await fetch(`?ajax=attendance_form${query}`, options); return response.json(); };
  const collectTemplate = () => Object.fromEntries(new FormData(form).entries());
  const applyTemplate = () => { const d = collectTemplate(); document.querySelectorAll('.afd-paper').forEach(p => { p.className = `afd-paper ${(d.paper_size || 'A4').toLowerCase()} ${d.orientation === 'landscape' ? 'landscape' : ''}`; const map = {font_family:'--afd-font',font_size:'--afd-size',text_color:'--afd-text',primary_color:'--afd-primary',border_color:'--afd-border',table_header_bg:'--afd-thead',background_color:'--afd-bg',margin_top:'--afd-mt',margin_right:'--afd-mr',margin_bottom:'--afd-mb',margin_left:'--afd-ml',header_height:'--afd-header',footer_height:'--afd-footer',row_height:'--afd-row',photo_width:'--afd-photo-w',photo_height:'--afd-photo-h',signature_width:'--afd-sign-w',signature_height:'--afd-sign-h'}; Object.entries(map).forEach(([k,v]) => p.style.setProperty(v, (d[k] || '') + (k === 'font_family' || k.includes('color') ? '' : k === 'font_size' ? 'pt' : 'mm'))); }); document.querySelectorAll('.afd-header-text').forEach(e => e.textContent = d.header || ''); document.querySelectorAll('.afd-title').forEach(e => e.textContent = d.title || ''); document.querySelectorAll('.afd-subtitle').forEach(e => e.textContent = d.subtitle || ''); document.querySelectorAll('.afd-register-footer span:first-child').forEach(e => e.textContent = d.footer || ''); document.querySelectorAll('.afd-watermark').forEach(e => e.textContent = d.watermark || ''); };
  const columnsInput = document.getElementById('afdColumnsJson');
  const columnManager = document.getElementById('afdColumnManager');
  const readColumns = () => { try { return JSON.parse(columnsInput.value || '[]'); } catch (_) { return []; } };
  const writeColumns = cols => { columnsInput.value = JSON.stringify(cols); renderColumnManager(); };
  const valueFor = (row, key) => { const time = new Date(String(row.scan_time || new Date()).replace(' ', 'T')); const map = {attendance_time: time.toLocaleTimeString(), attendance_date: Number.isNaN(time.getTime()) ? '' : time.toISOString().slice(0,10), msr_number: row.msr_number || row.member_id || '', madrassa: row.madrassa || row.department || '', remarks: row.remarks || row.status || 'present'}; return map[key] ?? row[key] ?? ''; };
  const renderColumnManager = () => { const cols = readColumns(); columnManager.innerHTML = cols.map((col, i) => `<div class="afd-column-row" draggable="true" data-index="${i}"><div class="row g-2 align-items-center"><div class="col-1"><i class="bi bi-grip-vertical text-muted"></i></div><div class="col-2"><input class="form-check-input afd-col-enabled" type="checkbox" ${col.enabled ? 'checked' : ''}></div><div class="col-4"><input class="form-control form-control-sm afd-col-label" value="${esc(col.label)}" placeholder="Label"></div><div class="col-2"><input class="form-control form-control-sm afd-col-width" type="number" min="6" max="40" value="${esc(col.width)}"></div><div class="col-3"><select class="form-select form-select-sm afd-col-align"><option ${col.align === 'left' ? 'selected' : ''}>left</option><option ${col.align === 'center' ? 'selected' : ''}>center</option><option ${col.align === 'right' ? 'selected' : ''}>right</option></select></div></div><div class="small text-muted mt-1">${esc(col.key)}</div></div>`).join(''); };
  columnManager?.addEventListener('input', e => { const row = e.target.closest('.afd-column-row'); if (!row) return; const cols = readColumns(); const col = cols[Number(row.dataset.index)]; if (!col) return; col.enabled = row.querySelector('.afd-col-enabled').checked; col.label = row.querySelector('.afd-col-label').value; col.width = Number(row.querySelector('.afd-col-width').value || 12); col.align = row.querySelector('.afd-col-align').value; columnsInput.value = JSON.stringify(cols); refreshPreviewOnly(); });
  columnManager?.addEventListener('dragstart', e => e.target.closest('.afd-column-row')?.classList.add('dragging'));
  columnManager?.addEventListener('dragend', e => e.target.closest('.afd-column-row')?.classList.remove('dragging'));
  columnManager?.addEventListener('dragover', e => { e.preventDefault(); const dragging = columnManager.querySelector('.dragging'); const target = e.target.closest('.afd-column-row:not(.dragging)'); if (dragging && target) columnManager.insertBefore(dragging, target); });
  columnManager?.addEventListener('drop', () => { const old = readColumns(); const next = [...columnManager.querySelectorAll('.afd-column-row')].map(row => old[Number(row.dataset.index)]); writeColumns(next); refreshPreviewOnly(); });
  document.getElementById('afdAddColumn')?.addEventListener('click', () => { const cols = readColumns(); cols.push({key:'custom_' + Date.now(), label:'Custom Column', enabled:true, width:12, align:'center'}); writeColumns(cols); refreshPreviewOnly(); });
  let currentRows = <?= json_encode($recentScans, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) ?>;
  const renderRows = rows => { currentRows = rows; const d = collectTemplate(); const cols = readColumns().filter(c => c.enabled); const chunkSize = Math.max(5, Math.floor(((d.orientation === 'landscape' ? 150 : 238) - Number(d.header_height || 31) - Number(d.footer_height || 14)) / Math.max(9, Number(d.row_height || 16)))); const pages = []; for (let i = 0; i < rows.length; i += chunkSize) pages.push(rows.slice(i, i + chunkSize)); if (!pages.length) pages.push([]); stage.innerHTML = '<div class="afd-ruler afd-no-print mb-3">Ruler / safe print area guide</div>' + pages.map((pageRows, pageIndex) => `<article class="afd-paper ${(d.paper_size || 'A4').toLowerCase()} ${d.orientation === 'landscape' ? 'landscape' : ''} ${pageIndex + 1 < pages.length ? 'afd-page-break' : ''}"><div class="afd-watermark">${esc(d.watermark)}</div><header class="afd-register-header" style="background:${esc(d.header_bg || '#fff')};border-color:${esc(d.header_border || d.primary_color || '#0f172a')};gap:${esc(d.header_gap || 4)}mm;text-align:${esc(d.header_align || 'center')};font-weight:${esc(d.header_font_weight || 700)}"><section class="afd-header-left"><div class="afd-logo-box afd-draggable" data-x-field="logo_x" data-y-field="logo_y" style="width:${esc(d.logo_size || 18)}mm;height:${esc(d.logo_size || 18)}mm;transform:translate(${esc(d.logo_x || 0)}mm,${esc(d.logo_y || 0)}mm)">${d.logo_url ? `<img src="${esc(d.logo_url)}" alt="Institution Logo">` : '<i class="bi bi-building"></i>'}</div><div><div class="fw-bold afd-header-text">${esc(d.header)}</div><div class="small">${esc(d.institution_address || '')}</div><div class="small">${esc(d.institution_phone || '')}${d.institution_email ? ' • ' + esc(d.institution_email) : ''}</div></div></section><section class="afd-header-center"><h2 class="afd-title">${esc(d.title)}</h2><div class="afd-official-lines"><span>Range Name : <strong>${esc(d.range_name || '')}</strong></span><span>Meeting : <strong>${esc(d.meeting_name || '')}</strong></span><span>Meeting No : <strong>${esc(d.meeting_number || '')}</strong></span><span>Meeting Date : <strong>${esc(d.meeting_date || '')}</strong></span><span>Meeting Place : <strong>${esc(d.meeting_place || '')}</strong></span></div></section><section class="afd-header-right"><div class="afd-logo-box afd-draggable" data-x-field="seal_x" data-y-field="seal_y" style="width:${esc(d.seal_size || 18)}mm;height:${esc(d.seal_size || 18)}mm;transform:translate(${esc(d.seal_x || 0)}mm,${esc(d.seal_y || 0)}mm)">${d.seal_url ? `<img src="${esc(d.seal_url)}" alt="Institution Seal">` : '<i class="bi bi-patch-check"></i>'}</div><div class="afd-logo-box afd-draggable" data-x-field="right_logo_x" data-y-field="right_logo_y" style="width:${esc(d.logo_size || 18)}mm;height:${esc(d.logo_size || 18)}mm;transform:translate(${esc(d.right_logo_x || 0)}mm,${esc(d.right_logo_y || 0)}mm)">${d.right_logo_url ? `<img src="${esc(d.right_logo_url)}" alt="Organization Logo">` : '<i class="bi bi-mortarboard"></i>'}</div></section></header><div class="afd-info-block"><span>Range Name : <strong>${esc(d.range_name || '')}</strong></span><span>Meeting : <strong>${esc(d.meeting_name || '')}</strong></span><span>Meeting Date : <strong>${esc(d.meeting_date || '')}</strong></span><span>Meeting Place : <strong>${esc(d.meeting_place || '')}</strong></span></div><table class="afd-register-table" style="width:${esc(d.table_width || 100)}%;border-spacing:${esc(d.cell_spacing || 0)}mm;border-collapse:${Number(d.cell_spacing || 0)>0?'separate':'collapse'};box-shadow:${d.table_shadow === '1' ? '0 14px 35px rgba(15,23,42,.12)' : 'none'}"><thead><tr>${cols.map(c => `<th style="width:${esc(c.width)}%;text-align:${esc(c.align)}">${esc(c.label)}</th>`).join('')}</tr></thead><tbody>${pageRows.length ? pageRows.map(row => `<tr>${cols.map(c => { const photo = asset(row.photo, row.member_id, 'profile'); const sig = asset(row.signature, row.member_id, 'signature'); const inner = c.key === 'photo' ? `<div class="afd-photo-cell">${photo ? `<img src="${esc(photo)}" alt="Photo">` : '<i class="bi bi-person text-secondary"></i>'}</div>` : c.key === 'signature' ? `<div class="afd-signature-cell">${sig ? `<img src="${esc(sig)}" alt="Signature">` : '<span>Stored signature</span>'}</div>` : esc(valueFor(row, c.key)); return `<td style="text-align:${esc(c.align)};padding:${esc(d.cell_padding || 3)}mm;border-width:${esc(d.border_thickness || 1)}px">${inner}</td>`; }).join('')}</tr>`).join('') : `<tr><td colspan="${cols.length || 1}" class="text-center py-5 text-muted">No attendance scans found for the selected filters.</td></tr>`}</tbody></table><footer class="afd-register-footer"><span>${esc(d.footer)}</span><span>${d.show_page_number === '1' ? `Page ${pageIndex + 1} of ${pages.length}` : ''}</span></footer></article>`).join(''); applyTemplate(); };
  const refreshPreviewOnly = () => renderRows(currentRows);
  renderColumnManager();
  document.querySelectorAll('.afd-live').forEach(el => ['input','change'].forEach(event => el.addEventListener(event, () => refreshPreviewOnly()))); renderRows(currentRows);
  const refresh = async () => { const filters = Object.fromEntries(new FormData(document.getElementById('afdFilterForm')).entries()); filters.action = 'search'; const result = await api(filters); if (result.ok) renderRows(result.recent); };
  ['attendanceSearch','rangeFilter','customDate','departmentFilter','classFilter','statusFilter','eventFilter'].forEach(id => document.getElementById(id)?.addEventListener('input', () => { clearTimeout(window.afdTimer); window.afdTimer = setTimeout(refresh, 250); }));
  document.getElementById('rangeFilter')?.addEventListener('change', e => document.getElementById('customDate').classList.toggle('d-none', e.target.value !== 'custom'));
  const setZoom = z => document.querySelectorAll('.afd-paper').forEach(p => p.style.transform = `scale(${z})`); document.getElementById('afdFitWidth').onclick = () => setZoom(Math.min(1, stage.clientWidth / (stage.querySelector('.afd-paper').offsetWidth + 80))); document.getElementById('afdFitPage').onclick = () => setZoom(.72);
  stage.addEventListener('pointerdown', e => { const el = e.target.closest('.afd-draggable'); if (!el) return; e.preventDefault(); const xField = form.elements[el.dataset.xField]; const yField = form.elements[el.dataset.yField]; if (!xField || !yField) return; const startX = e.clientX; const startY = e.clientY; const baseX = Number(xField.value || 0); const baseY = Number(yField.value || 0); const move = ev => { xField.value = Math.round((baseX + ((ev.clientX - startX) / 3.78)) * 10) / 10; yField.value = Math.round((baseY + ((ev.clientY - startY) / 3.78)) * 10) / 10; refreshPreviewOnly(); }; const up = () => { window.removeEventListener('pointermove', move); window.removeEventListener('pointerup', up); }; window.addEventListener('pointermove', move); window.addEventListener('pointerup', up); });
  const printRegister = () => window.print(); document.getElementById('afdPrint').onclick = printRegister; document.getElementById('afdModalPrint').onclick = printRegister;
  const makePdf = () => html2pdf().set({margin:0,filename:'common-attendance-register.pdf',image:{type:'jpeg',quality:.98},html2canvas:{scale:2,useCORS:true},jsPDF:{unit:'mm',format:(form.paper_size.value || 'a4').toLowerCase(),orientation:(form.orientation.value || 'portrait')}}).from(stage).save(); document.getElementById('afdPdf').onclick = makePdf; document.getElementById('afdModalPdf').onclick = makePdf;
  document.getElementById('afdPreviewModal').addEventListener('show.bs.modal', () => document.getElementById('afdModalPreview').innerHTML = stage.innerHTML);
})();
</script>

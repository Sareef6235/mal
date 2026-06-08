<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';

const NOTICE_FIELDS = [
    'title_malayalam', 'title_arabic', 'sub_title', 'main_topic', 'notice_date', 'notice_time', 'venue',
    'qiraat', 'welcome', 'president', 'inaugurator', 'translation_speech', 'speakers', 'singers', 'thanks',
    'association_stamp', 'bg_style', 'design_style', 'font_style_malayalam', 'font_style_english',
    'font_style_arabic', 'font_size_settings'
];

function ensure_tables(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS notices (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title_malayalam VARCHAR(255) NOT NULL,
        title_arabic VARCHAR(255) NOT NULL,
        sub_title VARCHAR(255) DEFAULT '',
        main_topic VARCHAR(255) DEFAULT '',
        notice_date VARCHAR(80) DEFAULT '',
        notice_time VARCHAR(80) DEFAULT '',
        venue VARCHAR(255) DEFAULT '',
        qiraat VARCHAR(255) DEFAULT '',
        welcome VARCHAR(255) DEFAULT '',
        president VARCHAR(255) DEFAULT '',
        inaugurator VARCHAR(255) DEFAULT '',
        translation_speech VARCHAR(255) DEFAULT '',
        speakers TEXT,
        singers TEXT,
        thanks VARCHAR(255) DEFAULT '',
        association_stamp VARCHAR(255) DEFAULT '',
        bg_style VARCHAR(40) DEFAULT 'emerald',
        design_style VARCHAR(40) DEFAULT 'classic',
        font_style_malayalam VARCHAR(40) DEFAULT 'manjari',
        font_style_english VARCHAR(40) DEFAULT 'inter',
        font_style_arabic VARCHAR(40) DEFAULT 'amiri',
        font_size_settings VARCHAR(40) DEFAULT 'normal',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS student_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        notice_id INT UNSIGNED NOT NULL,
        class_num VARCHAR(40) NOT NULL,
        student_name VARCHAR(255) NOT NULL,
        item_type VARCHAR(255) NOT NULL,
        item_icon VARCHAR(16) DEFAULT '🟢',
        position_order INT UNSIGNED DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_notice_class (notice_id, class_num)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function notice_payload(): array
{
    $payload = [];
    foreach (NOTICE_FIELDS as $field) {
        $payload[$field] = clean_text($_POST[$field] ?? '', in_array($field, ['speakers', 'singers'], true) ? 1500 : 500);
    }

    $payload['title_malayalam'] = $payload['title_malayalam'] ?: 'മഹല്ല് നോട്ടീസ്';
    $payload['title_arabic'] = $payload['title_arabic'] ?: 'إعلان';
    $payload['bg_style'] = in_array($payload['bg_style'], ['emerald', 'gold', 'midnight', 'royal', 'bw'], true) ? $payload['bg_style'] : 'emerald';
    $payload['font_style_malayalam'] = in_array($payload['font_style_malayalam'], ['manjari', 'gayathri', 'chilanka'], true) ? $payload['font_style_malayalam'] : 'manjari';
    $payload['font_style_arabic'] = in_array($payload['font_style_arabic'], ['amiri', 'noto', 'kufi'], true) ? $payload['font_style_arabic'] : 'amiri';
    $payload['font_size_settings'] = in_array($payload['font_size_settings'], ['compact', 'normal', 'large'], true) ? $payload['font_size_settings'] : 'normal';

    return $payload;
}

function fetch_notice(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM notices WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $notice = $stmt->get_result()->fetch_assoc();
    if (!$notice) {
        return null;
    }
    $notice['students'] = fetch_students($conn, $id);
    return $notice;
}

function fetch_students(mysqli $conn, int $noticeId): array
{
    $stmt = $conn->prepare('SELECT id, notice_id, class_num, student_name, item_type, item_icon, position_order FROM student_items WHERE notice_id = ? ORDER BY CAST(class_num AS UNSIGNED) DESC, position_order ASC, id ASC');
    $stmt->bind_param('i', $noticeId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetch_notices(mysqli $conn): array
{
    $result = $conn->query('SELECT n.*, COUNT(s.id) AS student_count FROM notices n LEFT JOIN student_items s ON s.notice_id = n.id GROUP BY n.id ORDER BY n.id DESC');
    $notices = [];
    while ($row = $result->fetch_assoc()) {
        $row['students'] = fetch_students($conn, (int) $row['id']);
        $notices[] = $row;
    }
    return $notices;
}

function save_students(mysqli $conn, int $noticeId, array $students): void
{
    $conn->begin_transaction();
    $delete = $conn->prepare('DELETE FROM student_items WHERE notice_id = ?');
    $delete->bind_param('i', $noticeId);
    $delete->execute();

    $insert = $conn->prepare('INSERT INTO student_items (notice_id, class_num, student_name, item_type, item_icon, position_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($students as $index => $student) {
        $classNum = clean_text((string) ($student['class_num'] ?? ''), 40);
        $name = clean_text((string) ($student['student_name'] ?? ''), 255);
        $item = clean_text((string) ($student['item_type'] ?? ''), 255);
        $icon = clean_text((string) ($student['item_icon'] ?? '🟢'), 16) ?: '🟢';
        if ($classNum === '' || $name === '' || $item === '') {
            continue;
        }
        $order = (int) $index;
        $insert->bind_param('issssi', $noticeId, $classNum, $name, $item, $icon, $order);
        $insert->execute();
    }
    $conn->commit();
}

ensure_tables($conn);

if (isset($_GET['api'])) {
    $action = (string) $_GET['api'];

    if ($action === 'bootstrap') {
        $notices = fetch_notices($conn);
        json_response([
            'success' => true,
            'csrf' => csrf_token(),
            'notices' => $notices,
            'stats' => [
                'notices' => count($notices),
                'students' => array_sum(array_map(fn ($n) => (int) $n['student_count'], $notices)),
                'programs' => array_sum(array_map(fn ($n) => count(array_filter([$n['qiraat'], $n['welcome'], $n['president'], $n['inaugurator'], $n['translation_speech'], $n['speakers'], $n['singers'], $n['thanks']])), $notices)),
            ],
        ]);
    }

    validate_csrf($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if ($action === 'save_notice') {
        $payload = notice_payload();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $sql = 'UPDATE notices SET ' . implode(' = ?, ', NOTICE_FIELDS) . ' = ? WHERE id = ?';
            $stmt = $conn->prepare($sql);
            $values = array_values($payload);
            $types = str_repeat('s', count($values)) . 'i';
            $values[] = $id;
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
        } else {
            $columns = implode(', ', NOTICE_FIELDS);
            $placeholders = implode(', ', array_fill(0, count(NOTICE_FIELDS), '?'));
            $stmt = $conn->prepare("INSERT INTO notices ($columns) VALUES ($placeholders)");
            $values = array_values($payload);
            $stmt->bind_param(str_repeat('s', count($values)), ...$values);
            $stmt->execute();
            $id = (int) $conn->insert_id;
        }
        $students = json_decode((string) ($_POST['students_json'] ?? '[]'), true);
        if (is_array($students)) {
            save_students($conn, $id, $students);
        }
        json_response(['success' => true, 'message' => 'Notice saved successfully.', 'notice' => fetch_notice($conn, $id)]);
    }

    if ($action === 'delete_notice') {
        $id = (int) ($_POST['id'] ?? 0);
        $studentStmt = $conn->prepare('DELETE FROM student_items WHERE notice_id = ?');
        $studentStmt->bind_param('i', $id);
        $studentStmt->execute();
        $stmt = $conn->prepare('DELETE FROM notices WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        json_response(['success' => true, 'message' => 'Notice deleted successfully.']);
    }

    if ($action === 'duplicate_notice') {
        $id = (int) ($_POST['id'] ?? 0);
        $notice = fetch_notice($conn, $id);
        if (!$notice) {
            json_response(['success' => false, 'message' => 'Notice not found.'], 404);
        }
        $payload = [];
        foreach (NOTICE_FIELDS as $field) {
            $payload[$field] = (string) ($notice[$field] ?? '');
        }
        $payload['title_malayalam'] .= ' - Copy';
        $columns = implode(', ', NOTICE_FIELDS);
        $placeholders = implode(', ', array_fill(0, count(NOTICE_FIELDS), '?'));
        $stmt = $conn->prepare("INSERT INTO notices ($columns) VALUES ($placeholders)");
        $values = array_values($payload);
        $stmt->bind_param(str_repeat('s', count($values)), ...$values);
        $stmt->execute();
        $newId = (int) $conn->insert_id;
        save_students($conn, $newId, array_map(fn ($s) => [
            'class_num' => $s['class_num'],
            'student_name' => $s['student_name'],
            'item_type' => $s['item_type'],
            'item_icon' => $s['item_icon'],
        ], $notice['students']));
        json_response(['success' => true, 'message' => 'Notice duplicated successfully.', 'notice' => fetch_notice($conn, $newId)]);
    }

    json_response(['success' => false, 'message' => 'Unknown API action.'], 404);
}
?>
<!doctype html>
<html lang="ml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="MHM SKSBV premium Islamic educational notice management dashboard">
    <title>MHM SKSBV Ultimate Notice Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Chilanka&family=Gayathri:wght@400;700&family=Inter:wght@400;500;600;700;800;900&family=Manjari:wght@400;700;900&family=Noto+Naskh+Arabic:wght@700&family=Reem+Kufi:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="selection:bg-amber-300 selection:text-slate-950">
    <div id="loadingScreen" class="loading-screen"><div class="loader-medallion">☪</div><p>MHM SKSBV Dashboard Loading...</p></div>
    <div class="pattern-overlay"></div>
    <div class="particle-field" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>

    <button id="mobileMenuButton" class="mobile-menu-btn no-print" type="button" aria-label="Open menu">☰</button>
    <aside id="sidebar" class="sidebar no-print">
        <div class="brand-block">
            <div class="brand-mark">☪</div>
            <div><p class="brand-kicker">MHM SKSBV</p><h1>Notice Command</h1></div>
        </div>
        <nav id="mainMenu" class="menu-list">
            <button data-section="dashboard" class="active">🏠 <span>Dashboard</span></button>
            <button data-section="create">📝 <span>Create Notice</span></button>
            <button data-section="notices">📢 <span>All Notices</span></button>
            <button data-section="students">🎓 <span>Student Items</span></button>
            <button data-section="preview">📄 <span>PDF Preview</span></button>
            <button data-section="print">🖨 <span>Print Center</span></button>
            <button data-section="reports">📊 <span>Reports</span></button>
            <button data-section="settings">⚙ <span>Settings</span></button>
        </nav>
        <button id="collapseSidebar" class="collapse-btn" type="button">⇤ Collapse</button>
    </aside>

    <main class="app-shell">
        <header class="topbar no-print">
            <div>
                <span class="premium-chip">Ultimate Professional Dashboard</span>
                <h2>Islamic Educational Notice Management</h2>
                <p>AJAX CRUD • Live Preview • Print-perfect A4 PDF • Malayalam + Arabic + English</p>
            </div>
            <div class="top-actions">
                <button class="premium-btn emerald" data-quick="create">+ New Notice</button>
                <button class="premium-btn gold" id="printActiveBtn">🖨 Print</button>
            </div>
        </header>

        <section data-panel="dashboard" class="panel active-panel">
            <div class="stats-grid">
                <article class="stat-card"><span>📢</span><p>Total Notices</p><strong id="statNotices">0</strong></article>
                <article class="stat-card"><span>🎓</span><p>Total Students</p><strong id="statStudents">0</strong></article>
                <article class="stat-card"><span>📋</span><p>Total Programs</p><strong id="statPrograms">0</strong></article>
                <article class="stat-card"><span>⚡</span><p>System Mode</p><strong>SPA</strong></article>
            </div>
            <div class="dashboard-grid">
                <article class="glass-panel"><h3>Latest Activities</h3><div id="activityFeed" class="activity-feed"></div></article>
                <article class="glass-panel"><h3>Premium Controls</h3><p class="muted">Use the sidebar to create, edit, duplicate, print and export notices without reloading this page.</p><div class="hero-glow">۞</div></article>
            </div>
        </section>

        <section data-panel="create" class="panel">
            <form id="noticeForm" class="form-grid" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" id="noticeId">
                <div class="glass-panel form-main">
                    <div class="section-title"><span>📝</span><div><h3>Create / Edit Notice</h3><p>All changes update the preview instantly.</p></div></div>
                    <div class="field-grid">
                        <label>Malayalam Title<input name="title_malayalam" data-preview required></label>
                        <label>Arabic Title<input name="title_arabic" data-preview dir="rtl" required></label>
                        <label>Subtitle<input name="sub_title" data-preview></label>
                        <label>Main Topic<input name="main_topic" data-preview></label>
                        <label>Date<input type="date" name="notice_date" data-preview></label>
                        <label>Time<input type="time" name="notice_time" data-preview></label>
                        <label>Venue<input name="venue" data-preview></label>
                        <label>Qiraat<input name="qiraat" data-preview></label>
                        <label>Welcome<input name="welcome" data-preview></label>
                        <label>President<input name="president" data-preview></label>
                        <label>Inaugurator<input name="inaugurator" data-preview></label>
                        <label>Translation Speech<input name="translation_speech" data-preview></label>
                        <label class="wide">Speakers<textarea name="speakers" data-preview></textarea></label>
                        <label class="wide">Singers<textarea name="singers" data-preview></textarea></label>
                        <label>Thanks<input name="thanks" data-preview></label>
                        <label>Association Stamp<input name="association_stamp" data-preview></label>
                    </div>
                </div>
                <div class="glass-panel settings-card">
                    <h3>Luxury Settings</h3>
                    <label>Theme<select name="bg_style" data-preview><option value="emerald">Emerald Gold</option><option value="gold">Royal Gold</option><option value="midnight">Midnight</option><option value="royal">Royal Blue</option><option value="bw">Black & White</option></select></label>
                    <label>Design<select name="design_style"><option value="classic">Classic Islamic</option><option value="modern">Modern Premium</option></select></label>
                    <label>Malayalam Font<select name="font_style_malayalam" data-preview><option value="manjari">Manjari</option><option value="gayathri">Gayathri</option><option value="chilanka">Chilanka</option></select></label>
                    <label>Arabic Font<select name="font_style_arabic" data-preview><option value="amiri">Amiri</option><option value="noto">Noto Naskh</option><option value="kufi">Reem Kufi</option></select></label>
                    <label>Size<select name="font_size_settings" data-preview><option value="compact">Compact</option><option value="normal">Normal</option><option value="large">Large</option></select></label>
                    <div class="button-stack"><button class="premium-btn gold" type="submit">💾 Save Notice</button><button class="premium-btn slate" type="button" id="resetFormBtn">↺ Reset</button></div>
                </div>
            </form>
        </section>

        <section data-panel="notices" class="panel">
            <div class="toolbar glass-panel"><input id="noticeSearch" placeholder="Search notices..."><select id="noticeFilter"><option value="all">All Themes</option><option value="emerald">Emerald</option><option value="gold">Gold</option><option value="bw">Black & White</option></select></div>
            <div id="noticeList" class="notice-list"></div>
        </section>

        <section data-panel="students" class="panel">
            <div class="glass-panel">
                <div class="section-title"><span>🎓</span><div><h3>Student Item Management</h3><p>Class-wise rows auto-save locally and are stored when you save the notice.</p></div></div>
                <div class="toolbar"><input id="studentSearch" placeholder="Search student..."><select id="studentSort"><option value="desc">Class Desc</option><option value="asc">Class Asc</option></select><button id="addStudentRow" class="premium-btn emerald" type="button">+ Row</button><button id="exportStudents" class="premium-btn gold" type="button">Export CSV</button></div>
                <div id="studentRows" class="student-editor"></div>
            </div>
        </section>

        <section data-panel="preview" class="panel"><div id="pdfPreview" class="preview-stage"></div></section>
        <section data-panel="print" class="panel"><div class="glass-panel print-center"><h3>Print Center</h3><p>Choose orientation and export through the browser print dialog using “Save as PDF”.</p><div><button class="premium-btn gold" id="portraitBtn">Portrait</button><button class="premium-btn emerald" id="landscapeBtn">Landscape</button><button class="premium-btn slate" id="previewPrintBtn">Print Preview</button></div></div></section>
        <section data-panel="reports" class="panel"><div id="reportsPanel" class="reports-grid"></div></section>
        <section data-panel="settings" class="panel"><div class="glass-panel"><h3>Security & Session Protection</h3><ul class="security-list"><li>CSRF token enabled</li><li>Prepared MySQL statements</li><li>Input sanitization + output escaping</li><li>Secure headers and session cookies</li><li>Keyboard inspection shortcuts disabled in UI</li></ul></div></section>
    </main>

    <div id="modal" class="modal-backdrop no-print" aria-hidden="true"><div class="premium-modal" role="dialog" aria-modal="true"><div id="modalIcon" class="modal-icon">✨</div><h3 id="modalTitle">Premium Notice</h3><p id="modalText"></p><div id="modalActions" class="modal-actions"></div></div></div>
    <script>window.MHM_CSRF = <?= json_encode(csrf_token()) ?>;</script>
    <script src="assets/app.js"></script>
</body>
</html>

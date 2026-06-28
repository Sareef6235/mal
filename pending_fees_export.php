<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
requireLogin();

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function pendingFeeFilters(): array {
    return [
        'student' => trim($_GET['student'] ?? ''),
        'class' => trim($_GET['class'] ?? ''),
        'month' => trim($_GET['month'] ?? ''),
        'payment_method' => trim($_GET['payment_method'] ?? ''),
        'date_from' => trim($_GET['date_from'] ?? ''),
        'date_to' => trim($_GET['date_to'] ?? ''),
    ];
}

function buildPendingFeesQuery(array $filters, bool $countOnly = false): array {
    $select = $countOnly
        ? "COUNT(*) AS total_entries, COUNT(DISTINCT f.student_id) AS total_students, COALESCE(SUM(f.amount * COALESCE(NULLIF(f.months_count, 0), 1)), 0) AS total_amount"
        : "f.id, f.student_id, f.class, f.amount, f.payment_method, f.date, f.month, f.status, f.months_count, f.verified_months, u.name AS student_name, u.class AS student_class";

    $sql = "SELECT {$select}
            FROM fees f
            JOIN users u ON f.student_id = u.id
            WHERE u.role = 'student'
              AND LOWER(COALESCE(f.status, '')) <> 'verified'";
    $params = [];

    if ($filters['student'] !== '') {
        $sql .= " AND u.name LIKE ?";
        $params[] = '%' . $filters['student'] . '%';
    }
    if ($filters['class'] !== '') {
        $sql .= " AND u.class = ?";
        $params[] = $filters['class'];
    }
    if ($filters['month'] !== '') {
        $sql .= " AND DATE_FORMAT(f.month, '%Y-%m') = ?";
        $params[] = $filters['month'];
    }
    if ($filters['payment_method'] !== '') {
        $sql .= " AND f.payment_method = ?";
        $params[] = $filters['payment_method'];
    }
    if ($filters['date_from'] !== '') {
        $sql .= " AND DATE(f.date) >= ?";
        $params[] = $filters['date_from'];
    }
    if ($filters['date_to'] !== '') {
        $sql .= " AND DATE(f.date) <= ?";
        $params[] = $filters['date_to'];
    }

    if (!$countOnly) {
        $sql .= " ORDER BY f.date DESC, f.id DESC";
    }

    return [$sql, $params];
}

function feeMonths(?string $month, int $monthsCount): array {
    $monthsCount = max(1, $monthsCount);
    $startMonth = !empty($month) ? date('Y-m', strtotime($month)) : date('Y-m');
    $monthObj = new DateTime($startMonth . '-01');
    $months = [];
    for ($i = 0; $i < $monthsCount; $i++) {
        $months[] = $monthObj->format('Y-m');
        $monthObj->modify('+1 month');
    }
    return $months;
}

$filters = pendingFeeFilters();
[$sql, $params] = buildPendingFeesQuery($filters);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

[$summarySql, $summaryParams] = buildPendingFeesQuery($filters, true);
$summaryStmt = $pdo->prepare($summarySql);
$summaryStmt->execute($summaryParams);
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_entries' => 0, 'total_students' => 0, 'total_amount' => 0];

$monthList = $pdo->query("SELECT DISTINCT DATE_FORMAT(month, '%Y-%m') AS month FROM fees WHERE LOWER(COALESCE(status, '')) <> 'verified' AND month IS NOT NULL ORDER BY month DESC")->fetchAll(PDO::FETCH_COLUMN);
$classList = $pdo->query("SELECT DISTINCT u.class FROM users u JOIN fees f ON f.student_id = u.id WHERE u.role='student' AND LOWER(COALESCE(f.status, '')) <> 'verified' AND u.class IS NOT NULL AND u.class <> '' ORDER BY u.class")->fetchAll(PDO::FETCH_COLUMN);
$paymentMethods = $pdo->query("SELECT DISTINCT payment_method FROM fees WHERE LOWER(COALESCE(status, '')) <> 'verified' AND payment_method IS NOT NULL AND payment_method <> '' ORDER BY payment_method")->fetchAll(PDO::FETCH_COLUMN);

$queryString = http_build_query(array_filter($filters, fn($value) => $value !== ''));
$pdfUrl = 'pending_fees_pdf.php' . ($queryString ? '?' . $queryString : '');
$excelUrl = 'pending_fees_excel.php' . ($queryString ? '?' . $queryString : '');

include 'header.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/pending_fees.css">

<div class="pending-page">
    <aside class="pending-sidebar" id="pendingSidebar">
        <div class="brand"><i class="bi bi-cash-coin"></i><span>Fee Manager</span></div>
        <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="view_fees.php"><i class="bi bi-receipt"></i> Fee Management</a>
        <a class="active" href="pending_fees_export.php"><i class="bi bi-hourglass-split"></i> Pending Export</a>
    </aside>

    <main class="pending-main">
        <section class="dashboard-hero glass-card">
            <button class="drawer-btn" id="drawerBtn"><i class="bi bi-list"></i></button>
            <div>
                <nav class="breadcrumb-line">Dashboard <i class="bi bi-chevron-right"></i> Fee Management <i class="bi bi-chevron-right"></i> Pending Export</nav>
                <h1>Pending Fee Export</h1>
                <p>Pending (Not Verified) Monthly Fee Records</p>
            </div>
            <div class="hero-actions">
                <button class="btn btn-light rounded-pill" id="themeToggle"><i class="bi bi-moon-stars"></i> Dark Mode</button>
                <button class="btn btn-primary rounded-pill export-confirm" data-url="<?= h($pdfUrl) ?>" data-type="PDF"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
                <button class="btn btn-success rounded-pill export-confirm" data-url="<?= h($excelUrl) ?>" data-type="Excel"><i class="bi bi-file-earmark-spreadsheet"></i> Export Excel</button>
            </div>
        </section>

        <section class="summary-grid">
            <div class="summary-card glass-card"><i class="bi bi-people"></i><span>Total Pending Students</span><strong class="counter" data-target="<?= (int)$summary['total_students'] ?>">0</strong></div>
            <div class="summary-card glass-card"><i class="bi bi-list-check"></i><span>Total Pending Entries</span><strong class="counter" data-target="<?= (int)$summary['total_entries'] ?>">0</strong></div>
            <div class="summary-card glass-card"><i class="bi bi-currency-rupee"></i><span>Total Pending Amount</span><strong>₹<?= number_format((float)$summary['total_amount'], 2) ?></strong></div>
            <div class="summary-card glass-card"><i class="bi bi-calendar-month"></i><span>Selected Month</span><strong><?= $filters['month'] ? h(date('F Y', strtotime($filters['month'] . '-01'))) : 'All Months' ?></strong></div>
            <div class="summary-card glass-card"><i class="bi bi-mortarboard"></i><span>Selected Class</span><strong><?= $filters['class'] ? h($filters['class']) : 'All Classes' ?></strong></div>
        </section>

        <section class="glass-card filter-card">
            <form method="GET" id="filterForm" class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-4"><label>Student Name</label><input class="form-control" name="student" value="<?= h($filters['student']) ?>" placeholder="Search student"></div>
                <div class="col-lg-2 col-md-4"><label>Class</label><input class="form-control" name="class" list="classOptions" value="<?= h($filters['class']) ?>" placeholder="Search class"><datalist id="classOptions"><?php foreach ($classList as $class): ?><option value="<?= h($class) ?>"><?php endforeach; ?></datalist></div>
                <div class="col-lg-2 col-md-4"><label>Month</label><select class="form-select" name="month"><option value="">All Months</option><?php foreach ($monthList as $month): ?><option value="<?= h($month) ?>" <?= $filters['month'] === $month ? 'selected' : '' ?>><?= h(date('F Y', strtotime($month . '-01'))) ?></option><?php endforeach; ?></select></div>
                <div class="col-lg-2 col-md-4"><label>Payment Method</label><select class="form-select" name="payment_method"><option value="">All Methods</option><?php foreach ($paymentMethods as $method): ?><option value="<?= h($method) ?>" <?= $filters['payment_method'] === $method ? 'selected' : '' ?>><?= h($method) ?></option><?php endforeach; ?></select></div>
                <div class="col-lg-2 col-md-4"><label>Date From</label><input class="form-control" type="date" name="date_from" value="<?= h($filters['date_from']) ?>"></div>
                <div class="col-lg-2 col-md-4"><label>Date To</label><input class="form-control" type="date" name="date_to" value="<?= h($filters['date_to']) ?>"></div>
                <div class="col-12 d-flex gap-2 flex-wrap"><button class="btn btn-primary rounded-pill"><i class="bi bi-search"></i> Search</button><button type="button" class="btn btn-outline-danger rounded-pill" id="resetFilters"><i class="bi bi-arrow-counterclockwise"></i> Reset</button><input id="liveSearch" class="form-control live-search" placeholder="Live search without reload..."></div>
            </form>
        </section>

        <section class="glass-card table-card">
            <div class="table-toolbar"><div><strong>Pending Fee Records</strong><small>Verified records are excluded.</small></div><label>Entries per page <select id="entriesPerPage" class="form-select form-select-sm"><option>10</option><option>25</option><option>50</option><option>100</option></select></label></div>
            <?php if (empty($fees)): ?>
                <div class="empty-state"><i class="bi bi-inbox"></i><h3>No Pending Fee Records Found</h3><p>Try adjusting filters or check after new submissions.</p></div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table pending-table" id="pendingTable">
                    <thead><tr><th>No</th><th>Student Name</th><th>Class</th><th>Fee Amount</th><th>Months Count</th><th>Total Amount</th><th>Month</th><th>Payment Method</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php $no = 1; foreach ($fees as $fee): $monthsCount = (int)($fee['months_count'] ?: 1); $months = feeMonths($fee['month'] ?? '', $monthsCount); $total = (float)$fee['amount'] * $monthsCount; ?>
                        <tr>
                            <td data-label="No"><?= $no++ ?></td>
                            <td data-label="Student Name"><?= h($fee['student_name']) ?></td>
                            <td data-label="Class"><?= h($fee['student_class'] ?: $fee['class']) ?></td>
                            <td data-label="Fee Amount">₹<?= number_format((float)$fee['amount'], 2) ?></td>
                            <td data-label="Months Count"><?= $monthsCount ?></td>
                            <td data-label="Total Amount"><strong>₹<?= number_format($total, 2) ?></strong></td>
                            <td data-label="Month"><button class="month-chip" type="button"><?= h(date('M Y', strtotime($months[0] . '-01'))) ?><?= $monthsCount > 1 ? ' +' . ($monthsCount - 1) : '' ?></button><div class="month-stack"><?php foreach ($months as $m): ?><span><?= h(date('M Y', strtotime($m . '-01'))) ?></span><?php endforeach; ?></div></td>
                            <td data-label="Payment Method"><?= h($fee['payment_method']) ?></td>
                            <td data-label="Date"><?= h($fee['date']) ?></td>
                            <td data-label="Status"><span class="badge text-bg-warning"><?= h($fee['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap"><button class="btn btn-sm btn-outline-primary" id="prevPage">Previous</button><span id="pageInfo"></span><button class="btn btn-sm btn-outline-primary" id="nextPage">Next</button></div>
            <?php endif; ?>
        </section>
    </main>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content premium-modal"><div class="modal-header"><h5 class="modal-title">Confirm Export</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p id="confirmText">Export filtered records?</p></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><a class="btn btn-primary" id="confirmExportBtn" href="#">Continue</a></div></div></div></div>
<div id="loadingOverlay"><div class="spinner-border text-primary"></div><strong>Preparing your export...</strong></div>
<script src="assets/pending_fees.js"></script>
<?php include 'footer.php'; ?>

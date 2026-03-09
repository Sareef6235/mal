<?php
declare(strict_types=1);
$path = basename((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
$menu = [
    ['Dashboard', 'dashboard.php', 'bi-speedometer2'],
    ['Students', 'students.php', 'bi-people'],
    ['Subjects', 'subjects.php', 'bi-journal-bookmark'],
    ['Exams', 'exams.php', 'bi-journal-check'],
    ['Results Import', 'results_import.php', 'bi-upload'],
    ['Rank List', 'rank_list.php', 'bi-trophy'],
    ['Marksheet', 'marksheet.php', 'bi-file-earmark-text'],
    ['Attendance (QR)', 'attendance_qr.php', 'bi-qr-code-scan'],
    ['Fees', 'fees.php', 'bi-cash-stack'],
    ['ID Cards', 'id_card.php', 'bi-person-vcard'],
    ['Reports', '../reports/result_report.php', 'bi-graph-up-arrow'],
    ['Database Select', '../database_select.php', 'bi-database-gear'],
    ['MySQL DB Page', '../database_mysql.php', 'bi-server'],
    ['SQLite DB Page', '../database_sqlite.php', 'bi-filetype-db'],
    ['Admin Portal', '../admin_portal.php', 'bi-person-workspace'],
    ['Class Sheet', '../class_result_sheet.php', 'bi-table'],
];
?>
<aside class="col-lg-2 sidebar p-3" id="sidebarNav">
  <ul class="nav flex-column gap-1">
    <?php foreach ($menu as [$label, $href, $icon]): ?>
      <li class="nav-item"><a class="nav-link <?= $path === basename($href) ? 'active' : '' ?>" href="<?= e($href) ?>"><i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?></a></li>
    <?php endforeach; ?>
  </ul>
</aside>
<main class="col-lg-10 p-3 content-wrap">

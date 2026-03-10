<?php
declare(strict_types=1);

$path = basename((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));

$groups = [
    [
        'title' => 'Main Portal',
        'icon' => 'bi-house-door',
        'items' => [
            ['Portal', '../index.php', 'bi-house'],
            ['Result Viewer', '../student_result_viewer.php', 'bi-search'],
        ],
    ],
    [
        'title' => 'ERP Dashboard',
        'icon' => 'bi-speedometer2',
        'items' => [
            ['ERP Dashboard', 'dashboard.php', 'bi-bar-chart'],
            ['Fest Dashboard', 'fest_dashboard.php', 'bi-stars'],
        ],
    ],
    [
        'title' => 'Festival Management',
        'icon' => 'bi-calendar-event',
        'items' => [
            ['Festivals', 'festivals.php', 'bi-calendar3'],
            ['Fest Events', 'festival_events.php', 'bi-megaphone'],
            ['Fest Participants', 'festival_participants.php', 'bi-people'],
            ['Fest Scoreboard', 'festival_scoreboard.php', 'bi-trophy'],
        ],
    ],
    [
        'title' => 'Student Management',
        'icon' => 'bi-mortarboard',
        'items' => [
            ['Students', 'students.php', 'bi-people-fill'],
            ['Subjects', 'subjects.php', 'bi-journal-bookmark'],
            ['Exams', 'exams.php', 'bi-journal-check'],
            ['Results Import', 'results_import.php', 'bi-upload'],
            ['Rank List', 'rank_list.php', 'bi-award'],
            ['Marksheet', 'marksheet.php', 'bi-file-earmark-text'],
        ],
    ],
    [
        'title' => 'Attendance',
        'icon' => 'bi-qr-code-scan',
        'items' => [
            ['Attendance QR', 'attendance_qr.php', 'bi-qr-code'],
            ['QR Attendance', '../attendance_qr.php', 'bi-camera-video'],
            ['Scanner Dashboard', '../qr_scanner_dashboard.php', 'bi-speedometer'],
            ['Camera Scanner', '../scanner.php', 'bi-camera'],
        ],
    ],
    [
        'title' => 'Fees',
        'icon' => 'bi-cash-stack',
        'items' => [
            ['Fees', 'fees.php', 'bi-cash-coin'],
            ['Fee Management', '../fee_management.php', 'bi-wallet2'],
        ],
    ],
    [
        'title' => 'ID Card System',
        'icon' => 'bi-person-vcard',
        'items' => [
            ['ID Cards', 'id_card.php', 'bi-person-vcard-fill'],
            ['ID Card', '../idcard.php', 'bi-person-badge'],
            ['Edit ID Card', '../idcard_edit.php', 'bi-pencil-square'],
            ['Self Card', '../self_card.php', 'bi-person-circle'],
            ['Self Card Bulk', '../self_card_bulk.php', 'bi-collection'],
            ['ID Bulk', '../id_card_generator_bulk.php', 'bi-grid-3x3-gap'],
            ['ID Admin', '../admin_cards.php', 'bi-shield-lock'],
            ['A4 ID Print', '../idcard_bulk.php', 'bi-printer'],
        ],
    ],
    [
        'title' => 'Reports',
        'icon' => 'bi-graph-up-arrow',
        'items' => [
            ['Reports', '../reports/result_report.php', 'bi-file-bar-graph'],
            ['Class Sheet', '../class_result_sheet.php', 'bi-table'],
            ['Register Result', '../register_result.php', 'bi-clipboard-data'],
            ['Class Result', '../class_result.php', 'bi-file-earmark-spreadsheet'],
        ],
    ],
    [
        'title' => 'Admission',
        'icon' => 'bi-person-plus',
        'items' => [
            ['Online Admission', '../admission_form.php', 'bi-person-plus-fill'],
        ],
    ],
    [
        'title' => 'Certificates',
        'icon' => 'bi-patch-check',
        'items' => [
            ['Certificate', '../certificate.php', 'bi-award'],
        ],
    ],
    [
        'title' => 'Database',
        'icon' => 'bi-database',
        'items' => [
            ['DB Select', '../database_select.php', 'bi-database-gear'],
            ['MySQL DB', '../database_mysql.php', 'bi-server'],
            ['SQLite DB', '../database_sqlite.php', 'bi-filetype-db'],
        ],
    ],
    [
        'title' => 'Admin Tools',
        'icon' => 'bi-tools',
        'items' => [
            ['Admin Portal', '../admin_portal.php', 'bi-person-workspace'],
            ['Bulk Import', '../admin_bulk_upload.php', 'bi-cloud-upload'],
        ],
    ],
];
?>
<nav class="erp-nav navbar navbar-expand-xl navbar-dark" id="erpNav">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#erpNavMenu" aria-controls="erpNavMenu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="erpNavMenu">
      <ul class="navbar-nav align-items-xl-center flex-wrap gap-xl-1">
        <?php foreach ($groups as $group): ?>
          <?php
            $isGroupActive = false;
            foreach ($group['items'] as $item) {
                if ($path === basename($item[1])) {
                    $isGroupActive = true;
                    break;
                }
            }
          ?>
          <li class="nav-item dropdown menu-group-dropdown <?= $isGroupActive ? 'active-group' : '' ?>">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi <?= e((string)$group['icon']) ?> me-1"></i><?= e((string)$group['title']) ?>
            </a>
            <ul class="dropdown-menu shadow-sm">
              <?php foreach ($group['items'] as [$label, $href, $icon]): ?>
                <?php $isActive = $path === basename($href); ?>
                <li>
                  <a class="dropdown-item menu-link <?= $isActive ? 'active is-checked' : '' ?>" href="<?= e($href) ?>" data-menu-label="<?= e(strtolower((string)$label . ' ' . (string)$group['title'])) ?>">
                    <i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php endforeach; ?>
      </ul>

      <form class="d-flex ms-xl-auto mt-3 mt-xl-0" role="search">
        <div class="input-group nav-search-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input id="menuSearch" class="form-control" type="search" placeholder="Search menu..." aria-label="Search menu">
        </div>
      </form>
    </div>
  </div>
</nav>

<main class="col-12 p-3 content-wrap">

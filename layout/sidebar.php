<?php
declare(strict_types=1);

$path = basename((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));

$groups = [
    [
        'title' => 'Main',
        'icon' => 'bi-grid-1x2',
        'items' => [
            ['Dashboard', 'dashboard.php', 'bi-speedometer2'],
            ['Students', 'students.php', 'bi-people'],
            ['Subjects', 'subjects.php', 'bi-journal-bookmark'],
            ['Exams', 'exams.php', 'bi-journal-check'],
            ['Results Import', 'results_import.php', 'bi-upload'],
            ['Rank List', 'rank_list.php', 'bi-trophy'],
            ['Marksheet', 'marksheet.php', 'bi-file-earmark-text'],
            ['User Guide', 'user_guide.php', 'bi-question-circle'],
        ],
    ],
    [
        'title' => 'Operations',
        'icon' => 'bi-gear',
        'items' => [
            ['Attendance (QR)', 'attendance_qr.php', 'bi-qr-code-scan'],
            ['Fees', 'fees.php', 'bi-cash-stack'],
            ['ID Cards', 'id_card.php', 'bi-person-vcard'],
            ['Reports', '../reports/result_report.php', 'bi-graph-up-arrow'],
        ],
    ],
    [
        'title' => 'Admin Tools',
        'icon' => 'bi-wrench-adjustable-circle',
        'items' => [
            ['Database Select', '../database_select.php', 'bi-database-gear'],
            ['MySQL DB Page', '../database_mysql.php', 'bi-server'],
            ['SQLite DB Page', '../database_sqlite.php', 'bi-filetype-db'],
            ['Admin Portal', '../admin_portal.php', 'bi-person-workspace'],
            ['Class Sheet', '../class_result_sheet.php', 'bi-table'],
            ['Register Result', '../register_result.php', 'bi-search'],
            ['Certificate', '../certificate.php', 'bi-award'],
            ['ID Admin', '../admin_cards.php', 'bi-person-badge'],
            ['A4 ID Print', '../idcard_bulk.php', 'bi-printer'],
        ],
    ],
    [
        'title' => 'Fest ERP',
        'icon' => 'bi-stars',
        'items' => [
            ['Fest Dashboard', 'fest_dashboard.php', 'bi-speedometer'],
            ['Festivals', 'festivals.php', 'bi-calendar-event'],
            ['Events', 'festival_events.php', 'bi-megaphone'],
            ['Participants', 'festival_participants.php', 'bi-person-lines-fill'],
            ['Festival Feedback', 'festival_feedback.php', 'bi-chat-left-dots'],
            ['Feedback Moderation', 'festival_feedback_admin.php', 'bi-shield-check'],
            ['Live Scoreboard', 'festival_scoreboard.php', 'bi-tv'],
        ],
    ],
];
?>
<aside class="col-lg-2 sidebar p-3" id="sidebarNav">
  <div class="sidebar-title">Navigation</div>

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
    <details class="menu-group" <?= $isGroupActive ? 'open' : '' ?>>
      <summary>
        <span><i class="bi <?= e((string)$group['icon']) ?> me-2"></i><?= e((string)$group['title']) ?></span>
        <i class="bi bi-chevron-down"></i>
      </summary>
      <ul class="nav flex-column gap-1 mt-2">
        <?php foreach ($group['items'] as [$label, $href, $icon]): ?>
          <?php $isActive = $path === basename($href); ?>
          <li class="nav-item">
            <a class="nav-link <?= $isActive ? 'active is-checked' : '' ?>" href="<?= e($href) ?>">
              <i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </details>
  <?php endforeach; ?>
</aside>
<main class="col-lg-10 p-3 content-wrap">

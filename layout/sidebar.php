<?php
declare(strict_types=1);
$menu = [
    'Dashboard' => 'dashboard.php',
    'Students' => 'students.php',
    'Admissions' => 'student_add.php',
    'Fees' => 'fees.php',
    'Attendance (QR)' => 'attendance_qr.php',
    'Exams' => 'exams.php',
    'Subjects' => 'subjects.php',
    'Results Import' => 'results_import.php',
    'Results View' => 'results_view.php',
    'Rank List' => 'rank_list.php',
    'Marksheet' => 'marksheet.php',
    'ID Cards' => 'id_card.php',
    'Reports' => '../reports/result_report.php',
];
?>
<aside class="col-md-2 bg-light min-vh-100 p-3">
  <ul class="nav flex-column gap-2">
    <?php foreach ($menu as $label => $href): ?>
      <li class="nav-item"><a class="nav-link" href="<?= h($href) ?>"><?= h($label) ?></a></li>
    <?php endforeach; ?>
  </ul>
</aside>
<main class="col-md-10 p-3">

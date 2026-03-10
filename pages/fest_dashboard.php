<?php
declare(strict_types=1);
require_once __DIR__ . '/../modules/festival.php';
$db = fest_db();
$stats = fest_stats($db);
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<div class="row g-3">
  <div class="col-md-4"><div class="card card-soft stat-card p-3"><h6>Total Festivals</h6><h3><?= (int)$stats['festivals'] ?></h3></div></div>
  <div class="col-md-4"><div class="card card-soft stat-card p-3"><h6>Total Events</h6><h3><?= (int)$stats['events'] ?></h3></div></div>
  <div class="col-md-4"><div class="card card-soft stat-card p-3"><h6>Total Participants</h6><h3><?= (int)$stats['participants'] ?></h3></div></div>
  <div class="col-md-4"><div class="card card-soft stat-card p-3"><h6>Completed Events</h6><h3><?= (int)$stats['completed_events'] ?></h3></div></div>
  <div class="col-md-4"><div class="card card-soft stat-card p-3"><h6>Top Performer</h6><h3><?= e($stats['top_performer']) ?></h3></div></div>
  <div class="col-md-4"><div class="card card-soft stat-card p-3"><h6>Top House</h6><h3><?= e($stats['top_house']) ?></h3></div></div>
</div>
<div class="card card-soft mt-3 p-3 fest-gradient">
  <h4>Festival ERP Center</h4>
  <p class="mb-0">Manage Milad Fest, Art Fest, Annual Fest with events, participants, scores, rankings and TV scoreboard.</p>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>

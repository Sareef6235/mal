<?php
declare(strict_types=1);
require_once __DIR__ . '/../modules/festival.php';
$db = fest_db();
$eventRanks = $db->query('SELECT e.event_name, p.name, p.class_name, COALESCE(SUM(s.score),0) AS total FROM festival_participants p JOIN festival_events e ON e.id=p.event_id LEFT JOIN festival_scores s ON s.participant_id=p.id GROUP BY e.event_name,p.id,p.name,p.class_name ORDER BY e.event_name ASC,total DESC')->fetchAll() ?: [];
$houseBoard = $db->query('SELECT h.house_name, h.color_code, COALESCE(SUM(hp.points),0) AS points FROM houses h LEFT JOIN house_points hp ON hp.house_id=h.id GROUP BY h.id,h.house_name,h.color_code ORDER BY points DESC')->fetchAll() ?: [];
include __DIR__ . '/../layout/header.php'; include __DIR__ . '/../layout/sidebar.php';
?>
<div class="card card-soft p-3 fest-gradient">
  <div class="d-flex justify-content-between align-items-center">
    <h3 class="mb-0">LIVE FEST SCOREBOARD</h3>
    <span class="badge bg-light text-dark">Auto refresh 15s</span>
  </div>
</div>
<div class="row g-3 mt-1">
  <div class="col-lg-7"><div class="card card-soft p-3"><h5>Event Ranking</h5><table class="table table-modern"><thead><tr><th>Event</th><th>Name</th><th>Class</th><th>Score</th></tr></thead><tbody><?php foreach($eventRanks as $r): ?><tr><td><?= e((string)$r['event_name']) ?></td><td><?= e((string)$r['name']) ?></td><td><?= e((string)$r['class_name']) ?></td><td><strong><?= e((string)$r['total']) ?></strong></td></tr><?php endforeach; ?></tbody></table></div></div>
  <div class="col-lg-5"><div class="card card-soft p-3"><h5>House Leaderboard</h5><?php foreach($houseBoard as $h): ?><div class="d-flex justify-content-between align-items-center p-2 mb-2 rounded" style="background:<?= e((string)$h['color_code']) ?>22;border-left:6px solid <?= e((string)$h['color_code']) ?>;"><strong><?= e((string)$h['house_name']) ?></strong><span class="badge bg-dark"><?= (int)$h['points'] ?> pts</span></div><?php endforeach; ?></div></div>
</div>
<script>setTimeout(()=>location.reload(),15000);</script>
<?php include __DIR__ . '/../layout/footer.php'; ?>

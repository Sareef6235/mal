<?php
require __DIR__ . '/config/bootstrap.php';

$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');

function topper(PDO $pdo, string $from, string $to): ?array {
    $stmt = $pdo->prepare('SELECT s.name, SUM(p.points) total_points FROM prayers p JOIN students s ON s.id=p.student_id WHERE p.date BETWEEN :f AND :t GROUP BY s.id,s.name ORDER BY total_points DESC LIMIT 1');
    $stmt->execute(['f' => $from, 't' => $to]);
    return $stmt->fetch() ?: null;
}

$daily = topper($pdo, $today, $today);
$weekly = topper($pdo, $weekStart, $today);
$monthly = topper($pdo, $monthStart, $today);

render_header('🕌 നിസ്കാരം ട്രാക്കർ');
?>
<section class="card">
  <h1 class="text-xl font-bold">🕌 നിസ്കാരം ട്രാക്കർ</h1>
  <p class="text-xs text-slate-600">Daily Prayer &amp; Study Tracker</p>
  <div class="mt-3 rounded-xl bg-emerald-100 p-3">
    <p class="text-xs text-emerald-700">📅 ഇന്നത്തെ തിയതി</p>
    <p class="text-sm font-semibold"><?= e((new IntlDateFormatter('ml_IN', IntlDateFormatter::FULL, IntlDateFormatter::NONE))->format(time())) ?></p>
  </div>
</section>
<section class="mt-4 space-y-3">
  <article class="card"><h2 class="font-semibold">🏆 ഇന്നത്തെ ടോപ്പർ</h2><p><?= $daily ? e($daily['name']) . ' - ' . (int)$daily['total_points'] . ' pt' : 'ഡാറ്റയില്ല' ?></p></article>
  <article class="card"><h2 class="font-semibold">📅 ആഴ്ചയിലെ ടോപ്പർ</h2><p><?= $weekly ? e($weekly['name']) . ' - ' . (int)$weekly['total_points'] . ' pt' : 'ഡാറ്റയില്ല' ?></p></article>
  <article class="card"><h2 class="font-semibold">📆 മാസത്തിലെ ടോപ്പർ</h2><p><?= $monthly ? e($monthly['name']) . ' - ' . (int)$monthly['total_points'] . ' pt' : 'ഡാറ്റയില്ല' ?></p></article>
</section>
<?php render_nav('home'); render_footer(); ?>

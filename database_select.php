<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$current = is_file(__DIR__ . '/db_config.php') ? ((include __DIR__ . '/db_config.php')['db_type'] ?? 'sqlite') : 'sqlite';
render_header('Database Select');
?>
<div class="card">
  <h2>Select Database</h2>
  <p class="small">Choose one setup page:</p>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
    <a href="database_mysql.php" style="display:block;padding:12px;border:1px solid rgba(255,255,255,.3);border-radius:10px;text-decoration:none;color:#fff;">MySQL (Professional Server)</a>
    <a href="database_sqlite.php" style="display:block;padding:12px;border:1px solid rgba(255,255,255,.3);border-radius:10px;text-decoration:none;color:#fff;">SQLite (Single File Database)</a>
  </div>
  <p class="small" style="margin-top:10px">Current selected DB: <strong><?= e(strtoupper($current)) ?></strong></p>
</div>
<?php render_footer();

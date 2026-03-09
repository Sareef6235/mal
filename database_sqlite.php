<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$current = is_file(__DIR__ . '/db_config.php') ? ((include __DIR__ . '/db_config.php')['db_type'] ?? 'sqlite') : 'sqlite';
render_header('SQLite Database Setup');
?>
<div class="card">
  <h2>SQLite (Single File Database)</h2>
  <p class="small">Use this option for local/single-file database mode.</p>
  <form method="post" action="save_db.php">
    <input type="hidden" name="db_type" value="sqlite">
    <input type="hidden" name="from" value="sqlite">
    <p>Current selected DB: <strong><?= e(strtoupper($current)) ?></strong></p>
    <button type="submit">Use SQLite</button>
  </form>
</div>
<?php render_footer();

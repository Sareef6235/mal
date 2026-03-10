<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$current = is_file(__DIR__ . '/db_config.php') ? ((include __DIR__ . '/db_config.php')['db_type'] ?? 'sqlite') : 'sqlite';
render_header('MySQL Database Setup');
?>
<div class="card">
  <p>
    <a class="active" href="database_mysql.php">MySQL DB</a>
    |
    <a class="" href="database_sqlite.php">SQLite DB</a>
  </p>
  <h2>MySQL (Professional Server)</h2>
  <p class="small">Use this option for production/server database.</p>
  <form method="post" action="save_db.php">
    <input type="hidden" name="db_type" value="mysql">
    <input type="hidden" name="from" value="mysql">
    <p>Current selected DB: <strong><?= e(strtoupper($current)) ?></strong></p>
    <button type="submit">Use MySQL</button>
  </form>
</div>
<?php render_footer();

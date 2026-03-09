<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$current = is_file(__DIR__ . '/db_config.php') ? ((include __DIR__ . '/db_config.php')['db_type'] ?? 'sqlite') : 'sqlite';
render_header('Database Select');
?>
<div class="card">
  <h2>Select Database</h2>
  <form method="post" action="save_db.php">
    <label><input type="radio" name="db_type" value="mysql" <?= $current==='mysql'?'checked':'' ?>> MySQL (Professional Server)</label><br><br>
    <label><input type="radio" name="db_type" value="sqlite" <?= $current==='sqlite'?'checked':'' ?>> SQLite (Single File Database)</label><br><br>
    <button type="submit">Save</button>
  </form>
</div>
<?php render_footer();

<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$user = requireAuth();
?>
<h2>Welcome, <?= sanitize($user['username']) ?></h2>
<p>Role: <?= sanitize($user['role']) ?></p>
<a href="logout.php">Logout</a>

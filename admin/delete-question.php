<?php
require_once __DIR__ . '/../config.php';
require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM questions WHERE id = ?');
    $stmt->execute([$id]);
    set_flash('success', 'Question deleted successfully.');
} else {
    set_flash('error', 'Invalid question ID.');
}

redirect('/admin/dashboard.php');

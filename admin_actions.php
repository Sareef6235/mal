<?php
require_once __DIR__ . '/functions.php';
require_admin();
verify_csrf();
$action = $_POST['action'] ?? '';
try {
    if ($action === 'delete_material') {
        $id = (int)$_POST['id'];
        $pdo->prepare('DELETE FROM materials WHERE id = ?')->execute([$id]);
        header('Location: admin.php?deleted=1');
        exit;
    }
    if ($action === 'update_material') {
        $stmt = $pdo->prepare('UPDATE materials SET title=?, description=?, subject_id=?, class_id=?, tags=?, is_published=?, is_featured=?, is_premium=? WHERE id=?');
        $stmt->execute([
            trim($_POST['title']), trim($_POST['description']), (int)$_POST['subject_id'], (int)$_POST['class_id'], trim($_POST['tags']),
            isset($_POST['is_published']) ? 1 : 0, isset($_POST['is_featured']) ? 1 : 0, isset($_POST['is_premium']) ? 1 : 0, (int)$_POST['id']
        ]);
        header('Location: admin.php?updated=1');
        exit;
    }
    if ($action === 'add_subject') {
        $name = trim($_POST['name']);
        $pdo->prepare('INSERT INTO subjects (name, slug, color) VALUES (?, ?, ?)')->execute([$name, slugify($name), $_POST['color'] ?: '#4f46e5']);
    }
    if ($action === 'add_class') {
        $name = trim($_POST['name']);
        $pdo->prepare('INSERT INTO classes (name, slug) VALUES (?, ?)')->execute([$name, slugify($name)]);
    }
    header('Location: admin.php?ok=1');
} catch (Throwable $e) {
    header('Location: admin.php?error=' . urlencode($e->getMessage()));
}

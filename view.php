<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/partials.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT m.*, s.name subject_name, s.color subject_color, c.name class_name FROM materials m JOIN subjects s ON s.id=m.subject_id JOIN classes c ON c.id=m.class_id WHERE m.id=? AND m.is_published=1');
$stmt->execute([$id]);
$material = $stmt->fetch();
if (!$material) { http_response_code(404); exit('Material not found.'); }
$pdo->prepare('UPDATE materials SET views_count = views_count + 1 WHERE id=?')->execute([$id]);
$pdo->prepare('INSERT INTO views (material_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)')->execute([$id, $_SESSION['user_id'] ?? null, $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
render_head($material['title']);
?>
<div class="app-shell">
    <?php render_sidebar('materials'); ?>
    <main>
        <div class="topbar d-flex align-items-center justify-content-between"><button class="btn btn-ghost d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button><div><div class="fw-bold"><?= e($material['title']) ?></div><small class="text-muted-premium"><?= e($material['subject_name']) ?> · <?= e($material['class_name']) ?></small></div><a class="btn btn-premium" href="download.php?id=<?= (int)$material['id'] ?>"><i class="bi bi-download"></i> Download</a></div>
        <div class="content">
            <section class="glass rounded-5 p-4 p-lg-5 reveal mb-4">
                <div class="d-flex gap-2 flex-wrap mb-3"><span class="badge-soft" style="border-color:<?= e($material['subject_color']) ?>66"><?= e($material['subject_name']) ?></span><span class="badge-soft"><?= e($material['class_name']) ?></span><span class="badge-soft text-uppercase"><?= e($material['file_type']) ?></span></div>
                <h1 class="fw-black display-5 mb-3"><?= e($material['title']) ?></h1>
                <p class="lead text-muted-premium"><?= e($material['description'] ?: 'Preview this resource and download it for offline learning.') ?></p>
                <div class="d-flex gap-4 text-muted-premium flex-wrap"><span><i class="bi bi-eye"></i> <?= number_format((int)$material['views_count'] + 1) ?> views</span><span><i class="bi bi-download"></i> <?= number_format((int)$material['downloads_count']) ?> downloads</span><span><i class="bi bi-hdd"></i> <?= e(format_bytes((int)$material['file_size'])) ?></span><span><i class="bi bi-calendar3"></i> <?= date('M j, Y', strtotime($material['created_at'])) ?></span></div>
            </section>
            <section class="glass rounded-5 p-3 p-lg-4 reveal">
                <?php if ($material['file_type'] === 'pdf'): ?>
                    <iframe src="<?= e($material['file_path']) ?>" class="w-100 rounded-4" style="height:75vh;border:0"></iframe>
                <?php elseif ($material['file_type'] === 'video'): ?>
                    <video controls class="w-100 rounded-4" style="max-height:75vh" src="<?= e($material['file_path']) ?>"></video>
                <?php elseif ($material['file_type'] === 'image'): ?>
                    <img class="img-fluid rounded-4 d-block mx-auto" src="<?= e($material['file_path']) ?>" alt="<?= e($material['title']) ?>">
                <?php else: ?>
                    <div class="text-center py-5"><i class="bi <?= icon_for_type($material['file_type']) ?> file-icon"></i><h3 class="mt-3">Preview unavailable</h3><p class="text-muted-premium">Download this file to open it on your device.</p><a class="btn btn-premium btn-lg" href="download.php?id=<?= (int)$material['id'] ?>">Download file</a></div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>
<?php render_mobile_nav(); render_scripts(); ?>

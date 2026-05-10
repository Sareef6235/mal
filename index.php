<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/partials.php';
$subjects = fetch_subjects($pdo);
$classes = fetch_classes($pdo);
$materials = material_query($pdo, ['limit' => 12]);
$trending = material_query($pdo, ['sort' => 'trending', 'limit' => 4]);
$recent = material_query($pdo, ['sort' => 'newest', 'limit' => 4]);
$totalMaterials = (int)$pdo->query('SELECT COUNT(*) FROM materials WHERE is_published = 1')->fetchColumn();
$totalDownloads = (int)$pdo->query('SELECT COALESCE(SUM(downloads_count),0) FROM materials')->fetchColumn();
$totalViews = (int)$pdo->query('SELECT COALESCE(SUM(views_count),0) FROM materials')->fetchColumn();
render_head('Public Materials Dashboard');
?>
<div class="app-shell">
    <?php render_sidebar('dashboard'); ?>
    <main>
        <div class="topbar d-flex align-items-center justify-content-between">
            <button class="btn btn-ghost d-lg-none" data-sidebar-toggle><i class="bi bi-list"></i></button>
            <div><div class="fw-bold">Public Study Materials</div><small class="text-muted-premium">Search, preview, favorite, and download trusted resources.</small></div>
            <a class="btn btn-premium" href="admin.php"><i class="bi bi-shield-lock"></i> Admin</a>
        </div>
        <div class="content">
            <section class="hero-card glass reveal mb-4">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <span class="badge-soft mb-3 d-inline-flex"><i class="bi bi-lightning-charge-fill me-2 text-warning"></i> World-class EdTech resource cloud</span>
                        <h1 class="gradient-title mb-3">Study smarter with beautiful materials.</h1>
                        <p class="lead text-muted-premium mb-4">A premium public dashboard for PDFs, videos, images, documents, and downloadable class resources with live search and trend discovery.</p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="#materials" class="btn btn-premium btn-lg px-4"><i class="bi bi-search"></i> Explore Materials</a>
                            <a href="#trending" class="btn btn-ghost btn-lg px-4"><i class="bi bi-fire"></i> Trending Now</a>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="row g-3">
                            <div class="col-6"><div class="stat-card glass p-4"><i class="bi bi-journal-richtext fs-2 text-info"></i><h3 class="mt-3 mb-0"><?= number_format($totalMaterials) ?></h3><small class="text-muted-premium">Materials</small></div></div>
                            <div class="col-6"><div class="stat-card glass p-4"><i class="bi bi-eye fs-2 text-success"></i><h3 class="mt-3 mb-0"><?= number_format($totalViews) ?></h3><small class="text-muted-premium">Views</small></div></div>
                            <div class="col-12"><div class="stat-card glass p-4"><i class="bi bi-download fs-2 text-warning"></i><h3 class="mt-3 mb-0"><?= number_format($totalDownloads) ?></h3><small class="text-muted-premium">Community downloads</small></div></div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="materials" class="glass rounded-5 p-3 p-lg-4 reveal mb-4">
                <form id="filterForm" class="row g-3 align-items-center mb-4">
                    <div class="col-lg-5"><input name="q" class="form-control search-box" placeholder="Search by title, tag, description..."></div>
                    <div class="col-md-4 col-lg-2"><select name="subject" class="form-select"><option value="">All subjects</option><?php foreach ($subjects as $subject): ?><option value="<?= e($subject['slug']) ?>"><?= e($subject['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4 col-lg-2"><select name="class" class="form-select"><option value="">All classes</option><?php foreach ($classes as $class): ?><option value="<?= e($class['slug']) ?>"><?= e($class['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4 col-lg-3"><select name="sort" class="form-select"><option value="newest">Newest first</option><option value="popular">Most downloaded</option><option value="trending">Trending score</option></select></div>
                </form>
                <div id="materialsGrid" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    <?php foreach ($materials as $material): ?>
                    <div class="col"><?php include __DIR__ . '/material_card.php'; ?></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="row g-4">
                <div class="col-xl-6" id="trending"><section class="glass rounded-5 p-4 reveal h-100"><h3 class="fw-bold mb-3"><i class="bi bi-fire text-warning"></i> Trending materials</h3><?php foreach ($trending as $material): ?><a class="d-flex align-items-center gap-3 p-3 rounded-4 mb-2 btn-ghost" href="view.php?id=<?= (int)$material['id'] ?>"><i class="bi <?= icon_for_type($material['file_type']) ?> fs-3 text-info"></i><span class="flex-grow-1"><strong><?= e($material['title']) ?></strong><small class="d-block text-muted-premium"><?= e($material['subject_name']) ?> · <?= number_format((int)$material['views_count']) ?> views</small></span><i class="bi bi-arrow-up-right"></i></a><?php endforeach; ?></section></div>
                <div class="col-xl-6"><section class="glass rounded-5 p-4 reveal h-100"><h3 class="fw-bold mb-3"><i class="bi bi-clock-history text-info"></i> Recently uploaded</h3><?php foreach ($recent as $material): ?><a class="d-flex align-items-center gap-3 p-3 rounded-4 mb-2 btn-ghost" href="view.php?id=<?= (int)$material['id'] ?>"><i class="bi <?= icon_for_type($material['file_type']) ?> fs-3 text-info"></i><span class="flex-grow-1"><strong><?= e($material['title']) ?></strong><small class="d-block text-muted-premium"><?= date('M j, Y', strtotime($material['created_at'])) ?> · <?= e(format_bytes((int)$material['file_size'])) ?></small></span><i class="bi bi-arrow-up-right"></i></a><?php endforeach; ?></section></div>
            </div>
        </div>
    </main>
</div>
<?php render_mobile_nav(); render_scripts(); ?>

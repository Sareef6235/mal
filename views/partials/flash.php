<?php $flash = get_flash(); ?>
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']); ?> js-alert">
        <?= e($flash['message']); ?>
    </div>
<?php endif; ?>

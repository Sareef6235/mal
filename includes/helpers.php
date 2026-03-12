<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function fetch_classes(PDO $pdo): array
{
    return $pdo->query('SELECT id, name, teacher_name FROM classes ORDER BY name')->fetchAll();
}

function render_header(string $title): void
{
    echo '<!DOCTYPE html><html lang="ml"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . e($title) . '</title><script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="/assets/css/style.css"></head><body class="min-h-screen bg-gradient-to-br from-emerald-50 via-slate-50 to-indigo-50 text-slate-800 pb-24"><main class="mx-auto w-full max-w-[420px] px-4 pt-4">';
}

function render_footer(): void
{
    echo '</main><script src="/assets/js/app.js"></script></body></html>';
}

function render_nav(string $active): void
{
    $items = [
        'home' => ['/index.php', '🏠', 'ഹോം'],
        'tracker' => ['/tracker.php', '📖', 'ട്രാക്കർ'],
        'history' => ['/history.php', '⏱', 'ഹിസ്റ്ററി'],
        'profile' => ['/profile.php', '👤', 'പ്രൊഫൈൽ'],
    ];

    echo '<nav class="fixed bottom-0 left-0 right-0 mx-auto grid w-full max-w-md grid-cols-4 border-t border-slate-200 bg-white/95 px-1 py-2 shadow-2xl backdrop-blur-md">';
    foreach ($items as $key => [$href, $icon, $label]) {
        $class = $active === $key ? 'text-emerald-600' : 'text-slate-500';
        echo '<a href="' . $href . '" class="flex flex-col items-center text-[11px] font-medium ' . $class . '"><span class="text-lg">' . $icon . '</span>' . $label . '</a>';
    }
    echo '</nav>';
}

function parse_bool(string $value): int
{
    return in_array(strtolower(trim($value)), ['1', 'yes', 'true', 'y'], true) ? 1 : 0;
}

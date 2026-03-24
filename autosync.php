<?php

declare(strict_types=1);

$defaults = [
    'auto_sync_enabled' => true,
    'sync_frequency' => 'realtime',
    'admin_sound' => 'chime',
    'customer_sound' => 'soft-bell',
    'notification_enabled' => true,
];

$settings = $defaults;
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset_default'])) {
        $settings = $defaults;
        $successMessage = 'Default settings restored successfully.';
    } else {
        $settings = [
            'auto_sync_enabled' => isset($_POST['auto_sync_enabled']) && $_POST['auto_sync_enabled'] === '1',
            'sync_frequency' => in_array($_POST['sync_frequency'] ?? '', ['realtime', '5sec', '10sec', 'manual'], true)
                ? (string) $_POST['sync_frequency']
                : 'realtime',
            'admin_sound' => in_array($_POST['admin_sound'] ?? '', ['chime', 'pulse', 'ring', 'silent'], true)
                ? (string) $_POST['admin_sound']
                : 'chime',
            'customer_sound' => in_array($_POST['customer_sound'] ?? '', ['soft-bell', 'ping', 'tone', 'silent'], true)
                ? (string) $_POST['customer_sound']
                : 'soft-bell',
            'notification_enabled' => isset($_POST['notification_enabled']) && $_POST['notification_enabled'] === '1',
        ];

        $successMessage = 'Auto Sync settings saved successfully.';
    }
}

function selected(string $actual, string $expected): string
{
    return $actual === $expected ? 'selected' : '';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Sync Settings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #5f3bff;
            --bg-2: #2678ff;
            --panel-bg: #ffffff;
            --text: #1e2436;
            --muted: #6f7690;
            --border: #e5e8f3;
            --success: #1f9d62;
            --danger: #dc3b50;
            --shadow: 0 24px 44px rgba(25, 39, 75, 0.15);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            min-height: 100vh;
            background: linear-gradient(130deg, var(--bg-1), var(--bg-2));
        }

        .topbar {
            width: min(1140px, 94vw);
            margin: 24px auto 18px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .topbar h1 {
            margin: 0;
            font-size: clamp(1.4rem, 2.5vw, 1.85rem);
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .badge {
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.38);
            color: #fff;
            border-radius: 999px;
            padding: 8px 14px;
            font-weight: 600;
            font-size: 0.87rem;
            backdrop-filter: blur(4px);
        }

        .shell {
            width: min(900px, 94vw);
            margin: 0 auto 36px;
        }

        .card {
            background: var(--panel-bg);
            border-radius: 18px;
            box-shadow: var(--shadow);
            padding: clamp(18px, 4vw, 30px);
        }

        .row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 14px;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .row:last-of-type {
            border-bottom: none;
        }

        .label {
            display: grid;
            gap: 6px;
        }

        .label strong {
            font-size: 1rem;
        }

        .label span {
            color: var(--muted);
            font-size: 0.9rem;
        }

        select {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            min-width: 220px;
            font-size: 0.95rem;
            color: var(--text);
            background: #f9fbff;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        select:focus {
            border-color: #6a57ff;
            box-shadow: 0 0 0 3px rgba(106, 87, 255, .16);
        }

        .switch {
            position: relative;
            display: inline-flex;
            width: 56px;
            height: 32px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            inset: 0;
            cursor: pointer;
            border-radius: 999px;
            background: #d4d9eb;
            transition: all .25s ease;
        }

        .slider::before {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            left: 4px;
            top: 4px;
            border-radius: 50%;
            background: white;
            box-shadow: 0 3px 8px rgba(0, 0, 0, .14);
            transition: all .25s ease;
        }

        .switch input:checked + .slider {
            background: linear-gradient(130deg, #6542ff, #2294ff);
        }

        .switch input:checked + .slider::before {
            transform: translateX(24px);
        }

        .actions {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            border: 0;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn-primary {
            background: linear-gradient(130deg, #6542ff, #2394ff);
            color: #fff;
            box-shadow: 0 8px 20px rgba(71, 85, 255, .34);
        }

        .btn-secondary {
            background: #eff2ff;
            color: #2c3470;
        }

        .btn-outline {
            border: 1px solid #cfd7f5;
            color: #35407f;
            background: #fff;
        }

        .alert {
            display: none;
            margin-bottom: 14px;
            padding: 12px 14px;
            border-radius: 10px;
            font-weight: 500;
        }

        .alert-success {
            display: block;
            background: #e9f9f1;
            color: #157548;
            border: 1px solid #c8edd9;
        }

        @media (max-width: 720px) {
            .row {
                grid-template-columns: 1fr;
            }

            select {
                width: 100%;
                min-width: 0;
            }

            .badge {
                font-size: .8rem;
                padding: 7px 11px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<header class="topbar">
    <h1>⚡ Auto Sync Settings</h1>
    <div class="badge" id="syncStatusBadge">Auto Sync Active</div>
</header>

<main class="shell">
    <section class="card">
        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success" id="saveSuccessMessage"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php else: ?>
            <div class="alert" id="saveSuccessMessage"></div>
        <?php endif; ?>

        <form method="POST" id="autoSyncForm">
            <div class="row">
                <label class="label" for="auto_sync_enabled">
                    <strong>⚡ Enable Auto Sync</strong>
                    <span>Turn automatic syncing on or off.</span>
                </label>
                <label class="switch">
                    <input type="checkbox" id="auto_sync_enabled" name="auto_sync_enabled" value="1" <?= $settings['auto_sync_enabled'] ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </div>

            <div class="row">
                <label class="label" for="sync_frequency">
                    <strong>⏱ Sync Frequency</strong>
                    <span>Select how often sync should run.</span>
                </label>
                <select id="sync_frequency" name="sync_frequency">
                    <option value="realtime" <?= selected($settings['sync_frequency'], 'realtime'); ?>>Real-time</option>
                    <option value="5sec" <?= selected($settings['sync_frequency'], '5sec'); ?>>5 sec</option>
                    <option value="10sec" <?= selected($settings['sync_frequency'], '10sec'); ?>>10 sec</option>
                    <option value="manual" <?= selected($settings['sync_frequency'], 'manual'); ?>>Manual</option>
                </select>
            </div>

            <div class="row">
                <label class="label" for="admin_sound">
                    <strong>🔊 Admin Sound Profile</strong>
                    <span>Choose alert sound for admin events.</span>
                </label>
                <select id="admin_sound" name="admin_sound">
                    <option value="chime" <?= selected($settings['admin_sound'], 'chime'); ?>>Chime</option>
                    <option value="pulse" <?= selected($settings['admin_sound'], 'pulse'); ?>>Pulse</option>
                    <option value="ring" <?= selected($settings['admin_sound'], 'ring'); ?>>Ring</option>
                    <option value="silent" <?= selected($settings['admin_sound'], 'silent'); ?>>Silent</option>
                </select>
            </div>

            <div class="row">
                <label class="label" for="customer_sound">
                    <strong>🔊 Customer Sound Profile</strong>
                    <span>Choose alert sound for customer actions.</span>
                </label>
                <select id="customer_sound" name="customer_sound">
                    <option value="soft-bell" <?= selected($settings['customer_sound'], 'soft-bell'); ?>>Soft Bell</option>
                    <option value="ping" <?= selected($settings['customer_sound'], 'ping'); ?>>Ping</option>
                    <option value="tone" <?= selected($settings['customer_sound'], 'tone'); ?>>Tone</option>
                    <option value="silent" <?= selected($settings['customer_sound'], 'silent'); ?>>Silent</option>
                </select>
            </div>

            <div class="row">
                <label class="label" for="notification_enabled">
                    <strong>🔔 Notifications</strong>
                    <span>Enable or disable sync-related notifications.</span>
                </label>
                <label class="switch">
                    <input type="checkbox" id="notification_enabled" name="notification_enabled" value="1" <?= $settings['notification_enabled'] ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary" name="save_settings" value="1">Save Settings</button>
                <button type="submit" class="btn btn-secondary" name="reset_default" value="1">Reset Default</button>
                <a class="btn btn-outline" href="dashboard.php">Back to Dashboard</a>
            </div>
        </form>
    </section>
</main>

<script>
    (function () {
        const autoSyncToggle = document.getElementById('auto_sync_enabled');
        const badge = document.getElementById('syncStatusBadge');
        const successMessage = document.getElementById('saveSuccessMessage');

        function updateBadge() {
            if (autoSyncToggle.checked) {
                badge.textContent = 'Auto Sync Active';
                badge.style.background = 'rgba(255, 255, 255, 0.2)';
            } else {
                badge.textContent = 'Auto Sync Disabled';
                badge.style.background = 'rgba(255, 129, 152, 0.26)';
            }
        }

        updateBadge();
        autoSyncToggle.addEventListener('change', updateBadge);

        document.getElementById('autoSyncForm').addEventListener('submit', function () {
            if (!successMessage.classList.contains('alert-success')) {
                successMessage.className = 'alert alert-success';
                successMessage.textContent = 'Auto Sync settings saved successfully.';
            }
        });
    })();
</script>
</body>
</html>

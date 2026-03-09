<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$db = db_connect($config['db']);
if (!$db instanceof PDO) { http_response_code(500); echo '<p>Database connection failed.</p>'; exit; }
$logs = [];
try { $q = $db->query('SELECT name, msr_no, phone, place, attended_at FROM attendance_logs ORDER BY id DESC LIMIT 100'); $logs = $q ? ($q->fetchAll() ?: []) : []; } catch (Throwable) { $logs = []; }
render_header('Scanner Dashboard');
?>
<div class="inner-card" style="color:#0f172a;background:#fff;border-radius:12px;padding:14px;box-shadow:0 8px 20px rgba(0,0,0,.08)">
    <h3 style="margin-top:0">Live Attendance Logs</h3>
    <p class="small" style="color:#334155"><a href="scanner.php">Open Camera Scanner</a> | <a href="attendance_qr.php">Open QR Attendance</a></p>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:8px">Name</th>
                <th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:8px">MSR No</th>
                <th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:8px">Phone</th>
                <th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:8px">Place</th>
                <th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:8px">Time</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #f1f5f9"><?= e((string) ($log['name'] ?? '-')) ?></td>
                <td style="padding:8px;border-bottom:1px solid #f1f5f9"><?= e((string) ($log['msr_no'] ?? '-')) ?></td>
                <td style="padding:8px;border-bottom:1px solid #f1f5f9"><?= e((string) ($log['phone'] ?? '-')) ?></td>
                <td style="padding:8px;border-bottom:1px solid #f1f5f9"><?= e((string) ($log['place'] ?? '-')) ?></td>
                <td style="padding:8px;border-bottom:1px solid #f1f5f9"><?= e((string) ($log['attended_at'] ?? '-')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php render_footer(); ?>

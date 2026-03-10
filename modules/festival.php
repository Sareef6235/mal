<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

function fest_db(): PDO {
    global $config;
    $db = connect_db($config['db']);
    ensure_core_tables($db);
    return $db;
}

function fest_csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    return (string)$_SESSION['csrf_token'];
}

function fest_verify_csrf(): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $token = (string)($_POST['csrf_token'] ?? '');
    return $token !== '' && hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token);
}

function fest_stats(PDO $db): array {
    $out = [];
    foreach ([
        'festivals' => 'SELECT COUNT(*) FROM festivals',
        'events' => 'SELECT COUNT(*) FROM festival_events',
        'participants' => 'SELECT COUNT(*) FROM festival_participants',
        'completed_events' => "SELECT COUNT(*) FROM festival_events WHERE event_date IS NOT NULL AND event_date <= DATE('now')",
    ] as $k => $q) {
        try { $out[$k] = (int)$db->query($q)->fetchColumn(); } catch (Throwable) { $out[$k] = 0; }
    }
    $out['top_performer'] = 'N/A';
    try {
        $row = $db->query('SELECT p.name, COALESCE(SUM(s.score),0) AS total FROM festival_participants p LEFT JOIN festival_scores s ON s.participant_id=p.id GROUP BY p.id,p.name ORDER BY total DESC LIMIT 1')->fetch();
        if ($row && !empty($row['name'])) $out['top_performer'] = (string)$row['name'];
    } catch (Throwable) {}

    $out['top_house'] = 'N/A';
    try {
        $row = $db->query('SELECT h.house_name, COALESCE(SUM(hp.points),0) AS pts FROM houses h LEFT JOIN house_points hp ON hp.house_id=h.id GROUP BY h.id,h.house_name ORDER BY pts DESC LIMIT 1')->fetch();
        if ($row && !empty($row['house_name'])) $out['top_house'] = (string)$row['house_name'];
    } catch (Throwable) {}
    return $out;
}

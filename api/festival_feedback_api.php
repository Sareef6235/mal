<?php
declare(strict_types=1);

require_once __DIR__ . '/../modules/festival.php';
header('Content-Type: application/json; charset=utf-8');

$db = fest_db();
$action = (string)($_POST['action'] ?? $_GET['action'] ?? 'list');

function out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function react_key(): string {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? 'ua');
    return hash('sha256', $ip . '|' . $ua);
}

if ($action === 'submit') {
    if (!fest_verify_csrf()) out(['ok' => false, 'message' => 'Invalid CSRF token'], 422);

    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $role = trim((string)($_POST['role'] ?? 'Visitor'));
    $festivalId = (int)($_POST['festival_id'] ?? 0);
    $eventId = (int)($_POST['event_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim((string)($_POST['comment'] ?? ''));
    $isAnonymous = (int)($_POST['is_anonymous'] ?? 0) === 1 ? 1 : 0;
    $captchaAnswer = trim((string)($_POST['captcha_answer'] ?? ''));
    $hp = trim((string)($_POST['website'] ?? '')); // honeypot

    if ($hp !== '') out(['ok' => false, 'message' => 'Spam blocked'], 422);
    if ($festivalId <= 0 || $eventId <= 0) out(['ok' => false, 'message' => 'Festival/Event required'], 422);
    if ($comment === '' || mb_strlen($comment) < 8 || mb_strlen($comment) > 600) out(['ok' => false, 'message' => 'Comment must be 8-600 chars'], 422);
    if ($rating < 1 || $rating > 5) out(['ok' => false, 'message' => 'Rating must be 1-5'], 422);
    if ($name === '' && $isAnonymous === 0) out(['ok' => false, 'message' => 'Name required'], 422);

    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $expected = (string)($_SESSION['feedback_captcha'] ?? '');
    if ($expected === '' || !hash_equals($expected, $captchaAnswer)) out(['ok' => false, 'message' => 'Invalid CAPTCHA answer'], 422);

    $dup = $db->prepare('SELECT id FROM festival_comments WHERE event_id=:event_id AND comment=:comment AND created_at >= :since LIMIT 1');
    $since = date('Y-m-d H:i:s', time() - 600);
    $dup->execute(['event_id' => $eventId, 'comment' => $comment, 'since' => $since]);
    if ($dup->fetch()) out(['ok' => false, 'message' => 'Duplicate comment detected. Please wait.'], 422);

    $st = $db->prepare('INSERT INTO festival_comments(name,email,role,festival_id,event_id,rating,comment,is_anonymous,status) VALUES(:name,:email,:role,:festival_id,:event_id,:rating,:comment,:is_anonymous,:status)');
    $st->execute([
        'name' => $name === '' ? 'Anonymous User' : $name,
        'email' => $email !== '' ? $email : null,
        'role' => in_array($role, ['Student','Visitor','Teacher','Parent'], true) ? $role : 'Visitor',
        'festival_id' => $festivalId,
        'event_id' => $eventId,
        'rating' => $rating,
        'comment' => $comment,
        'is_anonymous' => $isAnonymous,
        'status' => 'pending',
    ]);

    out(['ok' => true, 'message' => 'Feedback submitted. Awaiting admin approval.']);
}

if ($action === 'react') {
    if (!fest_verify_csrf()) out(['ok' => false, 'message' => 'Invalid CSRF token'], 422);
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $type = (string)($_POST['reaction_type'] ?? 'like');
    if ($commentId <= 0 || !in_array($type, ['like','helpful'], true)) out(['ok' => false, 'message' => 'Invalid reaction'], 422);

    $driver = db_driver($db);
    if ($driver === 'sqlite') {
        $st = $db->prepare('INSERT OR IGNORE INTO festival_comment_reactions(comment_id,reaction_type,react_key) VALUES(:cid,:type,:rk)');
    } else {
        $st = $db->prepare('INSERT INTO festival_comment_reactions(comment_id,reaction_type,react_key) VALUES(:cid,:type,:rk) ON DUPLICATE KEY UPDATE reaction_type=VALUES(reaction_type)');
    }
    $st->execute(['cid' => $commentId, 'type' => $type, 'rk' => react_key()]);
    out(['ok' => true]);
}

if ($action === 'moderate') {
    if (!fest_verify_csrf()) out(['ok' => false, 'message' => 'Invalid CSRF token'], 422);
    $id = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? 'pending');
    if ($id <= 0 || !in_array($status, ['approved','rejected','hidden','pending'], true)) out(['ok' => false, 'message' => 'Invalid request'], 422);
    $st = $db->prepare('UPDATE festival_comments SET status=:status WHERE id=:id');
    $st->execute(['status' => $status, 'id' => $id]);
    out(['ok' => true, 'message' => 'Comment updated']);
}

if ($action === 'reply') {
    if (!fest_verify_csrf()) out(['ok' => false, 'message' => 'Invalid CSRF token'], 422);
    $id = (int)($_POST['id'] ?? 0);
    $reply = trim((string)($_POST['admin_reply'] ?? ''));
    if ($id <= 0 || mb_strlen($reply) > 400) out(['ok' => false, 'message' => 'Invalid reply'], 422);
    $st = $db->prepare('UPDATE festival_comments SET admin_reply=:reply WHERE id=:id');
    $st->execute(['reply' => $reply, 'id' => $id]);
    out(['ok' => true, 'message' => 'Reply saved']);
}

if ($action === 'stats') {
    $festivalId = (int)($_GET['festival_id'] ?? 0);
    $eventId = (int)($_GET['event_id'] ?? 0);
    $where = ' WHERE status = "approved" ';
    $params = [];
    if ($festivalId > 0) { $where .= ' AND festival_id = :festival_id '; $params['festival_id'] = $festivalId; }
    if ($eventId > 0) { $where .= ' AND event_id = :event_id '; $params['event_id'] = $eventId; }

    $avgSt = $db->prepare('SELECT COUNT(*) AS total, COALESCE(AVG(rating),0) AS avg_rating FROM festival_comments ' . $where);
    $avgSt->execute($params);
    $summary = $avgSt->fetch() ?: ['total' => 0, 'avg_rating' => 0];

    $dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $dSt = $db->prepare('SELECT rating, COUNT(*) AS cnt FROM festival_comments ' . $where . ' GROUP BY rating');
    $dSt->execute($params);
    foreach (($dSt->fetchAll() ?: []) as $r) {
        $k = (int)$r['rating'];
        if (isset($dist[$k])) $dist[$k] = (int)$r['cnt'];
    }
    out(['ok' => true, 'summary' => $summary, 'distribution' => $dist]);
}

if ($action === 'admin_list') {
    $rows = $db->query('SELECT c.*, f.festival_name, e.event_name FROM festival_comments c JOIN festivals f ON f.id=c.festival_id LEFT JOIN festival_events e ON e.id=c.event_id ORDER BY c.id DESC LIMIT 300')->fetchAll() ?: [];
    out(['ok' => true, 'rows' => $rows]);
}

$festivalId = (int)($_GET['festival_id'] ?? 0);
$eventId = (int)($_GET['event_id'] ?? 0);
$rating = (int)($_GET['rating'] ?? 0);
$where = ' WHERE c.status = "approved" ';
$params = [];
if ($festivalId > 0) { $where .= ' AND c.festival_id = :festival_id '; $params['festival_id'] = $festivalId; }
if ($eventId > 0) { $where .= ' AND c.event_id = :event_id '; $params['event_id'] = $eventId; }
if ($rating >= 1 && $rating <= 5) { $where .= ' AND c.rating = :rating '; $params['rating'] = $rating; }

$sql = 'SELECT c.*, f.festival_name, e.event_name,
        (SELECT COUNT(*) FROM festival_comment_reactions r WHERE r.comment_id = c.id AND r.reaction_type = "like") AS like_count,
        (SELECT COUNT(*) FROM festival_comment_reactions r WHERE r.comment_id = c.id AND r.reaction_type = "helpful") AS helpful_count
        FROM festival_comments c
        JOIN festivals f ON f.id = c.festival_id
        LEFT JOIN festival_events e ON e.id = c.event_id
        ' . $where . ' ORDER BY c.id DESC LIMIT 200';

$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll() ?: [];
out(['ok' => true, 'rows' => $rows]);

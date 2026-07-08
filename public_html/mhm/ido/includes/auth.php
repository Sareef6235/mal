<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
function db(): PDO { static $pdo; if (!$pdo) { $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); } return $pdo; }
function secure_session(): void { if (session_status()===PHP_SESSION_ACTIVE) return; session_set_cookie_params(['lifetime'=>0,'path'=>APP_BASE,'secure'=>!empty($_SERVER['HTTPS']),'httponly'=>true,'samesite'=>'Lax']); session_start(); $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_token(): string { secure_session(); return $_SESSION['csrf']; }
function verify_csrf(): void { secure_session(); if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) { http_response_code(419); exit('Invalid CSRF token'); } }
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function current_user(): ?array { secure_session(); return $_SESSION['user'] ?? null; }
function require_login(): array { $u=current_user(); if (!$u) { header('Location: '.APP_BASE.'/auth/login.php'); exit; } return $u; }
function user_can(string $permission): bool { $u=current_user(); return in_array($permission, $u['permissions'] ?? [], true) || in_array('admin', $u['permissions'] ?? [], true); }
function require_permission(string $permission): void { if (!user_can($permission)) { http_response_code(403); include __DIR__.'/../auth/403.php'; exit; } }
function audit(string $action, ?string $entity=null, ?int $entityId=null, array $meta=[]): void { secure_session(); $stmt=db()->prepare('INSERT INTO activity_logs(user_id,action,entity,entity_id,ip_address,user_agent,metadata) VALUES (?,?,?,?,?,?,?)'); $stmt->execute([$_SESSION['user']['id']??null,$action,$entity,$entityId,$_SERVER['REMOTE_ADDR']??'',substr($_SERVER['HTTP_USER_AGENT']??'',0,255),$meta?json_encode($meta):null]); }
function load_permissions(int $roleId): array { $s=db()->prepare('SELECT p.name FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id WHERE rp.role_id=?'); $s->execute([$roleId]); return array_column($s->fetchAll(),'name'); }
function redirect(string $path): never { header('Location: '.APP_BASE.$path); exit; }

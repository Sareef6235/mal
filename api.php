<?php
declare(strict_types=1);
require_once __DIR__ . '/vendor/autoload.php';
$functions=__DIR__.'/../includes/functions.php'; if (is_file($functions)) require_once $functions;
if (session_status()!==PHP_SESSION_ACTIVE) session_start(); if (function_exists('require_login')) require_login();
header('Content-Type: application/json; charset=utf-8');
function respond(array $body,int $status=200): never { http_response_code($status); echo json_encode($body,JSON_UNESCAPED_SLASHES); exit; }
try {
  if (!function_exists('db')) throw new RuntimeException('Database helper is unavailable.'); $pdo=db();
  $token=$_SERVER['HTTP_X_CSRF_TOKEN']??$_POST['csrf_token']??''; if (!hash_equals((string)($_SESSION['document_csrf_token']??''),(string)$token)) respond(['ok'=>false,'message'=>'Invalid security token.'],419);
  $action=$_POST['action']??$_GET['action']??''; $actor=(int)(($_SESSION['user']['id']??$_SESSION['user_id']??0)); $role=strtolower((string)(($_SESSION['user']['role']??$_SESSION['role']??''))); $admin=in_array($role,['admin','administrator','super_admin','super administrator'],true); if (in_array($action,['upload','map','generate'],true) && !$admin) respond(['ok'=>false,'message'=>'Administrator permission required.'],403); $service=new DocumentAutomation\DocumentAutomationService($pdo,__DIR__.'/storage/templates');
  if ($action==='upload') respond(['ok'=>true,'template_id'=>$service->upload($_FILES['template']??[],$_POST,$actor)]);
  if ($action==='preview') respond(['ok'=>true,'rows'=>$service->previewRows((int)($_GET['template_id']??0),$_GET)]);
  if ($action==='map') { $service->saveMapping((int)$_POST['template_id'],(string)$_POST['placeholder'],$_POST['source_table']?:null,$_POST['source_column']?:null); respond(['ok'=>true]); }
  if ($action==='generate') respond(['ok'=>true,'document'=>$service->generate((int)$_POST['template_id'],$_POST['record_ids']??[],$actor)]);
  respond(['ok'=>false,'message'=>'Unknown action.'],404);
} catch (Throwable $e) { respond(['ok'=>false,'message'=>$e->getMessage()],422); }

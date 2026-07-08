<?php
declare(strict_types=1);
function password_errors(string $p): array { $e=[]; if(strlen($p)<6)$e[]='Use at least 6 characters.'; if(!preg_match('/[A-Z]/',$p))$e[]='Add an uppercase letter.'; if(!preg_match('/[a-z]/',$p))$e[]='Add a lowercase letter.'; if(!preg_match('/\d/',$p))$e[]='Add a number.'; if(!preg_match('/[^A-Za-z0-9]/',$p))$e[]='Add a special character.'; return $e; }
function valid_username(string $u): bool { return (bool)preg_match('/^[A-Za-z0-9_.-]{3,60}$/',$u); }
function require_fields(array $data, array $fields): array { $missing=[]; foreach($fields as $f){ if(trim((string)($data[$f]??''))==='') $missing[]=$f; } return $missing; }

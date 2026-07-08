<?php
declare(strict_types=1);
require_once __DIR__.'/auth.php'; require_once __DIR__.'/mail.php';
function create_otp(?int $userId, string $email, string $purpose): string { $otp=(string)random_int(100000,999999); $s=db()->prepare('INSERT INTO otps(user_id,email,purpose,otp_hash,expires_at) VALUES (?,?,?,?,DATE_ADD(NOW(), INTERVAL 10 MINUTE))'); $s->execute([$userId,$email,$purpose,password_hash($otp,PASSWORD_DEFAULT)]); send_otp_email($email,$otp,$purpose); return $otp; }
function latest_otp(string $email,string $purpose): ?array { $s=db()->prepare('SELECT * FROM otps WHERE email=? AND purpose=? ORDER BY id DESC LIMIT 1'); $s->execute([$email,$purpose]); return $s->fetch() ?: null; }
function verify_otp_code(string $email,string $purpose,string $code): bool { $o=latest_otp($email,$purpose); if(!$o || $o['verified_at'] || strtotime($o['expires_at'])<time() || $o['attempts']>=5) return false; db()->prepare('UPDATE otps SET attempts=attempts+1 WHERE id=?')->execute([$o['id']]); if(!password_verify($code,$o['otp_hash'])) return false; db()->prepare('UPDATE otps SET verified_at=NOW() WHERE id=?')->execute([$o['id']]); return true; }
function can_resend(string $email,string $purpose): bool { $o=latest_otp($email,$purpose); return !$o || (int)$o['resend_count']<3; }

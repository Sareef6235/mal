INSERT INTO users (name, email, password_hash, plan, created_at, updated_at)
VALUES (
    'Demo User',
    '123v213@gmail.com',
    '$2y$12$iUon89t7jywVwY1pGFp63uZHONJ1GnsekE/rw/s.PxKos33WGTe.O',
    'starter',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password_hash = VALUES(password_hash),
    plan = VALUES(plan),
    updated_at = NOW();

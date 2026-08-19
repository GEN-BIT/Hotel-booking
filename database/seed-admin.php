<?php
// Run from CLI only: /opt/lampp/bin/php database/seed-admin.php
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

require_once __DIR__ . '/../config/db.php';

$email = 'admin@hotel-booking.local';
$password = 'Admin@12345';
$name = 'Hotel Admin';

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);

if ($stmt->fetch()) {
    echo "Admin account already exists ($email). No changes made.\n";
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$pdo->beginTransaction();
$stmt = $pdo->prepare(
    'INSERT INTO users (role_id, full_name, email, password_hash, is_verified)
     VALUES ((SELECT id FROM roles WHERE name = "admin"), ?, ?, ?, 1)'
);
$stmt->execute([$name, $email, $hash]);
$userId = $pdo->lastInsertId();

$stmt = $pdo->prepare('INSERT INTO staff (user_id, position, is_active) VALUES (?, "System Administrator", 1)');
$stmt->execute([$userId]);
$pdo->commit();

echo "Admin account created.\n";
echo "Email:    $email\n";
echo "Password: $password\n";
echo "Log in, then change the password immediately from Change Password in the sidebar.\n";

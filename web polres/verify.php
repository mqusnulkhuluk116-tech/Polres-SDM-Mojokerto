<?php
require __DIR__ . '/config.php';

$username = 'admin';
$plain    = 'admin123';

$stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username=?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: text/plain');

if (!$user) { die("User tidak ditemukan\n"); }

echo "Hash: {$user['password_hash']}\n";
echo "Algo cocok? " . (password_verify($plain, $user['password_hash']) ? "YA" : "TIDAK") . "\n";

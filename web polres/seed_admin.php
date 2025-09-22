<?php
// php/seed_admin.php
require __DIR__ . '/db.php';

$pdo->exec("
CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(128) NULL,
  role VARCHAR(32) DEFAULT 'admin',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Ganti kredensial default di sini
$seedUsername = 'admin';
$seedPasswordPlain = 'admin123';
$seedFullName = 'Administrator';

$stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
$stmt->execute([$seedUsername]);
if (!$stmt->fetch()) {
  $hash = password_hash($seedPasswordPlain, PASSWORD_DEFAULT);
  $ins = $pdo->prepare("INSERT INTO admin_users (username, password_hash, full_name) VALUES (?, ?, ?)");
  $ins->execute([$seedUsername, $hash, $seedFullName]);
  echo "Seed admin created: $seedUsername / $seedPasswordPlain\n";
} else {
  echo "Seed admin already exists.\n";
}

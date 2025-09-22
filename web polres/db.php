<?php
// php/db.php
// UBAH sesuai kredensial MySQL kamu:
$host = '127.0.0.1';
$db   = 'sdm_portal';   // <-- samakan dengan nama database kamu
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
$pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
  http_response_code(500);
  echo "DB connection failed";
  exit;
}

if (session_status() === PHP_SESSION_NONE) {
  // cookie httpOnly, secure disarankan jika pakai https
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
  ]);
  session_start();
}

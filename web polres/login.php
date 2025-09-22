<?php
// login.php (NO HASH) — DEMO SAJA (tidak aman untuk produksi)
session_start();
header('Content-Type: application/json');
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success'=>false,'message'=>'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$username = trim($input['username'] ?? ($_POST['username'] ?? ''));
$password = (string)($input['password'] ?? ($_POST['password'] ?? ''));

if ($username === '' || $password === '') {
  echo json_encode(['success'=>false,'message'=>'Username dan password wajib diisi.']);
  exit;
}

try {
  // Cocokkan langsung username + password plain
  $stmt = $pdo->prepare("
    SELECT id, username, full_name, role
    FROM admin_users
    WHERE username = ? AND password = ?
    LIMIT 1
  ");
  $stmt->execute([$username, $password]);
  $user = $stmt->fetch();

  if (!$user) {
    echo json_encode(['success'=>false,'message'=>'Username atau password salah.']);
    exit;
  }

  $_SESSION['admin_id']       = $user['id'];
  $_SESSION['admin_username'] = $user['username'];
  $_SESSION['admin_fullname'] = $user['full_name'];
  $_SESSION['admin_role']     = $user['role'];

  echo json_encode(['success'=>true,'message'=>'Login berhasil.','user'=>$user]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Terjadi kesalahan server.','detail'=>$e->getMessage()]);
}

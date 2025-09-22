<?php
// php/check_session.php
header('Content-Type: application/json');
require __DIR__ . '/db.php';

if (!empty($_SESSION['admin_id'])) {
  echo json_encode([
    'logged_in' => true,
    'username'  => $_SESSION['admin_username'] ?? null,
    'fullname'  => $_SESSION['admin_fullname'] ?? null,
    'role'      => $_SESSION['admin_role'] ?? null
  ]);
} else {
  echo json_encode(['logged_in' => false]);
}

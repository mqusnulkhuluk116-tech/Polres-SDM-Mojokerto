<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require __DIR__ . '/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'ID tidak valid']); exit; }

try {
  $s = $pdo->prepare("SELECT media_path FROM berita WHERE id=?");
  $s->execute([$id]);
  if ($row = $s->fetch()) {
    if ($row['media_path'] && file_exists(__DIR__.'/uploads/'.$row['media_path'])) @unlink(__DIR__.'/uploads/'.$row['media_path']);
  }
  $stmt = $pdo->prepare("DELETE FROM berita WHERE id=?");
  $stmt->execute([$id]);
  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'DB error','detail'=>$e->getMessage()]);
}

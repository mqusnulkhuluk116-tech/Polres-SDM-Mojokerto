<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require __DIR__ . '/config.php';

$raw = file_get_contents('php://input');
$req = json_decode($raw, true) ?: [];
$id  = isset($req['id']) ? (int)$req['id'] : 0;

if(!$id){ echo json_encode(['success'=>false,'message'=>'ID tidak valid.']); exit; }

try{
  // (opsional) hapus file fotonya
  $stmt = $pdo->prepare("SELECT foto_path FROM struktur WHERE id=?");
  $stmt->execute([$id]);
  if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['foto_path'])) {
      $f = __DIR__ . '/' . ltrim($row['foto_path'],'/');
      if (is_file($f)) @unlink($f);
    }
  }

  $stmt = $pdo->prepare("DELETE FROM struktur WHERE id=?");
  $stmt->execute([$id]);

  echo json_encode(['success'=>true]);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Gagal menghapus.']);
}

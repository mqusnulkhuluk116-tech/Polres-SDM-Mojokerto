<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// wajib login
if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

try {
  require __DIR__ . '/config.php';
  if (!isset($pdo)) throw new Exception('PDO tidak tersedia');

  // body JSON
  $raw  = file_get_contents('php://input');
  $body = json_decode($raw, true) ?: [];

  $type = isset($body['type']) ? strtolower(trim($body['type'])) : '';
  $id   = isset($body['id']) ? (int)$body['id'] : 0;

  if (!$id || !in_array($type, ['bagian','sdm'], true)) {
    echo json_encode(['success'=>false,'message'=>"Param 'type' (bagian/sdm) dan 'id' wajib."]);
    exit;
  }

  // mapping tabel
  $table = ($type === 'bagian') ? 'kepala_bagian' : 'kepala_sdm';

  // ambil data untuk tahu file fotonya (kolomnya adalah foto_url, bukan foto_path)
  $stmt = $pdo->prepare("SELECT id, foto_url FROM {$table} WHERE id = :id");
  $stmt->execute([':id'=>$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    echo json_encode(['success'=>false,'message'=>'Data tidak ditemukan']);
    exit;
  }

  // hapus file jika ada
  if (!empty($row['foto_url'])) {
    // normalisasi ke path lokal
    $p = $row['foto_url'];

    // kalau absolute URL (http/https) kita tidak hapus file
    if (!preg_match('~^https?://~i', $p) && !preg_match('~^data:~i', $p)) {
      // buang leading ./ atau /, dan pastikan menuju folder uploads/
      $p = preg_replace('~^[./]+~', '', $p);
      $p = preg_replace('~^(?:uploads/)+~i', 'uploads/', $p);
      if (!preg_match('~^uploads/~i', $p)) $p = 'uploads/' . $p;

      $full = __DIR__ . '/' . $p;
      if (is_file($full)) @unlink($full);
    }
  }

  // hapus row
  $del = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
  $del->execute([':id'=>$id]);

  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success'=>false,
    'message'=>'Gagal hapus: '.$e->getMessage()
  ]);
}

<?php
// kepala_list.php (diagnostik)
header('Content-Type: application/json; charset=utf-8');

// TAMPILKAN ERROR AGAR KELIHATAN (matikan saat produksi)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$diag = [];
try {
  // pastikan file config-mu benar2 membuat $pdo (PDO MySQL)
  require __DIR__ . '/config.php';
  if (!isset($pdo)) {
    throw new Exception('PDO ($pdo) tidak tersedia dari config.php');
  }

  $type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
  $published = isset($_GET['published']) ? (int)$_GET['published'] : null;

  // mapping tabel sesuai type
  if ($type === 'bagian') {
    $table = 'kepala_bagian';
  } elseif ($type === 'sdm') {
    $table = 'kepala_sdm';
  } else {
    throw new Exception("Parameter 'type' wajib 'bagian' atau 'sdm'. Diterima: '{$type}'");
  }
  $diag['table'] = $table;

  // bangun SQL
  $sql = "SELECT id, nama, jabatan, deskripsi, foto_path, published, urutan, created_at FROM {$table}";
  $params = [];
  if ($published !== null) {
    $sql .= " WHERE published = :pub";
    $params[':pub'] = $published ? 1 : 0;
  }
  $sql .= " ORDER BY urutan ASC, id DESC";
  $diag['sql'] = $sql;
  $diag['params'] = $params;

  // eksekusi
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['success'=>true, 'data'=>$rows, 'diag'=>$diag]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'data'    => [],
    'message' => 'DB / Server error',
    'detail'  => $e->getMessage(),
    'diag'    => $diag
  ]);
}

<?php
// struktur_list.php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/config.php';

$publishedOnly = isset($_GET['published_only']) && $_GET['published_only'] !== '0';

try {
  $where = $publishedOnly ? "WHERE published = 1" : "";
  $sql = "
    SELECT
      id,
      nama,
      jabatan,
      urutan,
      -- normalisasi: kalau foto_path belum diawali '/' atau 'http', tambahkan '/uploads/'
      CASE
        WHEN foto_path IS NULL OR foto_path = '' THEN NULL
        WHEN foto_path REGEXP '^(https?://|/|uploads/)' THEN foto_path
        ELSE CONCAT('uploads/', foto_path)
      END AS foto_url,
      CAST(published AS UNSIGNED) AS published,
      created_at, updated_at
    FROM struktur
    $where
    ORDER BY urutan ASC, updated_at DESC, id DESC
  ";
  $data = $pdo->query($sql)->fetchAll() ?: [];
  echo json_encode(['success'=>true,'data'=>$data]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Query error','detail'=>$e->getMessage()]);
}

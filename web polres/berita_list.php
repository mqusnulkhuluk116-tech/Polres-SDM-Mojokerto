<?php
header('Content-Type: application/json');
session_start();
require __DIR__ . '/config.php';

$publishedOnly = isset($_GET['published_only']) ? (int)$_GET['published_only'] : (isset($_SESSION['admin_id']) ? 0 : 1);
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 0;

$sql = "SELECT id, title, content, media_path, media_mime, published, created_at, updated_at FROM berita";
$params = [];
if ($publishedOnly) { $sql .= " WHERE published = 1"; }
$sql .= " ORDER BY created_at DESC";
if ($limit) { $sql .= " LIMIT ?"; $params[] = $limit; }

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
  $r['media_url'] = $r['media_path'] ? ('uploads/'.$r['media_path']) : null;
}
echo json_encode(['success'=>true,'data'=>$rows]);

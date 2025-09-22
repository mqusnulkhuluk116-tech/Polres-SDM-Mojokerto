<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
require __DIR__ . '/config.php';

$id        = trim($_POST['id'] ?? '');
$title     = trim($_POST['title'] ?? '');
$content   = trim($_POST['content'] ?? '');
$published = isset($_POST['published']) ? (int)$_POST['published'] : 1;

if ($title === '' || $content === '') {
  echo json_encode(['success'=>false,'message'=>'Judul dan isi wajib diisi']);
  exit;
}

$mediaPath = null;
$mediaMime = null; // <-- TAMBAHAN: siapkan variabel MIME

if (!empty($_FILES['media']['name'])) {
  $allowed = ['jpg','jpeg','png','gif','webp','mp4','webm','ogg'];
  $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
  if (!in_array($ext,$allowed)) { echo json_encode(['success'=>false,'message'=>'Tipe file tidak didukung']); exit; }
  if ($_FILES['media']['error'] !== UPLOAD_ERR_OK) { echo json_encode(['success'=>false,'message'=>'Gagal upload']); exit; }
  if (!is_dir(__DIR__.'/uploads')) { mkdir(__DIR__.'/uploads',0775,true); }

  // perbaiki pola nama file (hindari _ext ganda)
  $fname = 'media_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.'.$ext;
  if (!move_uploaded_file($_FILES['media']['tmp_name'], __DIR__.'/uploads/'.$fname)) {
    echo json_encode(['success'=>false,'message'=>'Gagal simpan file']); exit;
  }
  $mediaPath = $fname;

  // simpan MIME. Kalau tidak ada dari PHP, tentukan dari ekstensi.
  $mediaMime = $_FILES['media']['type'] ?? null;
  if (!$mediaMime) {
    $map = [
      'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp',
      'mp4'=>'video/mp4','webm'=>'video/webm','ogg'=>'video/ogg'
    ];
    $mediaMime = $map[$ext] ?? null;
  }
}

try{
  if ($id) {
    if ($mediaPath) {
      // <-- UBAH: ikut update media_mime
      $stmt = $pdo->prepare("UPDATE berita SET title=?, content=?, media_path=?, media_mime=?, published=?, updated_at=NOW() WHERE id=?");
      $stmt->execute([$title,$content,$mediaPath,$mediaMime,$published,$id]);
    } else {
      $stmt = $pdo->prepare("UPDATE berita SET title=?, content=?, published=?, updated_at=NOW() WHERE id=?");
      $stmt->execute([$title,$content,$published,$id]);
    }
  } else {
    // <-- UBAH: ikut insert media_mime
    $stmt = $pdo->prepare("INSERT INTO berita (title,content,media_path,media_mime,published,author_id) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$title,$content,$mediaPath,$mediaMime,$published,$_SESSION['admin_id']]);
  }
  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'DB error','detail'=>$e->getMessage()]);
}

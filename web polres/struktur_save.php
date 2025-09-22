<?php
// struktur_save.php
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

require __DIR__ . '/config.php'; // menyediakan $pdo + tabel struktur (lihat config.php)

// Ambil input
$id        = isset($_POST['id']) ? trim($_POST['id']) : '';
$nama      = isset($_POST['nama']) ? trim($_POST['nama']) : '';
$jabatan   = isset($_POST['jabatan']) ? trim($_POST['jabatan']) : '';
$urutan    = isset($_POST['urutan']) && $_POST['urutan'] !== '' ? (int)$_POST['urutan'] : 1;
$published = (isset($_POST['published']) && $_POST['published'] !== '0') ? 1 : 0;

if ($nama === '' || $jabatan === '') {
  echo json_encode(['success'=>false,'message'=>'Nama dan jabatan wajib diisi']);
  exit;
}

// Siapkan folder upload
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

$fotoPathBaru = null; // nilai yang akan ditulis ke kolom foto_path

// Jika ada file foto
if (!empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
  $f = $_FILES['foto'];
  if ($f['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'Upload gagal (err '.$f['error'].')']);
    exit;
  }

  // Validasi tipe
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime  = finfo_file($finfo, $f['tmp_name']);
  finfo_close($finfo);
  $allow = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
  if (!isset($allow[$mime])) {
    echo json_encode(['success'=>false,'message'=>'Format foto harus JPG/PNG/WebP/GIF']);
    exit;
  }

  // Simpan file
  $ext  = $allow[$mime];
  $base = preg_replace('~[^a-zA-Z0-9_-]+~','-', pathinfo($f['name'], PATHINFO_FILENAME));
  $name = date('Ymd_His') . '_' . substr(md5($f['name'].mt_rand()),0,6) . '_' . $base . '.' . $ext;
  $dest = $uploadDir . '/' . $name;

  if (!move_uploaded_file($f['tmp_name'], $dest)) {
    echo json_encode(['success'=>false,'message'=>'Gagal menyimpan file']);
    exit;
  }

  // Simpan path web ke DB (konsisten dengan frontend)
 $fotoPathBaru = 'uploads/' . $name; // relatif
}

try {
  if ($id) {
    // UPDATE
    if ($fotoPathBaru) {
      $sql = "UPDATE struktur
                SET nama=?, jabatan=?, urutan=?, foto_path=?, published=?, updated_at=NOW()
              WHERE id=?";
      $pdo->prepare($sql)->execute([$nama,$jabatan,$urutan,$fotoPathBaru,$published,$id]);
    } else {
      $sql = "UPDATE struktur
                SET nama=?, jabatan=?, urutan=?, published=?, updated_at=NOW()
              WHERE id=?";
      $pdo->prepare($sql)->execute([$nama,$jabatan,$urutan,$published,$id]);
    }
  } else {
    // INSERT
    $sql = "INSERT INTO struktur (nama,jabatan,urutan,foto_path,published,author_id,created_at)
            VALUES (?,?,?,?,?, ?, NOW())";
    $author = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    $pdo->prepare($sql)->execute([$nama,$jabatan,$urutan,$fotoPathBaru,$published,$author]);
    $id = $pdo->lastInsertId();
  }

  echo json_encode(['success'=>true,'id'=>$id,'foto_url'=>$fotoPathBaru]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'DB error','detail'=>$e->getMessage()]);
}

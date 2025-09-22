<?php
/**
 * kepala_save.php
 * Tambah/Update Kepala SDM & Kepala Bagian (dengan upload foto).
 * - Method: POST (multipart/form-data)
 * - Field umum : type = 'sdm' | 'bagian'
 *                id   (kosong untuk tambah, angka untuk update)
 *                nama, jabatan, deskripsi, published(0|1)
 *                foto (file, opsional)
 * - Khusus bagian: urutan (int)
 * - Simpan kolom foto ke: foto_url
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// ====== Wajib login ======
if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

try {
  require __DIR__ . '/config.php';
  if (!isset($pdo)) { throw new Exception('PDO tidak tersedia dari config.php'); }

  // ====== Ambil input ======
  $type      = isset($_POST['type']) ? strtolower(trim($_POST['type'])) : '';
  $id        = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $nama      = trim($_POST['nama'] ?? '');
  $jabatan   = trim($_POST['jabatan'] ?? '');
  $deskripsi = trim($_POST['deskripsi'] ?? '');
  $published = isset($_POST['published']) && (string)$_POST['published'] !== '0' ? 1 : 0;
  $urutan    = isset($_POST['urutan']) ? (int)$_POST['urutan'] : 1; // khusus bagian

  if (!in_array($type, ['sdm','bagian'], true)) {
    echo json_encode(['success'=>false,'message'=>"Param 'type' harus 'sdm' atau 'bagian'."]);
    exit;
  }
  if ($nama === '') {
    echo json_encode(['success'=>false,'message'=>'Nama wajib diisi.']);
    exit;
  }
  if ($jabatan === '') {
    $jabatan = ($type === 'sdm') ? 'Kepala SDM' : 'Kepala Bagian';
  }

  $table = ($type === 'bagian') ? 'kepala_bagian' : 'kepala_sdm';

  // ====== Siapkan folder upload ======
  $uploadDir = __DIR__ . '/uploads';
  if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
  }

  // ====== Ambil data lama (kalau update) untuk tahu foto lama ======
  $old = null;
  if ($id > 0) {
    $stmt = $pdo->prepare("SELECT id, foto_url FROM {$table} WHERE id = :id");
    $stmt->execute([':id'=>$id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$old) {
      echo json_encode(['success'=>false,'message'=>'Data tidak ditemukan.']);
      exit;
    }
  }

  // ====== Proses upload (jika ada file) ======
  $foto_url = $old['foto_url'] ?? ''; // default pakai lama
  if (!empty($_FILES['foto']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {

    // validasi ringan
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $mime = mime_content_type($_FILES['foto']['tmp_name']);
    if ($mime && !in_array($mime, $allowed, true)) {
      echo json_encode(['success'=>false,'message'=>'Tipe file tidak didukung.']);
      exit;
    }

    // buat nama unik
    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $ext = $ext ? ('.'.strtolower($ext)) : '';
    $newName = 'kepala_' . $type . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . $ext;
    $dest = $uploadDir . '/' . $newName;

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
      echo json_encode(['success'=>false,'message'=>'Gagal menyimpan file upload.']);
      exit;
    }

    // hapus foto lama (jika update & lokal)
    if (!empty($old['foto_url'])) {
      $p = $old['foto_url'];
      if (!preg_match('~^https?://~i', $p) && !preg_match('~^data:~i', $p)) {
        $p = preg_replace('~^[./]+~', '', $p);
        $p = preg_replace('~^(?:uploads/)+~i', 'uploads/', $p);
        if (!preg_match('~^uploads/~i', $p)) { $p = 'uploads/'.$p; }
        $full = __DIR__ . '/' . $p;
        if (is_file($full)) { @unlink($full); }
      }
    }

    // simpan URL relatif untuk dipakai di front-end
    $foto_url = 'uploads/' . $newName;
  }

  // ====== Simpan ke DB ======
  if ($id > 0) {
    // UPDATE
    if ($type === 'bagian') {
      $sql = "UPDATE {$table}
              SET nama=:nama, jabatan=:jabatan, deskripsi=:deskripsi,
                  foto_url=:foto_url, published=:published, urutan=:urutan, updated_at=NOW()
              WHERE id=:id";
      $params = [
        ':nama'=>$nama, ':jabatan'=>$jabatan, ':deskripsi'=>$deskripsi,
        ':foto_url'=>$foto_url, ':published'=>$published, ':urutan'=>$urutan, ':id'=>$id
      ];
    } else { // sdm (tak ada kolom urutan)
      $sql = "UPDATE {$table}
              SET nama=:nama, jabatan=:jabatan, deskripsi=:deskripsi,
                  foto_url=:foto_url, published=:published, updated_at=NOW()
              WHERE id=:id";
      $params = [
        ':nama'=>$nama, ':jabatan'=>$jabatan, ':deskripsi'=>$deskripsi,
        ':foto_url'=>$foto_url, ':published'=>$published, ':id'=>$id
      ];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
  } else {
    // INSERT
    if ($type === 'bagian') {
      $sql = "INSERT INTO {$table} (nama, jabatan, deskripsi, foto_url, published, urutan, updated_at)
              VALUES (:nama, :jabatan, :deskripsi, :foto_url, :published, :urutan, NOW())";
      $params = [
        ':nama'=>$nama, ':jabatan'=>$jabatan, ':deskripsi'=>$deskripsi,
        ':foto_url'=>$foto_url, ':published'=>$published, ':urutan'=>$urutan
      ];
    } else { // sdm
      $sql = "INSERT INTO {$table} (nama, jabatan, deskripsi, foto_url, published, updated_at)
              VALUES (:nama, :jabatan, :deskripsi, :foto_url, :published, NOW())";
      $params = [
        ':nama'=>$nama, ':jabatan'=>$jabatan, ':deskripsi'=>$deskripsi,
        ':foto_url'=>$foto_url, ':published'=>$published
      ];
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $id = (int)$pdo->lastInsertId();
  }

  echo json_encode([
    'success'=>true,
    'id'=>$id,
    'foto_url'=>$foto_url
  ]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'success'=>false,
    'message'=>'Gagal menyimpan: '.$e->getMessage()
  ]);
}

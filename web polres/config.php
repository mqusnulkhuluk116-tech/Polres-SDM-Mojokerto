<?php
// config.php (DEV) – auto-buat database & tabel jika belum ada + debug error
$DB_HOST = '127.0.0.1';   // atau 'localhost'
$DB_PORT = 3306;          // ubah bila MySQL-mu pakai port lain
$DB_NAME = 'sdm_portal';
$DB_USER = 'root';
$DB_PASS = '';            // isi jika root kamu ada password

$pdo_opts = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

// 1) connect ke server tanpa pilih DB (supaya bisa CREATE DATABASE)
try {
  $pdo0 = new PDO("mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4", $DB_USER, $DB_PASS, $pdo_opts);
} catch (PDOException $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Koneksi ke server MySQL gagal.\n";
  echo "Host: {$DB_HOST}:{$DB_PORT}\nUser: {$DB_USER}\n";
  echo "Error: " . $e->getMessage() . "\n";
  exit;
}

// 2) buat DB kalau belum ada
$pdo0->exec("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// 3) konek ke DB-nya
try {
  $pdo = new PDO(
    "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    $pdo_opts
  );
} catch (PDOException $e) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Koneksi ke database gagal.\n";
  echo "DSN  : mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4\n";
  echo "User : {$DB_USER}\n";
  echo "Error: " . $e->getMessage() . "\n";
  exit;
}

// 4) pastikan tabel-tabel ada (NO HASH version)
$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS admin_users (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(64) NOT NULL,
  `password` varchar(128) NOT NULL,
  full_name varchar(128) DEFAULT NULL,
  role varchar(32) DEFAULT 'admin',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS berita (
  id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  content mediumtext NOT NULL,
  media_path varchar(255) DEFAULT NULL,
  published tinyint(1) NOT NULL DEFAULT 1,
  author_id int(11) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY (published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS struktur (
  id int(11) NOT NULL AUTO_INCREMENT,
  nama varchar(128) NOT NULL,
  jabatan varchar(128) NOT NULL,
  urutan int(11) NOT NULL DEFAULT 1,
  foto_path varchar(255) DEFAULT NULL,
  published tinyint(1) NOT NULL DEFAULT 1,
  author_id int(11) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY (urutan),
  KEY (published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

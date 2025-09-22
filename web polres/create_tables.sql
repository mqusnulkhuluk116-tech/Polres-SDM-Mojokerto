-- NO HASH schema
CREATE DATABASE IF NOT EXISTS web_polres
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web_polres;

DROP TABLE IF EXISTS admin_users;
CREATE TABLE admin_users (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(64) NOT NULL,
  `password` varchar(128) NOT NULL, -- PLAIN TEXT (DEMO SAJA)
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

<?php
require __DIR__ . '/config.php';
$username='admin'; $password='admin123'; $full='Administrator'; $role='admin';
$cek=$pdo->prepare("SELECT id FROM admin_users WHERE username=?"); $cek->execute([$username]);
if($cek->fetch()){ die("User 'admin' sudah ada.\n"); }
$ins=$pdo->prepare("INSERT INTO admin_users (username,password,full_name,role) VALUES (?,?,?,?)");
$ins->execute([$username,$password,$full,$role]);
echo "OK: admin/admin123 dibuat.\n";

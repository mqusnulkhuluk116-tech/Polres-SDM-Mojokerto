<?php
require __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
echo "Terhubung ke DB web_polres!\n";
echo "MySQL: " . $pdo->query("SELECT VERSION() AS v")->fetch()['v'] . "\n";

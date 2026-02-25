<?php
header('Content-Type: text/plain; charset=utf-8');
$config = require __DIR__ . '/db-config.php';
$pdo = new PDO("pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}", $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$sql = "SELECT c.id, c.name, COUNT(a.id) as ads_count FROM category c INNER JOIN ad a ON a.category_id = c.id WHERE a.country_id = 1 AND a.is_deleted = false AND c.is_deleted = false GROUP BY c.id, c.name HAVING COUNT(a.id) >= 50 ORDER BY ads_count DESC LIMIT 10";
$result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "=== Categories in Kyrgyzstan (country_id=1) ===\n\n";
foreach ($result as $row) {
    echo "ID: {$row['id']} | {$row['name']} | {$row['ads_count']} ads\n";
}

<?php
header('Content-Type: text/plain; charset=utf-8');
$config = require __DIR__ . '/db-config.php';
$pdo = new PDO("pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}", $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Find best category with params for country 12
$sql = "
SELECT c.id, c.name, COUNT(DISTINCT a.id) as ads_count, COUNT(DISTINCT ap.param_id) as params_count
FROM category c
INNER JOIN ad a ON a.category_id = c.id
INNER JOIN ad_param ap ON ap.ad_id = a.id
WHERE a.country_id = 12 AND a.is_deleted = false AND c.is_deleted = false
GROUP BY c.id, c.name
HAVING COUNT(DISTINCT a.id) >= 1000 AND COUNT(DISTINCT ap.param_id) >= 5
ORDER BY params_count DESC, ads_count DESC
LIMIT 10
";

$result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "=== Best categories for country_id=12 (with ads & params) ===\n\n";
foreach ($result as $row) {
    echo "ID: {$row['id']} | {$row['name']} | {$row['ads_count']} ads | {$row['params_count']} params\n";
}

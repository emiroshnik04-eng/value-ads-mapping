<?php
header('Content-Type: text/plain; charset=utf-8');
$config = require __DIR__ . '/db-config.php';
$pdo = new PDO("pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}", $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Check country table
echo "=== Checking country table structure ===\n\n";
$sql = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'country' ORDER BY ordinal_position";
try {
    $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $col) {
        echo "{$col['column_name']} ({$col['data_type']})\n";
    }
} catch (Exception $e) {
    echo "No country table or error: " . $e->getMessage() . "\n";
}

echo "\n=== Sample countries ===\n\n";
$sql = "SELECT * FROM country LIMIT 10";
try {
    $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    print_r($result);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Ads count by country_id ===\n\n";
$sql = "SELECT country_id, COUNT(*) as cnt FROM ad WHERE is_deleted = false GROUP BY country_id ORDER BY cnt DESC LIMIT 10";
$result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    echo "country_id {$row['country_id']}: " . number_format($row['cnt']) . " ads\n";
}

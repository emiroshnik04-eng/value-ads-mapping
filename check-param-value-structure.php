<?php
header('Content-Type: text/plain; charset=utf-8');
$config = require __DIR__ . '/db-config.php';
$pdo = new PDO("pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}", $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== param_value table structure ===\n\n";
$sql = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'param_value' ORDER BY ordinal_position";
$result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $col) {
    echo "{$col['column_name']} ({$col['data_type']})\n";
}

echo "\n=== Sample param_value records ===\n\n";
$sql = "SELECT * FROM param_value LIMIT 5";
$result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
print_r($result);

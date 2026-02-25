<?php
header('Content-Type: text/plain; charset=utf-8');
$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== AD TABLE STRUCTURE ===\n\n";
    
    $result = $pdo->query("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'ad'
        ORDER BY ordinal_position
    ");
    
    foreach ($result as $row) {
        echo "{$row['column_name']}: {$row['data_type']}\n";
    }
    
    echo "\n=== SAMPLE AD DATA ===\n\n";
    
    $result = $pdo->query("
        SELECT id, title, url, status_id, is_deleted, country_id
        FROM ad
        WHERE category_id = 7859
          AND country_id = 12
          AND is_deleted = false
        LIMIT 3
    ");
    
    foreach ($result as $row) {
        echo "ad_id: {$row['id']}\n";
        echo "  title: " . substr($row['title'], 0, 50) . "\n";
        echo "  url: {$row['url']}\n";
        echo "  status_id: {$row['status_id']}\n";
        echo "  country_id: {$row['country_id']}\n\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

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

    echo "=== CHECKING STATUS 6 ADS ===\n\n";
    
    $result = $pdo->query("
        SELECT 
            id,
            title,
            title_slug,
            status_id
        FROM ad
        WHERE status_id = 6
          AND is_deleted = false
          AND category_id = 7859
          AND country_id = 12
        LIMIT 3
    ");
    
    $count = 0;
    foreach ($result as $row) {
        $count++;
        echo "ad_id {$row['id']}:\n";
        echo "  title: " . substr($row['title'], 0, 60) . "\n";
        echo "  title_slug: " . ($row['title_slug'] ?: 'EMPTY') . "\n";
        echo "  URL: https://lalafo.kg/" . ($row['title_slug'] ? "{$row['title_slug']}-{$row['id']}" : "ad/{$row['id']}") . "\n\n";
    }
    
    if ($count > 0) {
        echo "Status 6 ads exist and have title_slug\n";
        echo "Try opening one of these URLs to see if they work.\n";
    } else {
        echo "No status 6 ads found in this category\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

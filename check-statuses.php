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

    echo "=== AD STATUSES IN CATEGORY 7859 ===\n\n";
    
    $result = $pdo->query("
        SELECT 
            status_id,
            COUNT(*) as count
        FROM ad
        WHERE category_id = 7859
          AND country_id = 12
          AND is_deleted = false
        GROUP BY status_id
        ORDER BY count DESC
    ");
    
    foreach ($result as $row) {
        echo "status_id {$row['status_id']}: {$row['count']} ads\n";
    }
    
    echo "\nActive statuses for public ads: 1 (active), 2, 3, 5\n";
    echo "Status 8 is likely deactivated/archived\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

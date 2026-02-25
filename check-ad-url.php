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

    echo "=== CHECKING AD DETAILS FOR PROPER URL FORMAT ===\n\n";
    
    // Get a few active ads from category 7859
    $result = $pdo->query("
        SELECT 
            a.id,
            a.title,
            a.url,
            a.status_id,
            a.is_deleted,
            c.name as country_name,
            co.code as country_code,
            cat.name as category_name
        FROM ad a
        INNER JOIN city c ON c.id = a.city_id
        INNER JOIN country co ON co.id = a.country_id
        INNER JOIN category cat ON cat.id = a.category_id
        WHERE a.category_id = 7859
          AND a.country_id = 12
          AND a.is_deleted = false
          AND a.status_id IN (1, 2, 3, 5)
        LIMIT 5
    ");
    
    foreach ($result as $row) {
        echo "ad_id: {$row['id']}\n";
        echo "  title: {$row['title']}\n";
        echo "  url: {$row['url']}\n";
        echo "  status: {$row['status_id']}\n";
        echo "  country: {$row['country_name']} ({$row['country_code']})\n";
        echo "  category: {$row['category_name']}\n";
        echo "  Full URL format: https://lalafo.{$row['country_code']}/{$row['url']}\n\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

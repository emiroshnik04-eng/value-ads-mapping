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

    echo "=== CHECKING AD URL FORMAT ===\n\n";
    
    // Get one active ad with all related data
    $result = $pdo->query("
        SELECT 
            a.id,
            a.title,
            a.title_slug,
            a.status_id,
            a.country_id
        FROM ad a
        WHERE a.category_id = 7859
          AND a.country_id = 12
          AND a.is_deleted = false
          AND a.status_id IN (1, 2, 3, 5)
          AND a.title_slug IS NOT NULL
        ORDER BY a.created_time DESC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Found ad: {$result['id']}\n";
        echo "Title: {$result['title']}\n";
        echo "Title slug: {$result['title_slug']}\n";
        echo "Country ID: {$result['country_id']}\n\n";
        
        // Check country code
        $country = $pdo->query("SELECT code FROM country WHERE id = {$result['country_id']}")->fetch(PDO::FETCH_ASSOC);
        echo "Country code: {$country['code']}\n\n";
        
        echo "Possible URL formats:\n";
        echo "1. https://lalafo.{$country['code']}/ad/{$result['title_slug']}-{$result['id']}\n";
        echo "2. https://lalafo.{$country['code']}/{$result['title_slug']}-{$result['id']}\n";
        echo "3. https://lalafo.kg/ad/{$result['title_slug']}-{$result['id']}\n";
    } else {
        echo "No active ads found\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

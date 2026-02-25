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

    $ad_id = 69117430;
    
    echo "=== CHECKING AD $ad_id ===\n\n";
    
    $result = $pdo->query("
        SELECT 
            id,
            title,
            title_slug,
            status_id,
            is_deleted,
            country_id,
            category_id
        FROM ad
        WHERE id = $ad_id
    ")->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Ad found:\n";
        echo "  id: {$result['id']}\n";
        echo "  title: {$result['title']}\n";
        echo "  title_slug: {$result['title_slug']}\n";
        echo "  status_id: {$result['status_id']}\n";
        echo "  is_deleted: " . ($result['is_deleted'] ? 'true' : 'false') . "\n";
        echo "  country_id: {$result['country_id']}\n";
        echo "  category_id: {$result['category_id']}\n\n";
        
        if ($result['is_deleted']) {
            echo "⚠️ This ad is DELETED - that's why it returns 404\n";
        }
        
        if ($result['status_id'] != 1 && $result['status_id'] != 2) {
            echo "⚠️ Ad status is {$result['status_id']} - might not be publicly visible\n";
        }
        
        echo "\nURL being generated: https://lalafo.kg/{$result['title_slug']}-{$result['id']}\n";
    } else {
        echo "❌ Ad not found in database\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

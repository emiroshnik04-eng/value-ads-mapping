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

    $ad_id = 109980273;
    
    echo "=== VERIFYING AD $ad_id ===\n\n";
    
    $result = $pdo->query("
        SELECT 
            id,
            title,
            title_slug,
            status_id,
            is_deleted
        FROM ad
        WHERE id = $ad_id
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "Ad details:\n";
    echo "  id: {$result['id']}\n";
    echo "  title: " . substr($result['title'], 0, 60) . "\n";
    echo "  title_slug: {$result['title_slug']}\n";
    echo "  status_id: {$result['status_id']}\n";
    echo "  is_deleted: " . ($result['is_deleted'] ? 'true' : 'false') . "\n\n";
    
    $status = in_array($result['status_id'], [1, 2, 3, 5]) ? '✅ ACTIVE' : '❌ INACTIVE';
    echo "Status check: $status\n\n";
    
    echo "Generated URL: https://lalafo.kg/{$result['title_slug']}-{$result['id']}\n";
    echo "\n";
    echo "This URL should work because:\n";
    echo "  - Ad has title_slug: " . (!empty($result['title_slug']) ? 'YES' : 'NO') . "\n";
    echo "  - Ad is not deleted: " . (!$result['is_deleted'] ? 'YES' : 'NO') . "\n";
    echo "  - Ad is active (status 1,2,3,5): " . (in_array($result['status_id'], [1,2,3,5]) ? 'YES' : 'NO') . "\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

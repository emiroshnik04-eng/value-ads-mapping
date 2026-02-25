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

    echo "=== ANALYZING CATEGORY 7859 (Baby monitors) ===\n\n";
    
    // Total ads in category
    $total = $pdo->query("
        SELECT COUNT(*) FROM ad 
        WHERE category_id = 7859 
          AND country_id = 12 
          AND is_deleted = false
    ")->fetchColumn();
    echo "Total ads in category: $total\n";
    
    // Ads WITH Condition parameter
    $withParam = $pdo->query("
        SELECT COUNT(DISTINCT a.id) FROM ad a
        INNER JOIN ad_param ap ON ap.ad_id = a.id
        WHERE a.category_id = 7859
          AND a.country_id = 12
          AND a.is_deleted = false
          AND ap.param_id = 29
    ")->fetchColumn();
    echo "Ads WITH Condition param: $withParam\n";
    
    // Ads WITHOUT Condition parameter
    $withoutParam = $total - $withParam;
    echo "Ads WITHOUT Condition param: $withoutParam\n\n";
    
    if ($withoutParam > 0) {
        echo "Sample ads WITHOUT Condition param:\n";
        $result = $pdo->query("
            SELECT a.id, LEFT(a.title, 100) as title
            FROM ad a
            LEFT JOIN ad_param ap ON ap.ad_id = a.id AND ap.param_id = 29
            WHERE a.category_id = 7859
              AND a.country_id = 12
              AND a.is_deleted = false
              AND ap.ad_id IS NULL
            LIMIT 5
        ");
        foreach ($result as $row) {
            echo "  ad_id={$row['id']}: {$row['title']}\n";
        }
    } else {
        echo "✅ ALL ads have Condition parameter linked!\n";
        echo "This is GOOD - it means all ads are properly tagged.\n";
        echo "\nTo test the search feature, you need a category where some ads\n";
        echo "mention parameter values in text but don't have them linked.\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

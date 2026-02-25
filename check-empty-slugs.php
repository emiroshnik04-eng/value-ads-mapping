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

    echo "=== CHECKING title_slug STATISTICS ===\n\n";
    
    $result = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN title_slug IS NOT NULL AND title_slug != '' THEN 1 END) as with_slug,
            COUNT(CASE WHEN title_slug IS NULL OR title_slug = '' THEN 1 END) as without_slug
        FROM ad
        WHERE category_id = 7859
          AND country_id = 12
          AND is_deleted = false
          AND status_id IN (1, 2, 3, 5)
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "Total active ads: {$result['total']}\n";
    echo "With title_slug: {$result['with_slug']}\n";
    echo "Without title_slug: {$result['without_slug']}\n\n";
    
    if ($result['without_slug'] > 0) {
        echo "Examples of ads without title_slug:\n";
        $examples = $pdo->query("
            SELECT id, title
            FROM ad
            WHERE category_id = 7859
              AND country_id = 12
              AND is_deleted = false
              AND status_id IN (1, 2, 3, 5)
              AND (title_slug IS NULL OR title_slug = '')
            LIMIT 3
        ");
        foreach ($examples as $row) {
            echo "  ad_id {$row['id']}: " . substr($row['title'], 0, 50) . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

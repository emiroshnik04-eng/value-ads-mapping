<?php
/**
 * Find active categories in Kyrgyzstan with most ads
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Find categories with most ads and params in KG
    $sql = "
        SELECT
            c.id,
            c.name,
            COUNT(DISTINCT a.id) as ads_count,
            COUNT(DISTINCT ap.param_id) as params_count
        FROM category c
        INNER JOIN ad a ON a.category_id = c.id
        LEFT JOIN ad_param ap ON ap.ad_id = a.id
        WHERE a.country_id = 1
          AND a.is_deleted = false
          AND c.is_deleted = false
        GROUP BY c.id, c.name
        HAVING COUNT(DISTINCT a.id) >= 100
           AND COUNT(DISTINCT ap.param_id) >= 3
        ORDER BY ads_count DESC
        LIMIT 20
    ";

    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== TOP 20 Categories in Kyrgyzstan (with ads & params) ===\n\n";

    foreach ($categories as $cat) {
        echo sprintf(
            "ID: %d | Name: %s | Ads: %s | Params: %d\n",
            $cat['id'],
            $cat['name'],
            number_format($cat['ads_count']),
            $cat['params_count']
        );
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}

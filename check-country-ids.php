<?php
/**
 * Check country_id distribution
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

    // Count ads by country
    $sql = "
        SELECT
            country_id,
            COUNT(*) as ads_count
        FROM ad
        WHERE is_deleted = false
        GROUP BY country_id
        ORDER BY ads_count DESC
        LIMIT 20
    ";

    $stmt = $pdo->query($sql);
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== Ads distribution by country_id ===\n\n";

    foreach ($countries as $c) {
        echo sprintf(
            "country_id: %d | Ads: %s\n",
            $c['country_id'],
            number_format($c['ads_count'])
        );
    }

    echo "\n=== Sample categories with country_id = 11 ===\n\n";

    // Get sample categories for country 11
    $sql = "
        SELECT
            c.id,
            c.name,
            COUNT(a.id) as ads_count
        FROM category c
        INNER JOIN ad a ON a.category_id = c.id
        WHERE a.country_id = 11
          AND a.is_deleted = false
          AND c.is_deleted = false
        GROUP BY c.id, c.name
        ORDER BY ads_count DESC
        LIMIT 10
    ";

    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categories as $cat) {
        echo sprintf(
            "ID: %d | Name: %s | Ads: %s\n",
            $cat['id'],
            $cat['name'],
            number_format($cat['ads_count'])
        );
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}

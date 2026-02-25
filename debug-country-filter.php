<?php
/**
 * Debug: Check how to filter params by country
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

    $categoryId = 4288;

    echo "=== Testing different approaches to filter by country ===\n\n";

    // Approach 1: Filter by param name prefix (KG -, PL -, etc)
    $sql = "
        SELECT DISTINCT
            p.id,
            p.name,
            p.order_id
        FROM category_param cp
        INNER JOIN param p ON p.id = cp.param_id
        WHERE cp.category_id = :category_id
          AND (p.name LIKE 'KG -%' OR p.name NOT LIKE 'PL -%' AND p.name NOT LIKE 'UA -%' AND p.name NOT LIKE 'RS -%' AND p.name NOT LIKE 'AZ -%')
        ORDER BY p.order_id ASC
        LIMIT 15
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':category_id' => $categoryId]);
    $result1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Approach 1: Filter by name prefix (KG - or no country prefix)\n";
    echo "Count: " . count($result1) . "\n";
    foreach ($result1 as $p) {
        echo "  - [{$p['id']}] {$p['name']}\n";
    }
    echo "\n\n";

    // Approach 2: Check country_param table
    $sql = "SELECT COUNT(*) FROM country_param WHERE country_id = 1 LIMIT 1";
    $stmt = $pdo->query($sql);
    $countryParamExists = $stmt->fetchColumn();

    echo "Approach 2: country_param table\n";
    echo "Has records: " . ($countryParamExists > 0 ? 'YES' : 'NO') . "\n";

    if ($countryParamExists) {
        $sql = "
            SELECT DISTINCT
                p.id,
                p.name,
                p.order_id
            FROM category_param cp
            INNER JOIN param p ON p.id = cp.param_id
            INNER JOIN country_param cpa ON cpa.param_id = p.id
            WHERE cp.category_id = :category_id
              AND cpa.country_id = 1
            ORDER BY p.order_id ASC
            LIMIT 15
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':category_id' => $categoryId]);
        $result2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Count: " . count($result2) . "\n";
        foreach ($result2 as $p) {
            echo "  - [{$p['id']}] {$p['name']}\n";
        }
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}

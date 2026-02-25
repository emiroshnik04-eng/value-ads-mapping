<?php
/**
 * Debug: Check parameters for category
 */

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $categoryId = 7859;

    // Test 1: Check if category exists
    $sql = "SELECT id, name FROM category WHERE id = :id AND is_deleted = false";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "=== Category ===\n";
    print_r($category);
    echo "\n\n";

    // Test 2: Check category_param records
    $sql = "SELECT * FROM category_param WHERE category_id = :id LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $categoryId]);
    $categoryParams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== Category Params (category_param table) ===\n";
    echo "Count: " . count($categoryParams) . "\n";
    print_r($categoryParams);
    echo "\n\n";

    // Test 3: Get params without country filter
    $sql = "
        SELECT DISTINCT
            p.id,
            p.name,
            p.order_id
        FROM category_param cp
        INNER JOIN param p ON p.id = cp.param_id
        WHERE cp.category_id = :category_id
        ORDER BY p.order_id ASC, p.name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':category_id' => $categoryId]);
    $paramsNoCountry = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== Params (without country filter) ===\n";
    echo "Count: " . count($paramsNoCountry) . "\n";
    print_r($paramsNoCountry);
    echo "\n\n";

    // Test 4: Get params with country filter
    $sql = "
        SELECT DISTINCT
            p.id,
            p.name,
            p.order_id,
            cpa.country_id
        FROM category_param cp
        INNER JOIN param p ON p.id = cp.param_id
        LEFT JOIN country_param cpa ON cpa.param_id = p.id AND cpa.country_id = 1
        WHERE cp.category_id = :category_id
        ORDER BY p.order_id ASC, p.name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':category_id' => $categoryId]);
    $paramsWithCountry = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== Params (with LEFT JOIN country_param) ===\n";
    echo "Count: " . count($paramsWithCountry) . "\n";
    print_r($paramsWithCountry);

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}

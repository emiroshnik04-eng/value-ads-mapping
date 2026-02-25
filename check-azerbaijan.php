<?php
/**
 * Check Azerbaijan configuration in database
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

    echo "=== Checking Azerbaijan (country_id=13) Configuration ===\n\n";

    // Check if Azerbaijan has configuration for category 1473
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM country_category_param
        WHERE country_id = 13 AND category_id = 1473
    ');
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "Azerbaijan (13) + Category 1473 params count: $count\n\n";

    // Check if category 1473 exists for other countries
    $stmt = $pdo->prepare('
        SELECT country_id, COUNT(*) as cnt
        FROM country_category_param
        WHERE category_id = 1473
        GROUP BY country_id
        ORDER BY country_id
    ');
    $stmt->execute();
    echo "Category 1473 configured for countries:\n";
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "  Country {$row['country_id']}: {$row['cnt']} params\n";
    }

    echo "\n=== Checking params for Azerbaijan + Category 1473 ===\n\n";

    $stmt = $pdo->prepare('
        SELECT p.id, p.name, ccp.status_id
        FROM country_category_param ccp
        INNER JOIN param p ON p.id = ccp.param_id
        WHERE ccp.country_id = 13 AND ccp.category_id = 1473
        ORDER BY p.name
    ');
    $stmt->execute();
    $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($params) > 0) {
        foreach ($params as $param) {
            echo "  Param {$param['id']}: {$param['name']} (status: {$param['status_id']})\n";
        }
    } else {
        echo "  NO PARAMS FOUND!\n";
    }

    echo "\n=== Checking if category 1473 exists in category table ===\n\n";

    $stmt = $pdo->prepare('SELECT id, name FROM category WHERE id = 1473');
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        echo "  Category found: {$category['id']} - {$category['name']}\n";
    } else {
        echo "  CATEGORY 1473 NOT FOUND!\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
/**
 * Extract real parameters and values for categories from database
 * Generates category-params.json file
 */

// Load database config
$configFile = __DIR__ . '/db-config.php';
if (file_exists($configFile)) {
    $dbConfig = require $configFile;
} else {
    die("Error: db-config.php not found. Create it from db-config.example.php\n");
}

try {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['dbname']
    );

    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Connected to database successfully\n\n";

    // Get popular categories (you can adjust this list)
    $popularCategories = [1361, 7859, 1543, 1345, 1342];

    $result = [
        'generated_at' => date('Y-m-d H:i:s'),
        'categories' => []
    ];

    foreach ($popularCategories as $categoryId) {
        echo "Processing category $categoryId...\n";

        // Get parameters for this category
        $sql = "
            SELECT DISTINCT
                p.id,
                p.name
            FROM category_param cp
            INNER JOIN param p ON p.id = cp.param_id
            WHERE cp.category_id = :category_id
              AND p.is_deleted = false
            ORDER BY p.order_id ASC, p.name ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':category_id' => $categoryId]);
        $params = $stmt->fetchAll();

        if (empty($params)) {
            echo "  No parameters found for category $categoryId\n";
            continue;
        }

        echo "  Found " . count($params) . " parameters\n";

        $categoryData = [
            'category_id' => $categoryId,
            'params' => []
        ];

        // Get values for each parameter
        foreach ($params as $param) {
            echo "    - {$param['name']} (ID: {$param['id']})\n";

            // Get parameter values with country-specific translations
            $sqlValues = "
                SELECT DISTINCT
                    cpv.param_value_id AS id,
                    pv.value,
                    cpv.alias,
                    cpv.country_id
                FROM country_param_value cpv
                INNER JOIN param_value pv ON pv.id = cpv.param_value_id
                WHERE pv.param_id = :param_id
                  AND pv.is_deleted = false
                ORDER BY cpv.country_id, cpv.alias
                LIMIT 20
            ";

            $stmtValues = $pdo->prepare($sqlValues);
            $stmtValues->execute([':param_id' => $param['id']]);
            $values = $stmtValues->fetchAll();

            $categoryData['params'][] = [
                'id' => $param['id'],
                'name' => $param['name'],
                'values' => $values,
                'values_count' => count($values)
            ];
        }

        $result['categories'][$categoryId] = $categoryData;
        echo "  ✓ Category $categoryId completed\n\n";
    }

    // Save to JSON file
    $jsonFile = __DIR__ . '/category-params.json';
    file_put_contents($jsonFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "✓ Generated category-params.json with " . count($result['categories']) . " categories\n";
    echo "File: $jsonFile\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n\nMake sure:\n1. VPN is connected\n2. db-config.php has correct credentials\n3. PostgreSQL server is accessible\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

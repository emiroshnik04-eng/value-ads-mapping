<?php
header('Content-Type: text/plain; charset=utf-8');

$configFile = __DIR__ . '/db-config.php';
$dbConfig = require $configFile;

try {
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
        $dbConfig['host'], $dbConfig['port'], $dbConfig['dbname']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "=== Finding categories with active Color (param 105) values ===\n\n";

    // First, let's check what columns exist in country_category_param_value
    $sql = "
        SELECT column_name
        FROM information_schema.columns
        WHERE table_name = 'country_category_param_value'
        ORDER BY ordinal_position
    ";
    $columns = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in country_category_param_value:\n";
    echo implode(', ', $columns) . "\n\n";

    // Now find categories that have active values for param 105
    // Need to join with param_param_value to find which param_value_ids belong to param 105
    $sql = "
        SELECT
            ccpv.category_id,
            c.name as category_name,
            COUNT(DISTINCT ccpv.param_value_id) as active_color_values
        FROM country_category_param_value ccpv
        INNER JOIN param_param_value ppv ON ppv.param_value_id = ccpv.param_value_id
        LEFT JOIN category c ON c.id = ccpv.category_id
        WHERE ccpv.country_id = 12
          AND ccpv.status_id = 1
          AND ppv.param_id = 105
        GROUP BY ccpv.category_id, c.name
        HAVING COUNT(DISTINCT ccpv.param_value_id) > 0
        ORDER BY active_color_values DESC
        LIMIT 20
    ";

    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll();

    if (empty($categories)) {
        echo "No categories found with active Color values for country 12\n";
    } else {
        echo "Categories with active Color (param 105) values:\n\n";
        foreach ($categories as $cat) {
            echo "Category {$cat['category_id']}: {$cat['category_name']} - {$cat['active_color_values']} active color values\n";
            echo "  Test URL: http://localhost:8888/get-param-values.php?country_id=12&category_id={$cat['category_id']}&param_id=105\n\n";
        }

        // Test the first one
        echo "\n=== Testing first category: {$categories[0]['category_id']} ===\n";
        $testUrl = "http://localhost:8888/get-param-values.php?country_id=12&category_id={$categories[0]['category_id']}&param_id=105";
        exec("curl -s \"$testUrl\"", $output, $returnCode);

        if ($returnCode === 0) {
            $json = implode("\n", $output);
            $values = json_decode($json, true);
            echo "API returned: " . count($values) . " values\n";

            if (count($values) > 0) {
                echo "First 3 values:\n";
                foreach (array_slice($values, 0, 3) as $v) {
                    echo "  - {$v['display_value_translated']} ({$v['display_value']}) - Usage: {$v['usage_count']}\n";
                }
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

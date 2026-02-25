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

    $categoryId = 1473;
    $paramId = 105;

    echo "=== Check category_param_value table ===\n\n";

    // Get values for category 1473, filtered by param 105 via JOIN
    $sql = "
        SELECT
            cpv.category_id,
            cpv.param_value_id,
            pv.value
        FROM category_param_value cpv
        INNER JOIN param_value pv ON pv.id = cpv.param_value_id
        INNER JOIN param_param_value ppv ON ppv.param_value_id = cpv.param_value_id
        WHERE cpv.category_id = ?
          AND ppv.param_id = ?
        ORDER BY cpv.param_value_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoryId, $paramId]);
    $values = $stmt->fetchAll();

    echo "Total values in category_param_value for category 1473, param 105: " . count($values) . "\n\n";

    if (count($values) > 0) {
        echo "Values:\n";
        foreach ($values as $row) {
            echo "  ID {$row['param_value_id']}: {$row['value']}\n";
        }
    } else {
        echo "No values found in category_param_value\n";
    }

    // Compare with country_category_param_value
    echo "\n\n=== Compare with country_category_param_value ===\n";
    $sql = "
        SELECT
            ccpv.param_value_id,
            pv.value
        FROM country_category_param_value ccpv
        INNER JOIN param_param_value ppv ON ppv.param_value_id = ccpv.param_value_id
        INNER JOIN param_value pv ON pv.id = ccpv.param_value_id
        WHERE ccpv.country_id = 12
          AND ccpv.category_id = ?
          AND ppv.param_id = ?
        ORDER BY ccpv.param_value_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categoryId, $paramId]);
    $ccpvValues = $stmt->fetchAll();

    echo "Total values in country_category_param_value: " . count($ccpvValues) . "\n";

    // Check if they match
    $cpvIds = array_column($values, 'param_value_id');
    $ccpvIds = array_column($ccpvValues, 'param_value_id');

    if ($cpvIds === $ccpvIds) {
        echo "✅ Both tables have SAME values!\n";
    } else {
        echo "⚠️ Tables have DIFFERENT values\n";
        $onlyInCpv = array_diff($cpvIds, $ccpvIds);
        $onlyInCcpv = array_diff($ccpvIds, $cpvIds);

        if (!empty($onlyInCpv)) {
            echo "Only in category_param_value: " . implode(', ', $onlyInCpv) . "\n";
        }
        if (!empty($onlyInCcpv)) {
            echo "Only in country_category_param_value: " . implode(', ', $onlyInCcpv) . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

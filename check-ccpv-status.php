<?php
header('Content-Type: text/plain; charset=utf-8');

// Load database config
$configFile = __DIR__ . '/db-config.php';
if (file_exists($configFile)) {
    $dbConfig = require $configFile;
} else {
    die("db-config.php not found");
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

    $countryId = 12;
    $categoryId = 1473;
    $paramId = 105;

    echo "=== Checking country_category_param_value ===\n";
    echo "Country: $countryId, Category: $categoryId (Toys), Param: $paramId (Color)\n\n";

    // Check what status_id values exist
    $sql = "SELECT DISTINCT status_id FROM country_category_param_value ORDER BY status_id";
    $stmt = $pdo->query($sql);
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Available status_id values: " . implode(', ', $statuses) . "\n\n";

    // Get problematic values and their status
    $problemValues = [23108, 27221, 44999, 39849, 30483]; // Silver Iphone, Blue-1, etc

    echo "=== Checking problematic values ===\n\n";

    foreach ($problemValues as $valueId) {
        $sql = "SELECT pv.id, pv.value, ccpv.status_id, ccpv.country_id, ccpv.category_id
                FROM param_value pv
                LEFT JOIN country_category_param_value ccpv
                    ON ccpv.param_value_id = pv.id
                    AND ccpv.country_id = $countryId
                    AND ccpv.category_id = $categoryId
                WHERE pv.id = $valueId";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch();

        if ($result) {
            echo "Value ID {$valueId}: {$result['value']}\n";
            if ($result['status_id']) {
                echo "  → Status in CCPV: {$result['status_id']}\n";
            } else {
                echo "  → NOT in country_category_param_value\n";
            }
        }
        echo "\n";
    }

    echo "\n=== All values with status_id for this category ===\n\n";

    // Get all values for this combination grouped by status
    $sql = "SELECT pv.id, pv.value, ccpv.status_id, COUNT(DISTINCT ap.ad_id) as usage_count
            FROM param_value pv
            INNER JOIN param_param_value ppv ON ppv.param_value_id = pv.id
            INNER JOIN country_category_param_value ccpv
                ON ccpv.param_value_id = pv.id
                AND ccpv.country_id = $countryId
                AND ccpv.category_id = $categoryId
            LEFT JOIN ad_param ap ON ap.param_value_id = pv.id AND ap.param_id = $paramId
            WHERE ppv.param_id = $paramId
            GROUP BY pv.id, pv.value, ccpv.status_id
            ORDER BY ccpv.status_id, usage_count DESC
            LIMIT 100";
    $stmt = $pdo->query($sql);
    $values = $stmt->fetchAll();

    $byStatus = [];
    foreach ($values as $v) {
        $byStatus[$v['status_id']][] = $v;
    }

    foreach ($byStatus as $statusId => $vals) {
        echo "Status $statusId (" . count($vals) . " values):\n";
        foreach (array_slice($vals, 0, 10) as $v) {
            echo sprintf("  ID: %-6s %-30s (used in %d ads)\n", $v['id'], $v['value'], $v['usage_count']);
        }
        if (count($vals) > 10) {
            echo "  ... and " . (count($vals) - 10) . " more\n";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

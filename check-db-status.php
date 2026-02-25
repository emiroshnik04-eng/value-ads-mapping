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

    $countryId = 12;
    $categoryId = 1473;
    $problemIds = [23108, 27221, 44999, 39849, 30483];

    echo "=== Checking status_id in country_category_param_value ===\n\n";
    echo "Country: $countryId (Kyrgyzstan)\n";
    echo "Category: $categoryId (Toys)\n\n";

    foreach ($problemIds as $valueId) {
        $sql = "SELECT pv.id, pv.value, ccpv.status_id
                FROM param_value pv
                LEFT JOIN country_category_param_value ccpv
                    ON ccpv.param_value_id = pv.id
                    AND ccpv.country_id = $countryId
                    AND ccpv.category_id = $categoryId
                WHERE pv.id = $valueId";
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();

        echo "ID {$valueId}: {$row['value']}\n";
        if ($row['status_id']) {
            echo "  status_id: {$row['status_id']}";
            if ($row['status_id'] == 1) {
                echo " (ACTIVE - will be shown)\n";
            } else {
                echo " (INACTIVE - will be hidden)\n";
            }
        } else {
            echo "  NOT in country_category_param_value (will be hidden)\n";
        }
        echo "\n";
    }

    echo "\n=== What status_id values mean? ===\n";
    $sql = "SELECT DISTINCT status_id FROM country_category_param_value ORDER BY status_id";
    $stmt = $pdo->query($sql);
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Available status_id values: " . implode(', ', $statuses) . "\n";
    echo "\nCommon interpretation:\n";
    echo "  1 = Active (should be shown)\n";
    echo "  2 = Pending/Draft\n";
    echo "  3 = Inactive/Disabled\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

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
    $paramId = 105;

    echo "=== Debug: Category 1473, Country 12, Param 105 ===\n\n";

    // STEP 1: Check what's in country_category_param_value
    echo "STEP 1: Checking country_category_param_value\n";
    $sql = "
        SELECT param_value_id, status_id
        FROM country_category_param_value
        WHERE country_id = ?
          AND category_id = ?
        ORDER BY param_value_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$countryId, $categoryId]);
    $ccpvRows = $stmt->fetchAll();

    echo "Total rows: " . count($ccpvRows) . "\n";
    if (count($ccpvRows) > 0) {
        echo "First 10 rows:\n";
        foreach (array_slice($ccpvRows, 0, 10) as $row) {
            $statusLabel = $row['status_id'] == 1 ? 'ACTIVE' : 'INACTIVE';
            echo "  param_value_id: {$row['param_value_id']} - status_id: {$row['status_id']} ($statusLabel)\n";
        }
    }

    // Count by status
    $sql = "
        SELECT status_id, COUNT(*) as cnt
        FROM country_category_param_value
        WHERE country_id = ?
          AND category_id = ?
        GROUP BY status_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$countryId, $categoryId]);
    $statuses = $stmt->fetchAll();
    echo "\nBreakdown by status_id:\n";
    foreach ($statuses as $row) {
        $label = $row['status_id'] == 1 ? 'ACTIVE' : 'INACTIVE';
        echo "  status_id {$row['status_id']} ($label): {$row['cnt']} rows\n";
    }

    // STEP 2: Get active values for param 105
    echo "\n\nSTEP 2: Active values for param 105 specifically\n";
    $sql = "
        SELECT DISTINCT ccpv.param_value_id, pv.value
        FROM country_category_param_value ccpv
        INNER JOIN param_param_value ppv ON ppv.param_value_id = ccpv.param_value_id
        INNER JOIN param_value pv ON pv.id = ccpv.param_value_id
        WHERE ccpv.country_id = ?
          AND ccpv.category_id = ?
          AND ccpv.status_id = 1
          AND ppv.param_id = ?
        ORDER BY ccpv.param_value_id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$countryId, $categoryId, $paramId]);
    $activeColorValues = $stmt->fetchAll();

    echo "Active color values (param 105): " . count($activeColorValues) . "\n";
    if (count($activeColorValues) > 0) {
        foreach ($activeColorValues as $row) {
            echo "  ID {$row['param_value_id']}: {$row['value']}\n";
        }
    } else {
        echo "NO ACTIVE COLOR VALUES FOUND!\n";
    }

    // STEP 3: Check what values are in param_param_value for param 105
    echo "\n\nSTEP 3: All values in param_param_value for param 105\n";
    $sql = "
        SELECT DISTINCT pv.id, pv.value
        FROM param_param_value ppv
        INNER JOIN param_value pv ON pv.id = ppv.param_value_id
        WHERE ppv.param_id = ?
        ORDER BY pv.id
        LIMIT 20
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$paramId]);
    $allColorValues = $stmt->fetchAll();
    echo "Total color values for param 105: " . count($allColorValues) . "\n";
    echo "First 20:\n";
    foreach ($allColorValues as $row) {
        echo "  ID {$row['id']}: {$row['value']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

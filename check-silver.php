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

    echo "=== Searching for 'silver' values ===\n\n";

    // Find all param values with 'silver' in name
    $sql = "SELECT id, value FROM param_value WHERE LOWER(value) LIKE '%silver%' LIMIT 20";
    $stmt = $pdo->query($sql);
    $values = $stmt->fetchAll();

    echo "All param_values with 'silver':\n";
    foreach ($values as $v) {
        echo "  ID: {$v['id']}, Value: {$v['value']}\n";
    }

    echo "\n\n=== Checking param 105 (Color) ===\n\n";

    // Check which silver values are linked to param 105
    $sql = "SELECT pv.id, pv.value
            FROM param_value pv
            INNER JOIN param_param_value ppv ON ppv.param_value_id = pv.id
            WHERE ppv.param_id = 105
            AND LOWER(pv.value) LIKE '%silver%'";
    $stmt = $pdo->query($sql);
    $linkedValues = $stmt->fetchAll();

    echo "Silver values linked to param 105 via param_param_value:\n";
    if (empty($linkedValues)) {
        echo "  (none found)\n";
    } else {
        foreach ($linkedValues as $v) {
            echo "  ID: {$v['id']}, Value: {$v['value']}\n";
        }
    }

    echo "\n\n=== Checking country_category_param_value ===\n\n";

    // Check which are in country_category_param_value for category 1473, country 12
    $sql = "SELECT pv.id, pv.value, ccpv.status_id
            FROM param_value pv
            INNER JOIN country_category_param_value ccpv ON ccpv.param_value_id = pv.id
            WHERE ccpv.country_id = 12
            AND ccpv.category_id = 1473
            AND LOWER(pv.value) LIKE '%silver%'";
    $stmt = $pdo->query($sql);
    $ccpvValues = $stmt->fetchAll();

    echo "Silver values in country_category_param_value for category 1473, country 12:\n";
    if (empty($ccpvValues)) {
        echo "  (none found)\n";
    } else {
        foreach ($ccpvValues as $v) {
            echo "  ID: {$v['id']}, Value: {$v['value']}, Status: {$v['status_id']}\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

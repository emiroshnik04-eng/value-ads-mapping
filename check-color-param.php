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

    echo "=== All color values for param 105 ===\n\n";

    // Get all values for param 105 via param_param_value
    $sql = "SELECT pv.id, pv.value
            FROM param_value pv
            INNER JOIN param_param_value ppv ON ppv.param_value_id = pv.id
            WHERE ppv.param_id = 105
            ORDER BY pv.value ASC
            LIMIT 100";
    $stmt = $pdo->query($sql);
    $values = $stmt->fetchAll();

    echo "Total values: " . count($values) . "\n\n";
    foreach ($values as $v) {
        echo sprintf("ID: %-6s Value: %s\n", $v['id'], $v['value']);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

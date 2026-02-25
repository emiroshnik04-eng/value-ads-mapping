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

    echo "=== country_param_value table structure ===\n\n";

    $sql = "
        SELECT column_name, data_type, character_maximum_length
        FROM information_schema.columns
        WHERE table_name = 'country_param_value'
        ORDER BY ordinal_position
    ";
    $columns = $pdo->query($sql)->fetchAll();

    foreach ($columns as $col) {
        $len = $col['character_maximum_length'] ? " ({$col['character_maximum_length']})" : '';
        echo "{$col['column_name']}: {$col['data_type']}{$len}\n";
    }

    echo "\n\n=== Sample data for param_value_id 23108 (Silver Iphone) ===\n";
    $sql = "
        SELECT *
        FROM country_param_value
        WHERE param_value_id = 23108
        LIMIT 5
    ";
    $rows = $pdo->query($sql)->fetchAll();

    if (empty($rows)) {
        echo "No records found\n";
    } else {
        foreach ($rows as $row) {
            echo "\nCountry ID: {$row['country_id']}\n";
            foreach ($row as $key => $val) {
                if ($key !== 'country_id') {
                    echo "  $key: " . ($val ?: '(empty)') . "\n";
                }
            }
        }
    }

    echo "\n\n=== Check if alias field exists and has translations for other values ===\n";
    $sql = "
        SELECT cpv.param_value_id, pv.value, cpv.alias, cpv.country_id
        FROM country_param_value cpv
        INNER JOIN param_value pv ON pv.id = cpv.param_value_id
        WHERE cpv.country_id = 12
          AND cpv.alias IS NOT NULL
          AND cpv.alias != ''
        LIMIT 10
    ";
    $stmt = $pdo->query($sql);
    $samples = $stmt->fetchAll();

    if (empty($samples)) {
        echo "No values with alias field populated\n";
    } else {
        echo "Sample values with alias:\n";
        foreach ($samples as $row) {
            echo "  {$row['value']} => {$row['alias']}\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

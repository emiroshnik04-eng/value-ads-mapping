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

    echo "=== Looking for translations for Silver Iphone and Blue-1 ===\n\n";

    // Check if there's a param_value_translation table or similar
    echo "STEP 1: Find translation-related tables\n";
    $sql = "
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND (table_name LIKE '%translation%' OR table_name LIKE '%i18n%' OR table_name LIKE '%lang%')
        ORDER BY table_name
    ";
    $tables = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo "Translation-related tables:\n";
    if (empty($tables)) {
        echo "  No translation tables found\n";
    } else {
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    }

    // Check param_value table for any translation fields
    echo "\n\nSTEP 2: Check param_value table columns\n";
    $sql = "
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'param_value'
        ORDER BY ordinal_position
    ";
    $columns = $pdo->query($sql)->fetchAll();
    echo "Columns in param_value:\n";
    foreach ($columns as $col) {
        echo "  - {$col['column_name']} ({$col['data_type']})\n";
    }

    // Get the actual data for Silver Iphone and Blue-1
    echo "\n\nSTEP 3: Get data for Silver Iphone and Blue-1\n";
    $sql = "SELECT * FROM param_value WHERE value IN ('Silver Iphone', 'Blue-1')";
    $values = $pdo->query($sql)->fetchAll();

    if (empty($values)) {
        echo "No records found\n";
    } else {
        foreach ($values as $row) {
            echo "\nID {$row['id']}: {$row['value']}\n";
            foreach ($row as $key => $val) {
                if ($key !== 'id' && $key !== 'value') {
                    echo "  $key: $val\n";
                }
            }
        }
    }

    // Check country_param_value for translations
    echo "\n\nSTEP 4: Check country_param_value table for alias field\n";
    $sql = "
        SELECT cpv.*, pv.value
        FROM country_param_value cpv
        INNER JOIN param_value pv ON pv.id = cpv.param_value_id
        WHERE pv.value IN ('Silver Iphone', 'Blue-1')
          AND cpv.country_id = 12
    ";
    $stmt = $pdo->query($sql);
    $countryValues = $stmt->fetchAll();

    if (empty($countryValues)) {
        echo "No records in country_param_value\n";
    } else {
        echo "Found in country_param_value:\n";
        foreach ($countryValues as $row) {
            echo "\nValue: {$row['value']}\n";
            echo "  Alias: {$row['alias']}\n";
            echo "  Language: {$row['language_id']}\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

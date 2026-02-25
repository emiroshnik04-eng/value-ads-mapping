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

    echo "=== Searching ALL tables for potential translations ===\n\n";

    // Get all tables
    $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name";
    $allTables = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

    // Filter tables that might contain translations
    $translationTables = array_filter($allTables, function($table) {
        return strpos($table, 'param') !== false || 
               strpos($table, 'value') !== false ||
               strpos($table, 'translation') !== false ||
               strpos($table, 'i18n') !== false ||
               strpos($table, 'lang') !== false;
    });

    echo "Found " . count($translationTables) . " tables that might contain translations:\n";
    foreach ($translationTables as $table) {
        echo "  - $table\n";
    }

    // Check each table for param_value_id 23108 (Silver Iphone)
    echo "\n\n=== Checking for param_value_id 23108 (Silver Iphone) ===\n\n";

    foreach ($translationTables as $table) {
        try {
            // Get columns for this table
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '$table'";
            $columns = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

            // Check if has param_value_id column
            if (in_array('param_value_id', $columns)) {
                $sql = "SELECT * FROM $table WHERE param_value_id = 23108 LIMIT 1";
                $row = $pdo->query($sql)->fetch();

                if ($row) {
                    echo "✅ Found in table: $table\n";
                    foreach ($row as $key => $val) {
                        if ($val) {
                            echo "    $key: $val\n";
                        }
                    }
                    echo "\n";
                }
            }
        } catch (Exception $e) {
            // Skip tables with errors
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

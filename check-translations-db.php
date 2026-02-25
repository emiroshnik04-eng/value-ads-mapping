<?php
header('Content-Type: text/plain; charset=utf-8');

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

    echo "=== Finding translation tables ===\n\n";

    // Find tables with 'translation' or 'lang' in name
    $sql = "SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = 'public'
            AND (table_name LIKE '%translation%' OR table_name LIKE '%lang%' OR table_name LIKE '%locale%')
            ORDER BY table_name";
    $stmt = $pdo->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Tables with translations:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    echo "\n\n=== Checking category translations ===\n\n";

    // Check if category has translation columns
    $sql = "SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'category'
            ORDER BY ordinal_position";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Category table columns:\n";
    foreach ($columns as $col) {
        echo "  - $col\n";
    }

    // Check category 1473 (Toys)
    echo "\n\nCategory 1473 data:\n";
    $sql = "SELECT * FROM category WHERE id = 1473";
    $stmt = $pdo->query($sql);
    $cat = $stmt->fetch();
    if ($cat) {
        foreach ($cat as $key => $value) {
            echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }

    echo "\n\n=== Checking param translations ===\n\n";

    // Check if param has translation columns
    $sql = "SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'param'
            ORDER BY ordinal_position";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Param table columns:\n";
    foreach ($columns as $col) {
        echo "  - $col\n";
    }

    // Check param 105 (Color)
    echo "\n\nParam 105 data:\n";
    $sql = "SELECT * FROM param WHERE id = 105";
    $stmt = $pdo->query($sql);
    $param = $stmt->fetch();
    if ($param) {
        foreach ($param as $key => $value) {
            echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    }

    echo "\n\n=== Checking param_value translations ===\n\n";

    // Check if param_value has translation columns
    $sql = "SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'param_value'
            ORDER BY ordinal_position";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Param_value table columns:\n";
    foreach ($columns as $col) {
        echo "  - $col\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

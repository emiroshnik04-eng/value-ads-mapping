<?php
/**
 * Check how production gets translations for Serbia
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== Checking category table columns ===\n\n";

    $stmt = $pdo->prepare("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'category'
        ORDER BY ordinal_position
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "{$col['column_name']}: {$col['data_type']}\n";
    }

    echo "\n=== Sample category data (id=4287 - Dresses) ===\n\n";

    $stmt = $pdo->prepare("SELECT * FROM category WHERE id = 4287");
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        foreach ($category as $key => $value) {
            if (strlen($value) < 100) {
                echo "$key: $value\n";
            } else {
                echo "$key: [too long]\n";
            }
        }
    }

    echo "\n=== Checking param table columns ===\n\n";

    $stmt = $pdo->prepare("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'param'
        ORDER BY ordinal_position
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "{$col['column_name']}: {$col['data_type']}\n";
    }

    echo "\n=== Sample param data (id=220 - Dresses Type) ===\n\n";

    $stmt = $pdo->prepare("SELECT * FROM param WHERE id = 220");
    $stmt->execute();
    $param = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($param) {
        foreach ($param as $key => $value) {
            if ($value && strlen($value) < 100) {
                echo "$key: $value\n";
            }
        }
    }

    echo "\n=== Checking param_value table columns ===\n\n";

    $stmt = $pdo->prepare("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'param_value'
        ORDER BY ordinal_position
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "{$col['column_name']}: {$col['data_type']}\n";
    }

    echo "\n=== Sample param_value data (Evening dress) ===\n\n";

    $stmt = $pdo->prepare("SELECT * FROM param_value WHERE value ILIKE '%evening%' LIMIT 1");
    $stmt->execute();
    $value = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($value) {
        foreach ($value as $key => $val) {
            if ($val && strlen($val) < 100) {
                echo "$key: $val\n";
            }
        }
    }

    echo "\n=== Checking country_param_value table for aliases ===\n\n";

    // Check if country_param_value stores localized aliases
    $stmt = $pdo->prepare("
        SELECT cpv.*, pv.value
        FROM country_param_value cpv
        INNER JOIN param_value pv ON pv.id = cpv.param_value_id
        WHERE cpv.country_id = 11
        AND pv.value ILIKE '%evening%'
        LIMIT 3
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        echo "Value: {$row['value']}, Alias: {$row['alias']}, Country: {$row['country_id']}\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

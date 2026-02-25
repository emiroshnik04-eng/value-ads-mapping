<?php
/**
 * Find real Serbian translations from translation-microservice
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

    echo "=== Searching for real Serbian translations in database ===\n\n";

    // Check if there are translation tables with Serbian data
    // Country ID for Serbia is 11

    // First, check what tables contain translations
    $stmt = $pdo->prepare("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        AND (
            table_name LIKE '%translation%'
            OR table_name LIKE '%message%'
            OR table_name LIKE '%i18n%'
            OR table_name LIKE '%lang%'
        )
        ORDER BY table_name
    ");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Found translation-related tables:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    echo "\n=== Checking fast_message_country for Serbia (country_id=11) ===\n\n";

    // Check structure of fast_message_country
    $stmt = $pdo->prepare("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'fast_message_country'
        ORDER BY ordinal_position
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Table structure:\n";
    foreach ($columns as $col) {
        echo "  {$col['column_name']}: {$col['data_type']}\n";
    }

    echo "\n=== Sample data for Serbia ===\n\n";

    $stmt = $pdo->prepare("
        SELECT *
        FROM fast_message_country
        WHERE country_id = 11
        LIMIT 20
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($rows) . " rows for Serbia\n";
    foreach ($rows as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }

    // Try to find Dresses translations
    echo "\n=== Searching for 'Dresses' key ===\n\n";

    // Search in category table
    $stmt = $pdo->prepare("
        SELECT id, name, name_sr
        FROM category
        WHERE name = 'Dresses'
        LIMIT 1
    ");
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        echo "Found in category table:\n";
        print_r($category);
    } else {
        echo "Not found in category table with exact name 'Dresses'\n";
    }

    // Check if category has name_sr column
    echo "\n=== Checking category table structure ===\n\n";

    $stmt = $pdo->prepare("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'category'
        AND column_name LIKE '%name%'
        ORDER BY ordinal_position
    ");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "{$col['column_name']}: {$col['data_type']}\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
/**
 * Check if translations exist in database
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

    echo "=== Checking Translation Tables in Database ===\n\n";

    // List all tables with 'translation' or 'message' in name
    $stmt = $pdo->prepare("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        AND (table_name LIKE '%translation%' OR table_name LIKE '%message%')
        ORDER BY table_name
    ");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) > 0) {
        echo "Found translation-related tables:\n";
        foreach ($tables as $table) {
            echo "  - $table\n";

            // Get row count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            echo "    Rows: $count\n";
        }
    } else {
        echo "❌ No translation tables found in database\n";
        echo "   Translation system uses translation-microservice API\n";
    }

    echo "\n=== Checking for Yii2 Message Tables ===\n\n";

    // Check for standard Yii2 i18n tables
    $yiiTables = ['source_message', 'message'];

    foreach ($yiiTables as $table) {
        $stmt = $pdo->prepare("
            SELECT EXISTS (
                SELECT FROM information_schema.tables
                WHERE table_schema = 'public'
                AND table_name = '$table'
            )
        ");
        $stmt->execute();
        $exists = $stmt->fetchColumn();

        if ($exists) {
            echo "✅ Found $table table\n";

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            echo "   Rows: $count\n";

            if ($table === 'source_message') {
                // Check for our keys
                $keys = ['Dresses', 'Dresses Type', 'Evening', 'Cocktail'];
                foreach ($keys as $key) {
                    $stmt = $pdo->prepare("SELECT id FROM source_message WHERE message = ? LIMIT 1");
                    $stmt->execute([$key]);
                    $id = $stmt->fetchColumn();
                    if ($id) {
                        echo "   ✅ Found key '$key' (id: $id)\n";
                    }
                }
            }
        } else {
            echo "❌ Table $table not found\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

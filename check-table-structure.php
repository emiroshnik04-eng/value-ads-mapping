<?php
/**
 * Check actual structure of fast_message_country table
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

    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║         Checking fast_message_country Table Structure        ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Get table structure
    $stmt = $pdo->query("
        SELECT
            column_name,
            data_type,
            character_maximum_length,
            is_nullable
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'fast_message_country'
        ORDER BY ordinal_position
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Table: fast_message_country\n";
    echo "Columns:\n\n";
    foreach ($columns as $col) {
        $maxLen = $col['character_maximum_length'] ? "({$col['character_maximum_length']})" : '';
        echo "  - {$col['column_name']}: {$col['data_type']}{$maxLen} " .
             ($col['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }

    echo "\n\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║         Sample Data from fast_message_country                 ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    // Get sample data
    $stmt = $pdo->query("
        SELECT *
        FROM fast_message_country
        WHERE country_id = 11
        LIMIT 5
    ");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($samples) > 0) {
        echo "Sample rows for Serbia (country_id = 11):\n\n";
        foreach ($samples as $i => $row) {
            echo "Row " . ($i + 1) . ":\n";
            foreach ($row as $key => $value) {
                $displayValue = $value;
                if (is_string($value) && strlen($value) > 100) {
                    $displayValue = substr($value, 0, 100) . '...';
                }
                echo "  $key: $displayValue\n";
            }
            echo "\n";
        }
    } else {
        echo "No data found for Serbia (country_id = 11)\n";
    }

    // Count total rows
    $stmt = $pdo->query("SELECT COUNT(*) FROM fast_message_country");
    $total = $stmt->fetchColumn();
    echo "Total rows in table: $total\n";

    $stmt = $pdo->query("SELECT COUNT(*) FROM fast_message_country WHERE country_id = 11");
    $serbiaTotal = $stmt->fetchColumn();
    echo "Total rows for Serbia: $serbiaTotal\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

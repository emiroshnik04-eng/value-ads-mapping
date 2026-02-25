<?php
/**
 * Check fast_message tables content
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

    echo "=== Checking fast_message table ===\n\n";

    $stmt = $pdo->prepare("SELECT * FROM fast_message ORDER BY id");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as $msg) {
        echo "ID {$msg['id']}: {$msg['key']}\n";
    }

    echo "\n=== Checking fast_message_country table ===\n\n";

    // Get structure
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
        echo "  - {$col['column_name']} ({$col['data_type']})\n";
    }

    echo "\n";

    // Get sample data for Serbia, Kyrgyzstan, Azerbaijan, Poland
    $countries = [11, 12, 13, 14];

    foreach ($countries as $countryId) {
        $stmt = $pdo->prepare("
            SELECT fmc.*, fm.key
            FROM fast_message_country fmc
            INNER JOIN fast_message fm ON fm.id = fmc.fast_message_id
            WHERE fmc.country_id = ?
            LIMIT 5
        ");
        $stmt->execute([$countryId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Country $countryId (" . count($rows) . " rows sample):\n";
        foreach ($rows as $row) {
            echo "  Key: {$row['key']} → Value: {$row['value']}\n";
        }
        echo "\n";
    }

    // Check if our specific keys exist
    echo "=== Checking for Dresses-related keys ===\n\n";

    $searchKeys = ['Dresses', 'Dresses Type', 'Evening', 'Cocktail', 'Oversize'];

    foreach ($searchKeys as $key) {
        $stmt = $pdo->prepare("SELECT id FROM fast_message WHERE key = ?");
        $stmt->execute([$key]);
        $id = $stmt->fetchColumn();

        if ($id) {
            echo "✅ Found key '$key' (id: $id)\n";

            // Get translations for all our countries
            $stmt = $pdo->prepare("
                SELECT country_id, value
                FROM fast_message_country
                WHERE fast_message_id = ?
                AND country_id IN (11, 12, 13, 14)
            ");
            $stmt->execute([$id]);
            $translations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($translations as $trans) {
                echo "   Country {$trans['country_id']}: {$trans['value']}\n";
            }
        } else {
            echo "❌ Key '$key' not found\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
/**
 * Try to get translations from translation-microservice API for Serbia
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== Trying to get real translations from translation-microservice ===\n\n";

// Check translation-microservice documentation or API
// Common URLs:
// - http://translation-microservice/api/...
// - Internal service URL

// For now, let's check what we know about Serbia:
// Country code: RS
// Language code: sr (Serbian)
// Possible variants: sr_Latn (Latin script), sr_Cyrl (Cyrillic script)

echo "Serbia info:\n";
echo "  Country code: RS\n";
echo "  Country ID: 11\n";
echo "  Language: Serbian (sr)\n";
echo "  Scripts: Latin (Haljine) or Cyrillic (Хаљине)\n\n";

echo "Question: Which script does lalafo.rs use?\n";
echo "  Check https://lalafo.rs/ to see actual translations\n\n";

// Let's check the country table for language info
$config = require __DIR__ . '/db-config.php';

try {
    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== Checking country table for Serbia ===\n\n";

    $stmt = $pdo->prepare("SELECT * FROM country WHERE id = 11");
    $stmt->execute();
    $country = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($country) {
        foreach ($country as $key => $value) {
            if ($value && strlen($value) < 200) {
                echo "$key: $value\n";
            }
        }
    }

    echo "\n=== Checking for language/locale settings ===\n\n";

    // Check if there's a locale or language field
    $stmt = $pdo->prepare("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'country'
        AND (
            column_name LIKE '%lang%'
            OR column_name LIKE '%locale%'
            OR column_name LIKE '%translation%'
        )
    ");
    $stmt->execute();
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($cols) > 0) {
        foreach ($cols as $col) {
            echo "{$col['column_name']}: {$col['data_type']}\n";
        }
    } else {
        echo "No language/locale columns found\n";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Recommendation ===\n\n";
echo "Need to:\n";
echo "1. Check production site https://lalafo.rs/ manually\n";
echo "2. See which translations are used (Latin or Cyrillic)\n";
echo "3. Extract actual translations from the site\n";
echo "4. Update translations.php with exact same values\n";

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

    echo "=== Searching for translation source for param values ===\n\n";

    $testValueIds = [16496, 16482, 23108, 27221]; // black, white, Silver Iphone, Blue-1

    foreach ($testValueIds as $valueId) {
        echo "Checking param_value_id: $valueId\n";

        // Get base value
        $sql = "SELECT * FROM param_value WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$valueId]);
        $value = $stmt->fetch();

        echo "  English value: {$value['value']}\n";

        // Check country_param_value for translations
        $sql = "
            SELECT cpv.country_id, cpv.alias, c.code as country_code
            FROM country_param_value cpv
            LEFT JOIN country c ON c.id = cpv.country_id
            WHERE cpv.param_value_id = ?
            ORDER BY cpv.country_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$valueId]);
        $countryValues = $stmt->fetchAll();

        if (!empty($countryValues)) {
            echo "  Country-specific data:\n";
            foreach ($countryValues as $cv) {
                echo "    Country {$cv['country_id']} ({$cv['country_code']}): alias = '{$cv['alias']}'\n";
            }
        } else {
            echo "  No country-specific data\n";
        }

        echo "\n";
    }

    // Check if there are translation tables
    echo "\n=== Looking for translation tables ===\n";
    $sql = "
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND (table_name LIKE '%translation%' OR table_name LIKE '%i18n%')
        ORDER BY table_name
    ";
    $tables = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "No translation tables found in database.\n";
        echo "\nConclusion: Translations are managed externally (translation-microservice)\n";
        echo "For admin tools, we use local translations.php file.\n";
    } else {
        echo "Found tables:\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    }

    // Check how production gets translations
    echo "\n\n=== How does production handle translations? ===\n";
    echo "1. Database stores only English values in param_value.value\n";
    echo "2. Translations are stored in translation-microservice\n";
    echo "3. Production uses Yii2 i18n with HybridMessageSource\n";
    echo "4. For admin tools, we use local translations.php file\n";
    echo "\n";
    echo "The translation key format is likely: param_value.value (e.g., 'black', 'white')\n";
    echo "We should use the same keys in translations.php\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

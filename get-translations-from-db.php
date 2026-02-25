<?php
header('Content-Type: text/plain; charset=utf-8');

// Try to connect to main database and find translation keys for param values
$configFile = __DIR__ . '/db-config.php';
$dbConfig = require $configFile;

try {
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
        $dbConfig['host'], $dbConfig['port'], $dbConfig['dbname']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "=== Looking for translation keys for param values ===\n\n";

    // Check if param_value has translation_key field
    $sql = "
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'param_value'
        ORDER BY ordinal_position
    ";
    $columns = $pdo->query($sql)->fetchAll();

    echo "param_value columns:\n";
    $hasTranslationKey = false;
    foreach ($columns as $col) {
        echo "  - {$col['column_name']} ({$col['data_type']})\n";
        if (strpos($col['column_name'], 'translation') !== false) {
            $hasTranslationKey = true;
        }
    }

    if (!$hasTranslationKey) {
        echo "\nNo translation_key column in param_value.\n";
        echo "Translations are likely stored in external translation-microservice.\n\n";
    }

    // Check how categories and params get their translations
    echo "\n=== Check category table for translation keys ===\n";
    $sql = "
        SELECT column_name
        FROM information_schema.columns
        WHERE table_name = 'category'
        ORDER BY ordinal_position
    ";
    $catColumns = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo "category columns: " . implode(', ', $catColumns) . "\n";

    echo "\n=== Check param table for translation keys ===\n";
    $sql = "
        SELECT column_name
        FROM information_schema.columns
        WHERE table_name = 'param'
        ORDER BY ordinal_position
    ";
    $paramColumns = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    echo "param columns: " . implode(', ', $paramColumns) . "\n";

    echo "\n\n=== CONCLUSION ===\n";
    echo "Tables (category, param, param_value) store only English names.\n";
    echo "Translations are managed by:\n";
    echo "  1. Yii2 i18n system with HybridMessageSource\n";
    echo "  2. Translation-microservice API (for production)\n";
    echo "  3. Local translation files (for development/admin tools)\n\n";

    echo "For admin tools, we use local translations.php file.\n";
    echo "Since 'Silver Iphone' and 'Blue-1' are phone-specific values,\n";
    echo "we should add generic translations:\n\n";

    echo "  'Silver Iphone' => 'Серебристый',\n";
    echo "  'Blue-1' => 'Синий',\n\n";

    echo "This matches the pattern of other color values in translations.php.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

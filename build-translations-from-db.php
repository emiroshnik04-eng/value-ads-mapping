<?php
/**
 * Build translations.php from database (fast_message_country table)
 * This will create the CORRECT translations file based on actual DB data
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
    echo "║   Building translations.php from Database                     ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    $countries = [
        11 => 'sr', // Serbia → Serbian
        12 => 'ru', // Kyrgyzstan → Russian
        13 => 'az', // Azerbaijan → Azerbaijani
        14 => 'pl', // Poland → Polish
    ];

    // Check if fast_message_country exists
    $stmt = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = 'fast_message_country'
        )
    ");
    $tableExists = $stmt->fetchColumn();

    if (!$tableExists) {
        die("❌ fast_message_country table does not exist. Cannot build translations.\n");
    }

    echo "✅ fast_message_country table exists\n\n";

    // Load current translations.php to preserve anything not in DB
    $currentTranslations = require __DIR__ . '/translations.php';

    $newTranslations = [];

    foreach ($countries as $countryId => $langCode) {
        echo "Processing country $countryId ($langCode)...\n";

        // Get all translations for this country
        $stmt = $pdo->prepare("
            SELECT message, translation
            FROM fast_message_country
            WHERE country_id = :country_id
            AND translation IS NOT NULL
            AND translation != ''
            AND message != translation
            ORDER BY message
        ");
        $stmt->execute([':country_id' => $countryId]);
        $dbTranslations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "  Found " . count($dbTranslations) . " translations in DB\n";

        // Start with current translations or empty array
        if (!isset($newTranslations[$langCode])) {
            $newTranslations[$langCode] = $currentTranslations[$langCode] ?? [
                'categories' => [],
                'params' => [],
                'values' => []
            ];
        }

        // Map DB translations to categories/params/values
        foreach ($dbTranslations as $trans) {
            $key = $trans['message'];
            $value = $trans['translation'];

            // Try to determine if it's a category, param, or value
            // This is heuristic-based since we don't have type info

            // Keep existing categorization if it exists
            if (isset($currentTranslations[$langCode]['categories'][$key])) {
                $newTranslations[$langCode]['categories'][$key] = $value;
            } elseif (isset($currentTranslations[$langCode]['params'][$key])) {
                $newTranslations[$langCode]['params'][$key] = $value;
            } elseif (isset($currentTranslations[$langCode]['values'][$key])) {
                $newTranslations[$langCode]['values'][$key] = $value;
            } else {
                // New key - try to guess category
                // Params often have hyphens, country prefixes, or specific keywords
                if (
                    strpos($key, ' - ') !== false ||
                    preg_match('/^[A-Z]{2} - /', $key) ||
                    stripos($key, 'Type') !== false ||
                    stripos($key, 'Length') !== false ||
                    stripos($key, 'Brand') !== false ||
                    stripos($key, 'Material') !== false
                ) {
                    $newTranslations[$langCode]['params'][$key] = $value;
                } elseif (strlen($key) < 30 && !strpos($key, "'")) {
                    // Short strings without quotes are likely values
                    $newTranslations[$langCode]['values'][$key] = $value;
                } else {
                    // Default to params
                    $newTranslations[$langCode]['params'][$key] = $value;
                }
            }
        }

        echo "  Result: " .
            count($newTranslations[$langCode]['categories']) . " categories, " .
            count($newTranslations[$langCode]['params']) . " params, " .
            count($newTranslations[$langCode]['values']) . " values\n\n";
    }

    // Generate PHP file content
    echo "Generating translations-from-db.php...\n";

    $output = "<?php\n";
    $output .= "/**\n";
    $output .= " * Translations built from fast_message_country database table\n";
    $output .= " * Generated on: " . date('Y-m-d H:i:s') . "\n";
    $output .= " */\n\n";
    $output .= "return " . var_export($newTranslations, true) . ";\n";

    file_put_contents(__DIR__ . '/translations-from-db.php', $output);

    echo "✅ Created translations-from-db.php\n\n";

    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                   Summary                                      ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";

    foreach ($newTranslations as $lang => $sections) {
        echo "Language: $lang\n";
        echo "  Categories: " . count($sections['categories']) . "\n";
        echo "  Parameters: " . count($sections['params']) . "\n";
        echo "  Values: " . count($sections['values']) . "\n\n";
    }

    echo "Next steps:\n";
    echo "1. Review translations-from-db.php\n";
    echo "2. If correct, backup current translations.php\n";
    echo "3. Replace translations.php with translations-from-db.php\n";
    echo "4. Test the interface\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

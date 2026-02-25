<?php
/**
 * Auto-extract translations from database and apply them
 * This script runs itself and saves output
 */

$logFile = __DIR__ . '/extraction-log.txt';
$outputFile = __DIR__ . '/translations.php';
$backupFile = __DIR__ . '/translations.php.before-db-extraction';

// Start output buffering
ob_start();

try {
    echo "=== Starting automatic translation extraction ===\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

    $config = require __DIR__ . '/db-config.php';

    $pdo = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "✅ Database connected\n\n";

    $countries = [
        11 => 'sr', // Serbia
        12 => 'ru', // Kyrgyzstan
        13 => 'az', // Azerbaijan
        14 => 'pl', // Poland
    ];

    $translations = [];

    // STEP 1: Extract from param table
    echo "STEP 1: Extracting param translations...\n";

    foreach ($countries as $countryId => $lang) {
        $columnName = 'name_' . $lang;

        echo "  Checking param.$columnName...\n";

        // Check if column exists
        $stmt = $pdo->prepare("
            SELECT EXISTS (
                SELECT FROM information_schema.columns
                WHERE table_schema = 'public'
                AND table_name = 'param'
                AND column_name = :column_name
            )
        ");
        $stmt->execute([':column_name' => $columnName]);

        if (!$stmt->fetchColumn()) {
            echo "    ⚠️  Column $columnName does not exist in param table\n";
            continue;
        }

        $sql = "
            SELECT name, $columnName as translation
            FROM param
            WHERE $columnName IS NOT NULL
            AND $columnName != ''
            AND name != $columnName
        ";

        $stmt = $pdo->query($sql);
        $params = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!isset($translations[$lang])) {
            $translations[$lang] = [
                'categories' => [],
                'params' => [],
                'values' => []
            ];
        }

        foreach ($params as $param) {
            $translations[$lang]['params'][$param['name']] = $param['translation'];
        }

        echo "    ✅ Found " . count($params) . " param translations\n";

        // Show Brand translation specifically
        if (isset($translations[$lang]['params']['Brand'])) {
            echo "    >>> Brand: " . $translations[$lang]['params']['Brand'] . "\n";
        }
        if (isset($translations[$lang]['params']['Clothing Brand'])) {
            echo "    >>> Clothing Brand: " . $translations[$lang]['params']['Clothing Brand'] . "\n";
        }
    }

    echo "\n";

    // STEP 2: Extract from category table
    echo "STEP 2: Extracting category translations...\n";

    foreach ($countries as $countryId => $lang) {
        $columnName = 'name_' . $lang;

        echo "  Checking category.$columnName...\n";

        // Check if column exists
        $stmt = $pdo->prepare("
            SELECT EXISTS (
                SELECT FROM information_schema.columns
                WHERE table_schema = 'public'
                AND table_name = 'category'
                AND column_name = :column_name
            )
        ");
        $stmt->execute([':column_name' => $columnName]);

        if (!$stmt->fetchColumn()) {
            echo "    ⚠️  Column $columnName does not exist in category table\n";
            continue;
        }

        $sql = "
            SELECT name, $columnName as translation
            FROM category
            WHERE $columnName IS NOT NULL
            AND $columnName != ''
            AND name != $columnName
        ";

        $stmt = $pdo->query($sql);
        $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cats as $cat) {
            $translations[$lang]['categories'][$cat['name']] = $cat['translation'];
        }

        echo "    ✅ Found " . count($cats) . " category translations\n";
    }

    echo "\n";

    // STEP 3: Extract param value aliases from country_param_value
    echo "STEP 3: Extracting param value aliases...\n";

    foreach ($countries as $countryId => $lang) {
        echo "  Country $countryId ($lang)...\n";

        $stmt = $pdo->prepare("
            SELECT
                pv.value,
                pv.display_value,
                cpv.alias
            FROM country_param_value cpv
            JOIN param_value pv ON pv.id = cpv.param_value_id
            WHERE cpv.country_id = :country_id
            AND cpv.alias IS NOT NULL
            AND cpv.alias != ''
        ");
        $stmt->execute([':country_id' => $countryId]);
        $aliases = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($aliases as $alias) {
            // Use display_value if available, otherwise value
            $key = !empty($alias['display_value']) ? $alias['display_value'] : $alias['value'];

            // Only add if key is different from alias
            if ($key && $alias['alias'] && $key !== $alias['alias']) {
                $translations[$lang]['values'][$key] = $alias['alias'];
            }
        }

        echo "    ✅ Found " . count($aliases) . " value aliases\n";
    }

    echo "\n";

    // STEP 4: Summary
    echo "=== SUMMARY ===\n\n";

    foreach ($translations as $lang => $sections) {
        echo "Language: $lang\n";
        echo "  Categories: " . count($sections['categories']) . "\n";
        echo "  Parameters: " . count($sections['params']) . "\n";
        echo "  Values: " . count($sections['values']) . "\n\n";
    }

    // STEP 5: Backup current translations.php
    if (file_exists($outputFile)) {
        echo "Backing up current translations.php...\n";
        copy($outputFile, $backupFile);
        echo "  ✅ Backup saved to: $backupFile\n\n";
    }

    // STEP 6: Write new translations.php
    echo "Writing new translations.php...\n";

    $phpCode = "<?php\n";
    $phpCode .= "/**\n";
    $phpCode .= " * Translations extracted from database\n";
    $phpCode .= " * Source: param.name_*, category.name_*, country_param_value.alias\n";
    $phpCode .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
    $phpCode .= " * DO NOT EDIT MANUALLY - Use database columns instead\n";
    $phpCode .= " */\n\n";
    $phpCode .= "return " . var_export($translations, true) . ";\n";

    file_put_contents($outputFile, $phpCode);

    echo "  ✅ New translations.php written\n\n";

    echo "=== SUCCESS ===\n";
    echo "Translations extracted and applied successfully!\n";
    echo "Total languages: " . count($translations) . "\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Save log
$log = ob_get_contents();
file_put_contents($logFile, $log);

// Output to screen
echo $log;

ob_end_clean();
